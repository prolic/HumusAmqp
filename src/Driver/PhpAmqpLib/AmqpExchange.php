<?php
/*
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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Constants;
use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpExchangeException;
use PhpAmqpLib\Message\AMQPMessage;

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
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $flags = Constants::AMQP_NOPARAM;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * Create an instance of AMQPExchange.
     *
     * Returns a new instance of an AMQPExchange object, associated with the
     * given AmqpChannel object.
     *
     * @param AmqpChannel $amqpChannel A valid AmqpChannel object, connected
     *                                 to a broker.
     */
    public function __construct(AmqpChannel $amqpChannel)
    {
        $this->channel = $amqpChannel;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName($exchangeName)
    {
        return $this->name = $exchangeName;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function setType($exchangeType)
    {
        return $this->type = $exchangeType;
    }

    /**
     * @inheritdoc
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @inheritdoc
     */
    public function setFlags($flags)
    {
        return $this->flags = (int) $flags;
    }

    /**
     * @inheritdoc
     */
    public function getArgument($key)
    {
        return isset($this->arguments[$key]) ? $this->arguments[$key] : false;
    }

    /**
     * @inheritdoc
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @inheritdoc
     */
    public function setArgument($key, $value)
    {
        $this->arguments[$key] = $value;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setArguments(array $arguments)
    {
        return $this->arguments = $arguments;
    }

    /**
     * @inheritdoc
     */
    public function declareExchange()
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->exchange_declare(
                $this->name,
                $this->type,
                (bool) ($this->flags & Constants::AMQP_PASSIVE),
                (bool) ($this->flags & Constants::AMQP_DURABLE),
                (bool) ($this->flags & Constants::AMQP_AUTODELETE),
                (bool) ($this->flags & Constants::AMQP_INTERNAL),
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                $this->arguments,
                null
            );
        } catch (\Exception $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($exchangeName = null, $flags = Constants::AMQP_NOPARAM)
    {
        if (null === $exchangeName) {
            $exchangeName = $this->name;
        }

        try {
            return $this->channel->getPhpAmqpLibChannel()->exchange_delete($exchangeName, $flags);
        } catch (\Exception $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function bind($exchangeName, $routingKey = '', array $arguments = [])
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->exchange_bind(
                $exchangeName,
                $this->name,
                $routingKey,
                false,
                $arguments,
                null
            );
        } catch (\Exception $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function unbind($exchangeName, $routingKey = '', array $arguments = [])
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->exchange_unbind(
                $exchangeName,
                $this->name,
                $routingKey,
                $arguments,
                null
            );
        } catch (\Exception $e) {
            throw AmqpExchangeException::fromAmqpExtension($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function publish($message, $routingKey = null, $flags = Constants::AMQP_NOPARAM, array $attributes = [])
    {
        $message = new AMQPMessage($message, $attributes);

        if (null === $routingKey) {
            $routingKey = '';
        }

        try {
            return $this->channel->getPhpAmqpLibChannel()->basic_publish(
                $message,
                $this->name,
                $routingKey,
                (bool) ($this->flags & Constants::AMQP_MANDATORY),
                (bool) ($this->flags & Constants::AMQP_IMMEDIATE),
                null
            );
        } catch (\Exception $e) {
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
     * Get the AbstractAmqpConnection object in use
     *
     * @return AbstractAmqpConnection
     */
    public function getConnection()
    {
        return $this->channel->getConnection();
    }
}
