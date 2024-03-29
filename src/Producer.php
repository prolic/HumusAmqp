<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

use JsonSerializable;

interface Producer
{
    /**
     * Publish a message
     *
     * @param string|JsonSerializable  $message     The message to publish.
     * @param string  $routingKey  The optional routing key to which to
     *                             publish to.
     * @param int     $flags       One or more of Constants::AMQP_MANDATORY and
     *                             Constants::AMQP_IMMEDIATE.
     * @param array   $attributes  One of content_type, content_encoding,
     *                             correlation_id, reply_to, headers,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration or type.
     * @return void
     */
    public function publish(
        $message,
        string $routingKey = '',
        int $flags = Constants::AMQP_NOPARAM,
        array $attributes = []
    );

    /**
     * Start a transaction.
     *
     * This method must be called on the given channel prior to calling
     * Producer::commitTransaction() or Producer::rollbackTransaction().
     */
    public function startTransaction(): void;

    /**
     * Commit a pending transaction.
     */
    public function commitTransaction(): void;

    /**
     * Rollback a transaction.
     *
     * Rollback an existing transaction. Producer::startTransaction() must
     * be called prior to this.
     */
    public function rollbackTransaction(): void;

    /**
     * Set the channel to use publisher acknowledgements. This can only used on a non-transactional channel.
     */
    public function confirmSelect(): void;

    /**
     * Set callback to process basic.ack and basic.nac AMQP server methods (applicable when channel in confirm mode).
     *
     * Callback functions with all arguments have the following signature:
     *
     *      function ackCallback(int $deliveryTag, bool $multiple): bool;
     *      function nackCallback(int $deliveryTag, bool $multiple, bool $requeue): bool;
     *
     * and should return boolean false when wait loop should be canceled.
     *
     * Note, basic.nack server method will only be delivered if an internal error occurs in the Erlang process
     * responsible for a queue (see https://www.rabbitmq.com/confirms.html for details).
     */
    public function setConfirmCallback(?callable $ackCallback = null, ?callable $nackCallback = null): void;

    /**
     * Wait until all messages published since the last call have been either ack'd or nack'd by the broker.
     *
     * Note, this method also catch all basic.return message from server.
     */
    public function waitForConfirm(float $timeout = 0.0): void;

    /**
     * Set callback to process basic.return AMQP server method
     *
     * Callback function with all arguments has the following signature:
     *
     *      function callback(int $replyCode,
     *                        string $replyText,
     *                        string $exchange,
     *                        string $routingKey,
     *                        Envelope $envelope,
     *                        string $body): bool;
     *
     * and should return boolean false when wait loop should be canceled.
     *
     */
    public function setReturnCallback(?callable $returnCallback = null): void;

    /**
     * Start wait loop for basic.return AMQP server methods
     */
    public function waitForBasicReturn(float $timeout = 0.0): void;
}
