<?php
/**
 * Copyright (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

use Humus\Amqp\Connection;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Driver\PhpAmqpLib\LazyConnection;
use Humus\Amqp\Driver\PhpAmqpLib\LazySocketConnection;
use Humus\Amqp\Driver\PhpAmqpLib\SocketConnection;
use Humus\Amqp\Driver\PhpAmqpLib\SslConnection;
use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use Humus\Amqp\Exception;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Psr\Container\ContainerInterface;

/**
 * Class ConnectionFactory
 * @package Humus\Amqp\Container
 */
final class ConnectionFactory implements ProvidesDefaultOptions, RequiresConfigId
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_connection' => [ConnectionFactory::class, 'your_connection_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return Connection
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): Connection
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($name))->__invoke($arguments[0]);
    }

    /**
     * ConnectionFactory constructor.
     * @param string $connectionName
     */
    public function __construct(string $connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @param ContainerInterface $container
     * @return Connection
     */
    public function __invoke(ContainerInterface $container): Connection
    {
        if (! $container->has(Driver::class)) {
            throw new Exception\RuntimeException('No driver factory registered in container');
        }

        $options = $this->options($container->get('config'), $this->connectionName);

        switch ($container->get(Driver::class)) {
            case Driver::AMQP_EXTENSION():
                $connection = new \Humus\Amqp\Driver\AmqpExtension\Connection($options);
                $connection->connect();
                break;
            case Driver::PHP_AMQP_LIB():
            default:
                if (! isset($options['type'])) {
                    throw new Exception\InvalidArgumentException(
                        'For php-amqplib driver a connection type is required'
                    );
                }
                $type = $options['type'];
                unset($options['type']);
                switch ($type) {
                    case 'lazy':
                    case LazyConnection::class:
                        $connection = new LazyConnection($options);
                        break;
                    case 'lazy_socket':
                    case LazySocketConnection::class:
                        $connection = new LazySocketConnection($options);
                        break;
                    case 'socket':
                    case SocketConnection::class:
                        $connection = new SocketConnection($options);
                        break;
                    case 'ssl':
                    case SslConnection::class:
                        return new SslConnection($options);
                    case 'stream':
                    case StreamConnection::class:
                        $connection = new StreamConnection($options);
                        break;
                    default:
                        throw new Exception\InvalidArgumentException(
                            'Invalid connection type for php-amqplib driver given'
                        );
                }
                break;
        }

        return $connection;
    }

    /**
     * @return array
     */
    public function dimensions(): array
    {
        return ['humus', 'amqp', 'connection'];
    }

    /**
     * @return array
     */
    public function defaultOptions(): array
    {
        return [
            'host' => 'localhost',
            'port' => 5672,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'persistent' => false,
            'read_timeout' => 1, //sec, float allowed
            'write_timeout' => 1, //sec, float allowed,
            'heartbeat' => 0,
        ];
    }
}
