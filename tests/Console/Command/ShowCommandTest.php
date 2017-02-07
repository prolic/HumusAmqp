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

namespace HumusTest\Amqp\Console\Command;

use Humus\Amqp\Console\Command\ShowCommand;
use Humus\Amqp\Console\Helper\ContainerHelper;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ShowCommandTest
 * @package HumusTest\Amqp\Console\Command
 */
class ShowCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_when_invalid_type_given()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-t' => 'invalid']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertStringStartsWith('Invalid type given, use one of', $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_returns_when_no_name_given()
    {
        $tester = $this->createCommandTester($this->prophesize(ContainerInterface::class)->reveal());
        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("No type given\n", $tester->getDisplay(true));
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function it_returns_when_types_not_available(string $type)
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(new \ArrayObject())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['--type' => $type]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals("No $type found\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_lists_all_types_with_specs()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(
            [
                'humus' => [
                    'amqp' => [
                        'driver' => 'amqp-extension',
                        'exchange' => [
                            'demo' => [
                                'name' => 'demo',
                                'type' => 'direct',
                            ],
                        ],
                        'queue' => [
                            'foo' => [
                                'name' => 'foo',
                                'exchange' => 'demo',
                            ],
                        ],
                        'connection' => [
                            'default' => [
                                'type' => 'socket',
                            ],
                        ],
                        'producer' => [
                            'demo-producer' => [
                                'type' => 'plain',
                                'exchange' => 'demo',
                                'qos' => [
                                    'prefetch_size' => 0,
                                    'prefetch_count' => 10
                                ],
                            ],
                        ],
                        'callback_consumer' => [
                            'demo-consumer' => [
                                'queue' => 'foo',
                                'callback' => 'echo',
                                'idle_timeout' => 10,
                                'delivery_callback' => 'my_callback'
                            ],
                        ],
                    ]
                ]
            ]
        )->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['--type' => 'all', '--details' => true]);

        $this->assertEquals(0, $tester->getStatusCode());
        $output = $tester->getDisplay(true);
        $this->assertRegExp('/Connection: default/', $output);
        $this->assertRegExp('/Exchange: demo/', $output);
        $this->assertRegExp('/Queue: foo/', $output);
        $this->assertRegExp('/Callback_consumer: demo-consumer/', $output);
        $this->assertRegExp('/"delivery_callback": "my_callback"/', $output);
        $this->assertRegExp('/Producer: demo-producer/', $output);
    }

    /**
     * @param ContainerInterface $container
     * @return CommandTester
     */
    private function createCommandTester(ContainerInterface $container)
    {
        $command = new ShowCommand();
        $command->setHelperSet(
            new HelperSet([
                'container' => new ContainerHelper($container)
            ])
        );
        return new CommandTester($command);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            ['connections'],
            ['exchanges'],
            ['queues'],
            ['callback_consumers'],
            ['producers'],
            ['json_rpc_clients'],
            ['json_rpc_servers'],
        ];
    }
}
