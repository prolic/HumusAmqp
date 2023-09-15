<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace Humus\Amqp\JsonRpc;

use Assert\Assertion;
use Humus\Amqp\Exception;

final class JsonRpcRequest implements Request
{
    public const JSONRPC_VERSION = '2.0';

    private string $server;

    private string $method;

    /**
     * @var array|string|int|float|bool
     */
    private $params;

    private ?string $id;

    private string $routingKey;

    private int $expiration = 0;

    private int $timestamp;

    /**
     * @param string $server
     * @param string $method
     * @param array|string|int|float|bool $params
     * @param string|null $id
     * @param string $routingKey
     * @param int $expiration in milliseconds
     * @param int $timestamp
     *
     * @throws \Assert\AssertionFailedException
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        string $server,
        string $method,
        $params,
        ?string $id = null,
        string $routingKey = '',
        int $expiration = 0, // in milliseconds
        int $timestamp = 0
    ) {
        if (! \is_array($params) && ! \is_scalar($params) && null !== $params) {
            throw new Exception\InvalidArgumentException('Params must be of type array, scalar or null');
        }

        Assertion::minLength($server, 1);

        $this->server = $server;
        $this->method = $method;
        $this->params = $params;
        $this->id = $id;
        $this->routingKey = $routingKey;
        $this->expiration = $expiration;
        $this->timestamp = (0 === $timestamp) ? $timestamp : time();
    }

    /**
     * @return array|bool|float|int|string
     */
    public function params()
    {
        return $this->params;
    }

    public function server(): string
    {
        return $this->server;
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }

    public function expiration(): int
    {
        return $this->expiration;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function method(): string
    {
        return $this->method;
    }
}
