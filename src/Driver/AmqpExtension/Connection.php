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

use AMQPConnection;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Exception\InvalidArgumentException;
use Traversable;

final class Connection implements ConnectionInterface
{
    private AMQPConnection $connection;
    private ConnectionOptions $options;

    /**
     * @param ConnectionOptions|array|Traversable $options
     */
    public function __construct($options)
    {
        if (! $options instanceof ConnectionOptions) {
            $options = new ConnectionOptions($options);
        }

        if (true === $options->verify() && null === $options->caCert()) {
            throw new InvalidArgumentException('CA cert not set, so it can\'t be verified.');
        }

        $this->options = $options;
        $this->connection = new AMQPConnection($options->toArray());
    }

    public function getResource(): AMQPConnection
    {
        return $this->connection;
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function connect(): void
    {
        if ($this->options->isPersistent()) {
            $this->connection->pconnect();
        } else {
            $this->connection->connect();
        }
    }

    public function disconnect(): void
    {
        if ($this->options->isPersistent()) {
            $this->connection->pdisconnect();
        } else {
            $this->connection->disconnect();
        }
    }

    public function reconnect(): void
    {
        if ($this->options->isPersistent()) {
            $this->connection->preconnect();
        } else {
            $this->connection->reconnect();
        }
    }

    public function getOptions(): ConnectionOptions
    {
        return $this->options;
    }

    public function newChannel(): ChannelInterface
    {
        return new Channel($this);
    }
}
