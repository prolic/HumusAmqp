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

/**
 * Represents a AMQP channel between PHP and a AMQP server.
 *
 * Interface AmqpChannel
 * @package Humus\Amqp\Driver
 */
interface AmqpChannel
{
    /**
     * Check the channel connection.
     *
     * @return bool Indicates whether the channel is connected.
     */
    public function isConnected();

    /**
     * Return internal channel ID
     *
     * @return integer
     */
    public function getChannelId();

    /**
     * Set the window size to prefetch from the broker.
     *
     * Set the prefetch window size, in octets, during a call to
     * AMQPQueue::consume() or AMQPQueue::get(). Any call to this method will
     * automatically set the prefetch message count to 0, meaning that the
     * prefetch message count setting will be ignored. If the call to either
     * AMQPQueue::consume() or AMQPQueue::get() is done with the AMQP_AUTOACK
     * flag set, this setting will be ignored.
     *
     * @param integer $size The window size, in octets, to prefetch.
     *
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setPrefetchSize($size);

    /**
     * Get the window size to prefetch from the broker.
     *
     * @return integer
     */
    public function getPrefetchSize();

    /**
     * Set the number of messages to prefetch from the broker.
     *
     * Set the number of messages to prefetch from the broker during a call to
     * AMQPQueue::consume() or AMQPQueue::get(). Any call to this method will
     * automatically set the prefetch window size to 0, meaning that the
     * prefetch window size setting will be ignored.
     *
     * @param integer $count The number of messages to prefetch.
     *
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setPrefetchCount($count);

    /**
     * Get the number of messages to prefetch from the broker.
     *
     * @return integer
     */
    public function getPrefetchCount();

    /**
     * Set the Quality Of Service settings for the given channel.
     *
     * Specify the amount of data to prefetch in terms of window size (octets)
     * or number of messages from a queue during a AMQPQueue::consume() or
     * AMQPQueue::get() method call. The client will prefetch data up to size
     * octets or count messages from the server, whichever limit is hit first.
     * Setting either value to 0 will instruct the client to ignore that
     * particular setting. A call to AMQPChannel::qos() will overwrite any
     * values set by calling AMQPChannel::setPrefetchSize() and
     * AmqpChannel::setPrefetchCount(). If the call to either
     * AMQPQueue::consume() or AMQPQueue::get() is done with the AMQP_AUTOACK
     * flag set, the client will not do any prefetching of data, regardless of
     * the QOS settings.
     *
     * @param integer $size  The window size, in octets, to prefetch.
     * @param integer $count The number of messages to prefetch.
     *
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function qos($size, $count);

    /**
     * Start a transaction.
     *
     * This method must be called on the given channel prior to calling
     * AmqpChannel::commitTransaction() or AmqpChannel::rollbackTransaction().
     *
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function startTransaction();

    /**
     * Commit a pending transaction.
     *
     * @throws AmqpChannelException    If no transaction was started prior to
     *                                 calling this method.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commitTransaction();

    /**
     * Rollback a transaction.
     *
     * Rollback an existing transaction. AmqpChannel::startTransaction() must
     * be called prior to this.
     *
     * @throws AmqpChannelException    If no transaction was started prior to
     *                                 calling this method.
     * @throws AmqpConnectionException If the connection to the broker was lost.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollbackTransaction();

    /**
     * Get the AmqpConnection object in use
     *
     * @return AmqpConnection
     */
    public function getConnection();

    /**
     * Redeliver unacknowledged messages.
     *
     * @param bool $requeue
     */
    public function basicRecover($requeue = true);
}
