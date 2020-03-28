<?php
/**
 * Copyright (c) 2016-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

use Humus\Amqp\CallbackConsumer;
use Humus\Amqp\Channel;
use Humus\Amqp\Connection;
use Humus\Amqp\Container\CallbackConsumerFactory;
use Humus\Amqp\Queue;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CallbackConsumerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_callback_consumer(): void
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
                    'callback_consumer' => [
                        'my_consumer' => [
                            'queue' => 'my_queue',
                            'idle_timeout' => 1.5,
                            'delivery_callback' => 'my_callback',
                            'qos' => [
                                'prefetch_count' => 100,
                            ],
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
        $queue->getName()->willReturn('my_queue')->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();
        $container->get('my_callback')->willReturn(function (): void {
        })->shouldBeCalled();

        $factory = new CallbackConsumerFactory('my_consumer');
        $callbackConsumer = $factory($container->reveal());

        $this->assertInstanceOf(CallbackConsumer::class, $callbackConsumer);
    }

    /**
     * @test
     */
    public function it_creates_callback_consumer_with_call_static_and_defined_logger_delivery_and_flush_callback(): void
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
                    'callback_consumer' => [
                        'my_consumer' => [
                            'queue' => 'my_queue',
                            'idle_timeout' => 1.5,
                            'delivery_callback' => 'my_callback',
                            'flush_callback' => 'my_flush',
                            'error_callback' => 'my_error',
                            'logger' => 'my_logger',
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
        $queue->getName()->willReturn('my_queue')->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();
        $container->get('my_callback')->willReturn(function (): void {
        })->shouldBeCalled();
        $container->get('my_flush')->willReturn(function (): void {
        })->shouldBeCalled();
        $container->get('my_error')->willReturn(function (): void {
        })->shouldBeCalled();
        $container->get('my_logger')->willReturn($logger->reveal())->shouldBeCalled();

        $consumerName = 'my_consumer';
        $consumer = CallbackConsumerFactory::$consumerName($container->reveal());

        $this->assertInstanceOf(CallbackConsumer::class, $consumer);
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param(): void
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Psr\Container\ContainerInterface');

        $consumerName = 'my_consumer';
        CallbackConsumerFactory::$consumerName('invalid');
    }
}
