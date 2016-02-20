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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\BadMethodCallException;
use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Class AbstractAmqpConnection
 * @package Humus\Amqp\Driver\AmqpExtension
 */
abstract class AbstractAmqpConnection implements \Humus\Amqp\Driver\AmqpConnection
{
    /**
     * @var AbstractConnection
     */
    protected $connection;

    /**
     * @return AbstractConnection
     */
    public function getPhpAmqpLibConnection()
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return $this->connection->isConnected();
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function pconnect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function pdisconnect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        throw new BadMethodCallException();
    }

    /**
     * @inheritdoc
     */
    public function reconnect()
    {
        try {
            $this->connection->reconnect();
        } catch (\Exception $e) {
            throw AmqpConnectionException::fromPhpAmqpLib($e);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function preconnect()
    {
        throw new BadMethodCallException();
    }
}
