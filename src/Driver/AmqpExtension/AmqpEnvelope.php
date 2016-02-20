<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic
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

namespace Humus\Amqp\Driver\AmqpExtension;

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
