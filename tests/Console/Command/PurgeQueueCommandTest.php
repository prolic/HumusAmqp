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

use Humus\Amqp\Channel;
use Humus\Amqp\Connection;
use Humus\Amqp\Console\Command\PurgeQueueCommand;
use Humus\Amqp\Console\Helper\ContainerHelper;
use Humus\Amqp\Queue;
use Psr\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class PurgeQueueCommandTest
 * @package HumusTest\Amqp\Console\Command
 */
class PurgeQueueCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_when_invalid_name_given()
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

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-p' => 'invalid']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("Queue with name invalid not found\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_returns_when_no_name_given()
    {
        $tester = $this->createCommandTester($this->prophesize(ContainerInterface::class)->reveal());
        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("No queue name given\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_purges_the_queue()
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
                                'exchanges' => [
                                    'demo' => [],
                                ],
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

        $queue = $this->prophesize(Queue::class);
        $queue->setName('foo')->shouldBeCalled();
        $queue->setFlags(2)->shouldBeCalled();
        $queue->setArguments([])->shouldBeCalled();
        $queue->purge()->shouldBeCalled();

        $channel = $this->prophesize(Channel::class);
        $channel->newQueue()->willReturn($queue->reveal())->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->newChannel()->willReturn($channel->reveal())->shouldBeCalled();
        $container->get('default')->willReturn($connection->reveal())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['--name' => 'foo']);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals(
            "Queue foo purged\n",
            $tester->getDisplay(true)
        );
    }

    /**
     * @param ContainerInterface $container
     * @return CommandTester
     */
    private function createCommandTester(ContainerInterface $container)
    {
        $command = new PurgeQueueCommand();
        $command->setHelperSet(
            new HelperSet([
                'container' => new ContainerHelper($container)
            ])
        );
        return new CommandTester($command);
    }
}
