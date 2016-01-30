<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace Humus\Amqp\Driver;

use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpExchangeException;

/**
 * Represents a AMQP exchange
 * 
 * Interface AmqpExchange
 * @package Humus\Amqp\Driver
 */
interface AmqpExchange
{
    /**
     * Get the configured name.
     *
     * @return string The configured name as a string.
     */
    public function getName();

    /**
     * Set the name of the exchange.
     *
     * @param string $exchangeName The name of the exchange to set as string.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setName($exchangeName);

    /**
     * Get the configured type.
     *
     * @return string The configured type as a string.
     */
    public function getType();

    /**
     * Set the type of the exchange.
     *
     * Set the type of the exchange. This can be any of AMQP_EX_TYPE_DIRECT,
     * AMQP_EX_TYPE_FANOUT, AMQP_EX_TYPE_HEADERS or AMQP_EX_TYPE_TOPIC.
     *
     * @param string $exchangeType The type of exchange as a string.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setType($exchangeType);

    /**
     * Get all the flags currently set on the given exchange.
     *
     * @return int An integer bitmask of all the flags currently set on this
     *             exchange object.
     */
    public function getFlags();

    /**
     * Set the flags on an exchange.
     *
     * @param integer $flags A bitmask of flags. This call currently only
     *                       considers the following flags:
     *                       AMQP_DURABLE, AMQP_PASSIVE
     *                       (and AMQP_DURABLE, if librabbitmq version >= 0.5.3)
     *
     * @return boolean True on success or false on failure.
     */
    public function setFlags($flags);

    /**
     * Get the argument associated with the given key.
     *
     * @param string $key The key to look up.
     *
     * @return string|integer|boolean The string or integer value associated
     *                                with the given key, or FALSE if the key
     *                                is not set.
     */
    public function getArgument($key);

    /**
     * Get all arguments set on the given exchange.
     *
     * @return array An array containing all of the set key/value pairs.
     */
    public function getArguments();

    /**
     * Set the value for the given key.
     *
     * @param string         $key   Name of the argument to set.
     * @param string|integer $value Value of the argument to set.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setArgument($key, $value);

    /**
     * Set all arguments on the exchange.
     *
     * @param array $arguments An array of key/value pairs of arguments.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setArguments(array $arguments);

    /**
     * Declare a new exchange on the broker.
     *
     * @throws AmqpExchangeException   On failure.
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function declareExchange();

    /**
     * Delete the exchange from the broker.
     *
     * @param string  $exchangeName Optional name of exchange to delete.
     * @param integer $flags        Optionally AMQP_IFUNUSED can be specified
     *                              to indicate the exchange should not be
     *                              deleted until no clients are connected to
     *                              it.
     *
     * @throws AmqpExchangeException   On failure.
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean true on success or false on failure.
     */
    public function delete($exchangeName = null, $flags = AMQP_NOPARAM);

    /**
     * Bind to another exchange.
     *
     * Bind an exchange to another exchange using the specified routing key.
     *
     * @param string $exchangeName Name of the exchange to bind.
     * @param string $routingKey   The routing key to use for binding.
     * @param array  $arguments     Additional binding arguments.
     *
     * @throws AmqpExchangeException   On failure.
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     * @return boolean true on success or false on failure.
     */
    public function bind($exchangeName, $routingKey = '', array $arguments = array());

    /**
     * Remove binding to another exchange.
     *
     * Remove a routing key binding on an another exchange from the given exchange.
     *
     * @param string $exchangeName Name of the exchange to bind.
     * @param string $routingKey   The routing key to use for binding.
     * @param array  $arguments     Additional binding arguments.
     *
     * @throws AmqpExchangeException   On failure.
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     * @return boolean true on success or false on failure.
     */
    public function unbind($exchangeName, $routingKey = '', array $arguments = array());

    /**
     * Publish a message to an exchange.
     *
     * Publish a message to the exchange represented by the AMQPExchange object.
     *
     * @param string  $message     The message to publish.
     * @param string  $routingKey The optional routing key to which to
     *                             publish to.
     * @param integer $flags       One or more of AMQP_MANDATORY and
     *                             AMQP_IMMEDIATE.
     * @param array   $attributes  One of content_type, content_encoding,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration, type
     *                             or reply_to, headers.
     *
     * @throws AmqpExchangeException   On failure.
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function publish($message, $routingKey = null, $flags = AMQP_NOPARAM, array $attributes = []);

    /**
     * Get the AmqpChannel object in use
     *
     * @return AmqpChannel
     */
    public function getChannel();

    /**
     * Get the AmqpConnection object in use
     *
     * @return AMQPConnection
     */
    public function getConnection();    
}
