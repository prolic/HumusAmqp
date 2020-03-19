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

namespace Humus\Amqp\Console\Command;

use Humus\Amqp\Constants;
use Humus\Amqp\Exception;
use Humus\Amqp\Producer;
use JsonException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PublishMessageCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('publish-message')
            ->setAliases(['publish-message'])
            ->setDescription('Publish a message to an exchange')
            ->setDefinition([
                new InputOption(
                    'producer',
                    'p',
                    InputOption::VALUE_REQUIRED,
                    'name of the producer to use'
                ),
                new InputOption(
                    'message',
                    'm',
                    InputOption::VALUE_REQUIRED,
                    'message to send',
                    ''
                ),
                new InputOption(
                    'transactional',
                    't',
                    InputOption::VALUE_NONE,
                    'whether to use a transaction for message sending'
                ),
                new InputOption(
                    'confirm_select',
                    'c',
                    InputOption::VALUE_NONE,
                    'whether to use a confirm select mode for message sending'
                ),
                new InputOption(
                    'routing_key',
                    'r',
                    InputOption::VALUE_REQUIRED,
                    'routing key to use',
                    ''
                ),
                new InputOption(
                    'arguments',
                    'a',
                    InputOption::VALUE_OPTIONAL,
                    'arguments to add in JSON-format',
                    '{}'
                ),
                new InputOption(
                    'flags',
                    'f',
                    InputOption::VALUE_REQUIRED,
                    'One or more of Constants::AMQP_MANDATORY (1024) and Constants::AMQP_IMMEDIATE (2048).',
                    Constants::AMQP_NOPARAM
                ),
            ])
            ->setHelp('Purges a queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $producerName = $input->getOption('producer');

        if (! $producerName) {
            $output->writeln('No producer name given');

            return 1;
        }

        $container = $this->getContainer();

        if (! $container->has($producerName)) {
            $output->writeln('Producer with name ' . $producerName . ' not found');

            return 1;
        }

        $transactional = $input->getOption('transactional');

        if ($transactional) {
            $transactional = true;
        } else {
            $transactional = false;
        }

        $confirmSelect = $input->getOption('confirm_select');

        if ($confirmSelect) {
            $confirmSelect = true;
        } else {
            $confirmSelect = false;
        }

        if ($confirmSelect && $transactional) {
            $output->writeln('Can only use one of transactional or confirm select');

            return 1;
        }

        $arguments = $input->getOption('arguments');

        try {
            $arguments = \json_decode($arguments, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $output->writeln('Cannot decode arguments');

            return 1;
        }

        $producer = $container->get($producerName);

        assert($producer instanceof Producer);

        if ($transactional) {
            $producer->startTransaction();
        }

        if ($confirmSelect) {
            $producer->confirmSelect();

            $producer->setConfirmCallback(
                function (int $deliveryTag, bool $multiple = false): bool {
                    return false;
                },
                function (int $deliveryTag, bool $multiple, bool $requeue): void {
                    throw new Exception\RuntimeException('Message nacked');
                }
            );
        }

        $producer->publish(
            $input->getOption('message'),
            $input->getOption('routing_key'),
            (int) $input->getOption('flags'),
            $arguments
        );

        if ($transactional) {
            $producer->commitTransaction();
        }

        if ($confirmSelect) {
            try {
                $producer->waitForConfirm(2.0);
            } catch (\Throwable $e) {
                echo get_class($e) . ': ' . $e->getMessage();

                return 1;
            }
        }

        $output->writeln('Message published');

        return 0;
    }
}
