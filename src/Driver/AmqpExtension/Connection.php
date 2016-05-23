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

declare (strict_types=1);

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\ConnectionOptions;

/**
 * Class Connection
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Connection implements ConnectionInterface
{
    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var ConnectionOptions
     */
    private $options;

    /**
     * @inheritdoc
     */
    public function __construct(ConnectionOptions $options)
    {
        $this->options = $options;
        $this->connection = new \AMQPConnection($options->toArray());
    }

    /**
     * @return \AMQPConnection
     */
    public function getResource() : \AMQPConnection
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function isConnected() : bool
    {
        return $this->connection->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        if ($this->options->getPersistent()) {
            $this->connection->pconnect();
        } else {
            $this->connection->connect();
        }
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        if ($this->options->getPersistent()) {
            $this->connection->pdisconnect();
        } else {
            $this->connection->disconnect();
        }
    }

    /**
     * @inheritdoc
     */
    public function reconnect() : bool
    {
        if ($this->options->getPersistent()) {
            return $this->connection->preconnect();
        } else {
            return $this->connection->reconnect();
        }
    }
}
