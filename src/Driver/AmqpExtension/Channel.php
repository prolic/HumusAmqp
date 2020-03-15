<?php
/**
 * Copyright (c) 2016-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

use AMQPChannel;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exchange as ExchangeInterface;
use Humus\Amqp\Queue as QueueInterface;

final class Channel implements ChannelInterface
{
    private Connection $connection;
    private AMQPChannel $channel;

    public function __construct(Connection $amqpConnection)
    {
        $this->connection = $amqpConnection;
        $this->channel = new AMQPChannel($amqpConnection->getResource());
    }

    public function getResource(): \AMQPChannel
    {
        return $this->channel;
    }

    public function isConnected(): bool
    {
        return $this->channel->isConnected();
    }

    public function getChannelId(): int
    {
        return $this->channel->getChannelId();
    }

    public function setPrefetchSize(int $size): void
    {
        $this->channel->setPrefetchSize($size);
    }

    public function getPrefetchSize(): int
    {
        return $this->channel->getPrefetchSize();
    }

    public function setPrefetchCount(int $count): void
    {
        $this->channel->setPrefetchCount($count);
    }

    public function getPrefetchCount(): int
    {
        return $this->channel->getPrefetchCount();
    }

    public function qos(int $size, int $count): void
    {
        $this->channel->qos($size, $count);
    }

    public function startTransaction(): void
    {
        $this->channel->startTransaction();
    }

    public function commitTransaction(): void
    {
        $this->channel->commitTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->channel->rollbackTransaction();
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function basicRecover(bool $requeue = true): void
    {
        $this->channel->basicRecover($requeue);
    }

    public function confirmSelect(): void
    {
        $this->channel->confirmSelect();
    }

    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null): void
    {
        $this->channel->setConfirmCallback($ackCallback, $nackCallback);
    }

    public function waitForConfirm(float $timeout = 0.0): void
    {
        try {
            $this->channel->waitForConfirm($timeout);
        } catch (\AMQPChannelException $e) {
            throw ChannelException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw ChannelException::fromAmqpExtension($e);
        }
    }

    public function setReturnCallback(callable $returnCallback = null): void
    {
        $innerCallback = null;
        if ($returnCallback) {
            $innerCallback = function (
                int $replyCode,
                string $replyText,
                string $exchange,
                string $routingKey,
                \AMQPBasicProperties $properties,
                string $body
            ) use ($returnCallback) {
                return $returnCallback($replyCode, $replyText, $exchange, $routingKey, new Envelope($properties), $body);
            };
        }

        $this->channel->setReturnCallback($innerCallback);
    }

    public function waitForBasicReturn(float $timeout = 0.0): void
    {
        try {
            $this->channel->waitForBasicReturn($timeout);
        } catch (\AMQPChannelException $e) {
            throw ChannelException::fromAmqpExtension($e);
        } catch (\AMQPQueueException $e) {
            throw ChannelException::fromAmqpExtension($e);
        }
    }

    public function newExchange(): ExchangeInterface
    {
        return new Exchange($this);
    }

    public function newQueue(): QueueInterface
    {
        return new Queue($this);
    }
}
