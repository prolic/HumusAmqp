<?php
/**
 * Copyright (c) 2016-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
 *
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

declare(strict_types=1);

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Exception\BadMethodCallException;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exchange as ExchangeInterface;
use Humus\Amqp\Queue as QueueInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Helper\Protocol\Wait091;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Channel
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Channel implements ChannelInterface
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var int
     */
    private $prefetchCount;

    /**
     * @var int
     */
    private $prefechSize;

    /**
     * Create an instance of an AMQPChannel object.
     *
     * @param AbstractConnection $connection  An instance of AbstractConnection with an active connection to a broker.
     * @param AMQPChannel $channel
     */
    public function __construct(AbstractConnection $connection, AMQPChannel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
        $this->channel->set_ack_handler(function () {
            trigger_error('Unhandled basic.ack method from server received.');
        });
        $this->channel->set_nack_handler(function () {
            trigger_error('Unhandled basic.nack method from server received.');
        });
        $this->channel->set_return_listener(function () {
            trigger_error('Unhandled basic.return method from server received.');
        });
    }

    /**
     * @return AMQPChannel
     */
    public function getResource() : AMQPChannel
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function isConnected() : bool
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function getChannelId() : int
    {
        return $this->channel->getChannelId();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchSize(int $size)
    {
        $this->channel->basic_qos($size, 0, false);
        $this->prefechSize = $size;
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchSize() : int
    {
        return $this->prefechSize;
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchCount(int $count)
    {
        $this->channel->basic_qos(0, $count, false);
        $this->prefetchCount = $count;
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchCount() : int
    {
        return $this->prefetchCount;
    }

    /**
     * @inheritdoc
     */
    public function qos(int $size, int $count)
    {
        $this->channel->basic_qos($size, $count, false);
        $this->prefechSize = $size;
        $this->prefetchCount = $count;
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        $this->channel->tx_select();
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        $this->channel->tx_commit();
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        $this->channel->tx_rollback();
    }

    /**
     * @inheritdoc
     */
    public function getConnection() : ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function basicRecover(bool $requeue = true)
    {
        $this->channel->basic_recover($requeue);
    }

    /**
     * @inheritdoc
     */
    public function confirmSelect()
    {
        $this->channel->confirm_select();
    }

    /**
     * @inheritdoc
     */
    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null)
    {
        if (is_callable($ackCallback)) {
            $innerAckCallback = function (AMQPMessage $message) use ($ackCallback) {
                return $ackCallback((int) $message->get('delivery_tag'), false);
            };
            $this->channel->set_ack_handler($innerAckCallback);
        }

        if (is_callable($nackCallback)) {
            $innerNackCallback = function (AMQPMessage $message) use ($ackCallback) {
                return $ackCallback((int) $message->get('delivery_tag'), false, false);
            };
            $this->channel->set_nack_handler($innerNackCallback);
        }
    }

    /**
     * @inheritdoc
     */
    public function waitForConfirm(float $timeout = 0.0)
    {
        // hack phpamqplib, see: https://github.com/php-amqplib/php-amqplib/issues/428
        if ($timeout < 0) {
            throw new ChannelException('Timeout must be greater than or equal to zero.');
        }

        $publishedMessagesProperty = new \ReflectionProperty(get_class($this->channel), 'published_messages');
        $publishedMessagesProperty->setAccessible(true);

        $waitHelper = new Wait091();

        $functions = [
            $waitHelper->get_wait('basic.ack'),
            $waitHelper->get_wait('basic.nack'),
            $waitHelper->get_wait('basic.return'),
        ];

        $now = microtime(true);

        try {
            while (count($publishedMessagesProperty->getValue($this->channel)) != 0) {
                $this->channel->wait($functions);
                if ($timeout > 0 && (microtime(true) - $now) > $timeout) {
                    throw new AMQPTimeoutException('Timeout waiting on channel 4');
                }
            }
        } catch (\Exception $e) {
            throw ChannelException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function setReturnCallback(callable $returnCallback = null)
    {
        if (! $returnCallback) {
            return;
        }

        $innerCallback = function (
            $replyCode,
            $replyText,
            $exchange,
            $routingKey,
            $message
        ) use ($returnCallback) {
            $envelope = new Envelope($message);
            return $returnCallback($replyCode, $replyText, $exchange, $routingKey, $envelope, $envelope->getBody());
        };

        $this->channel->set_return_listener($innerCallback);
    }

    /**
     * @inheritdoc
     */
    public function waitForBasicReturn(float $timeout = 0.0)
    {
        try {
            $this->channel->wait(null, false, $timeout);
        } catch (\Exception $e) {
            throw ChannelException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @return ExchangeInterface
     */
    public function newExchange() : ExchangeInterface
    {
        return new Exchange($this);
    }

    /**
     * @return QueueInterface
     */
    public function newQueue() : QueueInterface
    {
        return new Queue($this);
    }
}
