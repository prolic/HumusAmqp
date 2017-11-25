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
use Humus\Amqp\Container\QueueFactory;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;

/**
 * Class QueueFactoryTest
 * @package HumusTest\Amqp\Container
 */
class QueueFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_queue()
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
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'test_queue',
                            'exchanges' => [
                                'test_exchange' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->setName('test_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');
        $queue = $factory($container->reveal());

        $this->assertInstanceOf(Queue::class, $queue);
    }

    /**
     * @test
     */
    public function it_creates_queue_with_call_static()
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
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'test_queue',
                            'exchanges' => [
                                'test_exchange' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->setName('test_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $queueName = 'my_queue';
        $queue = QueueFactory::$queueName($container->reveal());

        $this->assertInstanceOf(Queue::class, $queue);
    }

    /**
     * @test
     */
    public function it_creates_queue_with_call_static_and_given_channel()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $queue = $this->prophesize(Queue::class);
        $queue->setName('test_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue = $queue->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue)->shouldBeCalled();

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'test_queue',
                            'exchanges' => [
                                'test_exchange' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $queueName = 'my_queue';

        $this->assertSame($queue, QueueFactory::$queueName($container->reveal(), $channel->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Psr\Container\ContainerInterface');

        $queueName = 'my_queue';
        QueueFactory::$queueName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_channel_param()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The second argument must be a type of Humus\Amqp\Channel or null');

        $container = $this->prophesize(ContainerInterface::class);

        $queueName = 'my_queue';
        QueueFactory::$queueName($container->reveal(), 'invalid');
    }

    /**
     * @test
     */
    public function it_auto_declares_exchange()
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
                            'name' => 'my_exchange',
                            'auto_setup_fabric' => true,
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchanges' => [
                                'my_exchange' => [],
                            ],
                            'auto_setup_fabric' => true,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setName(Argument::any())->shouldBeCalled();
        $exchange->setFlags(Argument::any())->shouldBeCalled();
        $exchange->setType(Argument::any())->shouldBeCalled();
        $exchange->setArguments(Argument::any())->shouldBeCalled();
        $exchange->getName()->willReturn('my_exchange');
        $exchange->declareExchange()->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();
        $queue->bind('my_exchange', '', [])->shouldBeCalled();
        $queue = $queue->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue)->shouldBeCalled();
        $channel->newExchange()->willReturn($exchange->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');

        $this->assertSame($queue, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_auto_declares_exchange_as_iterator()
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
                            'name' => 'my_exchange',
                            'auto_setup_fabric' => true,
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchanges' => new \ArrayIterator([
                                'my_exchange' => [],
                            ]),
                            'auto_setup_fabric' => true,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setName(Argument::any())->shouldBeCalled();
        $exchange->setFlags(Argument::any())->shouldBeCalled();
        $exchange->setType(Argument::any())->shouldBeCalled();
        $exchange->setArguments(Argument::any())->shouldBeCalled();
        $exchange->getName()->willReturn('my_exchange');
        $exchange->declareExchange()->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();
        $queue->bind('my_exchange', '', [])->shouldBeCalled();
        $queue = $queue->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue)->shouldBeCalled();
        $channel->newExchange()->willReturn($exchange->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');

        $this->assertSame($queue, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_during_auto_declare_with_empty_exchanges()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array or traversable of exchange');

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
                            'name' => 'my_exchange',
                            'auto_setup_fabric' => true,
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchanges' => [],
                            'auto_setup_fabric' => true,
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

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');
        $factory($container->reveal());
    }

    /**
     * @test
     */
    public function it_auto_declares_exchange_with_routing_key_and_bind_arguments()
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
                            'name' => 'my_exchange',
                            'auto_setup_fabric' => true,
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchanges' => [
                                'my_exchange' => [
                                    [
                                        'routing_keys' => [
                                            '',
                                            'foo',
                                        ],
                                        'bind_arguments' => [
                                            'foo' => 'bar',
                                        ],
                                    ],
                                ],
                            ],
                            'auto_setup_fabric' => true,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setName(Argument::any())->shouldBeCalled();
        $exchange->setFlags(Argument::any())->shouldBeCalled();
        $exchange->setType(Argument::any())->shouldBeCalled();
        $exchange->setArguments(Argument::any())->shouldBeCalled();
        $exchange->getName()->willReturn('my_exchange');
        $exchange->declareExchange()->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();
        $queue->bind('my_exchange', '', ['foo' => 'bar'])->shouldBeCalled();
        $queue->bind('my_exchange', 'foo', ['foo' => 'bar'])->shouldBeCalled();
        $queue = $queue->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue)->shouldBeCalled();
        $channel->newExchange()->willReturn($exchange->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');

        $this->assertSame($queue, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_auto_declares_dead_letter_exchange()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'connection' => [
                        'my_connection' => [
                            'vhost' => '/humus-amqp-test',
                            'type' => 'socket',
                        ],
                    ],
                    'exchange' => [
                        'error_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'error_exchange',
                        ],
                        'my_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'my_exchange',
                        ],
                    ],
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'my_queue',
                            'exchanges' => [
                                'my_exchange' => [],
                            ],
                            'arguments' => [
                                'x-dead-letter-exchange' => 'error_exchange',
                            ],
                            'auto_setup_fabric' => true,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $deadLetterExchange = $this->prophesize(Exchange::class);
        $deadLetterExchange->setType('direct')->shouldBeCalled();
        $deadLetterExchange->setName('error_exchange')->shouldBeCalled();
        $deadLetterExchange->setFlags(2)->shouldBeCalled();
        $deadLetterExchange->setArguments([])->shouldBeCalled();
        $deadLetterExchange->declareExchange()->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('my_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange->declareExchange()->shouldBeCalled();
        $exchange->getName()->willReturn('my_exchange');

        $queue = $this->prophesize(Queue::class);
        $queue->setName('my_queue')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments(['x-dead-letter-exchange' => 'error_exchange'])->shouldBeCalled();
        $queue->declareQueue()->shouldBeCalled();
        $queue->bind('my_exchange', '', [])->shouldBeCalled();
        $queue = $queue->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue)->shouldBeCalled();
        $channel->newExchange()->willReturn($deadLetterExchange->reveal(), $exchange->reveal())->shouldBeCalledTimes(2);

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');

        $this->assertSame($queue, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_bool_given_to_call_static_as_third_parameter()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The third argument must be a boolean');

        $container = $this->prophesize(ContainerInterface::class);

        $queueName = 'test-queue';
        QueueFactory::$queueName($container->reveal(), null, 'invalid-param');
    }
}
