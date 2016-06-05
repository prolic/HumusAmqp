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

use Humus\Amqp\Container\QueueFactory;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use Humus\Amqp\Exception;
use Humus\Amqp\Queue;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;

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
                            'exchange' => 'test_exchange',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

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
                            'exchange' => 'test_exchange',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

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

        $connection = new StreamConnection(['vhost' => '/humus-amqp-test']);

        $channel = $connection->newChannel();

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'queue' => [
                        'my_queue' => [
                            'connection' => 'my_connection',
                            'name' => 'test_queue',
                            'exchange' => 'test_exchange',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $queueName = 'my_queue';
        $queue = QueueFactory::$queueName($container->reveal(), $channel);

        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertEquals($channel, $queue->getChannel());
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Interop\Container\ContainerInterface');

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
                            'exchange' => 'my_exchange',
                            'auto_setup_fabric' => true,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');
        $queue = $factory($container->reveal());

        /* @var Queue $queue */

        $this->assertInstanceOf(Queue::class, $queue);

        $queue->delete();

        $exchange = $queue->getChannel()->newExchange();
        $exchange->delete('my_exchange');
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
                            'exchange' => 'my_exchange',
                            'auto_setup_fabric' => true,
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
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');
        $queue = $factory($container->reveal());

        /* @var Queue $queue */

        $this->assertInstanceOf(Queue::class, $queue);

        $queue->delete();

        $exchange = $queue->getChannel()->newExchange();
        $exchange->delete('my_exchange');
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
                            'exchange' => 'my_exchange',
                            'arguments' => [
                                'x-dead-letter-exchange' => 'error_exchange'
                            ],
                            'auto_setup_fabric' => true,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new QueueFactory('my_queue');
        $queue = $factory($container->reveal());

        $this->assertInstanceOf(Queue::class, $queue);

        /* @var Queue $queue */

        $queue->delete();

        $exchange = $queue->getChannel()->newExchange();
        $exchange->delete('my_exchange');
        $exchange->delete('error_exchange');
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
