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
use Humus\Amqp\Driver\AmqpExtension\Connection;
use Humus\Amqp\Driver\Driver;
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
    public function it_creates_amqp_extension_exchange()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $container = $this->prophesize(ContainerInterface::class);

        $connection = new Connection(['vhost' => '/humus-amqp-test']);
        $connection->connect();

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'exchange' => [
                        'my_exchange' => [
                            'connection' => 'my_connection',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::AMQP_EXTENSION())->shouldBeCalled();

        $factory = new ExchangeFactory('my_exchange');
        $exchange = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Driver\AmqpExtension\Exchange::class, $exchange);
    }

    /**
     * @test
     */
    public function it_creates_amqp_extension_exchange_with_call_static()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $container = $this->prophesize(ContainerInterface::class);

        $connection = new Connection(['vhost' => '/humus-amqp-test']);
        $connection->connect();

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'exchange' => [
                        'my_exchange' => [
                            'connection' => 'my_connection',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::AMQP_EXTENSION())->shouldBeCalled();

        $exchangeName = 'my_exchange';
        $exchange = ExchangeFactory::$exchangeName($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\Driver\AmqpExtension\Exchange::class, $exchange);
    }

    /**
     * @test
     */
    public function it_creates_amqp_extension_exchange_with_call_static_and_given_channel()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $container = $this->prophesize(ContainerInterface::class);

        $connection = new Connection(['vhost' => '/humus-amqp-test']);
        $connection->connect();

        $channel = $connection->newChannel();

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'exchange' => [
                        'my_exchange' => [
                            'connection' => 'my_connection',
                        ]
                    ]
                ]
            ]
        ])->shouldBeCalled();

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::AMQP_EXTENSION())->shouldBeCalled();

        $exchangeName = 'my_exchange';
        $exchange = ExchangeFactory::$exchangeName($container->reveal(), $channel);

        $this->assertInstanceOf(\Humus\Amqp\Driver\AmqpExtension\Exchange::class, $exchange);
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
}
