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

    /**
     * @inheritdoc
     */
    public function confirmSelect()
    {
        $this->channel->confirm_select();
    }
}
