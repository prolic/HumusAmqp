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

use Humus\Amqp\Connection;
use Humus\Amqp\Container\ExchangeFactory;
use Humus\Amqp\Channel;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
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

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange = $exchange->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange)->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');

        $this->assertSame($exchange, $factory($container->reveal()));
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

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange = $exchange->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange)->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $exchangeName = 'my_exchange';

        $this->assertSame($exchange, ExchangeFactory::$exchangeName($container->reveal()));
    }

    /**
     * @test
     */
    public function it_creates_exchange_with_call_static_and_given_channel()
    {
        $container = $this->prophesize(ContainerInterface::class);

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

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange = $exchange->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange)->shouldBeCalled();

        $exchangeName = 'my_exchange';

        $this->assertSame($exchange, ExchangeFactory::$exchangeName($container->reveal(), $channel->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Interop\Container\ContainerInterface');

        $exchangeName = 'my_exchange';
        ExchangeFactory::$exchangeName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_channel_param()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
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

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange->declareExchange()->shouldBeCalled();
        $exchange = $exchange->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange)->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');

        $this->assertSame($exchange, $factory($container->reveal()));
    }

    /**
     * @test
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
                                    '' => [],
                                    'routing_key_three' => [],
                                ],
                                'base_exchange_three' => [],
                            ],
                        ],
                        'base_exchange_two' => [
                            'connection' => 'my_connection',
                            'name' => 'base_exchange_two',
                            'auto_setup_fabric' => true,
                        ],
                        'base_exchange_three' => [
                            'connection' => 'my_connection',
                            'name' => 'base_exchange_three',
                        ],
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $baseExchangeOne = $this->prophesize(Exchange::class);
        $baseExchangeOne->setType('direct')->shouldBeCalled();
        $baseExchangeOne->setName('base_exchange_one')->shouldBeCalled();
        $baseExchangeOne->setFlags(2)->shouldBeCalled();
        $baseExchangeOne->setArguments([])->shouldBeCalled();
        $baseExchangeOne->declareExchange()->shouldBeCalled();
        $baseExchangeOne = $baseExchangeOne->reveal();

        $baseExchangeTwo = $this->prophesize(Exchange::class);
        $baseExchangeTwo->setType('direct')->shouldBeCalled();
        $baseExchangeTwo->setName('base_exchange_two')->shouldBeCalled();
        $baseExchangeTwo->setFlags(2)->shouldBeCalled();
        $baseExchangeTwo->setArguments([])->shouldBeCalled();
        $baseExchangeTwo->declareExchange()->shouldBeCalled();
        $baseExchangeTwo = $baseExchangeTwo->reveal();

        $baseExchangeThree = $this->prophesize(Exchange::class);
        $baseExchangeThree->setType('direct')->shouldBeCalled();
        $baseExchangeThree->setName('base_exchange_three')->shouldBeCalled();
        $baseExchangeThree->setFlags(2)->shouldBeCalled();
        $baseExchangeThree->setArguments([])->shouldBeCalled();
        $baseExchangeThree->declareExchange()->shouldBeCalled();
        $baseExchangeThree = $baseExchangeThree->reveal();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments([])->shouldBeCalled();
        $exchange->declareExchange()->shouldBeCalled();
        $exchange->bind('base_exchange_one', 'routing_key_one', ['my first argument' => 'my first value'])->shouldBeCalled();
        $exchange->bind('base_exchange_one', 'routing_key_two', ['my second argument' => 'my second value'])->shouldBeCalled();
        $exchange->bind('base_exchange_two', '', [])->shouldBeCalled();
        $exchange->bind('base_exchange_two', 'routing_key_three', [])->shouldBeCalled();
        $exchange->bind('base_exchange_three')->shouldBeCalled();

        $exchange = $exchange->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange, $baseExchangeOne, $baseExchangeTwo, $baseExchangeThree)->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');

        $this->assertSame($exchange, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_auto_declares_alternate_exchange()
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
                        'alternate-exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'alternate-exchange',
                            'auto_setup_fabric' => false,
                        ],
                        'my_exchange' => [
                            'connection' => 'my_connection',
                            'name' => 'test_exchange',
                            'auto_setup_fabric' => true,
                            'arguments' => [
                                'alternate-exchange' => 'alternate-exchange',
                            ],
                        ],
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $alternateExchange = $this->prophesize(Exchange::class);
        $alternateExchange->setType('direct')->shouldBeCalled();
        $alternateExchange->setName('alternate-exchange')->shouldBeCalled();
        $alternateExchange->setFlags(2)->shouldBeCalled();
        $alternateExchange->setArguments([])->shouldBeCalled();
        $alternateExchange->declareExchange()->shouldBeCalled();
        $alternateExchange = $alternateExchange->reveal();

        $exchange = $this->prophesize(Exchange::class);
        $exchange->setType('direct')->shouldBeCalled();
        $exchange->setName('test_exchange')->shouldBeCalled();
        $exchange->setFlags(2)->shouldBeCalled();
        $exchange->setArguments(['alternate-exchange' => 'alternate-exchange'])->shouldBeCalled();
        $exchange->declareExchange()->shouldBeCalled();

        $exchange = $exchange->reveal();

        $channel = $this->prophesize(Channel::class);
        $channel->newExchange()->willReturn($exchange, $alternateExchange)->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('my_connection')->willReturn($connection->reveal())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');

        $this->assertSame($exchange, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_bool_given_to_call_static_as_third_parameter()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The third argument must be a boolean');

        $container = $this->prophesize(ContainerInterface::class);

        $exchangeName = 'test-exchange';
        ExchangeFactory::$exchangeName($container->reveal(), null, 'invalid-param');
    }
}
