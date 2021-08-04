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

use AMQPExchange;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Constants;
use Humus\Amqp\Exchange as ExchangeInterface;

/**
 * Class Exchange
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Exchange implements ExchangeInterface
{
    private Channel $channel;
    private AMQPExchange $exchange;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->exchange = new AMQPExchange($channel->getResource());
    }

    public function getName(): string
    {
        return $this->exchange->getName() ?: '';
    }

    public function setName(string $exchangeName): void
    {
        $this->exchange->setName($exchangeName);
    }

    public function getType(): string
    {
        return $this->exchange->getType() ?: '';
    }

    public function setType(string $exchangeType): void
    {
        $this->exchange->setType($exchangeType);
    }

    public function getFlags(): int
    {
        return $this->exchange->getFlags();
    }

    public function setFlags(int $flags): void
    {
        $this->exchange->setFlags($flags);
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string $key)
    {
        return $this->exchange->getArgument($key);
    }

    public function getArguments(): array
    {
        return $this->exchange->getArguments();
    }

    public function setArgument(string $key, $value): void
    {
        $this->exchange->setArgument($key, $value);
    }

    public function setArguments(array $arguments): void
    {
        $this->exchange->setArguments($arguments);
    }

    public function declareExchange(): void
    {
        $this->exchange->declareExchange();
    }

    public function delete(string $exchangeName = '', int $flags = Constants::AMQP_NOPARAM): void
    {
        $this->exchange->delete($exchangeName, $flags);
    }

    public function bind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $this->exchange->bind($exchangeName, $routingKey, $arguments);
    }

    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []): void
    {
        $this->exchange->unbind($exchangeName, $routingKey, $arguments);
    }

    public function publish(
        string $message,
        string $routingKey = '',
        int $flags = Constants::AMQP_NOPARAM,
        array $attributes = []
    ): void {
        $this->exchange->publish($message, $routingKey, $flags, $attributes);
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
