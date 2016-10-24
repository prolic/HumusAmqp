<?php
/**
 * Copyright (c) 2016-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

declare (strict_types=1);

namespace Humus\Amqp\Exception;

/**
 * Interface AmqpException
 * @package Humus\Amqp\Exception
 */
class AmqpException extends \Exception
{
    /**
     * @param \AMQPException $e
     * @return AmqpException
     */
    public static function fromAmqpExtension(\AMQPException $e)
    {
        // parse error code, see: https://github.com/pdezwart/php-amqp/issues/243
        $matches = [];
        preg_match('/^.+: (\d+), message:.+/', $e->getMessage(), $matches);
        $code = $matches[1] ?? 0;
        return new static($e->getMessage(), (int) $code, $e);
    }

    /**
     * @param \Exception $e
     * @return AmqpException
     */
    public static function fromPhpAmqpLib(\Exception $e)
    {
        return new static($e->getMessage(), $e->getCode(), $e);
    }
}
