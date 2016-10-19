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

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exchange as ExchangeInterface;
use Humus\Amqp\Queue as QueueInterface;

/**
 * Class Channel
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Channel implements ChannelInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * Create an instance of an AMQPChannel object.
     */
    public function __construct(Connection $amqpConnection)
    {
        $this->connection = $amqpConnection;
        $this->channel = new \AMQPChannel($amqpConnection->getResource());
    }

    /**
     * @return \AMQPChannel
     */
    public function getResource() : \AMQPChannel
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function isConnected() : bool
    {
        return $this->channel->isConnected();
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
        $this->channel->setPrefetchSize($size);
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchSize() : int
    {
        return $this->channel->getPrefetchSize();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchCount(int $count)
    {
        $this->channel->setPrefetchCount($count);
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchCount() : int
    {
        return $this->channel->getPrefetchCount();
    }

    /**
     * @inheritdoc
     */
    public function qos(int $size, int $count)
    {
        $this->channel->qos($size, $count);
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        $this->channel->startTransaction();
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        $this->channel->commitTransaction();
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        $this->channel->rollbackTransaction();
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
        $this->channel->basicRecover($requeue);
    }

    /**
     * @inheritdoc
     */
    public function confirmSelect()
    {
        $this->channel->confirmSelect();
    }

    /**
     * @inheritdoc
     */
    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null)
    {
        $this->channel->setConfirmCallback($ackCallback, $nackCallback);
    }

    /**
     * @inheritdoc
     */
    public function waitForConfirm(float $timeout = 0.0)
    {
        try {
            $this->channel->waitForConfirm($timeout);
        } catch (\AMQPChannelException $e) {
            throw ChannelException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw ChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function setReturnCallback(callable $returnCallback = null)
    {
        $innerCallback = null;
        if ($returnCallback) {
            $innerCallback = function (
                int $replyCode,
                string $replyText,
                string $exchange,
                string $routingKey,
                \AMQPEnvelope $properties,
                string $body
            ) use ($returnCallback) {
                $envelope = new Envelope($properties, is_string($body) ? $body : '');
                return $returnCallback($replyCode, $replyText, $exchange, $routingKey, $envelope, $body);
            };
        }

        $this->channel->setReturnCallback($innerCallback);
    }

    /**
     * @inheritdoc
     */
    public function waitForBasicReturn(float $timeout = 0.0)
    {
        try {
            $this->channel->waitForBasicReturn($timeout);
        } catch (\AMQPChannelException $e) {
            throw ChannelException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw ChannelException::fromAmqpExtension($e);
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
