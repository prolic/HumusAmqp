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
use Humus\Amqp\Channel as AmqpChannelInterface;
use Humus\Amqp\Connection as AmqpConnectionInterface;
use Humus\Amqp\Exchange as AmqpExchangeInterface;

/**
 * Class Exchange
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class Exchange implements AmqpExchangeInterface
{
    /**
     * @var Channel
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
     * given Channel object.
     *
     * @param Channel $amqpChannel A valid Channel object, connected
     *                                 to a broker.
     */
    public function __construct(Channel $amqpChannel)
    {
        $this->channel = $amqpChannel;
        $this->exchange = new \AMQPExchange($amqpChannel->getResource());
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
    public function declareExchange()
    {
        $this->exchange->declareExchange();
    }

    /**
     * @inheritdoc
     */
    public function delete(string $exchangeName = null, int $flags = Constants::AMQP_NOPARAM)
    {
        $this->exchange->delete($exchangeName, $flags);
    }

    /**
     * @inheritdoc
     */
    public function bind(string $exchangeName, string $routingKey = '', array $arguments = [])
    {
        $this->exchange->bind($exchangeName, $routingKey, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = [])
    {
        $this->exchange->unbind($exchangeName, $routingKey, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function publish(
        string $message,
        string $routingKey = null,
        int $flags = Constants::AMQP_NOPARAM,
        array $attributes = []
    ) {
        $this->exchange->publish($message, $routingKey, $flags, $attributes);
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
