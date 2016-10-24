<?php
/**
 * Copyright (c) 2016-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace Humus\Amqp\Console\Command;

use Humus\Amqp\Consumer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StartCallbackConsumerCommand
 * @package Humus\Amqp\Console\Command
 */
class StartCallbackConsumerCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('consumer')
            ->setAliases(['consumer'])
            ->setDescription('Start a consumer')
            ->setDefinition([
                new InputOption(
                    'name',
                    'c',
                    InputOption::VALUE_REQUIRED,
                    'name of the consumer to start'
                ),
                new InputOption(
                    'amount',
                    'a',
                    InputOption::VALUE_OPTIONAL,
                    'amount of messages to consume',
                    0
                )
            ])
            ->setHelp('Start a consumer');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumerName = $input->getOption('name');

        if (! $consumerName) {
            $output->writeln('No consumer given');
            return 1;
        }

        $container = $this->getContainer();

        if (! $container->has($consumerName)) {
            $output->writeln('No consumer with name ' . $consumerName . ' found');
            return 1;
        }

        $callbackConsumer = $container->get($consumerName);
        /* @var Consumer $callbackConsumer */

        $callbackConsumer->consume((int) $input->getOption('amount'));
    }
}
