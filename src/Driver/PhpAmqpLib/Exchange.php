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
use Humus\Amqp\Exchange as ExchangeInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class Exchange implements ExchangeInterface
{
    private Channel $channel;
    private string $name = '';
    private string $type = '';
    private int $flags = Constants::AMQP_NOPARAM;
    private array $arguments = [];

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $exchangeType): void
    {
        $this->type = $exchangeType;
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
        return $this->arguments[$key] ?? false;
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

    public function declareExchange(): void
    {
        $args = new AMQPTable($this->arguments);

        $this->channel->getResource()->exchange_declare(
            $this->name,
            $this->type,
            (bool) ($this->flags & Constants::AMQP_PASSIVE),
            (bool) ($this->flags & Constants::AMQP_DURABLE),
            (bool) ($this->flags & Constants::AMQP_AUTODELETE),
            (bool) ($this->flags & Constants::AMQP_INTERNAL),
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            $args,
            null
        );
    }

    public function delete(string $exchangeName = '', int $flags = Constants::AMQP_NOPARAM): void
    {
        if ('' === $exchangeName) {
            $exchangeName = $this->name;
        }

        $this->channel->getResource()->exchange_delete($exchangeName, $flags);
    }

    public function bind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $args = empty($arguments) ? null : new AMQPTable($arguments);

        $this->channel->getResource()->exchange_bind(
            $exchangeName,
            $this->name,
            $routingKey,
            false,
            $args,
            null
        );
    }

    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $args = empty($arguments) ? null : new AMQPTable($arguments);

        $this->channel->getResource()->exchange_unbind(
            $exchangeName,
            $this->name,
            $routingKey,
            false,
            $args,
            null
        );
    }

    public function publish(
        string $message,
        string $routingKey = '',
        int $flags = Constants::AMQP_NOPARAM, array $attributes = []
    ): void {
        $attributes['user_id'] = $this->getConnection()->getOptions()->login();
        $message = new AMQPMessage($message, $attributes);

        if (isset($attributes['headers'])) {
            $message->set('application_headers', new AMQPTable($attributes['headers']));
        }

        $this->channel->getResource()->basic_publish(
            $message,
            $this->name,
            $routingKey,
            (bool) ($flags & Constants::AMQP_MANDATORY),
            (bool) ($flags & Constants::AMQP_IMMEDIATE),
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
