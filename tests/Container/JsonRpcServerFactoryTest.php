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

use Humus\Amqp\Container\JsonRpcServerFactory;
use Humus\Amqp\Driver\Driver;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class JsonRpcServerFactoryTest
 * @package HumusTest\Amqp\Container
 */
class JsonRpcServerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_json_rpc_server()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'vhost' => '/humus-amqp-test',
                            'type' => 'stream',
                        ],
                    ],
                    'exchange' => [
                        'my_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'test_exchange',
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchange' => 'my_exchange',
                        ],
                    ],
                    'json_rpc_server' => [
                        'my_server' => [
                            'exchange' => 'my_exchange',
                            'queue' => 'my_queue',
                            'idle_timeout' => 1.5,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new JsonRpcServerFactory('my_server');
        $jsonRpcServer = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\JsonRpcServer::class, $jsonRpcServer);
    }

    /**
     * @test
     */
    public function it_creates_json_rpc_server_with_call_static_and_defined_logger()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'vhost' => '/humus-amqp-test',
                            'type' => 'stream',
                        ],
                    ],
                    'exchange' => [
                        'my_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'test_exchange',
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchange' => 'my_exchange',
                        ],
                    ],
                    'json_rpc_server' => [
                        'my_server' => [
                            'exchange' => 'my_exchange',
                            'queue' => 'my_queue',
                            'idle_timeout' => 1.5,
                            'logger' => 'my_logger',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();
        $container->get('my_logger')->willReturn($logger->reveal())->shouldBeCalled();

        $serverName = 'my_server';
        $jsonRpcServer = JsonRpcServerFactory::$serverName($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\JsonRpcServer::class, $jsonRpcServer);
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Interop\Container\ContainerInterface');

        $serverName = 'my_server';
        JsonRpcServerFactory::$serverName('invalid');
    }
}
