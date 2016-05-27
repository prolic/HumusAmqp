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

use Humus\Amqp\Connection;
use Humus\Amqp\Constants;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;

/**
 * Class ExchangeFactory
 * @package Humus\Amqp\Container
 */
final class ExchangeFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $exchangeName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_exchange' => [ExchangeFactory::class, 'your_exchange_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return Exchange
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments) : Exchange
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    /**
     * QueueFactory constructor.
     * @param string $exchangeName
     */
    public function __construct(string $exchangeName)
    {
        $this->exchangeName = $exchangeName;
    }

    /**
     * @param ContainerInterface $container
     * @return Exchange
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container) : Exchange
    {
        if (! $container->has(Driver::class)) {
            throw new Exception\RuntimeException('No driver factory registered in container');
        }

        $driver = $container->get(Driver::class);
        $config = $container->get('config');
        $options = $this->options($config, $this->exchangeName);

        $connection = $this->fetchConnection($container, $options['connection']);

        switch ($driver) {
            case Driver::AMQP_EXTENSION():
                $exchange = new \Humus\Amqp\Driver\AmqpExtension\Exchange($connection->newChannel());
                break;
            case Driver::PHP_AMQP_LIB():
                $exchange = new \Humus\Amqp\Driver\PhpAmqpLib\Exchange($connection->newChannel());
                break;
            default:
                throw new Exception\RuntimeException('Unknown driver');
        }

        $exchange->setArguments($options['arguments']);
        $exchange->setName($options['name']);
        $exchange->setFlags($this->getFlags($options));
        $exchange->setType($options['type']);

        if ($options['auto_setup_fabric']) {
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
     * @return array
     */
    public function dimensions()
    {
        return ['humus', 'amqp', 'exchange'];
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
            'auto_delete' => false, // RabbitMQ Extension
            'exchange_bindings' => [], // RabbitMQ Extension
            'passive' => false,
            'durable' => true,
            'name' => '',
            'type' => 'direct',
            // factory configs
            'auto_setup_fabric' => false,
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions()
    {
        return [
            'arguments',
            'auto_delete', // RabbitMQ Extension
            'exchange_bindings', // RabbitMQ Extension
            'passive',
            'durable',
            'name',
            'type',
            // factory configs
            'connection',
            'auto_setup_fabric',
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

    /**
     * @param ContainerInterface $container
     * @param string $connectionName
     * @return Connection
     */
    private function fetchConnection(ContainerInterface $container, string $connectionName) : Connection
    {
        if (! $container->has($connectionName)) {
            throw new Exception\RuntimeException(sprintf(
                'Connection %s not registered in container',
                $connectionName
            ));
        }

        $connection = $container->get($connectionName);

        if (! $connection instanceof Connection) {
            throw new Exception\RuntimeException(sprintf(
                'Connection %s is not an instance of %s',
                $connectionName,
                Connection::class
            ));
        }

        return $connection;
    }
}
