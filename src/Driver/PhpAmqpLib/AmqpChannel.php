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

use Humus\Amqp\AmqpConnection as AmqpConnectionInterface;
use Humus\Amqp\AmqpChannel as AmqpChannelInterface;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\BadMethodCallException;
use PhpAmqpLib\Channel\AMQPChannel as BaseAMQPChannel;

/**
 * Class AmqpChannel
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpChannel implements AmqpChannelInterface
{
    /**
     * @var AbstractAmqpConnection
     */
    private $connection;

    /**
     * @var BaseAMQPChannel
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
            $this->channel = new BaseAMQPChannel($amqpConnection->getPhpAmqpLibConnection());
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @return BaseAMQPChannel
     */
    public function getPhpAmqpLibChannel() : BaseAMQPChannel
    {
        return $this->channel;
    }

    /**
     * @inheritdoc
     */
    public function isConnected() : bool
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function getChannelId() : int
    {
        return $this->channel->getChannelId();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchSize(int $size) : bool
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
    public function getPrefetchSize() : int
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function setPrefetchCount(int $count) : bool
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
    public function getPrefetchCount() : int
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function qos(int $size, int $count) : bool
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
    public function startTransaction() : bool
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
    public function commitTransaction() : bool
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
    public function rollbackTransaction() : bool
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
    public function getConnection() : AmqpConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function basicRecover(bool $requeue = true) : bool
    {
        $this->channel->basic_recover($requeue);
    }

    /**
     * @inheritdoc
     */
    public function confirmSelect() : bool
    {
        $this->channel->confirm_select();
    }
}
