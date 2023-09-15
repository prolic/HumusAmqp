<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace Humus\Amqp\Driver\AmqpExtension;

use AMQPQueue;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Constants;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exception\QueueException;
use Humus\Amqp\Queue as AmqpQueueInterface;

final class Queue implements AmqpQueueInterface
{
    private Channel $channel;

    private AMQPQueue $queue;

    /** @internal */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->queue = new \AMQPQueue($channel->getResource());
    }

    public function getName(): string
    {
        return (string) $this->queue->getName();
    }

    public function setName(string $queueName): void
    {
        $this->queue->setName($queueName);
    }

    public function getFlags(): int
    {
        return $this->queue->getFlags();
    }

    public function setFlags(int $flags): void
    {
        $this->queue->setFlags($flags);
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string $key)
    {
        try {
            return $this->queue->getArgument($key);
        } catch (\AMQPQueueException $e) {
            return false;
        }
    }

    public function getArguments(): array
    {
        return $this->queue->getArguments();
    }

    public function setArgument(string $key, $value): void
    {
        $this->queue->setArgument($key, $value);
    }

    public function setArguments(array $arguments): void
    {
        $this->queue->setArguments($arguments);
    }

    public function declareQueue(): int
    {
        try {
            return $this->queue->declareQueue();
        } catch (\AMQPQueueException $e) {
            throw QueueException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw ChannelException::fromAmqpExtension($e);
        }
    }

    public function bind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $this->queue->bind($exchangeName, $routingKey, $arguments);
    }

    public function get(int $flags = Constants::AMQP_NOPARAM): ?Envelope
    {
        try {
            $envelope = $this->queue->get($flags);
        } catch (\AMQPChannelException $e) {
            throw new ChannelException($e->getMessage());
        }

        if ($envelope instanceof \AMQPEnvelope) {
            return new Envelope($envelope);
        }

        return null;
    }

    public function consume(?callable $callback = null, int $flags = Constants::AMQP_NOPARAM, string $consumerTag = ''): void
    {
        if (null !== $callback) {
            $innerCallback = function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($callback): bool {
                $envelope = new Envelope($envelope);

                return $callback($envelope, $this);
            };
        } else {
            $innerCallback = null;
        }

        try {
            $this->queue->consume($innerCallback, $flags, $consumerTag);
        } catch (\AMQPConnectionException $e) {
            throw QueueException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw QueueException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw QueueException::fromAmqpExtension($e);
        }
    }

    public function ack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->queue->ack($deliveryTag, $flags);
    }

    public function nack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->queue->nack($deliveryTag, $flags);
    }

    public function reject(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->queue->reject($deliveryTag, $flags);
    }

    public function purge(): void
    {
        $this->queue->purge();
    }

    public function cancel(string $consumerTag = ''): void
    {
        $this->queue->cancel($consumerTag);
    }

    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $this->queue->unbind($exchangeName, $routingKey, $arguments);
    }

    public function delete(int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->queue->delete($flags);
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->channel->getConnection();
    }
}
