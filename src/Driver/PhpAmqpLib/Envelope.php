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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use bar\baz\source_with_namespace;
use Humus\Amqp\Envelope as AmqpEnvelopeInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class Envelope
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class Envelope implements AmqpEnvelopeInterface
{
    /**
     * @var AMQPMessage
     */
    private $envelope;

    /**
     * Envelope constructor.
     * @param AMQPMessage $message
     */
    public function __construct(AMQPMessage $message)
    {
        $this->envelope = $message;
    }

    /**
     * @inheritdoc
     */
    public function getBody() : string
    {
        return $this->envelope->body;
    }

    /**
     * @inheritdoc
     */
    public function getRoutingKey() : string
    {
        return $this->getFromEnvelope('routing_key');
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryTag() : string
    {
        return $this->getFromEnvelope('delivery_tag');
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryMode() : int
    {
        if ($this->envelope->has('delivery_mode')) {
            return $this->envelope->get('delivery_mode');
        }

        return 1;
    }

    /**
     * @inheritdoc
     */
    public function getExchangeName() : string
    {
        return $this->getFromEnvelope('exchange');
    }

    /**
     * @inheritdoc
     */
    public function isRedelivery() : bool
    {
        if ($this->envelope->has('redelivered')) {
            return $this->envelope->get('redelivered');
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getContentType() : string
    {
        return $this->getFromEnvelope('content_type');
    }

    /**
     * @inheritdoc
     */
    public function getContentEncoding() : string
    {
        return $this->getFromEnvelope('content_encoding');
    }

    /**
     * @inheritdoc
     */
    public function getType() : string
    {
        return $this->getFromEnvelope('type');
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp() : string
    {
        return $this->getFromEnvelope('timestamp');
    }

    /**
     * @inheritdoc
     */
    public function getPriority() : int
    {
        if ($this->envelope->has('priority')) {
            return $this->envelope->get('priority');
        }

        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getExpiration() : string
    {
        return $this->getFromEnvelope('expiration');
    }

    /**
     * @inheritdoc
     */
    public function getUserId() : string
    {
        return $this->getFromEnvelope('user_id');
    }

    /**
     * @inheritdoc
     */
    public function getAppId() : string
    {
        return $this->getFromEnvelope('app_id');
    }

    /**
     * @inheritdoc
     */
    public function getMessageId() : string
    {
        return $this->getFromEnvelope('message_id');
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo() : string
    {
        return $this->getFromEnvelope('reply_to');
    }

    /**
     * @inheritdoc
     */
    public function getCorrelationId() : string
    {
        return $this->getFromEnvelope('correlation_id');
    }

    /**
     * @inheritdoc
     */
    public function getHeaders() : array
    {
        try {
            $headers = $this->envelope->get('application_headers');
        } catch (\OutOfBoundsException $e) {
            if ($e->getMessage() === 'No "application_headers" property') {
                return [];
            }

            throw $e;
        }

        if ($headers instanceof AMQPTable) {
            return $headers->getNativeData();
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $header)
    {
        $headers = $this->getHeaders();
        return $headers[$header] ?? false;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader(string $header) : bool
    {
        $headers = $this->getHeaders();

        return isset($headers[$header]);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getFromEnvelope(string $name) : string
    {
        if ($this->envelope->has($name)) {
            return $this->envelope->get($name);
        }

        return '';
    }
}
