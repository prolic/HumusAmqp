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

use Humus\Amqp\Container\ExchangeFactory;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class ExchangeFactoryTest
 * @package HumusTest\Amqp\Container
 */
class ExchangeFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_exchange()
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
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');
        $exchange = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Exchange::class, $exchange);
    }

    /**
     * @test
     */
    public function it_creates_exchange_with_call_static()
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
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $exchangeName = 'my_exchange';
        $exchange = ExchangeFactory::$exchangeName($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Exchange::class, $exchange);
    }

    /**
     * @test
     */
    public function it_creates_exchange_with_call_static_and_given_channel()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $connection = new StreamConnection(['vhost' => '/humus-amqp-test']);

        $channel = $connection->newChannel();

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'exchange' => [
                        'my_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'test_exchange',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $exchangeName = 'my_exchange';
        $exchange = ExchangeFactory::$exchangeName($container->reveal(), $channel);

        $this->assertInstanceOf(\Humus\Amqp\Exchange::class, $exchange);
        $this->assertEquals($channel, $exchange->getChannel());
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Interop\Container\ContainerInterface');

        $exchangeName = 'my_exchange';
        ExchangeFactory::$exchangeName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_channel_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The second argument must be a type of Humus\Amqp\Channel or null');

        $container = $this->prophesize(ContainerInterface::class);

        $exchangeName = 'my_exchange';
        ExchangeFactory::$exchangeName($container->reveal(), 'invalid');
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
                            'name' => 'test_exchange',
                            'auto_setup_fabric' => true,
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');
        $exchange = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Exchange::class, $exchange);

        $exchange->delete();
    }

    /**
     * @test
     * @group my
     */
    public function it_auto_declares_exchange_and_binds_exchange_to_exchange()
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
                        'base_exchange_one' => [
                            'connection' => 'my_connection',
                            'name' => 'base_exchange_one',
                            'auto_setup_fabric' => false,
                        ],
                        'my_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'test_exchange',
                            'auto_setup_fabric' => true,
                            'exchange_bindings' => [
                                'base_exchange_one' => [
                                    'routing_key_one' => [
                                        'my first argument' => 'my first value',
                                    ],
                                    'routing_key_two' => [
                                        'my second argument' => 'my second value',
                                    ],
                                ],
                                'base_exchange_two' => [
                                    null => [],
                                    'routing_key_three' => [],
                                ],
                            ],
                        ],
                        'base_exchange_two' => [
                            'connection' => 'my_connection',
                            'name' => 'base_exchange_two',
                            'auto_setup_fabric' => true,
                        ],
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');
        $exchange = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Exchange::class, $exchange);

        $exchange->delete();
        $exchange->delete('base_exchange_one');
        $exchange->delete('base_exchange_two');
    }
}
