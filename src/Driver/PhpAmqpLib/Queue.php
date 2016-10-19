<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
declare(strict_types=1);

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Constants;
use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\Connection as ConnectionInterface;
use Humus\Amqp\Exception;
use Humus\Amqp\Queue as QueueInterface;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPArray;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Queue.
 */
final class Queue implements QueueInterface
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
     * @var int
     */
    private $flags = Constants::AMQP_NOPARAM;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * Create an instance of an Queue object.
     *
     * @param Channel $channel The amqp channel to use
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $exchangeName)
    {
        $this->name = $exchangeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getFlags() : int
    {
        return $this->flags;
    }

    /**
     * {@inheritdoc}
     */
    public function setFlags(int $flags)
    {
        $this->flags = (int) $flags;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string $key)
    {
        return isset($this->arguments[$key]) ? $this->arguments[$key] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument(string $key, $value)
    {
        $this->arguments[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue() : int
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
                throw new Exception\InvalidArgumentException('Unknown argument type '.gettype($v));
            }
        }

        try {
            $result = $this->channel->getResource()->queue_declare(
                $this->name,
                (bool) ($this->flags & Constants::AMQP_PASSIVE),
                (bool) ($this->flags & Constants::AMQP_DURABLE),
                (bool) ($this->flags & Constants::AMQP_EXCLUSIVE),
                (bool) ($this->flags & Constants::AMQP_AUTODELETE),
                (bool) ($this->flags & Constants::AMQP_NOWAIT),
                $args,
                null
            );
        } catch (AMQPProtocolChannelException $e) {
            throw Exception\QueueException::fromPhpAmqpLib($e);
        } catch (AMQPRuntimeException $e) {
            throw Exception\ChannelException::fromPhpAmqpLib($e);
        }

        $this->name = $result[0];

        return $result[1];
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        if (null === $routingKey) {
            $routingKey = '';
        }

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
                throw new Exception\InvalidArgumentException('Unknown argument type '.gettype($v));
            }
        }

        $this->channel->getResource()->queue_bind(
            $this->name,
            $exchangeName,
            $routingKey,
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            $args,
            null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $flags = Constants::AMQP_NOPARAM)
    {
        $envelope = $this->channel->getResource()->basic_get(
            $this->name,
            (bool) ($flags & Constants::AMQP_AUTOACK),
            null
        );

        if ($envelope instanceof AMQPMessage) {
            return new Envelope($envelope);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(
        callable $callback = null,
        int $flags = Constants::AMQP_NOPARAM,
        string $consumerTag = null
    ) {
        if (null !== $callback) {
            $innerCallback = function (AMQPMessage $envelope) use ($callback) {
                $result = $callback(new Envelope($envelope), $this);
                if (false === $result) {
                    $this->cancel($envelope->delivery_info['consumer_tag']);
                }

                return $result;
            };
        } else {
            $innerCallback = null;
        }

        if (null === $consumerTag) {
            $consumerTag = bin2hex(random_bytes(24));
        }

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
                throw new Exception\InvalidArgumentException('Unknown argument type '.gettype($v));
            }
        }

        try {
            $this->channel->getResource()->basic_consume(
                $this->name,
                $consumerTag,
                (bool) ($flags & Constants::AMQP_NOLOCAL),
                (bool) ($flags & Constants::AMQP_AUTOACK),
                (bool) ($flags & Constants::AMQP_EXCLUSIVE),
                (bool) ($flags & Constants::AMQP_NOWAIT),
                $innerCallback,
                null,
                $args
            );

            if (isset($this->channel->getResource()->callbacks[$consumerTag])) {
                while (count($this->channel->getResource()->callbacks)) {
                    $this->channel->getResource()->wait();
                }
            }
        } catch (\Throwable $e) {
            throw Exception\QueueException::fromPhpAmqpLib($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function ack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->basic_ack(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_MULTIPLE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function nack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->basic_nack(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_MULTIPLE),
            (bool) ($flags & Constants::AMQP_REQUEUE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reject(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->basic_reject(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_REQUEUE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $this->channel->getResource()->queue_purge(
            $this->name,
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(string $consumerTag = '')
    {
        $this->channel->getResource()->basic_cancel(
            $consumerTag,
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            false
        );
    }

    /**
     * {@inheritdoc}
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
                throw new Exception\InvalidArgumentException('Unknown argument type '.gettype($v));
            }
        }

        $this->channel->getResource()->queue_unbind(
            $this->name,
            $exchangeName,
            $routingKey,
            $args,
            null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->queue_delete(
            $this->name,
            (bool) ($flags & Constants::AMQP_IFUNUSED),
            (bool) ($flags & Constants::AMQP_IFEMPTY),
            (bool) ($flags & Constants::AMQP_NOWAIT),
            null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel() : ChannelInterface
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection() : ConnectionInterface
    {
        return $this->channel->getConnection();
    }
}
