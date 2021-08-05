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
use Humus\Amqp\Constants;
use Humus\Amqp\Exception;
use Humus\Amqp\Queue as QueueInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class Queue implements QueueInterface
{
    private Channel $channel;
    private string $name = '';
    private int $flags = Constants::AMQP_NOPARAM;
    private array $arguments = [];

    /** @internal */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $exchangeName): void
    {
        $this->name = $exchangeName;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function setFlags(int $flags): void
    {
        $this->flags = (int) $flags;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string $key)
    {
        return isset($this->arguments[$key]) ? $this->arguments[$key] : false;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArgument(string $key, $value): void
    {
        $this->arguments[$key] = $value;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function declareQueue(): int
    {
        $args = new AMQPTable($this->arguments);

        try {
            $result = $this->channel->getResource()->queue_declare(
                $this->name,
                (bool) ($this->flags & Constants::AMQP_PASSIVE),
                (bool) ($this->flags & Constants::AMQP_DURABLE),
                (bool) ($this->flags & Constants::AMQP_EXCLUSIVE),
                (bool) ($this->flags & Constants::AMQP_AUTODELETE),
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                $args,
                null
            );
        } catch (AMQPProtocolChannelException $e) {
            throw Exception\QueueException::fromPhpAmqpLib($e);
        } catch (AMQPRuntimeException $e) {
            throw Exception\ChannelException::fromPhpAmqpLib($e);
        }

        $this->name = $result[0];

        return $result[1];
    }

    public function bind(string $exchangeName, string $routingKey = null, array $arguments = []): void
    {
        if (null === $routingKey) {
            $routingKey = '';
        }

        $args = empty($arguments) ? null : new AMQPTable($arguments);

        $this->channel->getResource()->queue_bind(
            $this->name,
            $exchangeName,
            $routingKey,
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            $args,
            null
        );
    }

    public function get(int $flags = Constants::AMQP_NOPARAM): ?Envelope
    {
        $envelope = $this->channel->getResource()->basic_get(
            $this->name,
            (bool) ($flags & Constants::AMQP_AUTOACK),
            null
        );

        if ($envelope instanceof AMQPMessage) {
            return new Envelope($envelope);
        }

        return null;
    }

    public function consume(
        ?callable $callback = null,
        int $flags = Constants::AMQP_NOPARAM,
        string $consumerTag = null
    ): void {
        if (null !== $callback) {
            $innerCallback = function (AMQPMessage $envelope) use ($callback): bool {
                $result = $callback(new Envelope($envelope), $this);

                if (false === $result) {
                    $this->cancel($envelope->delivery_info['consumer_tag']);
                }

                return $result;
            };
        } else {
            $innerCallback = null;
        }

        if (null === $consumerTag) {
            $consumerTag = bin2hex(random_bytes(24));
        }

        $args = new AMQPTable($this->arguments);

        try {
            $this->channel->getResource()->basic_consume(
                $this->name,
                $consumerTag,
                (bool) ($flags & Constants::AMQP_NOLOCAL),
                (bool) ($flags & Constants::AMQP_AUTOACK),
                (bool) ($flags & Constants::AMQP_EXCLUSIVE),
                (bool) ($flags & Constants::AMQP_NOWAIT),
                $innerCallback,
                null,
                $args
            );

            $readTimeout = $this->getConnection()->getOptions()->readTimeout();

            while ($this->channel->getResource()->is_consuming()) {
                $this->channel->getResource()->wait(null, false, $readTimeout);
            }
        } catch (\Throwable $e) {
            throw Exception\QueueException::fromPhpAmqpLib($e);
        }
    }

    public function ack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->channel->getResource()->basic_ack(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_MULTIPLE)
        );
    }

    public function nack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->channel->getResource()->basic_nack(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_MULTIPLE),
            (bool) ($flags & Constants::AMQP_REQUEUE)
        );
    }

    public function reject(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->channel->getResource()->basic_reject(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_REQUEUE)
        );
    }

    public function purge(): void
    {
        $this->channel->getResource()->queue_purge(
            $this->name,
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            null
        );
    }

    public function cancel(string $consumerTag = ''): void
    {
        $this->channel->getResource()->basic_cancel(
            $consumerTag,
            false,
            true
        );
    }

    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $args = empty($arguments) ? null : new AMQPTable($arguments);

        $this->channel->getResource()->queue_unbind(
            $this->name,
            $exchangeName,
            $routingKey,
            $args,
            null
        );
    }

    public function delete(int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->channel->getResource()->queue_delete(
            $this->name,
            (bool) ($flags & Constants::AMQP_IFUNUSED),
            (bool) ($flags & Constants::AMQP_IFEMPTY),
            (bool) ($flags & Constants::AMQP_NOWAIT),
            null
        );
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
