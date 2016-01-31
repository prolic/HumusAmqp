<?php

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\BadMethodCallException;

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
     * @var \PhpAmqpLib\Channel\AMQPChannel
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
            $this->channel = new \PhpAmqpLib\Channel\AMQPChannel($amqpConnection->getPhpAmqpLibConnection());
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    public function getPhpAmqpLibChannel()
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        throw new BadMethodCallException();
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
            return $this->channel->basic_qos($size, 0, false);
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchSize()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchCount($count)
    {
        try {
            return $this->channel->basic_qos(0, $count, false);
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getPrefetchCount()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function qos($size, $count)
    {
        try {
            return $this->channel->basic_qos($size, $count, false);
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        try {
            return $this->channel->tx_select();
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        try {
            return $this->channel->tx_commit();
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        try {
            return $this->channel->tx_rollback();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
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
        $this->channel->basic_recover($requeue);
    }
}
