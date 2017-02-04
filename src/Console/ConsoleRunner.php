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

namespace Humus\Amqp\Console;

use Humus\Amqp\Console\Helper\ContainerHelper;
use Interop\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Class ConsoleRunner
 * @package Humus\Amqp\Console
 */
class ConsoleRunner
{
    /**
     * Create a Symfony Console HelperSet
     *
     * @param ContainerInterface $container
     * @return HelperSet
     */
    public static function createHelperSet(ContainerInterface $container)
    {
        return new HelperSet([
            'container' => new ContainerHelper($container)
        ]);
    }

    /**
     * Runs console with the given helperset.
     *
     * @param HelperSet  $helperSet
     * @param \Symfony\Component\Console\Command\Command[] $commands
     *
     * @return void
     */
    public static function run(HelperSet $helperSet, array $commands = [])
    {
        $cli = self::createApplication($helperSet, $commands);
        $cli->run();
    }

    /**
     * Creates a console application with the given helperset and
     * optional commands.
     *
     * @param HelperSet $helperSet
     * @param \Symfony\Component\Console\Command\Command[] $commands
     *
     * @return Application
     */
    public static function createApplication(HelperSet $helperSet, $commands = [])
    {
        $cli = new Application('Humus Amqp Command Line Interface');

        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);

        self::addCommands($cli);

        $cli->addCommands($commands);

        return $cli;
    }

    /**
     * @param Application $cli
     *
     * @return void
     */
    public static function addCommands(Application $cli)
    {
        $cli->addCommands([
            new Command\PublishMessageCommand(),
            new Command\PurgeQueueCommand(),
            new Command\SetupFabricCommand(),
            new Command\ShowCommand(),
            new Command\StartCallbackConsumerCommand(),
            new Command\StartJsonRpcServerCommand()
        ]);
    }

    /**
     * @return void
     */
    public static function printCliConfigTemplate()
    {
        echo <<<'HELP'
You are missing a "humus-amqp-config.php" or "config/humus-amqp-config.php" file in your
project, which is required to get the Humus Amqp Console working. You can use the
following sample as a template:

<?php

use Humus\Amqp\Console\ConsoleRunner;

// replace with file to your own project bootstrap
require_once 'bootstrap.php';

// replace with mechanism to retrieve the container in your app
$container = GetContainer();

return ConsoleRunner::createHelperSet($container);

HELP;
    }
}
