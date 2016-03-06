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

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Constants;
use Humus\Amqp\AmqpChannel as AmqpChannelInterface;
use Humus\Amqp\AmqpConnection as AmqpConnectionInterface;
use Humus\Amqp\AmqpQueue as AmqpQueueInterface;
use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpQueueException;

/**
 * Class AmqpQueue
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpQueue implements AmqpQueueInterface
{
    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * Create an instance of an AmqpQueue object.
     *
     * @param AmqpChannel $amqpChannel The amqp channel to use.
     *
     * @throws AmqpQueueException      When amqp channel is not connected to a
     *                                 broker.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     */
    public function __construct(AmqpChannel $amqpChannel)
    {
        $this->channel = $amqpChannel;

        try {
            $this->queue = new \AMQPQueue($amqpChannel->getAmqpExtensionChannel());
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw AmqpQueueException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getName() : string
    {
        return $this->queue->getName();
    }

    /**
     * @inheritdoc
     */
    public function setName(string $queueName)
    {
        $this->queue->setName($queueName);
    }

    /**
     * @inheritdoc
     */
    public function getFlags() : int
    {
        return $this->queue->getFlags();
    }

    /**
     * @inheritdoc
     */
    public function setFlags(int $flags)
    {
        $this->queue->setFlags($flags);
    }

    /**
     * @inheritdoc
     */
    public function getArgument(string $key)
    {
        return $this->queue->getArgument($key);
    }

    /**
     * @inheritdoc
     */
    public function getArguments() : array
    {
        return $this->queue->getArguments();
    }

    /**
     * @inheritdoc
     */
    public function setArgument(string $key, $value)
    {
        $this->queue->setArgument($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function setArguments(array $arguments)
    {
        $this->queue->setArguments($arguments);
    }

    /**
     * @inheritdoc
     */
    public function declareQueue() : int
    {
        try {
            return $this->queue->declareQueue();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function bind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        try {
            return $this->queue->bind($exchangeName, $routingKey, $arguments);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function get(int $flags = Constants::AMQP_NOPARAM)
    {
        try {
            $envelope = $this->queue->get($flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }

        if ($envelope instanceof \AMQPEnvelope) {
            $envelope = new AmqpEnvelope($envelope);
        }

        return $envelope;
    }

    /**
     * @inheritdoc
     */
    public function consume(callable $callback = null, int $flags = Constants::AMQP_NOPARAM, string $consumerTag = null)
    {
        if (null !== $callback) {
            $innerCallback = function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($callback) {
                $envelope = new AmqpEnvelope($envelope);
                return $callback($envelope, $this);
            };
        } else {
            $innerCallback = null;
        }

        try {
            $this->queue->consume($innerCallback, $flags, $consumerTag);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function ack(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->queue->ack($deliveryTag, $flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function nack(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->queue->nack($deliveryTag, $flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function reject(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->queue->reject($deliveryTag, $flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        try {
            return $this->queue->purge();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function cancel(string $consumerTag = '')
    {
        try {
            return $this->queue->cancel($consumerTag);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function unbind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        try {
            $this->queue->unbind($exchangeName, $routingKey, $arguments);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(int $flags = Constants::AMQP_NOPARAM)
    {
        try {
            $this->queue->delete($flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getChannel() : AmqpChannelInterface
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function getConnection() : AmqpConnectionInterface
    {
        return $this->channel->getConnection();
    }
}
