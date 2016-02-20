<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic
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

use Humus\Amqp\Exception\AmqpConnectionException;

/**
 * Class AmqpConnection
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpConnection implements \Humus\Amqp\Driver\AmqpConnection
{
    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @inheritdoc
     */
    public function __construct(array $credentials = [])
    {
        try {
            $this->connection = new \AMQPConnection($credentials);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @return \AMQPConnection
     */
    public function getAmqpExtensionConnection()
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return $this->connection->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        try {
            return $this->connection->connect();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function pconnect()
    {
        try {
            return $this->connection->pconnect();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function pdisconnect()
    {
        return $this->connection->pdisconnect();
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        return $this->connection->disconnect();
    }

    /**
     * @inheritdoc
     */
    public function reconnect()
    {
        try {
            return $this->connection->reconnect();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function preconnect()
    {
        try {
            return $this->connection->preconnect();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }
}
