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

use Humus\Amqp\Channel;
use Humus\Amqp\Constants;
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
     * @var Channel|null
     */
    private $channel;

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
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        if (! isset($arguments[1])) {
            $arguments[1] = null;
        } elseif (!$arguments[1] instanceof Channel) {
            throw new Exception\InvalidArgumentException(
                sprintf('The second argument must be a type of %s or null', Channel::class)
            );
        }

        return (new static($name, $arguments[1]))->__invoke($arguments[0]);
    }

    /**
     * QueueFactory constructor.
     * @param string $exchangeName
     * @param Channel|null $channel
     */
    public function __construct(string $exchangeName, Channel $channel = null)
    {
        $this->exchangeName = $exchangeName;
        $this->channel = $channel;
    }

    /**
     * @param ContainerInterface $container
     * @return Exchange
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container) : Exchange
    {
        $config = $container->get('config');
        $options = $this->options($config, $this->exchangeName);

        if (null === $this->channel) {
            $connectionName = $options['connection'];
            $connection = ConnectionFactory::$connectionName($container);
            $this->channel = $connection->newChannel();
        }

        $exchange = $this->channel->newExchange();

        $exchange->setArguments($options['arguments']);
        $exchange->setName($options['name']);
        $exchange->setFlags($this->getFlags($options));
        $exchange->setType($options['type']);

        if ($options['auto_setup_fabric']) {
            $exchange->declareExchange();

            // rabbitmq extension: exchange to exchange bindings
            foreach ($options['exchange_bindings'] as $exchangeName => $config) {
                $this->bindExchange($container, $exchange, $exchangeName, $config);
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
            'connection',
            'name',
        ];
    }

    /**
     * @param array|ArrayAccess
     * @return int
     */
    private function getFlags($options)
    {
        $flags = 0;
        $flags |= $options['passive'] ? Constants::AMQP_PASSIVE : 0;
        $flags |= $options['durable'] ? Constants::AMQP_DURABLE : 0;
        $flags |= $options['auto_delete'] ? Constants::AMQP_AUTODELETE : 0; // RabbitMQ Extension

        return $flags;
    }

    /**
     * @param ContainerInterface $container
     * @param Exchange $exchange
     * @param string $exchangeName
     * @param array $config
     */
    private function bindExchange(ContainerInterface $container, Exchange $exchange, string $exchangeName, array $config)
    {
        $factory = new self($exchangeName, $this->channel);
        $otherExchange = $factory($container);
        $otherExchange->declareExchange();
        foreach ($config as $routingKey => $bindOptions) {
            $exchange->bind($exchangeName, $routingKey, $bindOptions);
        }
    }
}
