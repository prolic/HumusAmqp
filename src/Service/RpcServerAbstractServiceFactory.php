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

use Humus\Amqp\JsonRpcServer;
use Humus\Amqp\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class RpcServerAbstractServiceFactory
 * @package Humus\Amqp\Service
 */
class RpcServerAbstractServiceFactory extends AbstractAmqpQueueAbstractServiceFactory
{
    /**
     * @var string Second-level configuration key indicating connection configuration
     */
    protected $subConfigKey = 'rpc_servers';

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return JsonRpcServer
     * @throws Exception\InvalidArgumentException
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        // get global service locator, if we are in a plugin manager
        if ($serviceLocator instanceof AbstractPluginManager) {
            $serviceLocator = $serviceLocator->getServiceLocator();
        }

        $spec = $this->getSpec($serviceLocator, $name, $requestedName);
        $this->validateSpec($serviceLocator, $spec, $requestedName);

        $connection = $this->getConnection($serviceLocator, $spec);
        $channel    = $this->createChannel($connection, $spec);

        $queueSpec = $this->getQueueSpec($serviceLocator, $spec['queue']);
        $queue     = $this->getQueue($queueSpec, $channel, $this->useAutoSetupFabric($spec));

        $idleTimeout = isset($spec['idle_timeout']) ? $spec['idle_timeout'] : 5.0;
        $waitTimeout = isset($spec['wait_timeout']) ? $spec['wait_timeout'] : 100000;

        $rpcServer = new JsonRpcServer($queue, $idleTimeout, $waitTimeout);

        $callbackManager = $this->getCallbackManager($serviceLocator);
        $callback        = $callbackManager->get($spec['callback']);

        $rpcServer->setDeliveryCallback($callback);

        if (isset($spec['error_callback'])) {
            if (!$callbackManager->has($spec['error_callback'])) {
                throw new Exception\InvalidArgumentException(
                    'The required callback ' . $spec['error_callback'] . ' can not be found'
                );
            }
            $errorCallback = $callbackManager->get($spec['error_callback']);
            $rpcServer->setFlushCallback($errorCallback);
        }

        return $rpcServer;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param array $spec
     * @param string $requestedName
     * @throws Exception\InvalidArgumentException
     */
    protected function validateSpec(ServiceLocatorInterface $serviceLocator, array $spec, $requestedName)
    {
        if (!isset($spec['queue'])) {
            throw new Exception\InvalidArgumentException('Queue is missing for rpc client ' . $requestedName);
        }

        if (!isset($spec['callback'])) {
            throw new Exception\InvalidArgumentException('Callback is missing for rpc server ' . $requestedName);
        }

        $defaultConnection = $this->getDefaultConnectionName($serviceLocator);

        if (isset($spec['connection'])) {
            $connection = $spec['connection'];
        } else {
            $connection = $defaultConnection;
        }

        $config  = $this->getConfig($serviceLocator);

        // validate queue existence
        if (!isset($config['queues'][$spec['queue']])) {
            throw new Exception\InvalidArgumentException(
                'The rpc client queue ' . $spec['queue'] . ' is missing in the queues configuration'
            );
        }

        // validate queue connection
        $queue = $config['queues'][$spec['queue']];
        $testConnection = isset($queue['connection']) ? $queue['connection'] : $defaultConnection;
        if ($testConnection != $connection) {
            throw new Exception\InvalidArgumentException(
                'The rpc client connection for queue ' . $spec['queue'] . ' (' . $testConnection . ') does not '
                . 'match the rpc client connection for rpc client ' . $requestedName . ' (' . $connection . ')'
            );
        }
    }
}
