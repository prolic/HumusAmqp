<?php

namespace  Humus\Amqp\Driver;

use Humus\Amqp\Exception\AmqpConnectionException;

/**
 * Represents a AMQP connection between PHP and a AMQP server.
 * 
 * Interface AmqpConnection
 * @package Humus\Amqp\Driver
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
     *  $credentials = array(
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
     * @return boolean True if connected, false otherwise.
     */
    public function isConnected();

    /**
     * Establish a transient connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker.
     *
     * @throws AmqpConnectionException
     * @return boolean TRUE on success or throw an exception on failure.
     */
    public function connect();

    /**
     * Establish a persistent connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker
     * or reuse an existing one if present.
     *
     * @throws AmqpConnectionException
     * @return boolean TRUE on success or throws an exception on failure.
     */
    public function pconnect();

    /**
     * Closes a persistent connection with the AMQP broker.
     *
     * This method will close an open persistent connection with the AMQP
     * broker.
     *
     * @return boolean true if connection was found and closed,
     *                 false if no persistent connection with this host,
     *                 port, vhost and login could be found,
     */
    public function pdisconnect();

    /**
     * Closes the transient connection with the AMQP broker.
     *
     * This method will close an open connection with the AMQP broker.
     *
     * @return boolean true if connection was successfully closed, false otherwise.
     */
    public function disconnect();

    /**
     * Close any open transient connections and initiate a new one with the AMQP broker.
     *
     * @throws AMQPConnectionException
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function reconnect();

    /**
     * Close any open persistent connections and initiate a new one with the AMQP broker.
     *
     * @throws AMQPConnectionException
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function preconnect();

    /**
     * Get the configured login.
     *
     * @return string The configured login as a string.
     */
    public function getLogin();

    /**
     * Set the login string used to connect to the AMQP broker.
     *
     * @param string $login The login string used to authenticate
     *                      with the AMQP broker.
     *
     * @throws AmqpConnectionException If login is longer then 32 characters.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setLogin($login);

    /**
     * Get the configured password.
     *
     * @return string The configured password as a string.
     */
    public function getPassword();

    /**
     * Set the password string used to connect to the AMQP broker.
     *
     * @param string $password The password string used to authenticate
     *                         with the AMQP broker.
     *
     * @throws AmqpConnectionException If password is longer then 32characters.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setPassword($password);

    /**
     * Get the configured host.
     *
     * @return string The configured hostname of the broker
     */
    public function getHost();

    /**
     * Set the hostname used to connect to the AMQP broker.
     *
     * @param string $host The hostname of the AMQP broker.
     *
     * @throws AmqpConnectionException If host is longer then 1024 characters.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setHost($host);

    /**
     * Get the configured port.
     *
     * @return int The configured port as an integer.
     */
    public function getPort();

    /**
     * Set the port used to connect to the AMQP broker.
     *
     * @param integer $port The port used to connect to the AMQP broker.
     *
     * @throws AmqpConnectionException If port is longer not between
     *                                 1 and 65535.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setPort($port);

    /**
     * Get the configured vhost.
     *
     * @return string The configured virtual host as a string.
     */
    public function getVhost();

    /**
     * Sets the virtual host to which to connect on the AMQP broker.
     *
     * @param string $vhost The virtual host to use on the AMQP
     *                      broker.
     *
     * @throws AmqpConnectionException If host is longer then 32 characters.
     *
     * @return boolean true on success or false on failure.
     */
    public function setVhost($vhost);

    /**
     * Get the configured interval of time to wait for income activity
     * from AMQP broker
     *
     * @return float
     */
    public function getReadTimeout();

    /**
     * Sets the interval of time to wait for income activity from AMQP broker
     *
     * @param int $timeout
     *
     * @return bool
     */
    public function setReadTimeout($timeout);

    /**
     * Get the configured interval of time to wait for outcome activity
     * to AMQP broker
     *
     * @return float
     */
    public function getWriteTimeout();

    /**
     * Sets the interval of time to wait for outcome activity to AMQP broker
     *
     * @param int $timeout
     *
     * @return bool
     */
    public function setWriteTimeout($timeout);

    /**
     * Return last used channel id during current connection session.
     *
     * @return int
     */
    public function getUsedChannels();

    /**
     * Get the maximum number of channels the connection can handle.
     *
     * @return int|null
     */
    public function getMaxChannels();

    /**
     * Whether connection persistent.
     *
     * @return bool|null
     */
    public function isPersistent();
}
