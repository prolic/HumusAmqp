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

namespace Humus\Amqp;

/**
 * Represents a AMQP channel between PHP and a AMQP server.
 *
 * Interface Channel
 * @package Humus\Amqp
 */
interface Channel
{
    /**
     * Check the channel connection.
     *
     * @return bool Indicates whether the channel is connected.
     */
    public function isConnected() : bool;

    /**
     * Return internal channel ID
     *
     * @return integer
     */
    public function getChannelId() : int;

    /**
     * Set the window size to prefetch from the broker.
     *
     * Set the prefetch window size, in octets, during a call to
     * Queue::consume() or Queue::get(). Any call to this method will
     * automatically set the prefetch message count to 0, meaning that the
     * prefetch message count setting will be ignored. If the call to either
     * Queue::consume() or Queue::get() is done with the Constants::AMQP_AUTOACK
     * flag set, this setting will be ignored.
     *
     * @param integer $size The window size, in octets, to prefetch.
     * @return void
     */
    public function setPrefetchSize(int $size);

    /**
     * Get the window size to prefetch from the broker.
     *
     * @return integer
     */
    public function getPrefetchSize() : int;

    /**
     * Set the number of messages to prefetch from the broker.
     *
     * Set the number of messages to prefetch from the broker during a call to
     * Queue::consume() or Queue::get(). Any call to this method will
     * automatically set the prefetch window size to 0, meaning that the
     * prefetch window size setting will be ignored.
     *
     * @param integer $count The number of messages to prefetch.
     * @return void
     */
    public function setPrefetchCount(int $count);

    /**
     * Get the number of messages to prefetch from the broker.
     *
     * @return integer
     */
    public function getPrefetchCount() : int;

    /**
     * Set the Quality Of Service settings for the given channel.
     *
     * Specify the amount of data to prefetch in terms of window size (octets)
     * or number of messages from a queue during a Queue::consume() or
     * Queue::get() method call. The client will prefetch data up to size
     * octets or count messages from the server, whichever limit is hit first.
     * Setting either value to 0 will instruct the client to ignore that
     * particular setting. A call to Channel::qos() will overwrite any
     * values set by calling Channel::setPrefetchSize() and
     * Channel::setPrefetchCount(). If the call to either
     * Queue::consume() or Queue::get() is done with the Constants::AMQP_AUTOACK
     * flag set, the client will not do any prefetching of data, regardless of
     * the QOS settings.
     *
     * @param integer $size  The window size, in octets, to prefetch.
     * @param integer $count The number of messages to prefetch.
     * @return void
     */
    public function qos(int $size, int $count);

    /**
     * Start a transaction.
     *
     * This method must be called on the given channel prior to calling
     * Channel::commitTransaction() or Channel::rollbackTransaction().
     *
     * @return void
     */
    public function startTransaction();

    /**
     * Commit a pending transaction.
     *
     * @return void
     */
    public function commitTransaction();

    /**
     * Rollback a transaction.
     *
     * Rollback an existing transaction. Channel::startTransaction() must
     * be called prior to this.
     *
     * @return void
     */
    public function rollbackTransaction();

    /**
     * Get the Connection object in use
     *
     * @return Connection
     */
    public function getConnection() : Connection;

    /**
     * Redeliver unacknowledged messages.
     *
     * @param bool $requeue
     * @return void
     */
    public function basicRecover(bool $requeue = true);

    /**
     * Set the channel to use publisher acknowledgements. This can only used on a non-transactional channel.
     *
     * @return void
     */
    public function confirmSelect();

    /**
     * Set callback to process basic.ack and basic.nac AMQP server methods (applicable when channel in confirm mode).
     *
     * @param callable|null $ackCallback
     * @param callable|null $nackCallback
     *
     * Callback functions with all arguments have the following signature:
     *
     *      function ack_callback(int $delivery_tag, bool $multiple) : bool;
     *      function nack_callback(int $delivery_tag, bool $multiple, bool $requeue) : bool;
     *
     * and should return boolean false when wait loop should be canceled.
     *
     * Note, basic.nack server method will only be delivered if an internal error occurs in the Erlang process
     * responsible for a queue (see https://www.rabbitmq.com/confirms.html for details).
     *
     * @return void
     */
    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null);

    /**
     * Wait until all messages published since the last call have been either ack'd or nack'd by the broker.
     *
     * Note, this method also catch all basic.return message from server.
     *
     * @param float $timeout Timeout in seconds. May be fractional.
     */
    public function waitForConfirm($timeout = 0.0);

    /**
     * Set callback to process basic.return AMQP server method
     *
     * @param callable|null $returnCallback
     *
     * Callback function with all arguments has the following signature:
     *
     *      function callback(int $reply_code,
     *                        string $reply_text,
     *                        string $exchange,
     *                        string $routing_key,
     *                        AMQPBasicProperties $properties,
     *                        string $body) : bool;
     *
     * and should return boolean false when wait loop should be canceled.
     *
     */
    public function setReturnCallback(callable $returnCallback = null);

    /**
     * Start wait loop for basic.return AMQP server methods
     *
     * @param float $timeout Timeout in seconds. May be fractional.
     */
    public function waitForBasicReturn($timeout = 0.0);
}
