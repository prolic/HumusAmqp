<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
declare(strict_types=1);

namespace Humus\Amqp\Driver\AmqpExtension;

use Humus\Amqp\Envelope as EnvelopeInterface;
use Humus\Amqp\Exception;

/**
 * Class Envelope.
 */
final class Envelope implements EnvelopeInterface
{
    /**
     * @var \AMQPEnvelope|\AMQPBasicProperties
     */
    private $envelope;
    /**
     * @var string
     */
    private $body;

    /**
     * Envelope constructor.
     *
     * @param \AMQPBasicProperties|\AMQPEnvelope $envelope
     * @param string                             $body
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($envelope, string $body)
    {
        if (!$envelope instanceof \AMQPBasicProperties && !$envelope instanceof \AMQPEnvelope) {
            throw new Exception\InvalidArgumentException('Invalid envelope type');
        }

        $this->envelope = $envelope;
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody() : string
    {
        return (string) $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutingKey() : string
    {
        return $this->envelope->getRoutingKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryTag() : int
    {
        return (int) $this->envelope->getDeliveryTag();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryMode() : int
    {
        return $this->envelope->getDeliveryMode();
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeName() : string
    {
        return $this->envelope->getExchangeName();
    }

    /**
     * {@inheritdoc}
     */
    public function isRedelivery() : bool
    {
        return $this->envelope->isRedelivery();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType() : string
    {
        return $this->envelope->getContentType();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentEncoding() : string
    {
        return $this->envelope->getContentEncoding();
    }

    /**
     * {@inheritdoc}
     */
    public function getType() : string
    {
        return $this->envelope->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp() : int
    {
        return (int) $this->envelope->getTimeStamp();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority() : int
    {
        return $this->envelope->getPriority();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration() : int
    {
        return (int) $this->envelope->getExpiration();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId() : string
    {
        return $this->envelope->getUserId();
    }

    /**
     * {@inheritdoc}
     */
    public function getAppId() : string
    {
        return $this->envelope->getAppId();
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageId() : string
    {
        return $this->envelope->getMessageId();
    }

    /**
     * {@inheritdoc}
     */
    public function getReplyTo() : string
    {
        return $this->envelope->getReplyTo();
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelationId() : string
    {
        return $this->envelope->getCorrelationId();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders() : array
    {
        return $this->envelope->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $header)
    {
        return $this->envelope->getHeader($header);
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader(string $header) : bool
    {
        return array_key_exists($header, $this->envelope->getHeaders());
    }
}
