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

namespace Humus\Amqp\Container;

use Humus\Amqp\Exception;
use Humus\Amqp\JsonRpc\JsonRpcServer;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Class JsonRpcServerFactory
 * @package Humus\Amqp\Container
 */
final class JsonRpcServerFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $serverName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_json_rpc_server' => [JsonRpcServerFactory::class, 'your_json_rpc_server_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return JsonRpcServer
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): JsonRpcServer
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    /**
     * JsonRpcClientFactory constructor.
     * @param string $serverName
     */
    public function __construct(string $serverName)
    {
        $this->serverName = $serverName;
    }

    /**
     * @param ContainerInterface $container
     * @return JsonRpcServer
     */
    public function __invoke(ContainerInterface $container): JsonRpcServer
    {
        $options = $this->options($container->get('config'), $this->serverName);

        $queueName = $options['queue'];
        $queue = QueueFactory::$queueName($container);

        $deliveryCallback = $container->get($options['delivery_callback']);

        if (null === $options['logger']) {
            $logger = new NullLogger();
        } else {
            $logger = $container->get($options['logger']);
        }

        return new JsonRpcServer(
            $queue,
            $deliveryCallback,
            $logger,
            $options['idle_timeout'],
            $options['consumer_tag'],
            $options['app_id'],
            $options['return_trace']
        );
    }

    /**
     * @return array
     */
    public function dimensions(): iterable
    {
        return ['humus', 'amqp', 'json_rpc_server'];
    }

    /**
     * @return array
     */
    public function defaultOptions(): iterable
    {
        return [
            'consumer_tag' => '',
            'app_id' => '',
            'return_trace' => false,
            'logger' => null,
        ];
    }

    /**
     * @return array
     */
    public function mandatoryOptions(): iterable
    {
        return [
            'queue',
            'delivery_callback',
            'idle_timeout'
        ];
    }
}
