<?php
/**
 * Copyright (c) 2016-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace HumusTest\Amqp\Console;

use Humus\Amqp\Console\ConsoleRunner;
use Humus\Amqp\Console\Helper\ContainerHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\ApplicationTester;

class ConsumerRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_helper_set(): void
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();

        $helperSet = ConsoleRunner::createHelperSet($container);

        $this->assertInstanceOf(HelperSet::class, $helperSet);
        $this->assertInstanceOf(ContainerHelper::class, $helperSet->get('container'));
        $this->assertSame($container, $helperSet->get('container')->getContainer());
    }

    /**
     * @test
     */
    public function it_prints_cli_config_template(): void
    {
        ob_start();
        ConsoleRunner::printCliConfigTemplate();
        $output = ob_get_clean();

        $this->assertStringStartsWith('You are missing a "humus-amqp-config.php"', $output);
    }

    /**
     * @test
     * @backupGlobals
     */
    public function it_runs_application(): void
    {
        $container = $this->prophesize(ContainerInterface::class);

        $app = ConsoleRunner::createApplication(
            new HelperSet([
                'container' => new ContainerHelper($container->reveal()),
            ])
        );
        $app->setAutoExit(false);

        $tester = new ApplicationTester($app);
        $tester->run([]);

        $output = $tester->getDisplay(true);

        $this->assertRegExp('/json_rpc_server/', $output);
        $this->assertRegExp('/Start a JSON-RPC server/', $output);
    }
}
