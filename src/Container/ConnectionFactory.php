<?php

namespace Humus\Amqp\Container;

use AMQPConnection;
use Interop\Container\ContainerInterface;

/**
 * Class ConnectionFactory
 * @package Humus\Amqp\Container
 */
final class ConnectionFactory extends AbstractFactory
{
    /**
     * @var string
     */
    private $connectionName;

    /**
     * ConnectionFactory constructor.
     * @param string $connectionName
     */
    public function __construct($connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @return string
     */
    public function elementName()
    {
        return $this->connectionName;
    }

    /**
     * @param ContainerInterface $container
     * @return AMQPConnection
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $options = $this->options($config);

        $connection = new AMQPConnection($options);

        if (true === $options['persistent']) {
            $connection->pconnect();
        } else {
            $connection->connect();
        }

        return $connection;
    }

    /**
     * @return string
     */
    public function componentName()
    {
        return 'connection';
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'host' => 'localhost',
            'port' => 5672,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'persistent' => true,
            'read_timeout' => 1, //sec, float allowed
            'write_timeout' => 1, //sec, float allowed
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions()
    {
        return [
            'host',
            'port',
            'login',
            'password',
            'vhost',
            'persistent',
            'read_timeout',
            'write_timeout',
        ];
    }
}
