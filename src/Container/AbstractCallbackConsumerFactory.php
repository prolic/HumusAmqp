<?php

namespace Humus\Amqp\Container;

use AMQPChannel;
use Humus\Amqp\Exception;
use Humus\Amqp\CallbackConsumer;
use Interop\Container\ContainerInterface;

/**
 * Class AbstractCallbackConsumerFactory
 * @package Humus\Amqp\Container
 */
abstract class AbstractCallbackConsumerFactory extends AbstractFactory
{
    /**
     * @param ContainerInterface $container
     * @return CallbackConsumer
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $options = $this->options($config);

        $connectionName = $options['connection'];
        $connectionFactory = new ConnectionFactory($connectionName);
        $connection = $connectionFactory($container);

        $channel = new AMQPChannel($connection);
        $channel->qos($options['qos']['prefetch_size'], $options['qos']['prefetch_count']);

        $autoSetupFabric = $options['auto_setup_fabric'];

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

        $queueFactory = new QueueFactory($channel, $options['queue'], $connectionName, $autoSetupFabric);
        $queue = $queueFactory($container);

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
            $flushCallback = $container->get($options['flush_callback']);
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

        return new CallbackConsumer(
            $queue,
            $options['idle_timeout'],
            $deliveryCallback,
            $flushCallback,
            $errorCallback,
            $options['consumer_tag']
        );
    }

    /**
     * @return string
     */
    public function componentName()
    {
        return 'callback_consumer';
    }

    /**
     * @return array
     */
    public function mandatoryOptions()
    {
        return [
            'queue',
            'delivery_callback',
            'connection',
            'qos' => [
                'prefetchCount',
                'prefetchSize'
            ],
            'idle_timeout',
            'auto_setup_fabric',
            'consumer_tag',
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
            'auto_setup_fabric' => false,
            'consumer_tag' => null,
        ];
    }
}
