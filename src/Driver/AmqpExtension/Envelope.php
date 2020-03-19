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

namespace Humus\Amqp\Driver\AmqpExtension;

use AMQPBasicProperties;
use Humus\Amqp\Envelope as EnvelopeInterface;

final class Envelope implements EnvelopeInterface
{
    private AMQPBasicProperties $envelope;

    public function __construct(AMQPBasicProperties $envelope)
    {
        $this->envelope = $envelope;
    }

    public function getBody(): string
    {
        return (string) $this->envelope->getBody();
    }

    public function getRoutingKey(): string
    {
        return $this->envelope->getRoutingKey();
    }

    public function getDeliveryTag(): int
    {
        return $this->envelope->getDeliveryTag();
    }

    public function getDeliveryMode(): int
    {
        return $this->envelope->getDeliveryMode();
    }

    public function getExchangeName(): string
    {
        return $this->envelope->getExchangeName();
    }

    public function isRedelivery(): bool
    {
        return $this->envelope->isRedelivery();
    }

    public function getContentType(): string
    {
        return $this->envelope->getContentType();
    }

    public function getContentEncoding(): string
    {
        return $this->envelope->getContentEncoding();
    }

    public function getType(): string
    {
        return $this->envelope->getType();
    }

    public function getTimestamp(): int
    {
        return (int) $this->envelope->getTimeStamp();
    }

    public function getPriority(): int
    {
        return $this->envelope->getPriority();
    }

    public function getExpiration(): int
    {
        return (int) $this->envelope->getExpiration();
    }

    public function getUserId(): string
    {
        return $this->envelope->getUserId();
    }

    public function getAppId(): string
    {
        return $this->envelope->getAppId();
    }

    public function getMessageId(): string
    {
        return $this->envelope->getMessageId();
    }

    public function getReplyTo(): string
    {
        return $this->envelope->getReplyTo();
    }

    public function getCorrelationId(): string
    {
        return $this->envelope->getCorrelationId();
    }

    public function getHeaders(): array
    {
        return $this->envelope->getHeaders();
    }

    public function getHeader(string $header): ?string
    {
        if (! $this->hasHeader($header)) {
            return null;
        }

        return $this->envelope->getHeader($header);
    }

    public function hasHeader(string $header): bool
    {
        return $this->envelope->hasHeader($header);
    }
}
