<?php
/*
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

namespace Humus\Amqp;

use AMQPExchange;

/**
 * Class PlainProducer
 * @package Humus\Amqp
 */
final class PlainProducer implements Producer
{
    /**
     * @var AMQPExchange
     */
    private $exchange;

    /**
     * @var array
     */
    private $defaultAttributes;

    /**
     * @return array
     */
    public static function defaultAttributes()
    {
        return [
            'content_type' => 'text/plain',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2, // persistent
        ];
    }

    /**
     * Constructor
     *
     * @param AMQPExchange $exchange
     * @param array|null $defaultAttributes
     */
    public function __construct(AMQPExchange $exchange, array $defaultAttributes = null)
    {
        $this->exchange = $exchange;

        if (null !== $defaultAttributes) {
            $this->defaultAttributes = $defaultAttributes;
        } else {
            $this->defaultAttributes = self::defaultAttributes();
        }
    }

    /**
     * Publish a message
     *
     * @param string  $message     The message to publish.
     * @param string  $routingKey  The optional routing key to which to
     *                             publish to.
     * @param integer $flags       One or more of AMQP_MANDATORY and
     *                             AMQP_IMMEDIATE.
     * @param array   $attributes  One of content_type, content_encoding,
     *                             correlation_id, reply_to, headers,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration or type.
     */
    public function publish($message, $routingKey = null, $flags = AMQP_NOPARAM, array $attributes = [])
    {
        $attributes = array_merge($this->defaultAttributes, $attributes);

        $this->exchange->publish($message, $routingKey, $flags, $attributes);
    }
}
