<?php

namespace Humus\Amqp\Driver\PhpAmqpLib;

/**
 * Class AmqpEnvelope
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpEnvelope implements \Humus\Amqp\Driver\AmqpEnvelope
{
    /**
     * @var \AMQPEnvelope
     */
    private $envelope;

    /**
     * AmqpEnvelope constructor.
     * @param \AMQPEnvelope $envelope
     */
    public function __construct(\AMQPEnvelope $envelope)
    {
        $this->envelope = $envelope;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->envelope->getBody();
    }

    /**
     * @inheritdoc
     */
    public function getRoutingKey()
    {
        return $this->envelope->getRoutingKey();
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryTag()
    {
        return $this->envelope->getDeliveryTag();
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryMode()
    {
        return $this->envelope->getDeliveryMode();
    }

    /**
     * @inheritdoc
     */
    public function getExchangeName()
    {
        return $this->envelope->getExchangeName();
    }

    /**
     * @inheritdoc
     */
    public function isRedelivery()
    {
        return $this->envelope->isRedelivery();
    }

    /**
     * @inheritdoc
     */
    public function getContentType()
    {
        return $this->envelope->getContentType();
    }

    /**
     * @inheritdoc
     */
    public function getContentEncoding()
    {
        return $this->envelope->getContentEncoding();
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->envelope->getType();
    }

    /**
     * @inheritdoc
     */
    public function getTimeStamp()
    {
        return $this->envelope->getTimeStamp();
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return $this->envelope->getPriority();
    }

    /**
     * @inheritdoc
     */
    public function getExpiration()
    {
        return $this->envelope->getExpiration();
    }

    /**
     * @inheritdoc
     */
    public function getUserId()
    {
        return $this->envelope->getUserId();
    }

    /**
     * @inheritdoc
     */
    public function getAppId()
    {
        return $this->envelope->getAppId();
    }

    /**
     * @inheritdoc
     */
    public function getMessageId()
    {
        return $this->envelope->getMessageId();
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo()
    {
        return $this->envelope->getReplyTo();
    }

    /**
     * @inheritdoc
     */
    public function getCorrelationId()
    {
        return $this->envelope->getCorrelationId();
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->envelope->getHeaders();
    }

    /**
     * @inheritdoc
     */
    public function getHeader($headerKey)
    {
        return $this->envelope->getHeader($headerKey);
    }
}
