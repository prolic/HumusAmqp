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
use Humus\Amqp\Connection;
use Humus\Amqp\Constants;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Exception;
use Humus\Amqp\Queue;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;

/**
 * Class QueueFactory
 * @package Humus\Amqp\Container
 */
final class QueueFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $queueName;

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
     *     'your_queue' => [QueueFactory::class, 'your_queue_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return Queue
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments) : Queue
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
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
     * @param string $queueName
     * @param Channel|null $channel
     */
    public function __construct(string $queueName, Channel $channel = null)
    {
        $this->queueName = $queueName;
        $this->channel = $channel;
    }

    /**
     * @param ContainerInterface $container
     * @return Queue
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container) : Queue
    {
        $options = $this->options($container->get('config'), $this->queueName);

        $connection = $this->fetchConnection($container, $options['connection']);
        $channel = $this->channel ? $this->channel : $connection->newChannel();

        switch ($container->get(Driver::class)) {
            case Driver::AMQP_EXTENSION():
                $queue = new \Humus\Amqp\Driver\AmqpExtension\Queue($channel);
                break;
            case Driver::PHP_AMQP_LIB():
                $queue = new \Humus\Amqp\Driver\PhpAmqpLib\Queue($channel);
                break;
            default:
                throw new Exception\RuntimeException('Unknown driver');
        }

        if (null !== $options['name']) {
            $queue->setName($options['name']);
        }

        $queue->setFlags($this->getFlags($options));
        $queue->setArguments($options['arguments']);

        if ($options['auto_setup_fabric']) {
            $queue->declareQueue();

            $routingKeys = $options['routing_keys'];
            if (empty($routingKeys)) {
                $queue->bind($options['exchange'], null, $options['bind_arguments']);
            } else {
                foreach ($routingKeys as $routingKey) {
                    $queue->bind($options['exchange'], $routingKey, $options['bind_arguments']);
                }
            }
        }

        return $queue;
    }

    /**
     * @return array
     */
    public function dimensions()
    {
        return ['humus', 'amqp', 'queue'];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'name' => null,
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
            'arguments' => [],
            'routing_keys' => [],
            'bind_arguments' => [],
            // factory configs
            'auto_setup_fabric' => false
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions()
    {
        return [
            'connection',
            'exchange',
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
        $flags |= $options['exclusive'] ? Constants::AMQP_EXCLUSIVE : 0;
        $flags |= $options['auto_delete'] ? Constants::AMQP_AUTODELETE : 0;

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
