<?php
/**
 * Copyright (c) 2016-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
 *
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

declare(strict_types=1);

namespace Humus\Amqp;

use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exception\QueueException;

/**
 * Represents a AMQP queue
 *
 * Interface Queue
 * @package Humus\Amqp
 */
interface Queue
{
    /**
     * Get the configured name.
     *
     * @return string The configured name as a string.
     */
    public function getName(): string;

    /**
     * Set the queue name.
     *
     * @param string $queueName The name of the queue.
     *
     * @return void
     */
    public function setName(string $queueName);

    /**
     * Get all the flags currently set on the given queue.
     *
     * @return int An integer bitmask of all the flags currently set on this
     *             exchange object.
     */
    public function getFlags(): int;

    /**
     * Set the flags on the queue.
     *
     * @param int $flags A bitmask of flags:
     *                       Constants::AMQP_DURABLE, Constants::AMQP_PASSIVE,
     *                       Constants::AMQP_EXCLUSIVE, Constants::AMQP_AUTODELETE.
     * @return void
     */
    public function setFlags(int $flags);

    /**
     * Get the argument associated with the given key.
     *
     * @param string $key The key to look up.
     * @return string|int|bool The string or integer value associated
     *                                with the given key, or false if the key
     *                                is not set.
     */
    public function getArgument(string $key);

    /**
     * Get all set arguments as an array of key/value pairs.
     *
     * @return array An array containing all of the set key/value pairs.
     */
    public function getArguments(): array;

    /**
     * Set a queue argument.
     *
     * @param string $key   The key to set.
     * @param mixed  $value The value to set.
     * @return void
     */
    public function setArgument(string $key, $value);

    /**
     * Set all arguments on the given queue.
     *
     * All other argument settings will be wiped.
     *
     * @param array $arguments An array of key/value pairs of arguments.
     * @return void
     */
    public function setArguments(array $arguments);

    /**
     * Declare a new queue on the broker.
     *
     * @return int the message count.
     * @throws QueueException
     * @throws ChannelException
     */
    public function declareQueue(): int;

    /**
     * Bind the given queue to a routing key on an exchange.
     *
     * @param string $exchangeName Name of the exchange to bind to.
     * @param string $routingKey   Pattern or routing key to bind with.
     * @param array  $arguments     Additional binding arguments.
     *
     * @return void
     */
    public function bind(string $exchangeName, string $routingKey = '', array $arguments = []);

    /**
     * Retrieve the next message from the queue.
     *
     * Retrieve the next available message from the queue. If no messages are
     * present in the queue, this function will return FALSE immediately. This
     * is a non blocking alternative to the Queue::consume() method.
     * Currently, the only supported flag for the flags parameter is
     * Constants::AMQP_AUTOACK. If this flag is passed in, then the message returned will
     * automatically be marked as acknowledged by the broker as soon as the
     * frames are sent to the client.
     *
     * @param int $flags A bitmask of supported flags for the
     *                       method call. Currently, the only the
     *                       supported flag is Constants::AMQP_AUTOACK. If this
     *                       value is not provided, it will use the
     *                       value of ini-setting amqp.auto_ack.
     *
     * @return Envelope|null
     */
    public function get(int $flags = Constants::AMQP_NOPARAM): ?Envelope;

    /**
     * Consume messages from a queue.
     *
     * Blocking function that will retrieve the next message from the queue as
     * it becomes available and will pass it off to the callback.
     *
     * @param callable | null $callback    A callback function to which the
     *                              consumed message will be passed. The
     *                              function must accept at a minimum
     *                              one parameter, an Envelope object,
     *                              and an optional second parameter
     *                              the Queue object from which callback
     *                              was invoked. The Queue::consume() will
     *                              not return the processing thread back to
     *                              the PHP script until the callback
     *                              function returns FALSE.
     *                              If the callback is omitted or null is passed,
     *                              then the messages delivered to this client will
     *                              be made available to the first real callback
     *                              registered. That allows one to have a single
     *                              callback consuming from multiple queues.
     * @param int  $flags       A bitmask of any of the flags: Constants::AMQP_AUTOACK.
     * @param string   $consumerTag A string describing this consumer. Used
     *                              for canceling subscriptions with cancel().
     * @return void
     * @throws Exception\QueueException
     *
     * Callback function with all arguments have the following signature:
     *
     *      function callback(Envelope $envelope, Queue $queue = null): bool;
     */
    public function consume(?callable $callback = null, int $flags = Constants::AMQP_NOPARAM, string $consumerTag = '');

    /**
     * Acknowledge the receipt of a message.
     *
     * This method allows the acknowledgement of a message that is retrieved
     * without the Constants::AMQP_AUTOACK flag through Queue::get() or
     * Queue::consume()
     *
     * @param int  $deliveryTag The message delivery tag of which to
     *                              acknowledge receipt.
     * @param int $flags        The only valid flag that can be passed is
     *                              Constants::AMQP_MULTIPLE.
     * @return void
     */
    public function ack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM);

    /**
     * Mark a message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * Queue::consume() and Queue::get() and using the Constants::AMQP_AUTOACK
     * flag are not eligible. When called, the broker will immediately put the
     * message back onto the queue, instead of waiting until the connection is
     * closed. This method is only supported by the RabbitMQ broker. The
     * behavior of calling this method while connected to any other broker is
     * undefined.
     *
     * @param int     $deliveryTag Delivery tag of last message to reject.
     * @param int $flags        Constants::AMQP_REQUEUE to requeue the message(s),
     *                              Constants::AMQP_MULTIPLE to nack all previous
     *                              unacked messages as well.
     * @return void
     */
    public function nack(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM);

    /**
     * Mark one message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * Queue::consume() and Queue::get() and using the Constants::AMQP_AUTOACK
     * flag are not eligible.
     *
     * @param int     $deliveryTag Delivery tag of the message to reject.
     * @param int $flags        Constants::AMQP_REQUEUE to requeue the message(s).
     * @return void
     */
    public function reject(int $deliveryTag, int $flags = Constants::AMQP_NOPARAM);

    /**
     * Purge the contents of a queue.
     *
     * @return void
     */
    public function purge();

    /**
     * Cancel a queue that is already bound to an exchange and routing key.
     *
     * @param string $consumerTag The queue name to cancel, if the queue
     *                             object is not already representative of
     *                             a queue.
     * @return void;
     */
    public function cancel(string $consumerTag = '');

    /**
     * Remove a routing key binding on an exchange from the given queue.
     *
     * @param string $exchangeName The name of the exchange on which the
     *                              queue is bound.
     * @param string $routingKey   The binding routing key used by the
     *                              queue.
     * @param array  $arguments     Additional binding arguments.
     * @return void
     */
    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []);

    /**
     * Delete a queue from the broker.
     *
     * This includes its entire contents of unread or unacknowledged messages.
     *
     * @param int $flags            Optionally Constants::AMQP_IFUNUSED can be specified
     *                              to indicate the queue should not be
     *                              deleted until no clients are connected to
     *                              it.
     * @return void
     */
    public function delete(int $flags = Constants::AMQP_NOPARAM);

    /**
     * Get the Channel object in use
     *
     * @return Channel
     */
    public function getChannel(): Channel;

    /**
     * Get the Connection object in use
     *
     * @return Connection
     */
    public function getConnection(): Connection;
}
