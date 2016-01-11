<?php

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
     * Get the raw (undecoded) body of the message.
     *
     * @return string The raw contents of the message body.
     */
    public function getRawBody();

    /**
     * Get the (decoded) body of the message.
     *
     * @return string The (decoded) contents of the message body.
     */
    public function getBody();

    /**
     * Get the routing key of the message.
     *
     * @return string The message routing key.
     */
    public function getRoutingKey();

    /**
     * Get the delivery tag of the message.
     *
     * @return string The delivery tag of the message.
     */
    public function getDeliveryTag();

    /**
     * Get the delivery mode of the message.
     *
     * @return integer The delivery mode of the message.
     */
    public function getDeliveryMode();

    /**
     * Get the exchange name on which the message was published.
     *
     * @return string The exchange name on which the message was published.
     */
    public function getExchangeName();

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
    public function isRedelivery();

    /**
     * Get the message content type.
     *
     * @return string The content type of the message.
     */
    public function getContentType();

    /**
     * Get the content encoding of the message.
     *
     * @return string The content encoding of the message.
     */
    public function getContentEncoding();

    /**
     * Get the message type.
     *
     * @return string The message type.
     */
    public function getType();

    /**
     * Get the timestamp of the message.
     *
     * @return string The message timestamp.
     */
    public function getTimeStamp();

    /**
     * Get the priority of the message.
     *
     * @return int The message priority.
     */
    public function getPriority();

    /**
     * Get the expiration of the message.
     *
     * @return string The message expiration.
     */
    public function getExpiration();

    /**
     * Get the message user id.
     *
     * @return string The message user id.
     */
    public function getUserId();

    /**
     * Get the application id of the message.
     *
     * @return string The application id of the message.
     */
    public function getAppId();

    /**
     * Get the message id of the message.
     *
     * @return string The message id
     */
    public function getMessageId();

    /**
     * Get the reply-to address of the message.
     *
     * @return string The contents of the reply to field.
     */
    public function getReplyTo();

    /**
     * Get the message correlation id.
     *
     * @return string The correlation id of the message.
     */
    public function getCorrelationId();

    /**
     * Get the headers of the message.
     *
     * @return array An array of key value pairs associated with the message.
     */
    public function getHeaders();

    /**
     * Get a specific message header.
     *
     * @param string $headerKey Name of the header to get the value from.
     *
     * @return string|boolean The contents of the specified header or FALSE
     *                        if not set.
     */
    public function getHeader($headerKey);
}
