<?php

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\BadMethodCallException;
use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Class AbstractAmqpConnection
 * @package Humus\Amqp\Driver\AmqpExtension
 */
abstract class AbstractAmqpConnection implements \Humus\Amqp\Driver\AmqpConnection
{
    /**
     * @var AbstractConnection
     */
    protected $connection;

    /**
     * @return \AMQPConnection
     */
    public function getPhpAmqpLibConnection()
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
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function pconnect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function pdisconnect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function reconnect()
    {
        try {
            $this->connection->reconnect();
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromPhpAmqpLib($e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function preconnect()
    {
        throw new BadMethodCallException();
    }
}
