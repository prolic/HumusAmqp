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

namespace Humus\Amqp;

class ConnectionOptions extends AbstractOptions
{
    protected string $host = 'localhost';
    protected int $port = 5672;
    protected string $login = 'guest';
    protected string $password = 'guest';
    protected string $vhost = '/';
    protected bool $persistent = false;
    protected float $connectTimeout = 1.00; //secs
    protected float $readTimeout = 1.00; // secs
    protected float $writeTimeout = 1.00; // secs
    protected int $heartbeat = 0;
    protected ?string $cacert = null;
    protected ?string $cert = null;
    protected ?string $key = null;
    protected ?bool $verify = null;

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function setPersistent(bool $persistent): void
    {
        $this->persistent = $persistent;
    }

    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function connectTimeout(): float
    {
        return $this->connectTimeout;
    }

    public function setConnectTimeout(float $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function setReadTimeout(float $readTimeout): void
    {
        $this->readTimeout = $readTimeout;
    }

    public function readTimeout(): float
    {
        return $this->readTimeout;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function login(): string
    {
        return $this->login;
    }

    public function setVhost(string $vhost): void
    {
        $this->vhost = $vhost;
    }

    public function vhost(): string
    {
        return $this->vhost;
    }

    public function setWriteTimeout(float $writeTimeout): void
    {
        $this->writeTimeout = $writeTimeout;
    }

    public function writeTimeout(): float
    {
        return $this->writeTimeout;
    }

    public function heartbeat(): int
    {
        return $this->heartbeat;
    }

    public function setHeartbeat(int $heartbeat): void
    {
        $this->heartbeat = $heartbeat;
    }

    public function caCert(): ?string
    {
        return $this->cacert;
    }

    public function setCaCert(string $cacert): void
    {
        $this->cacert = $cacert;
    }

    public function cert(): ?string
    {
        return $this->cert;
    }

    public function setCert(string $cert): void
    {
        $this->cert = $cert;
    }

    public function key(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function verify(): ?bool
    {
        return $this->verify;
    }

    public function setVerify(bool $verify): void
    {
        $this->verify = $verify;
    }

    public function toArray(): array
    {
        $array = [];

        $transform = function ($letters): string {
            $letter = \array_shift($letters);

            return '_' . \strtolower($letter);
        };

        foreach ($this as $key => $value) {
            if ($key === '__strictMode__'
                || null === $value
            ) {
                continue;
            }
            $normalizedKey = \preg_replace_callback('/([A-Z])/', $transform, $key);
            $array[$normalizedKey] = $value;
        }

        return $array;
    }
}
