<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
use Humus\Amqp\Container\ProducerFactory;
use Humus\Amqp\Exchange;
use Humus\Amqp\JsonProducer;
use Humus\Amqp\PlainProducer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_plain_producer(): void
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
                    'producer' => [
                        'my_producer' => [
                            'exchange' => 'my_exchange',
                            'type' => PlainProducer::class,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ProducerFactory('my_producer');
        $producer = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\PlainProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_creates_json_producer(): void
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
                    'producer' => [
                        'my_producer' => [
                            'exchange' => 'my_exchange',
                            'type' => JsonProducer::class,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ProducerFactory('my_producer');
        $producer = $factory($container->reveal());

        $this->assertInstanceOf(JsonProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_creates_producer_with_call_static(): void
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
                    'producer' => [
                        'my_producer' => [
                            'exchange' => 'my_exchange',
                            'type' => JsonProducer::class,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $producerName = 'my_producer';
        $producer = ProducerFactory::$producerName($container->reveal());

        $this->assertInstanceOf(JsonProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param(): void
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Psr\Container\ContainerInterface');

        $producerName = 'my_producer';
        ProducerFactory::$producerName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_producer_type_given(): void
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown producer type invalid requested');

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
                    'producer' => [
                        'my_producer' => [
                            'exchange' => 'my_exchange',
                            'type' => 'invalid',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $exchange = $this->prophesize(Exchange::class);

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ProducerFactory('my_producer');
        $factory($container->reveal());
    }
}
