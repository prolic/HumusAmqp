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

namespace Humus\Amqp\Container;

use AMQPChannel;
use AMQPExchange;
use Humus\Amqp\Constants;
use Humus\Amqp\Exception;
use Interop\Container\ContainerInterface;

/**
 * Class ExchangeFactory
 * @package Humus\Amqp\Container
 */
final class ExchangeFactory extends AbstractFactory
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $exchangeName;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var bool
     */
    private $autoSetupFabric;

    /**
     * QueueFactory constructor.
     * @param AMQPChannel $channel
     * @param string $exchangeName
     * @param string $connectionName
     * @param bool $autoSetupFabric
     */
    public function __construct(AMQPChannel $channel, $exchangeName, $connectionName, $autoSetupFabric)
    {
        $this->channel = $channel;
        $this->exchangeName = $exchangeName;
        $this->connectionName = $connectionName;
        $this->autoSetupFabric = $autoSetupFabric;
    }

    /**
     * @param ContainerInterface $container
     * @return AMQPExchange
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $options = $this->options($config);

        if ($options['connection'] !== $this->connectionName) {
            throw new Exception\InvalidArgumentException(
                sprintf('The exchange\'s connection %s does not match the provided connection %s',
                    $options['connection'],
                    $this->connectionName
                )
            );
        }

        $exchange = new AMQPExchange($this->channel);

        $exchange->setArguments($options['arguments']);
        $exchange->setName($options['name']);
        $exchange->setFlags($this->getFlags($options));
        $exchange->setType($options['type']);

        if ($this->autoSetupFabric) {
            $exchange->declareExchange();

            $flags = $this->getFlags($options);
            // rabbitmq extension: exchange to exchange bindings
            foreach ($options['exchange_bindings'] as $exchangeName => $routingKeys) {
                foreach ($routingKeys as $routingKey) {
                    $exchange->bind($exchangeName, $routingKey, $flags);
                }
            }
        }

        return $exchange;
    }

    /**
     * @return string
     */
    public function componentName()
    {
        return 'exchange';
    }

    /**
     * @return string
     */
    public function elementName()
    {
        return $this->exchangeName;
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'arguments' => [
                'internal' => false // RabbitMQ Extension
            ],
            'connection' => 'default',
            'auto_delete' => false, // RabbitMQ Extension
            'exchange_bindings' => [], // RabbitMQ Extension
            'passive' => false,
            'durable' => true,
            'name' => '',
            'type' => 'direct',
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions()
    {
        return [
            'arguments',
            'connection',
            'auto_delete', // RabbitMQ Extension
            'exchange_bindings', // RabbitMQ Extension
            'passive',
            'durable',
            'name',
            'type',
        ];
    }

    /**
     * @param array|ArrayAccess
     * @return int
     */
    public function getFlags($options)
    {
        $flags = 0;
        $flags |= $options['passive'] ? Constants::AMQP_PASSIVE : 0;
        $flags |= $options['durable'] ? Constants::AMQP_DURABLE : 0;
        $flags |= $options['auto_delete'] ? Constants::AMQP_AUTODELETE : 0; // RabbitMQ Extension

        return $flags;
    }
}
