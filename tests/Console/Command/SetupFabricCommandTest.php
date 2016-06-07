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

namespace HumusTest\Amqp\Console\Command;

use Humus\Amqp\Console\Command\SetupFabricCommand;
use Humus\Amqp\Console\Helper\ContainerHelper;
use Humus\Amqp\Container\ExchangeFactory;
use Humus\Amqp\Container\QueueFactory;
use Humus\Amqp\Driver\Driver;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class SetupFabricCommand
 * @package HumusTest\Amqp\Console\Command
 */
class SetupFabricCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_outputs_that_nothing_is_to_do()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(new \ArrayObject())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute([]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals(
            "No exchanges to configure\nNo queues to configure\n",
            $tester->getDisplay(true)
        );
    }

    /**
     * @test
     */
    public function it_declares_exchanges_and_queues()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn(
            [
                'humus' => [
                    'amqp' => [
                        'driver' => 'php-amqplib',
                        'exchange' => [
                            'demo' => [
                                'name' => 'demo',
                                'type' => 'direct',
                                'connection' => 'default',
                            ],
                        ],
                        'queue' => [
                            'foo' => [
                                'name' => 'foo',
                                'exchange' => 'demo',
                                'connection' => 'default',
                            ],
                        ],
                        'connection' => [
                            'default' => [
                                'type' => 'socket',
                                'vhost' => '/humus-amqp-test',
                            ],
                        ],
                    ]
                ]
            ]
        )->shouldBeCalled();
        $container->has(Driver::class)->willReturn(true)->shouldBeCalled();
        $container->get(Driver::class)->willReturn(Driver::PHP_AMQP_LIB())->shouldBeCalled();

        $container = $container->reveal();
        $tester = $this->createCommandTester($container);
        $tester->execute([]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals(
            "Exchange demo declared\nQueue foo declared\n",
            $tester->getDisplay(true)
        );

        // cleanup
        $queueName = 'foo';
        $queue = QueueFactory::$queueName($container);
        $queue->delete();

        // cleanup
        $exchangeName = 'demo';
        $exchange = ExchangeFactory::$exchangeName($container);
        $exchange->delete();
    }

    /**
     * @param ContainerInterface $container
     * @return CommandTester
     */
    private function createCommandTester(ContainerInterface $container)
    {
        $command = new SetupFabricCommand();
        $command->setHelperSet(
            new HelperSet([
                'container' => new ContainerHelper($container)
            ])
        );
        return new CommandTester($command);
    }
}
