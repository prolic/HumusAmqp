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
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Exception\BadMethodCallException;
use PhpAmqpLib\Connection\AbstractConnection as PhpAmqplibAbstractConnection;

abstract class AbstractConnection implements ConnectionInterface
{
    protected PhpAmqplibAbstractConnection $connection;
    protected ConnectionOptions $options;

    public function getResource(): PhpAmqplibAbstractConnection
    {
        return $this->connection;
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function connect(): void
    {
        throw new BadMethodCallException();
    }

    public function disconnect(): void
    {
        throw new BadMethodCallException();
    }

    public function reconnect(): void
    {
        $this->connection->reconnect();
    }

    public function getOptions(): ConnectionOptions
    {
        return $this->options;
    }

    public function newChannel(): ChannelInterface
    {
        $channel = new Channel($this, $this->getResource()->channel());
        $channel->setPrefetchCount(3);
        $channel->setPrefetchSize(0);

        return $channel;
    }
}
