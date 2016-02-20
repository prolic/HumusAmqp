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

namespace Humus\Amqp\Container;

use AMQPConnection;
use Interop\Container\ContainerInterface;

/**
 * Class ConnectionFactory
 * @package Humus\Amqp\Container
 */
final class ConnectionFactory extends AbstractFactory
{
    /**
     * @var string
     */
    private $connectionName;

    /**
     * ConnectionFactory constructor.
     * @param string $connectionName
     */
    public function __construct($connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * @return string
     */
    public function elementName()
    {
        return $this->connectionName;
    }

    /**
     * @param ContainerInterface $container
     * @return AMQPConnection
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $options = $this->options($config);

        $connection = new AMQPConnection($options);

        if (true === $options['persistent']) {
            $connection->pconnect();
        } else {
            $connection->connect();
        }

        return $connection;
    }

    /**
     * @return string
     */
    public function componentName()
    {
        return 'connection';
    }

    /**
     * @return array
     */
    public function defaultOptions()
    {
        return [
            'host' => 'localhost',
            'port' => 5672,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'persistent' => true,
            'read_timeout' => 1, //sec, float allowed
            'write_timeout' => 1, //sec, float allowed
        ];
    }

    /**
     * return array
     */
    public function mandatoryOptions()
    {
        return [
            'host',
            'port',
            'login',
            'password',
            'vhost',
            'persistent',
            'read_timeout',
            'write_timeout',
        ];
    }
}
