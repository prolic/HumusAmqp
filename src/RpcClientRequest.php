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

use Assert\Assertion;

/**
 * Class RpcClientRequest
 * @package Humus\Amqp
 */
class RpcClientRequest
{
    /**
     * @var array|string|integer|float|bool
     */
    private $payload;

    /**
     * @var string
     */
    private $server;

    /**
     * @var string
     */
    private $requestId;

    /**
     * @var string|null
     */
    private $routingKey = null;

    /**
     * @var int
     */
    private $expiration = 0;

    /**
     * @var string|null
     */
    private $messageId = null;

    /**
     * @var string|null
     */
    private $timestamp = null;

    /**
     * @var string|null
     */
    private $type = null;

    /**
     * RpcClientRequest constructor.
     *
     * @param array|string|integer|float|bool $payload
     * @param string $server
     * @param string $requestId
     * @param string|null $routingKey
     * @param float $expiration in seconds
     * @param string|null $messageId
     * @param string|null $timestamp
     * @param string|null $type
     */
    public function __construct(
        $payload,
        string $server,
        string $requestId,
        string $routingKey = null,
        float $expiration = 0,
        string $messageId = null,
        string $timestamp = null,
        string $type = null
    ) {
        if (!is_array($payload) && !is_scalar($payload)) {
            throw new Exception\InvalidArgumentException('$payload must be of type array or scalar');
        }

        Assertion::minLength($server, 1);
        Assertion::minLength($requestId, 1);

        $this->payload = $payload;
        $this->server = $server;
        $this->requestId = $requestId;
        $this->routingKey = $routingKey;
        $this->expiration = $expiration;
        $this->messageId = $messageId;
        $this->timestamp = $timestamp;
        $this->type = $type;
    }

    /**
     * @return array|bool|float|int|string
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function server() : string
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function requestId() : string
    {
        return $this->requestId;
    }

    /**
     * @return null|string
     */
    public function routingKey()
    {
        return $this->routingKey;
    }

    /**
     * Expiration in seconds
     * @return float
     */
    public function expiration() : float
    {
        return $this->expiration;
    }

    /**
     * @return null|string
     */
    public function messageId()
    {
        return $this->messageId;
    }

    /**
     * @return null|string
     */
    public function timestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return null|string
     */
    public function type()
    {
        return $this->type;
    }
}
