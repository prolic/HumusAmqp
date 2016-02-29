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
     * @param AmqpConnection $amqpConnection An instance of AmqpConnection
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
            throw AmqpConnectionException::fromAmqpExtension($e);
        }
    }

    /**
     * @return \AMQPChannel
     */
    public function getAmqpExtensionChannel()
    {
        return $this->channel;
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
            throw AmqpConnectionException::fromAmqpExtension($e);
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
            throw AmqpConnectionException::fromAmqpExtension($e);
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
            throw AmqpConnectionException::fromAmqpExtension($e);
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
            throw AmqpConnectionException::fromAmqpExtension($e);
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
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
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
            throw AmqpConnectionException::fromAmqpExtension($e);
        } catch (\AMQPChannelException $e) {
            throw AmqpChannelException::fromAmqpExtension($e);
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

    /**
     * @inheritdoc
     */
    public function confirmSelect()
    {
        $this->channel->confirmSelect();
    }
}
