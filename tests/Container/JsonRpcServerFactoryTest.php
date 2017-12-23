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

namespace HumusTest\Amqp\Container;

use Humus\Amqp\Channel;
use Humus\Amqp\Connection;
use Humus\Amqp\Constants;
use Humus\Amqp\Container\JsonRpcServerFactory;
use Humus\Amqp\Exchange;
use Humus\Amqp\JsonRpc\JsonRpcServer;
use Humus\Amqp\Queue;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
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
                            'exchanges' => [
                                'my_exchange' => [],
                            ],
                        ],
                    ],
                    'json_rpc_server' => [
                        'my_server' => [
                            'exchange' => 'my_exchange',
                            'delivery_callback' => 'my_callback',
                            'queue' => 'my_queue',
                            'idle_timeout' => 1.5,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setFlags(Constants::AMQP_DURABLE)->shouldBeCalled();
        $exchange->getName()->willReturn('test_exchange')->shouldBeCalled();

        $channel2 = $this->prophesize(Channel::class);
        $channel2->newExchange()->willReturn($exchange->reveal());

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();
        $queue->bind('test_exchange', '', [])->shouldBeCalled();
        $queue->getChannel()->willReturn($channel2->reveal())->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal());
        $channel->newExchange()->willReturn($exchange->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal());

        $container->get('my_connection')->willReturn($connection->reveal());
        $container->get('my_callback')->willReturn(function () {
        });

        $factory = new JsonRpcServerFactory('my_server');
        $jsonRpcServer = $factory($container->reveal());

        $this->assertInstanceOf(JsonRpcServer::class, $jsonRpcServer);
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
                            'exchanges' => [
                                'my_exchange' => [],
                            ],
                        ],
                    ],
                    'json_rpc_server' => [
                        'my_server' => [
                            'exchange' => 'my_exchange',
                            'delivery_callback' => 'my_callback',
                            'queue' => 'my_queue',
                            'idle_timeout' => 1.5,
                            'logger' => 'my_logger',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setFlags(Constants::AMQP_DURABLE)->shouldBeCalled();
        $exchange->getName()->willReturn('test_exchange')->shouldBeCalled();

        $channel2 = $this->prophesize(Channel::class);
        $channel2->newExchange()->willReturn($exchange->reveal());

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();
        $queue->bind('test_exchange', '', [])->shouldBeCalled();
        $queue->getChannel()->willReturn($channel2->reveal())->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal());
        $channel->newExchange()->willReturn($exchange->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal());

        $container->get('my_connection')->willReturn($connection->reveal());
        $container->get('my_callback')->willReturn(function () {
        });
        $container->get('my_logger')->willReturn($logger->reveal())->shouldBeCalled();

        $serverName = 'my_server';
        $jsonRpcServer = JsonRpcServerFactory::$serverName($container->reveal());

        $this->assertInstanceOf(JsonRpcServer::class, $jsonRpcServer);
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Psr\Container\ContainerInterface');

        $serverName = 'my_server';
        JsonRpcServerFactory::$serverName('invalid');
    }
}
