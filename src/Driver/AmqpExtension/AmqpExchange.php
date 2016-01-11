<?php

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpExchangeException;

/**
 * Class AmqpExchange
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpExchange implements \Humus\Amqp\Driver\AmqpExchange
{
    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * Create an instance of AMQPExchange.
     *
     * Returns a new instance of an AMQPExchange object, associated with the
     * given AmqpChannel object.
     *
     * @param AmqpChannel $amqpChannel A valid AmqpChannel object, connected
     *                                 to a broker.
     *
     * @throws AmqpExchangeException   When amqp_channel is not connected to
     *                                 a broker.
     * @throws AmqpConnectionException If the connection to the broker was
     *                                 lost.
     */
    public function __construct(AmqpChannel $amqpChannel)
    {
        $this->channel = $amqpChannel;

        try {
            $this->exchange = new \AMQPExchange($amqpChannel->getAmqpExtensionChannel());
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPExchangeException $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->exchange->getName();
    }

    /**
     * @inheritdoc
     */
    public function setName($exchangeName)
    {
        return $this->exchange->setName($exchangeName);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->exchange->getType();
    }

    /**
     * @inheritdoc
     */
    public function setType($exchangeType)
    {
        return $this->exchange->setType($exchangeType);
    }

    /**
     * @inheritdoc
     */
    public function getFlags()
    {
        return $this->exchange->getFlags();
    }

    /**
     * @inheritdoc
     */
    public function setFlags($flags)
    {
        return $this->exchange->setFlags($flags);
    }

    /**
     * @inheritdoc
     */
    public function getArgument($key)
    {
        return $this->exchange->getArgument($key);
    }

    /**
     * @inheritdoc
     */
    public function getArguments()
    {
        return $this->exchange->getArguments();
    }

    /**
     * @inheritdoc
     */
    public function setArgument($key, $value)
    {
        return $this->exchange->setArgument($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function setArguments(array $arguments)
    {
        return $this->exchange->setArguments($arguments);
    }

    /**
     * @inheritdoc
     */
    public function declareExchange()
    {
        try {
            return $this->exchange->declareExchange();
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        } catch (\AMQPExchangeException $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($exchangeName = null, $flags = AMQP_NOPARAM)
    {
        try {
            return $this->exchange->delete($exchangeName, $flags);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        } catch (\AMQPExchangeException $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function bind($exchangeName, $routingKey = '', array $arguments = [])
    {
        try {
            return $this->exchange->bind($exchangeName, $routingKey, $arguments);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        } catch (\AMQPExchangeException $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function unbind($exchangeName, $routingKey = '', array $arguments = [])
    {
        try {
            return $this->exchange->unbind($exchangeName, $routingKey, $arguments);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        } catch (\AMQPExchangeException $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function publish($message, $routingKey = null, $flags = AMQP_NOPARAM, array $attributes = [])
    {
        try {
            return $this->exchange->publish($message, $routingKey, $flags, $attributes);
        } catch (\AMQPConnectionException $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
        } catch (\AMQPExchangeException $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * Get the AmqpChannel object in use
     *
     * @return AmqpChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Get the AmqpConnection object in use
     *
     * @return AmqpConnection
     */
    public function getConnection()
    {
        return $this->channel->getConnection();
    }
}
