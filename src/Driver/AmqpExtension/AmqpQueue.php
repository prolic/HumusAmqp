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

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Constants;
use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpQueueException;

/**
 * Class AmqpQueue
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpQueue implements \Humus\Amqp\Driver\AmqpQueue
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
    public function getName()
    {
        return $this->queue->getName();
    }

    /**
     * @inheritdoc
     */
    public function setName($queueName)
    {
        return $this->queue->setName($queueName);
    }

    /**
     * @inheritdoc
     */
    public function getFlags()
    {
        return $this->queue->getFlags();
    }

    /**
     * @inheritdoc
     */
    public function setFlags($flags)
    {
        return $this->queue->setFlags($flags);
    }

    /**
     * @inheritdoc
     */
    public function getArgument($key)
    {
        return $this->queue->getArgument($key);
    }

    /**
     * @inheritdoc
     */
    public function getArguments()
    {
        return $this->queue->getArguments();
    }

    /**
     * @inheritdoc
     */
    public function setArgument($key, $value)
    {
        return $this->queue->setArgument($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function setArguments(array $arguments)
    {
        return $this->queue->setArguments($arguments);
    }

    /**
     * @inheritdoc
     */
    public function declareQueue()
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
    public function bind($exchangeName, $routingKey = null, array $arguments = [])
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
    public function get($flags = Constants::AMQP_NOPARAM)
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
    public function consume(callable $callback = null, $flags = Constants::AMQP_NOPARAM, $consumerTag = null)
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
    public function ack($deliveryTag, $flags = Constants::AMQP_NOPARAM)
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
    public function nack($deliveryTag, $flags = Constants::AMQP_NOPARAM)
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
    public function reject($deliveryTag, $flags = Constants::AMQP_NOPARAM)
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
    public function cancel($consumerTag = '')
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
    public function unbind($exchangeName, $routingKey = null, array $arguments = [])
    {
        try {
            return $this->queue->unbind($exchangeName, $routingKey, $arguments);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->queue->delete($flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * Get the AmqpChannel object in use
     *
     * @return AmqpChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Get the AmqpConnection object in use
     *
     * @return AmqpConnection
     */
    public function getConnection()
    {
        return $this->channel->getConnection();
    }
}
