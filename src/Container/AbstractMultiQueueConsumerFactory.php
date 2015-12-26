<?php

namespace Humus\Amqp\Container;

use AMQPChannel;
use Humus\Amqp\Exception;
use Humus\Amqp\MultiQueueConsumer;
use Interop\Container\ContainerInterface;

/**
 * Class AbstractConsumerFactory
 * @package Humus\Amqp\Container
 */
abstract class AbstractConsumerFactory extends AbstractFactory
{
    /**
     * @param ContainerInterface $container
     * @return MultiQueueConsumer
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $options = $this->options($config);

        $connectionFactory = new ConnectionFactory($options['connection']);
        $connection = $connectionFactory($container);

        $channel = new AMQPChannel($connection);
        $channel->qos($options['qos']['prefetch_size'], $options['qos']['prefetch_count']);

        $autoSetupFabric = $options['auto_setup_fabric'];
        $queues = [];

        foreach ($options['queues'] as $queue) {
            if ($autoSetupFabric) {
                // will create the exchange to declare it on the channel
                // the created exchange will not be used afterwards
                if (!isset($config[$this->vendorName()][$this->packageName()]['queue'][$options['queue']]['exchange'])) {
                    throw new Exception\InvalidArgumentException(
                        sprintf('The exchange name for provided queue "%s" was not found in configuration',
                            $options['queue']
                        )
                    );
                }

                $exchangeName = $config[$this->vendorName()][$this->packageName()]['queue'][$options['queue']]['exchange'];
                $exchangeFactory = new ExchangeFactory($channel, $exchangeName, $connectionName, true);
                $exchangeFactory($container);
            }

            $queueFactory = new QueueFactory($channel, $queue, $connectionName, $autoSetupFabric);
            $queues[] = $queueFactory($container);
        }

        if (! $container->has($options['delivery_callback'])) {
            throw new Exception\InvalidArgumentException(
                'The required callback ' . $options['delivery_callback'] . ' can not be found'
            );
        }

        $deliveryCallback = $container->get($options['callback']);

        if (isset($options['flush_callback'])) {
            if (! $container->has($options['flush_callback'])) {
                throw new Exception\InvalidArgumentException(
                    'The required callback ' . $options['flush_callback'] . ' can not be found'
                );
            }
            $flushCallback = $connection->get($options['flush_callback']);
        } else {
            $flushCallback = null;
        }

        if (isset($spec['error_callback'])) {
            if (!$container->has($options['error_callback'])) {
                throw new Exception\InvalidArgumentException(
                    'The required callback ' . $options['error_callback'] . ' can not be found'
                );
            }
            $errorCallback = $container->get($options['error_callback']);
        } else {
            $errorCallback = null;
        }

        return new MultiQueueConsumer(
            $queues,
            $options['idle_timeout'],
            $options['wait_timeout'],
            $deliveryCallback,
            $flushCallback,
            $errorCallback
        );
    }

    /**
     * @return string
     */
    public function componentName()
    {
        return 'multi_queue_consumer';
    }

    /**
     * @return array
     */
    public function mandatoryOptions()
    {
        return [
            'queues',
            'callback',
            'connection',
            'qos' => [
                'prefetchCount',
                'prefetchSize'
            ],
            'idle_timeout',
            'wait_timeout',
            'auto_setup_fabric'
        ];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'connection' => 'default',
            'qos' => [
                'prefetch_count' => 3,
                'prefetch_size' => 0,
            ],
            'idle_timeout' => 5.0,
            'wait_timeout' => 100000,
            'auto_setup_fabric' => false,
        ];
    }
}
