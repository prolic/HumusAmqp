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

namespace Humus\Amqp\Container;

use Humus\Amqp\Exception;
use Humus\Amqp\JsonProducer;
use Humus\Amqp\PlainProducer;
use Humus\Amqp\Producer;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Psr\Container\ContainerInterface;

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
     *     'your_producer' => [ProducerFactory::class, 'your_producer_name'],
     * ];
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return Producer
     * @throws Exception\InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): Producer
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
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
    public function __invoke(ContainerInterface $container): Producer
    {
        $options = $this->options($container->get('config'), $this->producerName);

        $exchangeName = $options['exchange'];
        $exchange = ExchangeFactory::$exchangeName($container);

        switch ($options['type']) {
            case 'json':
            case JsonProducer::class:
                return new JsonProducer($exchange, $options['attributes']);
            case 'plain':
            case PlainProducer::class:
                return new PlainProducer($exchange, $options['attributes']);
            default:
                throw new Exception\InvalidArgumentException(
                    sprintf('Unknown producer type %s requested', $options['type']
                ));
        }
    }

    /**
     * @return array
     */
    public function dimensions(): array
    {
        return ['humus', 'amqp', 'producer'];
    }

    /**
     * @return array
     */
    public function defaultOptions(): array
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
    public function mandatoryOptions(): array
    {
        return [
            'exchange',
            'type',
        ];
    }
}
