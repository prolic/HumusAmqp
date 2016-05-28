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

namespace Humus\Amqp\Container;

use Humus\Amqp\Exception;
use Humus\Amqp\CallbackConsumer;
use Humus\Amqp\Queue;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class CallbackConsumerFactory
 * @package Humus\Amqp\Container
 */
class CallbackConsumerFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $consumerName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_consumer' => [ConsumerFactory::class, 'your_consumer_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return CallbackConsumer
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments) : CallbackConsumer
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    /**
     * CallbackConsumerFactory constructor.
     * @param string $consumerName
     */
    public function __construct(string $consumerName)
    {
        $this->consumerName = $consumerName;
    }

    /**
     * @param ContainerInterface $container
     * @return CallbackConsumer
     */
    public function __invoke(ContainerInterface $container)
    {
        $options = $this->options($container->get('config'), $this->consumerName);

        $queue = $this->fetchQueue($container, $options['queue']);

        $logger = $this->fetchLogger($container, $options['logger']);

        $deliveryCallback = $this->fetchCallback($container, $options['delivery_callback'], false);

        $flushCallback = $this->fetchCallback($container, $options['flush_callback'], true);

        $errorCallback = $this->fetchCallback($container, $options['error_callback'], true);

        return new CallbackConsumer(
            $queue,
            $logger,
            $options['idle_timeout'],
            $deliveryCallback,
            $flushCallback,
            $errorCallback,
            $options['consumer_tag']
        );
    }

    /**
     * @return array
     */
    public function dimensions()
    {
        return ['humus', 'amqp', 'callback_consumer'];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'logger' => null,
            'flush_callback' => null,
            'error_callback' => null,
            'qos' => [
                'prefetch_count' => 3,
                'prefetch_size' => 0,
            ],
            'idle_timeout' => 5.0,
            'consumer_tag' => null,
        ];
    }

    /**
     * @return array
     */
    public function mandatoryOptions()
    {
        return [
            'queue',
            'delivery_callback',
        ];
    }

    /**
     * @param ContainerInterface $container
     * @param string $queueName
     * @return Queue
     */
    private function fetchQueue(ContainerInterface $container, string $queueName) : Queue
    {
        if (! $container->has($queueName)) {
            throw new Exception\RuntimeException(sprintf(
                'Queue %s not registered in container',
                $queueName
            ));
        }

        $queue = $container->get($queueName);

        if (! $queue instanceof Queue) {
            throw new Exception\RuntimeException(sprintf(
                'Queue %s is not an instance of %s',
                $queueName,
                Queue::class
            ));
        }

        return $queue;
    }

    /**
     * @param ContainerInterface $container
     * @param string|null $callbackName
     * @param bool $allowNull
     * @return callable|null
     */
    private function fetchCallback(ContainerInterface $container, string $callbackName = null, bool $allowNull)
    {
        if (null === $callbackName && $allowNull) {
            return;
        }

        if (! $container->has($callbackName)) {
            throw new Exception\RuntimeException(sprintf(
                'Callback %s not registered in container',
                $callbackName
            ));
        }

        $callback = $container->get($callbackName);

        if (! is_callable($callback)) {
            throw new Exception\RuntimeException(sprintf(
                'Callback %s is not a callable',
                $callbackName
            ));
        }

        return $callback;
    }

    /**
     * @param ContainerInterface $container
     * @param string|null $loggerName
     * @return LoggerInterface
     */
    private function fetchLogger(ContainerInterface $container, string $loggerName = null) : LoggerInterface
    {
        if (null === $loggerName) {
            return new NullLogger();
        }

        if (! $container->has($loggerName)) {
            throw new Exception\RuntimeException(sprintf(
                'Logger %s not registered in container',
                $loggerName
            ));
        }

        $logger = $container->get($loggerName);

        if (! $logger instanceof LoggerInterface) {
            throw new Exception\RuntimeException(sprintf(
                'Logger %s is not an instance of %s',
                $loggerName,
                LoggerInterface::class
            ));
        }

        return $logger;
    }
}
