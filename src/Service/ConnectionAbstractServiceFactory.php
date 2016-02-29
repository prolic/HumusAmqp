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

use AMQPConnection;
use Humus\Amqp\ConnectionOptions;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Instantiates an AMQPConnection and created the connection
 * (persistent or not-persistent) dependent on specification
 */
class ConnectionAbstractServiceFactory extends AbstractAmqpAbstractServiceFactory
{
    /**
     * @var string Second-level configuration key indicating connection configuration
     */
    protected $subConfigKey = 'connections';

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param string $requestedName
     * @return AMQPConnection
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config  = $this->getConfig($serviceLocator);

        $options = new ConnectionOptions($config[$this->subConfigKey][$requestedName]);
        $connection = new AMQPConnection($options->toArray());

        if ($options->getPersistent()) {
            $connection->persistent = true;
        }

        return $connection;
    }
}
