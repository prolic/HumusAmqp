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
 * Class Response
 * @package Humus\Amqp\JsonRpc
 */
class Response
{
    CONST JSONRPC = "2.0";

    /**
     * @var array|string|integer|float|bool|null
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
     * @var array|string|integer|float|bool|null
     */
    private $data;

    /**
     * Response constructor.
     * @param array|string|integer|float|bool|null $result
     * @param Error $error
     * @param string|null $id
     * @param array|string|integer|float|bool|null $data
     */
    public function __construct($result = null, Error $error = null, string $id = null, $data = null)
    {
        $this->id = $id;

        if (! is_array($data) || ! is_scalar($data) || null !== $data) {
            throw new Exception\InvalidArgumentException(
                'Data must be of type array|string|integer|float|bool|null'
            );
        }

        if (null !== $error) {
            $this->error = $error;
            $this->data = $data;
        } elseif (! is_array($result) || ! is_scalar($result) || null !== $result) {
            throw new Exception\InvalidArgumentException(
                'Result must be of type array|string|integer|float|bool|null'
            );
        } else {
            $this->result = $result;
        }
    }

    /**
     * @return string
     */
    public function id() : string
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
        $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return null !== $this->error;
    }

    /**
     * @return array|bool|float|int|null|string
     */
    public function data()
    {
        return $this->data;
    }
}
