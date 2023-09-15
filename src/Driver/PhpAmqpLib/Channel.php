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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Exception\BadMethodCallException;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exchange as ExchangeInterface;
use Humus\Amqp\Queue as QueueInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Message\AMQPMessage;

final class Channel implements ChannelInterface
{
    private AbstractConnection $connection;
    private AMQPChannel $channel;
    private int $prefetchCount;
    private int $prefetchSize;
    

    public function __construct(AbstractConnection $connection, AMQPChannel $channel)
    {
        $this->connection = $connection;
        $this->channel = $channel;
        $this->channel->set_ack_handler(function (): void {
            trigger_error('Unhandled basic.ack method from server received.');
        });
        $this->channel->set_nack_handler(function (): void {
            trigger_error('Unhandled basic.nack method from server received.');
        });
        $this->channel->set_return_listener(function (): void {
            trigger_error('Unhandled basic.return method from server received.');
        });
    }

    public function getResource(): AMQPChannel
    {
        return $this->channel;
    }

    public function isConnected(): bool
    {
        throw new BadMethodCallException();
    }

    public function getChannelId(): int
    {
        return (int) $this->channel->getChannelId();
    }

    public function setPrefetchSize(int $size): void
    {
        $this->channel->basic_qos($size, 0, false);
        $this->prefetchSize = $size;
    }

    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    public function setPrefetchCount(int $count): void
    {
        $this->channel->basic_qos(0, $count, false);
        $this->prefetchCount = $count;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function qos(int $size, int $count): void
    {
        $this->channel->basic_qos($size, $count, false);
        $this->prefetchSize = $size;
        $this->prefetchCount = $count;
    }

    public function startTransaction(): void
    {
        $this->channel->tx_select();
    }

    public function commitTransaction(): void
    {
        $this->channel->tx_commit();
    }

    public function rollbackTransaction(): void
    {
        $this->channel->tx_rollback();
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function basicRecover(bool $requeue = true): void
    {
        $this->channel->basic_recover($requeue);
    }

    public function confirmSelect(): void
    {
        $this->channel->confirm_select();
    }

    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null): void
    {
        if (\is_callable($ackCallback)) {
            $innerAckCallback = function (AMQPMessage $message) use ($ackCallback): bool {
                return $ackCallback((int) $message->get('delivery_tag'), false);
            };
            $this->channel->set_ack_handler($innerAckCallback);
        }

        if (\is_callable($nackCallback)) {
            $innerNackCallback = function (AMQPMessage $message) use ($ackCallback): bool {
                return $ackCallback((int) $message->get('delivery_tag'), false, false);
            };
            $this->channel->set_nack_handler($innerNackCallback);
        }
    }

    public function waitForConfirm(float $timeout = 0.0): void
    {
        if ($timeout < 0) {
            throw new ChannelException('Timeout must be greater than or equal to zero.');
        }

        if ($timeout < 1) {
            $timeout = 1;
        }

        try {
            $this->channel->wait_for_pending_acks_returns((int) $timeout);
        } catch (AMQPExceptionInterface $e) {
            throw new ChannelException($e->getMessage());
        }
    }

    public function setReturnCallback(callable $returnCallback = null): void
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
        ) use ($returnCallback): bool {
            $envelope = new Envelope($message);

            return $returnCallback($replyCode, $replyText, $exchange, $routingKey, $envelope, $envelope->getBody());
        };

        $this->channel->set_return_listener($innerCallback);
    }

    public function waitForBasicReturn(float $timeout = 0.0): void
    {
        try {
            $this->channel->wait(null, false, $timeout);
        } catch (\Exception $e) {
            throw ChannelException::fromPhpAmqpLib($e);
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
