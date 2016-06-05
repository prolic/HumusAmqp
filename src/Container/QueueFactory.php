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
     * @var bool
     */
    private $autoSetupFabric;

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

        if (! isset($arguments[2])) {
            $arguments[2] = false;
        }

        if (! is_bool($arguments[2])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The third argument must be a boolean')
            );
        }

        return (new static($name, $arguments[1], $arguments[2]))->__invoke($arguments[0]);
    }

    /**
     * QueueFactory constructor.
     * @param string $queueName
     * @param Channel|null $channel
     * @param bool $autoSetupFabric
     */
    public function __construct(string $queueName, Channel $channel = null, bool $autoSetupFabric = false)
    {
        $this->queueName = $queueName;
        $this->channel = $channel;
        $this->autoSetupFabric = $autoSetupFabric;
    }

    /**
     * @param ContainerInterface $container
     * @return Queue
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container) : Queue
    {
        $options = $this->options($container->get('config'), $this->queueName);

        if (null === $this->channel) {
            $connectionName = $options['connection'];
            $connection = ConnectionFactory::$connectionName($container);
            $this->channel = $connection->newChannel();
        }

        $queue = $this->channel->newQueue();

        if ('' !== $options['name']) {
            $queue->setName($options['name']);
        }

        $queue->setFlags($this->getFlags($options));
        $queue->setArguments($options['arguments']);

        if ($this->autoSetupFabric || $options['auto_setup_fabric']) {
            // auto setup fabric depended exchange
            $exchangeName = $options['exchange'];
            ExchangeFactory::$exchangeName($container, $this->channel, true);

            if (isset($options['arguments']['x-dead-letter-exchange'])) {
                // auto setup fabric dead letter exchange
                $exchangeName = $options['arguments']['x-dead-letter-exchange'];
                ExchangeFactory::$exchangeName($container, $this->channel, true);
            }

            $queue->declareQueue();

            $routingKeys = $options['routing_keys'];
            if (empty($routingKeys)) {
                $queue->bind($options['exchange'], '', $options['bind_arguments']);
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
            'name' => '',
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
}
