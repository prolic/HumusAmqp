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
 * Interface Producer
 * @package Humus\Amqp
 */
interface Producer
{
    /**
     * @param string  $message     The message to publish.
     * @param string  $routingKey  The optional routing key to which to
     *                             publish to.
     * @param integer $flags       One or more of Constants::AMQP_MANDATORY and
     *                             Constants::AMQP_IMMEDIATE.
     * @param array   $attributes  One of content_type, content_encoding,
     *                             correlation_id, reply_to, headers,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration or type.
     */
    public function publish($message, $routingKey = null, $flags = Constants::AMQP_NOPARAM, array $attributes = []);

    /**
     * Start a transaction.
     *
     * This method must be called on the given channel prior to calling
     * Producer::commitTransaction() or Producer::rollbackTransaction().
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function startTransaction();

    /**
     * Commit a pending transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commitTransaction();

    /**
     * Rollback a transaction.
     *
     * Rollback an existing transaction. Producer::startTransaction() must
     * be called prior to this.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollbackTransaction();

    /**
     * @return array
     */
    public static function defaultAttributes();
}
