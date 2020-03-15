<?php
/**
 * Copyright (c) 2016-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Envelope as EnvelopeInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class Envelope implements EnvelopeInterface
{
    private AMQPMessage $envelope;

    public function __construct(AMQPMessage $message)
    {
        $this->envelope = $message;
    }

    public function getBody(): string
    {
        return $this->envelope->body;
    }

    public function getRoutingKey(): string
    {
        return (string) $this->envelope->get('routing_key');
    }

    public function getDeliveryTag(): int
    {
        return (int) $this->envelope->get('delivery_tag');
    }

    public function getDeliveryMode(): int
    {
        if ($this->envelope->has('delivery_mode')) {
            return (int) $this->envelope->get('delivery_mode');
        }

        return 1;
    }

    public function getExchangeName(): string
    {
        return $this->getFromEnvelope('exchange');
    }

    public function isRedelivery(): bool
    {
        return (bool) $this->envelope->get('redelivered');
    }

    public function getContentType(): string
    {
        return $this->getFromEnvelope('content_type');
    }

    public function getContentEncoding(): string
    {
        return $this->getFromEnvelope('content_encoding');
    }

    public function getType(): string
    {
        return $this->getFromEnvelope('type');
    }

    public function getTimestamp(): int
    {
        return (int) $this->getFromEnvelope('timestamp');
    }

    public function getPriority(): int
    {
        if ($this->envelope->has('priority')) {
            return (int) $this->envelope->get('priority');
        }

        return 0;
    }

    public function getExpiration(): int
    {
        return (int) $this->getFromEnvelope('expiration');
    }

    public function getUserId(): string
    {
        return $this->getFromEnvelope('user_id');
    }

    public function getAppId(): string
    {
        return $this->getFromEnvelope('app_id');
    }

    public function getMessageId(): string
    {
        return $this->getFromEnvelope('message_id');
    }

    public function getReplyTo(): string
    {
        return $this->getFromEnvelope('reply_to');
    }

    public function getCorrelationId(): string
    {
        return $this->getFromEnvelope('correlation_id');
    }

    public function getHeaders(): array
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

    public function getHeader(string $header): ?string
    {
        $headers = $this->getHeaders();

        return $headers[$header] ?? null;
    }

    public function hasHeader(string $header): bool
    {
        $headers = $this->getHeaders();

        return isset($headers[$header]);
    }

    private function getFromEnvelope(string $name): string
    {
        if ($this->envelope->has($name)) {
            return (string) $this->envelope->get($name);
        }

        return '';
    }
}
