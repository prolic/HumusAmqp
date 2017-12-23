<?php
/**
 * Copyright (c) 2016-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
 *
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

declare(strict_types=1);

namespace Humus\Amqp\Container;

use Humus\Amqp\Channel;
use Humus\Amqp\Constants;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Psr\Container\ContainerInterface;

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
    public static function __callStatic(string $name, array $arguments): Queue
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        if (! isset($arguments[1])) {
            $arguments[1] = null;
        } elseif (! $arguments[1] instanceof Channel) {
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
    public function __invoke(ContainerInterface $container): Queue
    {
        $options = $this->options($container->get('config'), $this->queueName);

        if (null === $this->channel) {
            $connection = $container->get($options['connection']);
            $this->channel = $connection->newChannel();
        }

        $queue = $this->channel->newQueue();

        if ('' !== $options['name']) {
            $queue->setName($options['name']);
        }

        $queue->setFlags($this->getFlags($options));
        $queue->setArguments($options['arguments']);

        $exchanges = $options['exchanges'];

        if ($exchanges instanceof \Traversable) {
            $exchanges = iterator_to_array($exchanges);
        }

        if (! is_array($exchanges) || empty($exchanges)) {
            throw new Exception\InvalidArgumentException('Expected an array or traversable of exchanges');
        }

        /** @var Exchange[] $exchangeObjects */
        $exchangeObjects = [];

        if ($this->autoSetupFabric || $options['auto_setup_fabric']) {
            // auto setup fabric depended exchange
            if (isset($options['arguments']['x-dead-letter-exchange'])) {
                // auto setup fabric dead letter exchange
                $exchangeName = $options['arguments']['x-dead-letter-exchange'];
                ExchangeFactory::$exchangeName($container, $this->channel, true);
            }

            foreach ($exchanges as $exchange => $exchangeOptions) {
                $exchangeObjects[$exchange] = ExchangeFactory::$exchange($container, $this->channel, true);
            }
        } else {
            foreach ($exchanges as $exchange => $exchangeOptions) {
                $exchangeObjects[$exchange] = ExchangeFactory::$exchange($container, $this->channel, false);
            }
        }

        $queue->declareQueue();

        foreach ($exchanges as $exchange => $exchangeOptions) {
            $exchangeObject = $exchangeObjects[$exchange];
            $exchangeName = $exchangeObject->getName();
            if (empty($exchangeOptions)) {
                $this->bindQueue($queue, $exchangeName, [], []);
            } else {
                foreach ($exchangeOptions as $exchangeOption) {
                    $routingKeys = $exchangeOption['routing_keys'] ?? [];
                    $bindArguments = $exchangeOption['bind_arguments'] ?? [];
                    $this->bindQueue($queue, $exchangeName, $routingKeys, $bindArguments);
                }
            }
        }

        return $queue;
    }

    /**
     * @return array
     */
    public function dimensions(): array
    {
        return ['humus', 'amqp', 'queue'];
    }

    /**
     * @return array
     */
    public function defaultOptions(): array
    {
        return [
            'name' => '',
            'passive' => false,
            'durable' => true,
            'arguments' => [],
            'exclusive' => false,
            'auto_delete' => false,
            // factory configs
            'auto_setup_fabric' => false,
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions(): array
    {
        return [
            'connection',
            'exchanges',
        ];
    }

    /**
     * @param array|ArrayAccess
     * @return int
     */
    private function getFlags($options): int
    {
        $flags = 0;
        $flags |= $options['passive'] ? Constants::AMQP_PASSIVE : 0;
        $flags |= $options['durable'] ? Constants::AMQP_DURABLE : 0;
        $flags |= $options['exclusive'] ? Constants::AMQP_EXCLUSIVE : 0;
        $flags |= $options['auto_delete'] ? Constants::AMQP_AUTODELETE : 0;

        return $flags;
    }

    /**
     * @param Queue $queue
     * @param string $exchange
     * @param array $routingKeys
     * @param array $bindArguments
     */
    private function bindQueue(Queue $queue, string $exchange, array $routingKeys, array $bindArguments)
    {
        if (empty($routingKeys)) {
            $queue->bind($exchange, '', $bindArguments);
        } else {
            foreach ($routingKeys as $routingKey) {
                $queue->bind($exchange, $routingKey, $bindArguments);
            }
        }
    }
}
