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

use Humus\Amqp\Exception;
use Humus\Amqp\Producer;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ProducerAbstractServiceFactory
 * @package Humus\Amqp\Service
 */
class ProducerAbstractServiceFactory extends AbstractAmqpAbstractServiceFactory
{
    /**
     * @var string Second-level configuration key indicating connection configuration
     */
    protected $subConfigKey = 'producers';

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return Producer
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        // get global service locator, if we are in a plugin manager
        // @todo: recheck, if this is really necessary and try to find another way of getting the global service locator
        if ($serviceLocator instanceof AbstractPluginManager) {
            $serviceLocator = $serviceLocator->getServiceLocator();
        }

        $spec       = $this->getSpec($serviceLocator, $name, $requestedName);
        $this->validateSpec($serviceLocator, $spec, $requestedName);
        $connection = $this->getConnection($serviceLocator, $spec);
        $channel    = $this->createChannel($connection, $spec);

        $exchange = $this->getExchange($serviceLocator, $channel, $spec['exchange'], $this->useAutoSetupFabric($spec));
        $producer = new Producer($exchange);

        return $producer;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param array $spec
     * @param string $requestedName
     * @throws Exception\InvalidArgumentException
     */
    protected function validateSpec(ServiceLocatorInterface $serviceLocator, array $spec, $requestedName)
    {
        $defaultConnection = $this->getDefaultConnectionName($serviceLocator);

        if (isset($spec['connection'])) {
            $connection = $spec['connection'];
        } else {
            $connection = $defaultConnection;
        }

        // exchange required
        if (!isset($spec['exchange'])) {
            throw new Exception\InvalidArgumentException(
                'Exchange is missing for producer ' . $requestedName
            );
        }

        $exchange = $spec['exchange'];
        $config  = $this->getConfig($serviceLocator);
        // validate exchange existence
        if (!isset($config['exchanges'][$exchange])) {
            throw new Exception\InvalidArgumentException(
                'The producer exchange ' . $exchange . ' is missing in the exchanges configuration'
            );
        }

        // validate exchange connection
        $testConnection = isset($config['exchanges'][$exchange]['connection'])
            ? $config['exchanges'][$exchange]['connection']
            : $this->getDefaultConnectionName($serviceLocator);

        if ($testConnection != $connection) {
            throw new Exception\InvalidArgumentException(
                'The producer connection for exchange ' . $exchange . ' (' . $testConnection . ') does not '
                . 'match the producer connection for producer ' . $requestedName . ' (' . $connection . ')'
            );
        }
    }
}
