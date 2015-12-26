<?php

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
    private $userId = null;

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
     * @param int $expiration
     * @param string|null $userId
     * @param string|null $messageId
     * @param string|null $timestamp
     * @param string|null $type
     */
    public function __construct(
        $payload,
        $server,
        $requestId,
        $routingKey = null,
        $expiration = 0,
        $userId = null,
        $messageId = null,
        $timestamp = null,
        $type = null
    )
    {
        if (!is_array($payload) && !is_scalar($payload)) {
            throw new Exception\InvalidArgumentException('$payload must be of type array or scalar');
        }

        Assertion::minLength($server, 1);
        Assertion::minLength($requestId, 1);
        Assertion::nullOrString($routingKey);
        Assertion::min($expiration, 0);
        Assertion::nullOrString($userId);
        Assertion::nullOrString($messageId);
        Assertion::nullOrString($timestamp);
        Assertion::nullOrString($type);

        $this->payload = $payload;
        $this->server = $server;
        $this->requestId = $requestId;
        $this->routingKey = $routingKey;
        $this->expiration = $expiration;
        $this->userId = $userId;
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
    public function server()
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function requestId()
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
     * @return int
     */
    public function expiration()
    {
        return $this->expiration;
    }

    /**
     * @return null|string
     */
    public function userId()
    {
        return $this->userId;
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
