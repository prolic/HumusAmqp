<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace Humus\Amqp;

use AMQPEnvelope;
use AMQPExchange;
use AMQPQueue;
use ArrayIterator;
use Assert\Assertion;
use InfiniteIterator;

/**
 * Class JsonRpcServer
 * @package Humus\Amqp
 */
final class JsonRpcServer extends AbstractConsumer
{
    /**
     * @var AMQPExchange
     */
    private $exchange;

    /**
     * @var string|null
     */
    private $appId;

    /**
     * Constructor
     *
     * @param AMQPQueue $queue
     * @param float $idleTimeout in seconds
     * @param string|null $consumerTag
     * @param string|null $appId
     */
    public function __construct(AMQPQueue $queue, $idleTimeout, $consumerTag = null, $appId = null)
    {
        Assertion::float($idleTimeout);
        Assertion::nullOrString($consumerTag);
        Assertion::nullOrString($appId);

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

        $this->blockSize = $queue->getChannel()->getPrefetchCount();
        $this->idleTimeout = (float) $idleTimeout;
        $this->queue = $queue;
        $this->consumerTag = $consumerTag;
        $this->appId = $appId;
    }

    /**
     * @param AMQPEnvelope $message
     * @param AMQPQueue $queue
     * @return bool|null
     */
    public function handleDelivery(AMQPEnvelope $message, AMQPQueue $queue)
    {
        $this->countMessagesConsumed++;
        $this->countMessagesUnacked++;
        $this->lastDeliveryTag = $message->getDeliveryTag();
        $this->timestampLastMessage = microtime(1);
        $this->ack();

        try {
            $result = parent::handleDelivery($message, $queue);

            $response = ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
        $this->sendReply($response, $message->getReplyTo(), $message->getCorrelationId());
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

        $this->getExchange()->publish(json_encode($response), $replyTo, AMQP_NOPARAM, $attributes);
    }

    /**
     * Handle process flag
     *
     * @param AMQPEnvelope $message
     * @param $flag
     * @return void
     */
    protected function handleProcessFlag(AMQPEnvelope $message, $flag)
    {
        // do nothing, message was already acknowledged
    }

    /**
     * @return AMQPExchange
     */
    protected function getExchange()
    {
        if (null !== $this->exchange) {
            return $this->exchange;
        }

        $channel = $this->queue->getChannel();

        $this->exchange = new AMQPExchange($channel);
        $this->exchange->setType(AMQP_EX_TYPE_DIRECT);

        return $this->exchange;
    }
}
