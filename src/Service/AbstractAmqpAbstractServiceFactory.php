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

namespace Humus\Amqp\Service;

use AMQPChannel;
use AMQPConnection;
use AMQPExchange;
use Humus\Amqp\Exception;
use Humus\Amqp\ExchangeFactory;
use Humus\Amqp\ExchangeFactoryInterface;
use Humus\Amqp\ExchangeSpecification;
use Humus\Amqp\PluginManager\Connection as ConnectionManager;
use Humus\Amqp\QosOptions;
use Traversable;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractAmqpAbstractServiceFactory
 * @package Humus\Amqp\Service
 */
abstract class AbstractAmqpAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string Top-level configuration key indicating amqp configuration
     */
    protected $configKey = 'humus_amqp_module';

    /**
     * @var string Second-level configuration key indicating connection configuration
     */
    protected $subConfigKey = '';

    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var string
     */
    protected $defaultConnectionName;

    /**
     * @var array
     */
    protected $specs = [];

    /**
     * @var ExchangeFactoryInterface
     */
    protected $exchangeFactory;

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        if (empty($config)) {
            return false;
        }

        if (!isset($config[$this->subConfigKey][$requestedName])) {
            return false;
        }

        $spec = $config[$this->subConfigKey][$requestedName];

        if ((is_array($spec) || $spec instanceof Traversable)) {
            return true;
        }

        return false;
    }

    /**
     * Get amqp configuration, if any
     *
     * @param  ServiceLocatorInterface $services
     * @return array
     */
    protected function getConfig(ServiceLocatorInterface $services)
    {
        if ($this->config !== null) {
            return $this->config;
        }

        // get global service locator, if we are in a plugin manager
        if ($services instanceof AbstractPluginManager) {
            $services = $services->getServiceLocator();
        }

        if (!$services->has('Config')) {
            $this->config = [];
            return $this->config;
        }

        $config = $services->get('Config');
        if (!isset($config[$this->configKey])
            || !is_array($config[$this->configKey])
        ) {
            $this->config = [];
            return $this->config;
        }

        $this->config = $config[$this->configKey];
        return $this->config;
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return string
     */
    protected function getDefaultConnectionName(ServiceLocatorInterface $services)
    {
        if (null === $this->defaultConnectionName) {
            $config = $this->getConfig($services);
            $this->defaultConnectionName = $config['default_connection'];
        }
        return $this->defaultConnectionName;
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return AMQPConnection
     */
    protected function getDefaultConnection(ServiceLocatorInterface $services)
    {
        $connectionManager = $this->getConnectionManager($services);
        $connection = $connectionManager->get($this->getDefaultConnectionName($services));

        return $connection;
    }

    /**
     * @param ServiceLocatorInterface $services
     * @param array $spec
     * @return AMQPConnection
     */
    protected function getConnection(ServiceLocatorInterface $services, array $spec)
    {
        if (!isset($spec['connection'])) {
            return $this->getDefaultConnection($services);
        }

        $connectionManager = $this->getConnectionManager($services);
        $connection = $connectionManager->get($spec['connection']);

        return $connection;
    }

    /**
     * Note: Exchanges are not shared, only using producers or consumers can be shared
     *
     * @param ServiceLocatorInterface $services
     * @param AMQPChannel $channel
     * @param string $name
     * @param bool $autoSetupFabric
     * @return AMQPExchange
     */
    protected function getExchange(
        ServiceLocatorInterface $services,
        AMQPChannel $channel,
        $name,
        $autoSetupFabric
    ) {
        $exchangeSpec = $this->getExchangeSpec($services, $name);
        $exchange = $this->getExchangeFactory()->create($exchangeSpec, $channel, $autoSetupFabric);

        return $exchange;
    }

    /**
     * @param AMQPConnection $connection
     * @param array $spec
     * @return AMQPChannel
     */
    protected function createChannel(AMQPConnection $connection, array $spec)
    {
        $qosOptions = isset($spec['qos']) ? new QosOptions($spec['qos']) : new QosOptions();

        $channel = new AMQPChannel($connection);
        $channel->setPrefetchSize($qosOptions->getPrefetchSize());
        $channel->setPrefetchCount($qosOptions->getPrefetchCount());

        return $channel;
    }

    /**
     * @return ExchangeFactoryInterface
     */
    public function getExchangeFactory()
    {
        if (null === $this->exchangeFactory) {
            $this->setExchangeFactory(new ExchangeFactory());
        }
        return $this->exchangeFactory;
    }

    /**
     * @param ExchangeFactoryInterface $exchangeFactory
     */
    public function setExchangeFactory(ExchangeFactoryInterface $exchangeFactory)
    {
        $this->exchangeFactory = $exchangeFactory;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $exchangeName
     * @return ExchangeSpecification
     */
    protected function getExchangeSpec(ServiceLocatorInterface $serviceLocator, $exchangeName)
    {
        $config  = $this->getConfig($serviceLocator);
        $spec = new ExchangeSpecification($config['exchanges'][$exchangeName]);
        return $spec;
    }

    /**
     * @param array $spec
     * @return bool
     */
    protected function useAutoSetupFabric(array $spec)
    {
        return (isset($spec['auto_setup_fabric']) && $spec['auto_setup_fabric']);
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return array
     */
    protected function getSpec(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (isset($this->specs[$name])) {
            return $this->specs[$name];
        }

        $config  = $this->getConfig($serviceLocator);
        $spec = $config[$this->subConfigKey][$requestedName];

        $this->specs[$name] = $spec;

        return $spec;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return ConnectionManager
     * @throws \Humus\Amqp\Exception\RuntimeException
     */
    protected function getConnectionManager(ServiceLocatorInterface $serviceLocator)
    {
        if (null === $this->connectionManager) {
            if (!$serviceLocator->has('Humus\Amqp\PluginManager\Connection')) {
                throw new Exception\RuntimeException(
                    'Humus\Amqp\PluginManager\Connection not found'
                );
            }
            $this->connectionManager = $serviceLocator->get('Humus\Amqp\PluginManager\Connection');
        }
        return $this->connectionManager;
    }
}
