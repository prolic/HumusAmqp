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

use Humus\Amqp\Driver\Driver;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
use Humus\Amqp\JsonProducer;
use Humus\Amqp\PlainProducer;
use Humus\Amqp\Producer;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;

/**
 * Class ProducerFactory
 * @package Humus\Amqp\Container
 */
final class ProducerFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $producerName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'your_producer' => [ProducerFactory::class, 'your_producer'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return Producer
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments) : Producer
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    /**
     * @param string $producerName
     */
    public function __construct(string $producerName)
    {
        $this->producerName = $producerName;
    }

    /**
     * @param ContainerInterface $container
     * @return Producer
     */
    public function __invoke(ContainerInterface $container) : Producer
    {
        if (! $container->has(Driver::class)) {
            throw new Exception\RuntimeException('No driver factory registered in container');
        }

        $config = $container->get('config');
        $options = $this->options($config, $this->producerName);

        $exchange = $this->fetchExchange($container, $options['exchange']);

        switch ($options['type']) {
            case 'json':
            case JsonProducer::class:
                $producer = new JsonProducer($exchange, $options['attributes']);
                break;
            case 'plain':
            case PlainProducer::class:
                $producer = new PlainProducer($exchange, $options['attributes']);
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'Unknown producer type %s requested',
                        $options['type']
                ));
        }

        return $producer;
    }


    /**
     * @return array
     */
    public function dimensions()
    {
        return ['humus', 'amqp', 'producer'];
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'attributes' => null,
            // factory configs
            'auto_setup_fabric' => false,
        ];
    }

    /**
     * @return array
     */
    public function mandatoryOptions()
    {
        return [
            'exchange',
            'type',
            'attributes',
            // factory configs
            'auto_setup_fabric'
        ];
    }
    /**
     * @param ContainerInterface $container
     * @param string $exchangeName
     * @return Exchange
     */
    private function fetchExchange(ContainerInterface $container, string $exchangeName) : Exchange
    {
        if (! $container->has($exchangeName)) {
            throw new Exception\RuntimeException(sprintf(
                'Exchange %s not registered in container',
                $exchangeName
            ));
        }

        $exchange = $container->get($exchangeName);

        if (! $exchange instanceof Exchange) {
            throw new Exception\RuntimeException(sprintf(
                'Exchange %s is not an instance of %s',
                $exchangeName,
                Exchange::class
            ));
        }

        return $exchange;
    }

}
