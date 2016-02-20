<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic
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

namespace Humus\Amqp\Container;

use AMQPChannel;
use AMQPQueue;
use Humus\Amqp\Constants;
use Humus\Amqp\Exception;
use Interop\Container\ContainerInterface;

/**
 * Class QueueFactory
 * @package Humus\Amqp\Container
 */
final class QueueFactory extends AbstractFactory
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var bool
     */
    private $autoSetupFabric;

    /**
     * QueueFactory constructor.
     * @param AMQPChannel $channel
     * @param string $queueName
     * @param string $connectionName
     * @param bool $autoSetupFabric
     */
    public function __construct(AMQPChannel $channel, $queueName, $connectionName, $autoSetupFabric)
    {
        $this->channel = $channel;
        $this->queueName = $queueName;
        $this->connectionName = $connectionName;
        $this->autoSetupFabric = $autoSetupFabric;
    }

    /**
     * @param ContainerInterface $container
     * @return AMQPQueue
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $options = $this->options($config);

        if ($options['connection'] !== $this->connectionName) {
            throw new Exception\InvalidArgumentException(
                sprintf('The queue\'s connection %s does not match the provided connection %s',
                    $options['connection'],
                    $this->connectionName
                )
            );
        }

        $queue = new AMQPQueue($this->channel);

        if (null !== $options['name']) {
            $queue->setName($options['name']);
        }

        $queue->setFlags($this->getFlags($options));
        $queue->setArguments($options['arguments']);

        if ($this->autoSetupFabric) {
            $queue->declareQueue();

            $routingKeys = $options['routing_keys'];
            if (empty($routingKeys)) {
                $queue->bind($options['exchange'], null, $options['bind_arguments']);
            } else {
                foreach ($routingKeys as $routingKey) {
                    $queue->bind($options['exchange'], $routingKey, $options['bind_arguments']);
                }
            }
        }

        return $queue;
    }

    /**
     * @return string
     */
    public function componentName()
    {
        return 'queue';
    }

    /**
     * @return string
     */
    public function elementName()
    {
        return $this->queueName;
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'connection' => 'default',
            'name' => null,
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
            'arguments' => [],
            'routing_keys' => [],
            'bind_arguments' => [],
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions()
    {
        return [
            'connection',
            'name',
            'exchange',
            'passive',
            'durable',
            'exclusive',
            'auto_delete',
            'arguments',
            'routing_keys',
            'bind_arguments',
        ];
    }

    /**
     * @param array|ArrayAccess
     * @return int
     */
    public function getFlags($options)
    {
        $flags = 0;
        $flags |= $options['passive'] ? Constants::AMQP_PASSIVE : 0;
        $flags |= $options['durable'] ? Constants::AMQP_DURABLE : 0;
        $flags |= $options['exclusive'] ? Constants::AMQP_EXCLUSIVE : 0;
        $flags |= $options['auto_delete'] ? Constants::AMQP_AUTODELETE : 0;

        return $flags;
    }
}
