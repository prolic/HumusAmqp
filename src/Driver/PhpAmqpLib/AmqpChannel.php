<?php

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;

/**
 * Class AmqpChannel
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpChannel implements \Humus\Amqp\Driver\AmqpChannel
{
    /**
     * @var AbstractAmqpConnection
     */
    private $connection;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * Create an instance of an AMQPChannel object.
     *
     * @param AbstractAmqpConnection $amqpConnection  An instance of AbstractAmqpConnection
     *                                        with an active connection to a
     *                                        broker.
     *
     * @throws AmqpConnectionException        If the connection to the broker
     *                                        was lost.
     */
    public function __construct(AbstractAmqpConnection $amqpConnection)
    {
        $this->connection = $amqpConnection;

        try {
            $this->channel = new \PhpAmqpLib\Channel\AMQPChannel($amqpConnection->getAmqpExtensionConnection());
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @return \AMQPChannel
     */
    public function getAmqpExtensionChannel()
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return $this->channel->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function getChannelId()
    {
        return $this->channel->getChannelId();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchSize($size)
    {
        try {
            return $this->channel->setPrefetchSize($size);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchSize()
    {
        return $this->channel->getPrefetchSize();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchCount($count)
    {
        try {
            return $this->channel->setPrefetchCount($count);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchCount()
    {
        return $this->channel->getPrefetchCount();
    }

    /**
     * @inheritdoc
     */
    public function qos($size, $count)
    {
        try {
            return $this->channel->qos($size, $count);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        try {
            return $this->channel->startTransaction();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        try {
            return $this->channel->commitTransaction();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        try {
            return $this->channel->rollbackTransaction();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function basicRecover($requeue = true)
    {
        $this->channel->basicRecover($requeue);
    }
}
