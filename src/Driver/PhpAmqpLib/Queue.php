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

declare (strict_types=1);

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Constants;
use Humus\Amqp\Channel as AmqpChannelInterface;
use Humus\Amqp\Connection as AmqpConnectionInterface;
use Humus\Amqp\Queue as AmqpQueueInterface;
use Humus\Amqp\Exception\ConnectionException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Queue
 * @package Humus\Amqp\Driver\AmqpExtension
 */
final class Queue implements AmqpQueueInterface
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
     * @param Channel $amqpChannel The amqp channel to use.
     */
    public function __construct(Channel $amqpChannel)
    {
        $this->channel = $amqpChannel;
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
        return isset($this->arguments[$key]) ? $this->arguments[$key] : false;
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
    public function declareQueue() : int
    {
        return $this->channel->getResource()->queue_declare(
            $this->name,
            (bool) ($this->flags & Constants::AMQP_PASSIVE),
            (bool) ($this->flags & Constants::AMQP_DURABLE),
            (bool) ($this->flags & Constants::AMQP_EXCLUSIVE),
            (bool) ($this->flags & Constants::AMQP_AUTODELETE),
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            $this->arguments,
            null
        )[1];
    }

    /**
     * @inheritdoc
     */
    public function bind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        if (null === $routingKey) {
            $routingKey = '';
        }

        $this->channel->getResource()->queue_bind(
            $this->name,
            $exchangeName,
            $routingKey,
            (bool) ($this->flags & Constants::AMQP_NOWAIT),
            $arguments,
            null
        );
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
                $this->arguments
            );

            if (isset($this->channel->getResource()->callbacks[$consumerTag])) {
                while (count($this->channel->getResource()->callbacks)) {
                    $this->channel->getResource()->wait();
                }
            }
        } catch (\Exception $e) {
            throw ConnectionException::fromPhpAmqpLib($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function ack(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->basic_ack(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_MULTIPLE)
        );
    }

    /**
     * @inheritdoc
     */
    public function nack(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->basic_nack(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_MULTIPLE),
            (bool) ($flags & Constants::AMQP_REQUEUE)
        );
    }

    /**
     * @inheritdoc
     */
    public function reject(string $deliveryTag, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->channel->getResource()->basic_reject(
            $deliveryTag,
            (bool) ($flags & Constants::AMQP_REQUEUE)
        );
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function unbind(string $exchangeName, string $routingKey = null, array $arguments = [])
    {
        if (null === $routingKey) {
            $routingKey = '';
        }

        $this->channel->getResource()->queue_unbind(
            $this->name,
            $exchangeName,
            $routingKey,
            $arguments,
            null
        );
    }

    /**
     * @inheritdoc
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
