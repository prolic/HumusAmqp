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

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Constants;
use Humus\Amqp\AmqpChannel as AmqpChannelInterface;
use Humus\Amqp\AmqpConnection as AmqpConnectionInterface;
use Humus\Amqp\AmqpExchange as AmqpExchangeInterface;
use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpExchangeException;

/**
 * Class AmqpExchange
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpExchange implements AmqpExchangeInterface
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
    public function getName() : string
    {
        return $this->exchange->getName();
    }

    /**
     * @inheritdoc
     */
    public function setName(string $exchangeName)
    {
        $this->exchange->setName($exchangeName);
    }

    /**
     * @inheritdoc
     */
    public function getType() : string
    {
        return $this->exchange->getType();
    }

    /**
     * @inheritdoc
     */
    public function setType(string $exchangeType)
    {
        $this->exchange->setType($exchangeType);
    }

    /**
     * @inheritdoc
     */
    public function getFlags() : int
    {
        return $this->exchange->getFlags();
    }

    /**
     * @inheritdoc
     */
    public function setFlags(int $flags)
    {
        $this->exchange->setFlags($flags);
    }

    /**
     * @inheritdoc
     */
    public function getArgument(string $key)
    {
        return $this->exchange->getArgument($key);
    }

    /**
     * @inheritdoc
     */
    public function getArguments() : array
    {
        return $this->exchange->getArguments();
    }

    /**
     * @inheritdoc
     */
    public function setArgument(string $key, $value)
    {
        $this->exchange->setArgument($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function setArguments(array $arguments)
    {
        $this->exchange->setArguments($arguments);
    }

    /**
     * @inheritdoc
     */
    public function declareExchange() : bool
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
    public function delete(string $exchangeName = null, int $flags = Constants::AMQP_NOPARAM) : bool
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
    public function bind(string $exchangeName, string $routingKey = '', array $arguments = []) : bool
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
    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []) : bool
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
    public function publish(
        string $message,
        string $routingKey = null,
        int $flags = Constants::AMQP_NOPARAM,
        array $attributes = []
    ) : bool {
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
     * @inheritdoc
     */
    public function getChannel() : AmqpChannelInterface
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function getConnection() : AmqpConnectionInterface
    {
        return $this->channel->getConnection();
    }
}
