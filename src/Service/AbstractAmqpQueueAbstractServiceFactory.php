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
use AMQPQueue;
use Humus\Amqp\Exception;
use Humus\Amqp\QueueFactory;
use Humus\Amqp\QueueFactoryInterface;
use Humus\Amqp\QueueSpecification;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractAmqpQueueAbstractServiceFactory
 * @package Humus\Amqp\Service
 */
abstract class AbstractAmqpQueueAbstractServiceFactory extends AbstractAmqpAbstractServiceFactory
{
    /**
     * @var \Humus\Amqp\PluginManager\Callback
     */
    protected $callbackManager;

    /**
     * @var QueueFactoryInterface
     */
    protected $queueFactory;

    /**
     * @param QueueSpecification $spec
     * @param AMQPChannel $channel
     * @param $autoSetupFabric
     * @return AMQPQueue
     */
    protected function getQueue(QueueSpecification $spec, AMQPChannel $channel, $autoSetupFabric)
    {
        $queue = $this->getQueueFactory()->create($spec, $channel, $autoSetupFabric);
        return $queue;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Humus\Amqp\PluginManager\Callback
     * @throws Exception\RuntimeException
     */
    protected function getCallbackManager(ServiceLocatorInterface $serviceLocator)
    {
        if (null !== $this->callbackManager) {
            return $this->callbackManager;
        }

        if (!$serviceLocator->has('Humus\Amqp\PluginManager\Callback')) {
            throw new Exception\RuntimeException(
                'Humus\Amqp\PluginManager\Callback not found'
            );
        }

        $this->callbackManager = $serviceLocator->get('Humus\Amqp\PluginManager\Callback');
        return $this->callbackManager;
    }

    /**
     * @return QueueFactoryInterface
     */
    public function getQueueFactory()
    {
        if (null === $this->queueFactory) {
            $this->setQueueFactory(new QueueFactory());
        }
        return $this->queueFactory;
    }

    /**
     * @param QueueFactoryInterface $queueFactory
     */
    public function setQueueFactory(QueueFactoryInterface $queueFactory)
    {
        $this->queueFactory = $queueFactory;
    }

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $queueName
     * @return QueueSpecification
     */
    protected function getQueueSpec(ServiceLocatorInterface $serviceLocator, $queueName)
    {
        $config  = $this->getConfig($serviceLocator);
        $specs = new QueueSpecification($config['queues'][$queueName]);
        return $specs;
    }
}
