<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace Humus\Amqp\Container;

use Humus\Amqp\Exception;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Psr\Container\ContainerInterface;
use Traversable;

final class JsonRpcClientFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    private string $clientName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_rpc_client' => [JsonRpcClientFactory::class, 'your_rpc_client_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     *
     * @return JsonRpcClient
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): JsonRpcClient
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $clientName)
    {
        $this->clientName = $clientName;
    }

    public function __invoke(ContainerInterface $container): JsonRpcClient
    {
        $options = $this->options($container->get('config'), $this->clientName);

        $queueName = $options['queue'];
        $queue = QueueFactory::$queueName($container);
        $channel = $queue->getChannel();

        if ($options['exchanges'] instanceof Traversable) {
            $options['exchanges'] = iterator_to_array($options['exchanges']);
        }

        if (! \is_array($options['exchanges']) || empty($options['exchanges'])) {
            throw new Exception\InvalidArgumentException(
                'Option "exchanges" must be a not empty array or an instance of Traversable'
            );
        }

        $exchanges = [];

        foreach ($options['exchanges'] as $exchange) {
            $exchanges[$exchange] = ExchangeFactory::$exchange($container, $channel);
        }

        $errorFactory = null;
        if (isset($options['error_factory'])) {
            $errorFactory = $container->get($options['error_factory']);
        }

        return new JsonRpcClient($queue, $exchanges, $options['wait_micros'], $options['app_id'], $errorFactory);
    }

    public function dimensions(): array
    {
        return ['humus', 'amqp', 'json_rpc_client'];
    }

    public function defaultOptions(): array
    {
        return [
            'wait_micros' => 1000,
            'app_id' => '',
        ];
    }

    public function mandatoryOptions(): array
    {
        return [
            'queue',
            'exchanges',
        ];
    }
}
