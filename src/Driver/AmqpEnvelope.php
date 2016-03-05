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

namespace Humus\Amqp\Driver;

/**
 * Represents a AMQP envelope
 *
 * Interface AmqpEnvelope
 * @package Humus\Amqp\Driver
 */
interface AmqpEnvelope
{
    /**
     * Get the body of the message.
     *
     * @return string The contents of the message body.
     */
    public function getBody() : string;

    /**
     * Get the routing key of the message.
     *
     * @return string The message routing key.
     */
    public function getRoutingKey() : string;

    /**
     * Get the delivery tag of the message.
     *
     * @return string The delivery tag of the message.
     */
    public function getDeliveryTag() : string;

    /**
     * Get the delivery mode of the message.
     *
     * @return integer The delivery mode of the message.
     */
    public function getDeliveryMode() : int;

    /**
     * Get the exchange name on which the message was published.
     *
     * @return string The exchange name on which the message was published.
     */
    public function getExchangeName() : string;

    /**
     * Whether this is a redelivery of the message.
     *
     * Whether this is a redelivery of a message. If this message has been
     * delivered and AmqpEnvelope::nack() was called, the message will be put
     * back on the queue to be redelivered, at which point the message will
     * always return TRUE when this method is called.
     *
     * @return bool TRUE if this is a redelivery, FALSE otherwise.
     */
    public function isRedelivery() : bool;

    /**
     * Get the message content type.
     *
     * @return string The content type of the message.
     */
    public function getContentType() : string;

    /**
     * Get the content encoding of the message.
     *
     * @return string The content encoding of the message.
     */
    public function getContentEncoding() : string;

    /**
     * Get the message type.
     *
     * @return string The message type.
     */
    public function getType() : string;

    /**
     * Get the timestamp of the message.
     *
     * @return string The message timestamp.
     */
    public function getTimeStamp() : string;

    /**
     * Get the priority of the message.
     *
     * @return int The message priority.
     */
    public function getPriority() : int;

    /**
     * Get the expiration of the message.
     *
     * @return string The message expiration.
     */
    public function getExpiration() : string;

    /**
     * Get the message user id.
     *
     * @return string The message user id.
     */
    public function getUserId() : string;

    /**
     * Get the application id of the message.
     *
     * @return string The application id of the message.
     */
    public function getAppId() : string;

    /**
     * Get the message id of the message.
     *
     * @return string The message id
     */
    public function getMessageId() : string;

    /**
     * Get the reply-to address of the message.
     *
     * @return string The contents of the reply to field.
     */
    public function getReplyTo() : string;

    /**
     * Get the message correlation id.
     *
     * @return string The correlation id of the message.
     */
    public function getCorrelationId() : string;

    /**
     * Get the headers of the message.
     *
     * @return array An array of key value pairs associated with the message.
     */
    public function getHeaders() : array;

    /**
     * Get a specific message header.
     *
     * @param string $headerKey Name of the header to get the value from.
     * @return string|false The contents of the specified header, false if header not set
     */
    public function getHeader(string $headerKey);


    /**
     * Check whether a specific message header is set.
     *
     * @param string $key
     * @return bool
     */
    public function hasHeader(string $key) : bool;
}
