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

namespace  Humus\Amqp;

use Humus\Amqp\Exception\AmqpConnectionException;

/**
 * Represents a AMQP connection between PHP and a AMQP server.
 *
 * Interface AmqpConnection
 * @package Humus\Amqp
 */
interface AmqpConnection
{
    /**
     * Create an instance of AmqpConnection.
     *
     * Creates an AmqpConnection instance representing a connection to an AMQP
     * broker. A connection will not be established until
     * AmqpConnection::connect() is called.
     *
     * $credentials = array(
     *      'host'  => amqp.host The host to connect too. Note: Max 1024 characters.
     *      'port'  => amqp.port Port on the host.
     *      'vhost' => amqp.vhost The virtual host on the host. Note: Max 128 characters.
     *      'login' => amqp.login The login name to use. Note: Max 128 characters.
     *      'password' => amqp.password Password. Note: Max 128 characters.
     *      'read_timeout'  => Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
     *      'write_timeout' => Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
     *      'connect_timeout' => Connection timeout. Note: 0 or greater seconds. May be fractional.
     * )
     *
     * @param array $credentials Optional array of credential information for
     *                           connecting to the AMQP broker.
     * @throws AmqpConnectionException
     */
    public function __construct(array $credentials = []);

    /**
     * Check whether the connection to the AMQP broker is still valid.
     *
     * It does so by checking the return status of the last connect-command.
     *
     * @return bool True if connected, false otherwise.
     */
    public function isConnected() : bool;

    /**
     * Establish a transient connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker.
     *
     * @throws AmqpConnectionException
     * @return bool TRUE on success or throw an exception on failure.
     */
    public function connect() : bool;

    /**
     * Establish a persistent connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker
     * or reuse an existing one if present.
     *
     * @throws AmqpConnectionException
     * @return bool TRUE on success or throws an exception on failure.
     */
    public function pconnect() : bool;

    /**
     * Closes a persistent connection with the AMQP broker.
     *
     * This method will close an open persistent connection with the AMQP
     * broker.
     *
     * @return bool true if connection was found and closed,
     *                 false if no persistent connection with this host,
     *                 port, vhost and login could be found,
     */
    public function pdisconnect() : bool;

    /**
     * Closes the transient connection with the AMQP broker.
     *
     * This method will close an open connection with the AMQP broker.
     *
     * @return bool true if connection was successfully closed, false otherwise.
     */
    public function disconnect() : bool;

    /**
     * Close any open transient connections and initiate a new one with the AMQP broker.
     *
     * @throws AMQPConnectionException
     * @return bool TRUE on success or FALSE on failure.
     */
    public function reconnect() : bool;

    /**
     * Close any open persistent connections and initiate a new one with the AMQP broker.
     *
     * @throws AMQPConnectionException
     * @return bool TRUE on success or FALSE on failure.
     */
    public function preconnect() : bool;
}
