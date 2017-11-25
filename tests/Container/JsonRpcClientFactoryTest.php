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
use Humus\Amqp\Container\JsonRpcClientFactory;
use Humus\Amqp\Exchange;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\Queue;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class JsonRpcClientFactoryTest
 * @package HumusTest\Amqp\Container
 */
class JsonRpcClientFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_json_rpc_client()
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
                    'json_rpc_client' => [
                        'my_client' => [
                            'exchanges' => [
                                'my_exchange',
                            ],
                            'queue' => 'my_queue',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $channel2 = $this->prophesize(Channel::class);
        $channel2->newExchange()->willReturn($exchange->reveal());

        $queue->getChannel()->willReturn($channel2->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new JsonRpcClientFactory('my_client');
        $jsonRpcClient = $factory($container->reveal());

        $this->assertInstanceOf(JsonRpcClient::class, $jsonRpcClient);
    }

    /**
     * @test
     */
    public function it_creates_json_rpc_client_with_call_static()
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
                    'json_rpc_client' => [
                        'my_client' => [
                            'exchanges' => new \ArrayIterator([
                                'my_exchange',
                            ]),
                            'queue' => 'my_queue',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $channel2 = $this->prophesize(Channel::class);
        $channel2->newExchange()->willReturn($exchange->reveal());

        $queue->getChannel()->willReturn($channel2->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $clientName = 'my_client';
        $jsonRpcClient = JsonRpcClientFactory::$clientName($container->reveal());

        $this->assertInstanceOf(JsonRpcClient::class, $jsonRpcClient);
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Psr\Container\ContainerInterface');

        $clientName = 'my_client';
        JsonRpcClientFactory::$clientName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_when_empty_exchanges_given()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "exchanges" must be a not empty array or an instance of Traversable');

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
                    'json_rpc_client' => [
                        'my_client' => [
                            'exchanges' => [],
                            'queue' => 'my_queue',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->getChannel()->shouldBeCalled();
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new JsonRpcClientFactory('my_client');
        $factory($container->reveal());
    }
}
