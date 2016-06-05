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

use Humus\Amqp\Container\ProducerFactory;
use Humus\Amqp\Driver\Driver;
use Humus\Amqp\JsonProducer;
use Humus\Amqp\PlainProducer;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class ProducerFactoryTest
 * @package HumusTest\Amqp\Container
 */
class ProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_plain_producer()
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

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ProducerFactory('my_producer');
        $producer = $factory($container->reveal());

        $this->assertInstanceOf(\Humus\Amqp\PlainProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_creates_json_producer()
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
                            'type' => JsonProducer::class
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ProducerFactory('my_producer');
        $producer = $factory($container->reveal());

        $this->assertInstanceOf(JsonProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_creates_producer_with_call_static()
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
                            'type' => JsonProducer::class
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $producerName = 'my_producer';
        $producer = ProducerFactory::$producerName($container->reveal());

        $this->assertInstanceOf(JsonProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_call_static_container_param()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type Interop\Container\ContainerInterface');

        $producerName = 'my_producer';
        ProducerFactory::$producerName('invalid');
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_producer_type_given()
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
                            'type' => 'invalid'
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $factory = new ProducerFactory('my_producer');
        $factory($container->reveal());
    }
}
