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

use Humus\Amqp\Console\Command\PublishMessageCommand;
use Humus\Amqp\Console\Helper\ContainerHelper;
use Humus\Amqp\Constants;
use Humus\Amqp\Producer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class PublishMessageCommandTest
 * @package HumusTest\Amqp\Console\Command
 */
class PublishMessageCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_when_invalid_name_given()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('invalid-producer')->willReturn(false)->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-p' => 'invalid-producer']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("Producer with name invalid-producer not found\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_returns_when_no_name_given()
    {
        $tester = $this->createCommandTester($this->prophesize(ContainerInterface::class)->reveal());
        $tester->execute([]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("No producer name given\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_returns_when_confirm_select_and_transactional_are_set()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('producer')->willReturn(true)->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-p' => 'producer', '--confirm_select' => true, '--transactional' => true]);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("Can only use one of transactional or confirm select\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_returns_when_cannot_decode_arguments()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('producer')->willReturn(true)->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-p' => 'producer', '-a' => '/&$&§&%§$§']);

        $this->assertEquals(1, $tester->getStatusCode());
        $this->assertEquals("Cannot decode arguments\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_publishes_with_arguments_and_routing_key_and_flag()
    {
        $producer = $this->prophesize(Producer::class);
        $producer->publish('test-message', 'test', Constants::AMQP_IMMEDIATE, [
            'foo' => 'bar',
            'bool' => true,
            'null' => null,
            'array' => [
                'baz', 'bam',
            ],
            'table' => [
                'one' => 'two',
                'three' => 'four',
            ],
        ])->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('producer')->willReturn(true)->shouldBeCalled();
        $container->get('producer')->willReturn($producer->reveal())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute([
            '-p' => 'producer',
            '-m' => 'test-message',
            '-r' => 'test',
            '-f' => Constants::AMQP_IMMEDIATE,
            '--arguments' => '{"foo":"bar","bool":true,"null":null,"array":["baz","bam"],"table":{"one":"two","three":"four"}}',
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals("Message published\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_publishes_transactional()
    {
        $producer = $this->prophesize(Producer::class);
        $producer->startTransaction()->shouldBeCalled();
        $producer->publish('', '', Constants::AMQP_NOPARAM, [])->shouldBeCalled();
        $producer->commitTransaction()->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('producer')->willReturn(true)->shouldBeCalled();
        $container->get('producer')->willReturn($producer->reveal())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-p' => 'producer', '-t' => true]);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals("Message published\n", $tester->getDisplay(true));
    }

    /**
     * @test
     */
    public function it_publishes_with_confirm_select()
    {
        $producer = $this->prophesize(Producer::class);
        $producer->confirmSelect()->shouldBeCalled();
        $producer->setConfirmCallback(Argument::any(), Argument::any())->shouldBeCalled();
        $producer->waitForConfirm(2.0)->shouldBeCalled();
        $producer->publish('test-message', '', Constants::AMQP_NOPARAM, [])->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('producer')->willReturn(true)->shouldBeCalled();
        $container->get('producer')->willReturn($producer->reveal())->shouldBeCalled();

        $tester = $this->createCommandTester($container->reveal());
        $tester->execute(['-p' => 'producer', '-c' => true, '--message' => 'test-message']);

        $this->assertEquals(0, $tester->getStatusCode());
        $this->assertEquals("Message published\n", $tester->getDisplay(true));
    }

    /**
     * @param ContainerInterface $container
     * @return CommandTester
     */
    private function createCommandTester(ContainerInterface $container)
    {
        $command = new PublishMessageCommand();
        $command->setHelperSet(
            new HelperSet([
                'container' => new ContainerHelper($container),
            ])
        );

        return new CommandTester($command);
    }
}
