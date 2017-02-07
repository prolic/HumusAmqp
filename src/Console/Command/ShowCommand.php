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

namespace Humus\Amqp\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShowCommand
 * @package Humus\Amqp\Console\Command
 */
class ShowCommand extends AbstractCommand
{
    /**
     * @var array
     */
    protected $knownTypes = [
        'connections',
        'exchanges',
        'queues',
        'callback_consumers',
        'producers',
        'json_rpc_clients',
        'json_rpc_servers',
        'all'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('show')
            ->setAliases(['show'])
            ->setDescription('Show all AMQP ' . implode(', ', $this->knownTypes))
            ->setDefinition([
                new InputOption(
                    'type',
                    't',
                    InputOption::VALUE_REQUIRED,
                    'one of ' . implode(', ', $this->knownTypes)
                ),
                new InputOption(
                    'details',
                    'd',
                    InputOption::VALUE_NONE,
                    'show details to given type'
                )
            ])
            ->setHelp('Show all AMQP ' . implode(', ', $this->knownTypes));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');

        if (! $type) {
            $output->writeln('No type given');
            return 1;
        }

        if (! in_array($type, $this->knownTypes)) {
            $output->writeln(
                'Invalid type given, use one of ' . implode(', ', $this->knownTypes)
            );
            return 1;
        }

        $config = $this->getContainer()->get('config');

        if ($config instanceof \Traversable) {
            $config = iterator_to_array($config);
        }

        $config = $config['humus']['amqp'] ?? [];

        switch ($type) {
            case 'connections':
            case 'exchanges':
            case 'queues':
            case 'callback_consumers':
            case 'producers':
            case 'json_rpc_clients':
            case 'json_rpc_servers':
                $this->listType($input, $output, $config, $type);
                break;
            case 'all':
                foreach ($this->knownTypes as $type) {
                    if ($type === 'all') {
                        continue;
                    }
                    $this->listType($input, $output, $config, $type);
                }
                break;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $config
     * @param string $type
     * @return void
     */
    protected function listType(InputInterface $input, OutputInterface $output, array $config, string $type)
    {
        $type = substr($type, 0, -1);

        if (! isset($config[$type]) || empty($config[$type])) {
            $output->writeln('No ' . $type . 's found');
        } else {
            foreach ($config[$type] as $name => $spec) {
                $output->writeln(ucfirst($type) . ': ' . $name);

                if ($input->getOption('details')) {
                    $output->writeln('Specs: ' . json_encode($spec, JSON_PRETTY_PRINT));
                    $output->writeln('');
                }
            }
        }
    }
}
