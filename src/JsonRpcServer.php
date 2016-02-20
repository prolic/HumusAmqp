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

namespace Humus\Amqp;

use Humus\Amqp\Driver\AmqpEnvelope;
use Humus\Amqp\Driver\AmqpExchange;
use Humus\Amqp\Driver\AmqpQueue;
use Assert\Assertion;

/**
 * Class JsonRpcServer
 * @package Humus\Amqp
 */
final class JsonRpcServer extends AbstractConsumer
{
    /**
     * @var AmqpExchange
     */
    private $exchange;

    /**
     * @var string|null
     */
    private $appId;

    /**
     * @var bool
     */
    private $returnTrace;

    /**
     * Constructor
     *
     * @param AmqpQueue $queue
     * @param AmqpExchange $exchange
     * @param float $idleTimeout in seconds
     * @param string|null $consumerTag
     * @param string|null $appId
     * @param bool $returnTrace
     */
    public function __construct(
        AmqpQueue $queue,
        AmqpExchange $exchange,
        $idleTimeout,
        $consumerTag = null,
        $appId = null,
        $returnTrace = false
    ) {
        Assertion::float($idleTimeout);
        Assertion::nullOrString($consumerTag);
        Assertion::nullOrString($appId);
        Assertion::boolean($returnTrace);

        if (null === $consumerTag) {
            $consumerTag = uniqid('', true);
        }

        if (function_exists('pcntl_signal_dispatch')) {
            $this->usePcntlSignalDispatch = true;
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGHUP, [$this, 'shutdown']);
        }

        $this->idleTimeout = (float) $idleTimeout;
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->consumerTag = $consumerTag;
        $this->appId = $appId;
        $this->returnTrace = $returnTrace;
    }

    /**
     * @param AmqpEnvelope $envelope
     * @param AmqpQueue $queue
     * @return bool|null
     */
    public function handleDelivery(AmqpEnvelope $envelope, AmqpQueue $queue)
    {
        $this->countMessagesConsumed++;
        $this->countMessagesUnacked++;
        $this->lastDeliveryTag = $envelope->getDeliveryTag();
        $this->timestampLastMessage = microtime(1);
        $this->ack();

        try {
            $result = parent::handleDelivery($envelope, $queue);

            $response = ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            $response = ['success' => false, 'error' => $e->getMessage()];
            if ($this->returnTrace) {
                $response['trace'] = $e->getTraceAsString();
            }
        }
        $this->sendReply($response, $envelope->getReplyTo(), $envelope->getCorrelationId());
    }

    /**
     * Send reply to rpc client
     *
     * @param array $response
     * @param string $replyTo
     * @param string $correlationId
     */
    protected function sendReply(array $response, $replyTo, $correlationId)
    {
        $attributes = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => $correlationId,
        ];

        if (null !== $this->appId) {
            $attributes['app_id'] = $this->appId;
        }

        $this->exchange->publish(json_encode($response), $replyTo, Constants::AMQP_NOPARAM, $attributes);
    }

    /**
     * Handle process flag
     *
     * @param AmqpEnvelope $envelope
     * @param $flag
     * @return void
     */
    protected function handleProcessFlag(AmqpEnvelope $envelope, $flag)
    {
        // do nothing, message was already acknowledged
    }
}
