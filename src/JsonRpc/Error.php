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

namespace Humus\Amqp\JsonRpc;

use Humus\Amqp\Exception;

/**
 * Class Error
 * @package Humus\Amqp\JsonRpc
 */
class Error
{
    /**#@+
     * @const int error codes
     */
    const ERROR_CODE_32700 = -32700;
    const ERROR_CODE_32600 = -32600;
    const ERROR_CODE_32601 = -32601;
    const ERROR_CODE_32602 = -32602;
    const ERROR_CODE_32603 = -32603;
    /**#@-*/

    /**
     * @var array Recommended Reason Phrases
     */
    private $recommendedMessagePhrases = [
        -32700 => 'Parse error',
        -32600 => 'Invalid Request',
        -32601 => 'Method not found',
        -32602 => 'Invalid params',
        -32603 => 'Internal error',
    ];

    /**
     * @var int
     */
    private $code;

    /**
     * @var
     */
    private $message;

    /**
     * Error constructor.
     * @param int $code
     * @param string|null $message
     */
    public function __construct(int $code, string $message = null)
    {
        if (! defined(Error::class . '::ERROR_CODE_' . -$code)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid status code provided: %s',
                $code
            ));
        }

        if (null === $message) {
            $message = $this->recommendedMessagePhrases[$code];
        }

        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
