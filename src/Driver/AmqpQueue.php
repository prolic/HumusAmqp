<?php

namespace Humus\Amqp\Driver;

use Humus\Amqp\Exception\AmqpChannelException;
use Humus\Amqp\Exception\AmqpConnectionException;
use Humus\Amqp\Exception\AmqpQueueException;

/**
 * Represents a AMQP queue
 *
 * Interface AmqpQueue
 * @package Humus\Amqp\Driver
 */
interface AmqpQueue
{
    /**
     * Get the configured name.
     *
     * @return string The configured name as a string.
     */
    public function getName();

    /**
     * Set the queue name.
     *
     * @param string $queueName The name of the queue.
     *
     * @return boolean
     */
    public function setName($queueName);

    /**
     * Get all the flags currently set on the given queue.
     *
     * @return int An integer bitmask of all the flags currently set on this
     *             exchange object.
     */
    public function getFlags();

    /**
     * Set the flags on the queue.
     *
     * @param integer $flags A bitmask of flags:
     *                       AMQP_DURABLE, AMQP_PASSIVE,
     *                       AMQP_EXCLUSIVE, AMQP_AUTODELETE.
     *
     * @return boolean
     */
    public function setFlags($flags);

    /**
     * Get the argument associated with the given key.
     *
     * @param string $key The key to look up.
     *
     * @return string|integer|boolean The string or integer value associated
     *                                with the given key, or false if the key
     *                                is not set.
     */
    public function getArgument($key);

    /**
     * Get all set arguments as an array of key/value pairs.
     *
     * @return array An array containing all of the set key/value pairs.
     */
    public function getArguments();

    /**
     * Set a queue argument.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     *
     * @return boolean
     */
    public function setArgument($key, $value);

    /**
     * Set all arguments on the given queue.
     *
     * All other argument settings will be wiped.
     *
     * @param array $arguments An array of key/value pairs of arguments.
     *
     * @return boolean
     */
    public function setArguments(array $arguments);

    /**
     * Declare a new queue on the broker.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return integer the message count.
     */
    public function declareQueue();

    /**
     * Bind the given queue to a routing key on an exchange.
     *
     * @param string $exchangeName Name of the exchange to bind to.
     * @param string $routingKey   Pattern or routing key to bind with.
     * @param array  $arguments     Additional binding arguments.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function bind($exchangeName, $routingKey = null, array $arguments = []);

    /**
     * Retrieve the next message from the queue.
     *
     * Retrieve the next available message from the queue. If no messages are
     * present in the queue, this function will return FALSE immediately. This
     * is a non blocking alternative to the AmqpQueue::consume() method.
     * Currently, the only supported flag for the flags parameter is
     * AMQP_AUTOACK. If this flag is passed in, then the message returned will
     * automatically be marked as acknowledged by the broker as soon as the
     * frames are sent to the client.
     *
     * @param integer $flags A bitmask of supported flags for the
     *                       method call. Currently, the only the
     *                       supported flag is AMQP_AUTOACK. If this
     *                       value is not provided, it will use the
     *                       value of ini-setting amqp.auto_ack.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return AmqpEnvelope|boolean
     */
    public function get($flags = AMQP_NOPARAM);

    /**
     * Consume messages from a queue.
     *
     * Blocking function that will retrieve the next message from the queue as
     * it becomes available and will pass it off to the callback.
     *
     * @param callable | null $callback    A callback function to which the
     *                              consumed message will be passed. The
     *                              function must accept at a minimum
     *                              one parameter, an AmqpEnvelope object,
     *                              and an optional second parameter
     *                              the AmqpQueue object from which callback
     *                              was invoked. The AmqpQueue::consume() will
     *                              not return the processing thread back to
     *                              the PHP script until the callback
     *                              function returns FALSE.
     *                              If the callback is omitted or null is passed,
     *                              then the messages delivered to this client will
     *                              be made available to the first real callback
     *                              registered. That allows one to have a single
     *                              callback consuming from multiple queues.
     * @param integer  $flags       A bitmask of any of the flags: AMQP_AUTOACK.
     * @param string   $consumerTag A string describing this consumer. Used
     *                              for canceling subscriptions with cancel().
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return void
     */
    public function consume(callable $callback = null, $flags = AMQP_NOPARAM, $consumerTag = null);

    /**
     * Acknowledge the receipt of a message.
     *
     * This method allows the acknowledgement of a message that is retrieved
     * without the AMQP_AUTOACK flag through AmqpQueue::get() or
     * AmqpQueue::consume()
     *
     * @param string  $deliveryTag The message delivery tag of which to
     *                              acknowledge receipt.
     * @param integer $flags        The only valid flag that can be passed is
     *                              AMQP_MULTIPLE.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function ack($deliveryTag, $flags = AMQP_NOPARAM);

    /**
     * Mark a message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AmqpQueue::consume() and AmqpQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible. When called, the broker will immediately put the
     * message back onto the queue, instead of waiting until the connection is
     * closed. This method is only supported by the RabbitMQ broker. The
     * behavior of calling this method while connected to any other broker is
     * undefined.
     *
     * @param string  $deliveryTag Delivery tag of last message to reject.
     * @param integer $flags        AMQP_REQUEUE to requeue the message(s),
     *                              AMQP_MULTIPLE to nack all previous
     *                              unacked messages as well.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function nack($deliveryTag, $flags = AMQP_NOPARAM);

    /**
     * Mark one message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AmqpQueue::consume() and AmqpQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible.
     *
     * @param string  $deliveryTag Delivery tag of the message to reject.
     * @param integer $flags        AMQP_REQUEUE to requeue the message(s).
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function reject($deliveryTag, $flags = AMQP_NOPARAM);

    /**
     * Purge the contents of a queue.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function purge();

    /**
     * Cancel a queue that is already bound to an exchange and routing key.
     *
     * @param string $consumerTag The queue name to cancel, if the queue
     *                             object is not already representative of
     *                             a queue.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return bool;
     */
    public function cancel($consumerTag = '');

    /**
     * Remove a routing key binding on an exchange from the given queue.
     *
     * @param string $exchangeName The name of the exchange on which the
     *                              queue is bound.
     * @param string $routingKey   The binding routing key used by the
     *                              queue.
     * @param array  $arguments     Additional binding arguments.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean
     */
    public function unbind($exchangeName, $routingKey = null, array $arguments = []);

    /**
     * Delete a queue from the broker.
     *
     * This includes its entire contents of unread or unacknowledged messages.
     *
     * @param integer $flags        Optionally AMQP_IFUNUSED can be specified
     *                              to indicate the queue should not be
     *                              deleted until no clients are connected to
     *                              it.
     *
     * @throws AmqpChannelException    If the channel is not open.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return integer The number of deleted messages.
     */
    public function delete($flags = AMQP_NOPARAM);

    /**
     * Get the AmqpChannel object in use
     *
     * @return AmqpChannel
     */
    public function getChannel();

    /**
     * Get the AmqpConnection object in use
     *
     * @return AmqpConnection
     */
    public function getConnection();
}
