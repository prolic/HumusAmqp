<?php

namespace Humus\Amqp\Driver\PhpAmqpLib;

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

    /**
     * @inheritdoc
     */
    public function getLogin()
    {
        return $this->connection->getLogin();
    }

    /**
     * @inheritdoc
     */
    public function setLogin($login)
    {
        try {
            return $this->connection->setLogin($login);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPassword()
    {
        return $this->connection->getPassword();
    }

    /**
     * @inheritdoc
     */
    public function setPassword($password)
    {
        try {
            return $this->connection->setPassword($password);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        return $this->connection->getHost();
    }

    /**
     * @inheritdoc
     */
    public function setHost($host)
    {
        try {
            return $this->connection->setHost($host);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPort()
    {
        return $this->connection->getPort();
    }

    /**
     * @inheritdoc
     */
    public function setPort($port)
    {
        try {
            return $this->connection->setPort($port);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getVhost()
    {
        return $this->connection->getVhost();
    }

    /**
     * @inheritdoc
     */
    public function setVhost($vhost)
    {
        try {
            return $this->connection->setVhost($vhost);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getReadTimeout()
    {
        return $this->connection->getReadTimeout();
    }

    /**
     * @inheritdoc
     */
    public function setReadTimeout($timeout)
    {
        try {
            return $this->connection->setReadTimeout($timeout);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getWriteTimeout()
    {
        return $this->connection->getWriteTimeout();
    }

    /**
     * @inheritdoc
     */
    public function setWriteTimeout($timeout)
    {
        try {
            return $this->connection->setWriteTimeout($timeout);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getUsedChannels()
    {
        return $this->connection->getUsedChannels();
    }

    /**
     * @inheritdoc
     */
    public function getMaxChannels()
    {
        return $this->connection->getMaxChannels();
    }

    /**
     * @inheritdoc
     */
    public function isPersistent()
    {
        return $this->connection->isPersistent();
    }
}
