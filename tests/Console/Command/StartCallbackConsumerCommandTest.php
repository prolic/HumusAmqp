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

use Humus\Amqp\Console\Command\StartCallbackConsumerCommand;
use Humus\Amqp\Console\Helper\ContainerHelper;
use Humus\Amqp\Consumer;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class StartCallbackConsumerCommandTest
 * @package HumusTest\Amqp\Console\Command
 */
class StartCallbackConsumerCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_starts_the_consumer()
    {
        $server = $this->prophesize(Consumer::class);
        $server->consume(4)->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('server')->willReturn(true)->shouldBeCalled();

        $container->get('server')->willReturn($server->reveal())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['--name' => 'server', '-a' => 4]);

        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_when_no_name_given()
    {
        $tester = $this->createCommandTester($this->prophesize(ContainerInterface::class)->reveal());
        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("No consumer given\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_returns_when_consumer_not_found()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('unknown')->willReturn(false)->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-c' => 'unknown']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("No consumer with name unknown found\n", $tester->getDisplay(true));
    }

    /**
     * @param ContainerInterface $container
     * @return CommandTester
     */
    private function createCommandTester(ContainerInterface $container)
    {
        $command = new StartCallbackConsumerCommand();
        $command->setHelperSet(
            new HelperSet([
                'container' => new ContainerHelper($container)
            ])
        );
        return new CommandTester($command);
    }
}
