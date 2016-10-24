<?php
/**
 * Copyright (c) 2016-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
 *
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

declare (strict_types=1);

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Constants;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange as ExchangeInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPArray;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Exchange
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Exchange implements ExchangeInterface
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $type = '';

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
     * given Channel object.
     *
     * @param Channel $channel A valid Channel object, connected to a broker.
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @inheritdoc
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $exchangeName)
    {
        $this->name = $exchangeName;
    }

    /**
     * @inheritdoc
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function setType(string $exchangeType)
    {
        $this->type = $exchangeType;
    }

    /**
     * @inheritdoc
     */
    public function getFlags() : int
    {
        return $this->flags;
    }

    /**
     * @inheritdoc
     */
    public function setFlags(int $flags)
    {
        $this->flags = (int) $flags;
    }

    /**
     * @inheritdoc
     */
    public function getArgument(string $key)
    {
        return $this->arguments[$key] ?? false;
    }

    /**
     * @inheritdoc
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }

    /**
     * @inheritdoc
     */
    public function setArgument(string $key, $value)
    {
        $this->arguments[$key] = $value;
    }

    /**
     * @inheritdoc
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @inheritdoc
     */
    public function declareExchange()
    {
        $args = []; // see: https://github.com/php-amqplib/php-amqplib/issues/405
        $supportedDataTypes = AMQPAbstractCollection::getSupportedDataTypes();
        foreach ($this->arguments as $k => $v) {
            if (is_array($v)) {
                if (empty($v) || (array_keys($v) === range(0, count($v) - 1))) {
                    $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_ARRAY], new AMQPArray($v)];
                } else {
                    $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_TABLE], new AMQPTable($v)];
                }
            } elseif (is_int($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_INT_LONG], $v];
            } elseif (is_bool($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_BOOL], $v];
            } elseif (is_string($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_STRING_LONG], $v];
            } else {
                throw new Exception\InvalidArgumentException('Unknown argument type ' . gettype($v));
            }
        }
        $this->channel->getResource()->exchange_declare(
            $this->name,
            $this->type,
            (bool) ($this->flags & Constants::AMQP_PASSIVE),
            (bool) ($this->flags & Constants::AMQP_DURABLE),
            (bool) ($this->flags & Constants::AMQP_AUTODELETE),
            (bool) ($this->flags & Constants::AMQP_INTERNAL),
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            $args,
            null
        );
    }

    /**
     * @inheritdoc
     */
    public function delete(string $exchangeName = '', int $flags = Constants::AMQP_NOPARAM)
    {
        if ('' === $exchangeName) {
            $exchangeName = $this->name;
        }

        $this->channel->getResource()->exchange_delete($exchangeName, $flags);
    }

    /**
     * @inheritdoc
     */
    public function bind(string $exchangeName, string $routingKey = '', array $arguments = [])
    {
        $args = []; // see: https://github.com/php-amqplib/php-amqplib/issues/405
        $supportedDataTypes = AMQPAbstractCollection::getSupportedDataTypes();
        foreach ($arguments as $k => $v) {
            if (is_array($v)) {
                if (empty($v) || (array_keys($v) === range(0, count($v) - 1))) {
                    $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_ARRAY], new AMQPArray($v)];
                } else {
                    $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_TABLE], new AMQPTable($v)];
                }
            } elseif (is_int($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_INT_LONG], $v];
            } elseif (is_bool($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_BOOL], $v];
            } elseif (is_string($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_STRING_LONG], $v];
            } else {
                throw new Exception\InvalidArgumentException('Unknown argument type ' . gettype($v));
            }
        }

        $this->channel->getResource()->exchange_bind(
            $exchangeName,
            $this->name,
            $routingKey,
            false,
            $args,
            null
        );
    }

    /**
     * @inheritdoc
     */
    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = [])
    {
        $args = []; // see: https://github.com/php-amqplib/php-amqplib/issues/405
        $supportedDataTypes = AMQPAbstractCollection::getSupportedDataTypes();
        foreach ($arguments as $k => $v) {
            if (is_array($v)) {
                if (empty($v) || (array_keys($v) === range(0, count($v) - 1))) {
                    $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_ARRAY], new AMQPArray($v)];
                } else {
                    $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_TABLE], new AMQPTable($v)];
                }
            } elseif (is_int($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_INT_LONG], $v];
            } elseif (is_bool($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_BOOL], $v];
            } elseif (is_string($v)) {
                $args[$k] = [$supportedDataTypes[AMQPAbstractCollection::T_STRING_LONG], $v];
            } else {
                throw new Exception\InvalidArgumentException('Unknown argument type ' . gettype($v));
            }
        }

        $this->channel->getResource()->exchange_unbind(
            $exchangeName,
            $this->name,
            $routingKey,
            $args,
            null
        );
    }

    /**
     * @inheritdoc
     */
    public function publish(
        string $message,
        string $routingKey = '',
        int $flags = Constants::AMQP_NOPARAM, array $attributes = []
    ) {
        $attributes['user_id'] = $this->getConnection()->getOptions()->getLogin();
        $message = new AMQPMessage($message, $attributes);

        if (isset($attributes['headers'])) {
            $message->set('application_headers', new AMQPTable($attributes['headers']));
        }

        $this->channel->getResource()->basic_publish(
            $message,
            $this->name,
            $routingKey,
            (bool) ($flags & Constants::AMQP_MANDATORY),
            (bool) ($flags & Constants::AMQP_IMMEDIATE),
            null
        );
    }

    /**
     * @inheritdoc
     */
    public function getChannel() : ChannelInterface
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function getConnection() : ConnectionInterface
    {
        return $this->channel->getConnection();
    }
}
