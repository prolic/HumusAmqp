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

namespace Humus\Amqp\Console\Command;

use Humus\Amqp\Container\ExchangeFactory;
use Humus\Amqp\Container\QueueFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetupFabricCommand
 * @package Humus\Amqp\Console\Command
 */
class SetupFabricCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('setup-fabric')
            ->setAliases(['setup-fabric'])
            ->setDescription('Declares all AMQP exchanges and queues')
            ->setHelp('Declares all AMQP exchanges and queues');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getHumusAmqpConfig();

        if (! isset($config['exchange']) || empty($config['exchange'])) {
            $output->writeln('No exchanges to configure');
        } else {
            foreach ($config['exchange'] as $exchange => $spec) {
                ExchangeFactory::$exchange($this->getContainer(), null, true);
                $output->writeln('Exchange ' . $exchange . ' declared');
            }    
        }

        if (! isset($config['queue']) || empty($config['queue'])) {
            $output->writeln('No queues to configure');
        } else {
            foreach ($config['queue'] as $queue => $spec) {
                QueueFactory::$queue($this->getContainer(), null, true);
                $output->writeln('Queue ' . $queue . ' declared');
            }
        }

        $output->writeln('Done.');
    }
}
