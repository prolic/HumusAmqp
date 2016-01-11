<?php

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;

/**
 * Class AmqpChannel
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpChannel implements \Humus\Amqp\Driver\AmqpChannel
{
    /**
     * @var AmqpConnection
     */
    private $connection;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * Create an instance of an AMQPChannel object.
     *
     * @param AmqpConnection $amqpConnection  An instance of AmqpConnection
     *                                        with an active connection to a
     *                                        broker.
     *
     * @throws AmqpConnectionException        If the connection to the broker
     *                                        was lost.
     */
    public function __construct(AmqpConnection $amqpConnection)
    {
        $this->connection = $amqpConnection;

        try {
            $this->channel = new \AMQPChannel($amqpConnection->getAmqpExtensionConnection());
        } catch (\AMQPConnectionException $e) {
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
        }
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
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
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
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
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
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
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
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
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
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
        } catch (\AMQPChannelException $e) {
            throw new AmqpChannelException($e->getMessage(), $e->getCode(), $e);
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
            throw new AmqpConnectionException($e->getMessage(), $e->getCode(), $e);
        } catch (\AMQPChannelException $e) {
            throw new AmqpChannelException($e->getMessage(), $e->getCode(), $e);
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
