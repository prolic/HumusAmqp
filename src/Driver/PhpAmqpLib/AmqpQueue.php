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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Constants;
use Humus\Amqp\Exception\AmqpQueueException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AmqpQueue
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpQueue implements \Humus\Amqp\Driver\AmqpQueue
{
    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var int
     */
    private $flags = Constants::AMQP_NOPARAM;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * Create an instance of an AmqpQueue object.
     *
     * @param AmqpChannel $amqpChannel The amqp channel to use.
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
    public function declareQueue()
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->queue_declare(
                $this->name,
                (bool) ($this->flags & Constants::AMQP_PASSIVE),
                (bool) ($this->flags & Constants::AMQP_DURABLE),
                (bool) ($this->flags & Constants::AMQP_EXCLUSIVE),
                (bool) ($this->flags & Constants::AMQP_AUTODELETE),
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                $this->arguments,
                null
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function bind($exchangeName, $routingKey = null, array $arguments = [])
    {
        if (null === $routingKey) {
            $routingKey = '';
        }

        try {
            return $this->channel->getPhpAmqpLibChannel()->queue_bind(
                $this->name,
                $exchangeName,
                $routingKey,
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                $arguments,
                null
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function get($flags = Constants::AMQP_NOPARAM)
    {
        try {
            $envelope = $this->channel->getPhpAmqpLibChannel()->basic_get(
                $this->name,
                (bool) ($flags & Constants::AMQP_AUTOACK),
                null
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }

        if ($envelope instanceof AMQPMessage) {
            $envelope = new AmqpEnvelope($envelope);
        }

        return $envelope;
    }

    /**
     * @inheritdoc
     */
    public function consume(callable $callback = null, $flags = Constants::AMQP_NOPARAM, $consumerTag = null)
    {
        if (null !== $callback) {
            $innerCallback = function (AMQPMessage $envelope) use ($callback) {
                $envelope = new AmqpEnvelope($envelope);
                return $callback($envelope, $this);
            };
        } else {
            $innerCallback = null;
        }

        try {
            $this->channel->getPhpAmqpLibChannel()->basic_consume(
                $this->name,
                $consumerTag,
                (bool) ($flags & Constants::AMQP_NOLOCAL),
                (bool) !($flags & Constants::AMQP_AUTOACK),
                (bool) ($flags & Constants::AMQP_EXCLUSIVE),
                (bool) ($flags & Constants::AMQP_NOWAIT),
                $innerCallback,
                null,
                $this->arguments
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function ack($deliveryTag, $flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->basic_ack(
                $deliveryTag,
                (bool) ($flags & Constants::AMQP_MULTIPLE)
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function nack($deliveryTag, $flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->basic_nack(
                $deliveryTag,
                (bool) ($flags & Constants::AMQP_MULTIPLE),
                (bool) ($flags & Constants::AMQP_REQUEUE)
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function reject($deliveryTag, $flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->basic_reject(
                $deliveryTag,
                (bool) ($flags & Constants::AMQP_REQUEUE)
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->queue_purge(
                $this->name,
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                null
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function cancel($consumerTag = '')
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->basic_cancel(
                $consumerTag,
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                false
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function unbind($exchangeName, $routingKey = null, array $arguments = [])
    {
        if (null === $routingKey) {
            $routingKey = '';
        }

        try {
            return $this->channel->getPhpAmqpLibChannel()->queue_unbind(
                $this->name,
                $exchangeName,
                $routingKey,
                $arguments,
                null
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($flags = Constants::AMQP_NOPARAM)
    {
        try {
            return $this->channel->getPhpAmqpLibChannel()->queue_delete(
                $this->name,
                (bool) ($flags & Constants::AMQP_IFUNUSED),
                (bool) ($flags & Constants::AMQP_IFEMPTY),
                (bool) ($flags & Constants::AMQP_NOWAIT),
                null
            );
        } catch (\Exception $e) {
            throw AmqpQueueException::fromPhpAmqpLib($e);
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
