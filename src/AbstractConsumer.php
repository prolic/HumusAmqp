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
use Humus\Amqp\Exception\ConnectionException;
use Humus\Amqp\Exception\QueueException;

/**
 * Class AbstractConsumer
 * @package Humus\Amqp
 */
abstract class AbstractConsumer implements Consumer
{
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
     * @var string
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
     * @throws ConnectionException
     */
    public function consume(int $msgAmount = 0)
    {
        Assertion::min($msgAmount, 0);

        $this->target = $msgAmount;
        if (!$this->timestampLastAck) {
            $this->timestampLastAck = microtime(true);
        }
        $callback = function (Envelope $envelope) {
            if ($envelope->getAppId() === __NAMESPACE__) {
                if ('shutdown' === $envelope->getType()) {
                    $this->shutdown();

                    return self::MSG_ACK;
                }
                if ('reconfigure' === $envelope->getType()) {
                    try {
                        list($idleTimeout, $blockSize, $target, $prefetchSize, $prefetchCount) = json_decode($envelope->getBody());

                        Assertion::float($idleTimeout);
                        Assertion::min($blockSize, 1);
                        Assertion::min($target, 0);
                        Assertion::min($prefetchSize, 0);
                        Assertion::min($prefetchCount, 0);
                    } catch (\Exception $e) {
                        return self::MSG_REJECT;
                    }

                    $this->idleTimeout = $idleTimeout;
                    $this->blockSize = $blockSize;
                    $this->target = $target;
                    $this->queue->getChannel()->qos($prefetchSize, $prefetchCount);

                    return self::MSG_ACK;
                }
            }

            try {
                $processFlag = $this->handleDelivery($envelope, $this->queue);
            } catch (\Exception $e) {
                $this->handleException($e);
                $processFlag = false;
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
                $this->queue->cancel($this->consumerTag);
                $this->shutdown();
                return false;
            }
        };

        do {
            try {
                $this->queue->consume($callback, Constants::AMQP_NOPARAM, $this->consumerTag);
            } catch (ConnectionException $e) {
                if (!$this->queue->getConnection()->reconnect()) {
                    throw $e;
                }
                $this->ackOrNackBlock();
                gc_collect_cycles();
            }

        } while ($this->keepAlive);
    }

    /**
     * @param Envelope $envelope
     * @param Queue $queue
     * @return bool|null
     */
    protected function handleDelivery(Envelope $envelope, Queue $queue)
    {
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
     * @param \Exception $e
     * @return void
     */
    protected function handleException(\Exception $e)
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
     * @return bool
     */
    protected function flushDeferred()
    {
        $callback = $this->flushCallback;

        if (null === $callback) {
            return true;
        }

        try {
            $result = $callback($this);
        } catch (\Exception $e) {
            $result = false;
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
    protected function handleProcessFlag(Envelope $envelope, $flag)
    {
        $this->countMessagesConsumed++;

        if ($flag === self::MSG_REJECT || false === $flag) {
            $this->ackOrNackBlock();
            $this->queue->reject($envelope->getDeliveryTag(), Constants::AMQP_NOPARAM);
        } elseif ($flag === self::MSG_REJECT_REQUEUE) {
            $this->ackOrNackBlock();
            $this->queue->reject($envelope->getDeliveryTag(), Constants::AMQP_REQUEUE);
        } elseif ($flag === self::MSG_ACK || true === $flag) {
            $this->countMessagesUnacked++;
            $this->lastDeliveryTag = $envelope->getDeliveryTag();
            $this->timestampLastMessage = microtime(true);
            $this->ack();
        } else { // $flag === self::MSG_DEFER || null === $flag
            $this->countMessagesUnacked++;
            $this->lastDeliveryTag = $envelope->getDeliveryTag();
            $this->timestampLastMessage = microtime(true);
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
        $flags = Constants::AMQP_MULTIPLE;
        if ($requeue) {
            $flags |= Constants::AMQP_REQUEUE;
        }
        $this->queue->nack($this->lastDeliveryTag, $flags);
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

        try {
            $deferredFlushResult = $this->flushDeferred();
        } catch (\Exception $e) {
            $deferredFlushResult = false;
        }

        if (true === $deferredFlushResult) {
            $this->ack();
        } else {
            $this->nackAll();
            $this->lastDeliveryTag = null;
        }
        $this->countMessagesUnacked = 0;
    }
}
