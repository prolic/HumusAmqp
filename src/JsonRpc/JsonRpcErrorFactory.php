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

namespace Humus\Amqp\JsonRpc;

use Humus\Amqp\Exception;

final class JsonRpcErrorFactory implements ErrorFactory
{
    private array $recommendedMessagePhrases = [
        -32700 => 'Parse error',
        -32600 => 'Invalid JsonRpcRequest',
        -32601 => 'Method not found',
        -32602 => 'Invalid params',
        -32603 => 'Internal error',
    ];
    private array $customMessagePhrases = [];

    public function __construct(array $customCodes = [])
    {
        $this->customMessagePhrases = $customCodes;
    }

    /**
     * @param int $code
     * @param string|null $message
     * @param array|bool|float|int|null|string $data
     *
     * @return Error
     */
    public function create(int $code, ?string $message = null, $data = null): Error
    {
        $isPredefinedCode = defined(JsonRpcError::class . '::ERROR_CODE_' . -$code);
        $isCustomCode = $code >= -32099 && $code <= -32000;

        if (! ($isPredefinedCode || $isCustomCode)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid status code provided: %s',
                $code
            ));
        }

        if ($isCustomCode && null === $message && isset($this->customMessagePhrases[$code])) {
            $message = $this->customMessagePhrases[$code];
        }

        if ($isPredefinedCode && null === $message) {
            $message = $this->recommendedMessagePhrases[$code];
        }

        if (null === $message) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Message is required for error code %s',
                $code
            ));
        }

        return new JsonRpcError($code, $message, $data);
    }
}
