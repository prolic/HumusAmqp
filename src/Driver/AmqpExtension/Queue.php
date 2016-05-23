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
use Humus\Amqp\Channel as AmqpChannelInterface;
use Humus\Amqp\Connection as AmqpConnectionInterface;
use Humus\Amqp\Exception\QueueException;
use Humus\Amqp\Queue as AmqpQueueInterface;
use Humus\Amqp\Exception\ConnectionException;

/**
 * Class Queue
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Queue implements AmqpQueueInterface
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * Create an instance of an Queue object.
     *
     * @param Channel $amqpChannel The amqp channel to use.
     */
    public function __construct(Channel $amqpChannel)
    {
        $this->channel = $amqpChannel;
        $this->queue = new \AMQPQueue($amqpChannel->getResource());
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
        return $this->queue->declareQueue();
    }

    /**
     * @inheritdoc
     */
    public function bind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        $this->queue->bind($exchangeName, $routingKey, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function get(int $flags = Constants::AMQP_NOPARAM)
    {
        $envelope = $this->queue->get($flags);

        if ($envelope instanceof \AMQPEnvelope) {
            $envelope = new Envelope($envelope);
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
                $envelope = new Envelope($envelope);
                return $callback($envelope, $this);
            };
        } else {
            $innerCallback = null;
        }

        try {
            $this->queue->consume($innerCallback, $flags, $consumerTag);
        } catch (\AMQPConnectionException $e) {
            throw ConnectionException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw QueueException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function ack(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->queue->ack($deliveryTag, $flags);
    }

    /**
     * @inheritdoc
     */
    public function nack(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->queue->nack($deliveryTag, $flags);
    }

    /**
     * @inheritdoc
     */
    public function reject(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->queue->reject($deliveryTag, $flags);
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        $this->queue->purge();
    }

    /**
     * @inheritdoc
     */
    public function cancel(string $consumerTag = '')
    {
        $this->queue->cancel($consumerTag);
    }

    /**
     * @inheritdoc
     */
    public function unbind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        $this->queue->unbind($exchangeName, $routingKey, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function delete(int $flags = Constants::AMQP_NOPARAM)
    {
        $this->queue->delete($flags);
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
