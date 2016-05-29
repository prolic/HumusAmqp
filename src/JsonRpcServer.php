<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 *  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 *  A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 *  OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 *  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 *  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 *  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  This software consists of voluntary contributions made by many individuals
 *  and is licensed under the MIT license.
 */

declare (strict_types=1);

namespace Humus\Amqp;

use Psr\Log\LoggerInterface;

/**
 * Class JsonRpcServer
 * @package Humus\Amqp
 */
final class JsonRpcServer extends AbstractConsumer
{
    /**
     * @var Exchange
     */
    private $exchange;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var bool
     */
    private $returnTrace;

    /**
     * Constructor
     *
     * @param Queue $queue
     * @param Exchange $exchange
     * @param LoggerInterface $logger
     * @param float $idleTimeout in seconds
     * @param string|null $consumerTag
     * @param string|null $appId
     * @param bool $returnTrace
     */
    public function __construct(
        Queue $queue,
        Exchange $exchange,
        LoggerInterface $logger,
        float $idleTimeout,
        string $consumerTag = null,
        string $appId = '',
        bool $returnTrace = false
    ) {
        if (null === $consumerTag) {
            $consumerTag = bin2hex(random_bytes(24));
        }

        if (function_exists('pcntl_signal_dispatch')) {
            $this->usePcntlSignalDispatch = true;
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGHUP, [$this, 'shutdown']);
        }

        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->logger = $logger;
        $this->idleTimeout = $idleTimeout;
        $this->consumerTag = $consumerTag;
        $this->appId = $appId;
        $this->returnTrace = $returnTrace;
    }

    /**
     * @param Envelope $envelope
     * @param Queue $queue
     * @return DeliveryResult
     */
    protected function handleDelivery(Envelope $envelope, Queue $queue) : DeliveryResult
    {
        $this->countMessagesConsumed++;
        $this->countMessagesUnacked++;
        $this->lastDeliveryTag = $envelope->getDeliveryTag();
        $this->timestampLastMessage = microtime(1);
        $this->ack();

        try {
            $this->logger->debug('Handling delivery of message', $this->extractMessageInformation($envelope));

            if ($envelope->getAppId() === __NAMESPACE__) {
                $this->handleInternalMessage($envelope);
            } else {
                $callback = $this->deliveryCallback;
                $result = $callback($envelope, $queue);
                $response = ['success' => true, 'result' => $result];
                $this->sendReply($response, $envelope);
            }
        } catch (\Exception $e) {
            $response = ['success' => false, 'error' => $e->getMessage()];
            if ($this->returnTrace) {
                $response['trace'] = $e->getTraceAsString();
            }
            $this->sendReply($response, $envelope);
        }

        return DeliveryResult::MSG_ACK();
    }

    /**
     * Send reply to rpc client
     *
     * @param array $response
     * @param Envelope $envelope
     * @param Envelope $envelope
     */
    protected function sendReply(array $response, Envelope $envelope)
    {
        $attributes = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => $envelope->getCorrelationId(),
            'app_id' => $this->appId,
        ];

        $this->exchange->publish(json_encode($response), $envelope->getReplyTo(), Constants::AMQP_NOPARAM, $attributes);
    }

    /**
     * Handle process flag
     *
     * @param Envelope $envelope
     * @param DeliveryResult $flag
     * @return void
     */
    protected function handleProcessFlag(Envelope $envelope, DeliveryResult $flag)
    {
        // do nothing, message was already acknowledged
    }
}
