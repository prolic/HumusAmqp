<?php
/**
 * Copyright (c) 2016-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

use Humus\Amqp\Exception;

/**
 * Class JsonRpcResponse
 * @package Humus\Amqp\JsonRpc
 */
final class JsonRpcResponse implements Response
{
    const JSONRPC_VERSION = '2.0';

    /**
     * @var array|string|int|float|bool|null
     */
    private $result;

    /**
     * @var Error|null
     */
    private $error;

    /**
     * @var string
     */
    private $id;

    /**
     * @var array|string|int|float|bool|null
     */
    private $data;

    /**
     * @param string|null $id
     * @param array|string|int|float|bool|null $result
     * @return Response
     */
    public static function withResult(string $id = null, $result)
    {
        $self = new self();

        $self->assertPayload($result, 'result');

        $self->id = $id;
        $self->result = $result;

        return $self;
    }

    /**
     * @param string|null $id
     * @param Error $error
     * @param array|string|int|float|bool|null $data
     * @return Response
     */
    public static function withError(string $id = null, Error $error, $data = null)
    {
        $self = new self();

        $self->assertPayload($data, 'data');

        $self->id = $id;
        $self->error = $error;
        $self->data = $data;

        return $self;
    }

    protected function __construct()
    {
    }

    /**
     * @return string|null
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return array|bool|float|int|string|null
     */
    public function result()
    {
        return $this->result;
    }

    /**
     * @return Error|null
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return null !== $this->error;
    }

    /**
     * @return array|bool|float|int|null|string
     * @deprecated will be moved to Error in 2.0
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param mixed $payload
     * @param string $name
     */
    private static function assertPayload($payload, string $name)
    {
        if (is_array($payload)) {
            foreach ($payload as $subPayload) {
                self::assertPayload($subPayload, $name);
            }

            return;
        }

        if (! is_scalar($payload) && null !== $payload) {
            throw new Exception\InvalidArgumentException($name . ' must only contain arrays and scalar values');
        }
    }
}
