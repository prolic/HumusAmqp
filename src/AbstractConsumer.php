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

use Assert\Assertion;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractConsumer
 * @package Humus\Amqp
 */
abstract class AbstractConsumer implements Consumer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var string|null
     */
    protected $consumerTag;

    /**
     * Number of consumed messages
     *
     * @var int
     */
    protected $countMessagesConsumed = 0;

    /**
     * Number of unacked messaged
     *
     * @var int
     */
    protected $countMessagesUnacked = 0;

    /**
     * Last delivery tag seen
     *
     * @var int
     */
    protected $lastDeliveryTag;

    /**
     * @var bool
     */
    protected $keepAlive = true;

    /**
     * Idle timeout in seconds
     *
     * @var float
     */
    protected $idleTimeout;

    /**
     * How many messages are handled in one block without acknowledgement
     *
     * @var int
     */
    protected $blockSize;

    /**
     * @var float
     */
    protected $timestampLastAck;

    /**
     * @var float
     */
    protected $timestampLastMessage;

    /**
     * How many messages we want to consume
     *
     * @var int
     */
    protected $target;

    /**
     * @var callable
     */
    protected $deliveryCallback;

    /**
     * @var callable
     */
    protected $flushCallback;

    /**
     * @var callable
     */
    protected $errorCallback;

    /**
     * @var bool
     */
    protected $usePcntlSignalDispatch = false;

    /**
     * Start consumer
     *
     * @param int $msgAmount
     * @throws Exception\QueueException
     */
    public function consume(int $msgAmount = 0)
    {
        Assertion::min($msgAmount, 0);

        $this->target = $msgAmount;

        if (!$this->timestampLastAck) {
            $this->timestampLastAck = microtime(true);
        }

        $callback = function (Envelope $envelope) {
            try {
                $processFlag = $this->handleDelivery($envelope, $this->queue);
            } catch (\Throwable $e) {
                $this->logger->error('Exception during handleDelivery: ' . $e->getMessage());
                $this->handleException($e);
                $processFlag = DeliveryResult::MSG_REJECT_REQUEUE();
            }

            $this->handleProcessFlag($envelope, $processFlag);

            $now = microtime(true);

            if ($this->countMessagesUnacked > 0
                && ($this->countMessagesUnacked === $this->blockSize
                    || ($now - $this->timestampLastAck) > $this->idleTimeout
                )) {
                $this->ackOrNackBlock();
            }

            if ($this->usePcntlSignalDispatch) {
                // Check for signals
                pcntl_signal_dispatch();
            }

            if (!$this->keepAlive || (0 !== $this->target && $this->countMessagesConsumed >= $this->target)) {
                $this->ackOrNackBlock();
                $this->queue->cancel($this->consumerTag);
                $this->shutdown();
                return false;
            }
        };

        try {
            $this->queue->consume($callback, Constants::AMQP_NOPARAM, $this->consumerTag);
        } catch (Exception\QueueException $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
            $this->ackOrNackBlock();

            // hack for different drivers
            $channel = $this->queue->getChannel();
            if ($channel instanceof Driver\PhpAmqpLib\Channel) {
                $channel->getResource()->close(); // preferred, but not present in amqp-extension
            } elseif ($channel instanceof Driver\AmqpExtension\Channel) {
                $channel->getConnection()->reconnect(); // needed in order to destroy (and close) the channel
            }
        }
    }

    /**
     * @param Envelope $envelope
     * @param Queue $queue
     * @return DeliveryResult
     */
    protected function handleDelivery(Envelope $envelope, Queue $queue) : DeliveryResult
    {
        $this->logger->debug('Handling delivery of message', $this->extractMessageInformation($envelope));

        if ($envelope->getAppId() === __NAMESPACE__) {
            return $this->handleInternalMessage($envelope);
        }

        $callback = $this->deliveryCallback;
        return $callback($envelope, $queue);
    }

    /**
     * Shutdown consumer
     *
     * @return void
     */
    public function shutdown()
    {
        $this->keepAlive = false;
    }

    /**
     * Handle exception
     *
     * @param \Throwable $e
     * @return void
     */
    protected function handleException(\Throwable $e)
    {
        $callback = $this->errorCallback;

        $callback($e, $this);
    }

    /**
     * Process buffered (unacked) messages
     *
     * Messages are deferred until the block size (see prefetch_count) or the timeout is reached
     * The unacked messages will also be flushed immediately when the handleDelivery method returns true
     *
     * @return FlushDeferredResult
     */
    protected function flushDeferred() : FlushDeferredResult
    {
        $callback = $this->flushCallback;

        if (null === $callback) {
            return FlushDeferredResult::MSG_ACK();
        }

        try {
            $result = $callback($this->queue);
        } catch (\Throwable $e) {
            $this->logger->error('Exception during flushDeferred: ' . $e->getMessage());
            $result = FlushDeferredResult::MSG_REJECT();
            $this->handleException($e);
        }

        return $result;
    }

    /**
     * Handle process flag
     *
     * @param Envelope $envelope
     * @param $flag
     * @return void
     */
    protected function handleProcessFlag(Envelope $envelope, DeliveryResult $flag)
    {
        $this->countMessagesConsumed++;

        switch ($flag) {
            case DeliveryResult::MSG_REJECT():
                $this->queue->nack($envelope->getDeliveryTag(), Constants::AMQP_NOPARAM);
                $this->logger->debug('Rejected message', $this->extractMessageInformation($envelope));
                break;
            case DeliveryResult::MSG_REJECT_REQUEUE():
                $this->queue->nack($envelope->getDeliveryTag(), Constants::AMQP_REQUEUE);
                $this->logger->debug('Rejected and requeued message', $this->extractMessageInformation($envelope));
                break;
            case DeliveryResult::MSG_ACK():
                $this->countMessagesUnacked++;
                $this->lastDeliveryTag = $envelope->getDeliveryTag();
                $this->timestampLastMessage = microtime(true);
                $this->ack();
                break;
            case DeliveryResult::MSG_DEFER():
                $this->countMessagesUnacked++;
                $this->lastDeliveryTag = $envelope->getDeliveryTag();
                $this->timestampLastMessage = microtime(true);
                break;
        }
    }

    /**
     * Acknowledge all deferred messages
     *
     * This will be called every time the block size (see prefetch_count) or timeout is reached
     *
     * @return void
     */
    protected function ack()
    {
        $this->queue->ack($this->lastDeliveryTag, Constants::AMQP_MULTIPLE);
        $this->lastDeliveryTag = null;
        $delta = $this->timestampLastMessage - $this->timestampLastAck;

        $this->logger->info(sprintf(
            'Acknowledged %d messages at %.0f msg/s',
            $this->countMessagesUnacked,
            $delta ? $this->countMessagesUnacked / $delta : 0
        ));

        $this->timestampLastAck = microtime(true);
        $this->countMessagesUnacked = 0;
    }

    /**
     * Send nack for all deferred messages
     *
     * @param bool $requeue
     * @return void
     */
    protected function nackAll($requeue = false)
    {
        $delta = $this->timestampLastMessage - $this->timestampLastAck;

        $this->logger->info(sprintf(
            'Not acknowledged %d messages at %.0f msg/s',
            $this->countMessagesUnacked,
            $delta ? $this->countMessagesUnacked / $delta : 0
        ));

        $flags = Constants::AMQP_MULTIPLE;

        if ($requeue) {
            $flags |= Constants::AMQP_REQUEUE;
        }

        $this->queue->nack($this->lastDeliveryTag, $flags);
        $this->lastDeliveryTag = null;
        $this->countMessagesUnacked = 0;
    }

    /**
     * Handle deferred acknowledgments
     *
     * @return void
     */
    protected function ackOrNackBlock()
    {
        if (! $this->lastDeliveryTag) {
            return;
        }

        $result = $this->flushDeferred();

        switch ($result) {
            case FlushDeferredResult::MSG_ACK():
                $this->ack();
                break;
            case FlushDeferredResult::MSG_REJECT():
                $this->nackAll(false);
                break;
            case FlushDeferredResult::MSG_REJECT_REQUEUE():
                $this->nackAll(true);
                break;
        }
    }

    /**
     * @param Envelope $envelope
     * @return DeliveryResult
     */
    protected function handleInternalMessage(Envelope $envelope) : DeliveryResult
    {
        if ('shutdown' === $envelope->getType()) {
            $this->logger->info('Shutdown message received');
            $this->shutdown();

            $result = DeliveryResult::MSG_ACK();
        } elseif ('reconfigure' === $envelope->getType()) {
            $this->logger->info('Reconfigure message received');
            try {
                list($idleTimeout, $blockSize, $target, $prefetchSize, $prefetchCount) = json_decode($envelope->getBody());

                if (is_numeric($idleTimeout)) {
                    $idleTimeout = (float) $idleTimeout;
                }
                Assertion::float($idleTimeout);
                Assertion::min($blockSize, 1);
                Assertion::min($target, 0);
                Assertion::min($prefetchSize, 0);
                Assertion::min($prefetchCount, 0);
            } catch (\Throwable $e) {
                $this->logger->error('Exception during reconfiguration: ' . $e->getMessage());
                return DeliveryResult::MSG_REJECT();
            }

            $this->idleTimeout = $idleTimeout;
            $this->blockSize = $blockSize;
            $this->target = $target;
            $this->queue->getChannel()->qos($prefetchSize, $prefetchCount);

            $result = DeliveryResult::MSG_ACK();
        } elseif ('ping' === $envelope->getType()) {
            $this->logger->info('Ping message received');
            $result = DeliveryResult::MSG_ACK();
        } else {
            $this->logger->error('Invalid internal message: ' . $envelope->getType());
            $result = DeliveryResult::MSG_REJECT();
        }

        return $result;
    }

    /**
     * @param Envelope $envelope
     * @return array
     */
    protected function extractMessageInformation(Envelope $envelope) : array
    {
        return [
            'body' => $envelope->getBody(),
            'routing_key' => $envelope->getRoutingKey(),
            'type' => $envelope->getType(),
        ];
    }
}
