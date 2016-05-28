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

namespace HumusTest\Amqp\Container;

use Humus\Amqp\Container\ConnectionFactory;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Driver\PhpAmqpLib\LazyConnection;
use Humus\Amqp\Driver\PhpAmqpLib\SocketConnection;
use Humus\Amqp\Driver\PhpAmqpLib\SslConnection;
use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class ConnectionFactoryTest
 * @package HumusTest\Amqp\Container
 */
class ConnectionFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_amqp_extension_connection()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::AMQP_EXTENSION())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $connection = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Driver\AmqpExtension\Connection::class, $connection);
    }

    /**
     * @test
     */
    public function it_creates_amqp_extension_connection_with_call_static()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::AMQP_EXTENSION())->shouldBeCalled();

        $connectionName = 'my_connection';
        $connection = ConnectionFactory::$connectionName($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Driver\AmqpExtension\Connection::class, $connection);
    }

    /**
     * @test
     */
    public function it_creates_php_amqplib_lazy_connection()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'type' => 'lazy'
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $connection = $factory($container->reveal());

        $this->assertInstanceOf(LazyConnection::class, $connection);
    }

    /**
     * @test
     */
    public function it_creates_php_amqplib_socket_connection()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'type' => 'socket'
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $connection = $factory($container->reveal());

        $this->assertInstanceOf(SocketConnection::class, $connection);
    }

    /**
     * @test
     */
    public function it_creates_php_amqplib_stream_connection()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'type' => 'stream'
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $connection = $factory($container->reveal());

        $this->assertInstanceOf(StreamConnection::class, $connection);
    }

    /**
     * @test
     * @group ssl
     */
    public function it_creates_php_amqplib_ssl_connection()
    {
        if (true) {
            $this->markTestSkipped('SSL Connection tests have to be run manually');
        }

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'type' => 'ssl',
                            'vhost' => '/humus-amqp-test',
                            'port' => 5671,
                            'cacert' => __DIR__ . '/../test_certs/cacert.pem',
                            'cert' => __DIR__ . '/../test_certs/cert.pem',
                            'key' => __DIR__ . '/../test_certs/key.pem',
                            'verify' => false,
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $connection = $factory($container->reveal());

        $this->assertInstanceOf(SslConnection::class, $connection);
    }

    /**
     * @test
     */
    public function it_throws_exception_during_php_amqplib_connection_creation_when_type_is_missing()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('For php-amqplib driver a connection type is required');

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $factory($container->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_during_php_amqplib_connection_creation_when_invalid_type_given()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid connection type for php-amqplib driver given');

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'type' => 'invalid'
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $factory($container->reveal());
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Interop\Container\ContainerInterface');

        $connectionName = 'my_connection';
        ConnectionFactory::$connectionName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_driver_registered()
    {
        $this->expectException(\Humus\Amqp\Exception\RuntimeException::class);
        $this->expectExceptionMessage('No driver factory registered in container');

        $container = $this->prophesize(ContainerInterface::class);

        $container->has(Driver::class)->willReturn(false)->shouldBeCalled();

        $factory = new ConnectionFactory('my_connection');
        $factory($container->reveal());
    }
}
