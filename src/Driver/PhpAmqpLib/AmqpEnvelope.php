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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AmqpEnvelope
 * @package Humus\Amqp\Driver\AmqpExtension
 */
class AmqpEnvelope implements \Humus\Amqp\Driver\AmqpEnvelope
{
    /**
     * @var AMQPMessage
     */
    private $envelope;

    /**
     * AmqpEnvelope constructor.
     * @param AMQPMessage $message
     */
    public function __construct(AMQPMessage $message)
    {
        $this->envelope = $message;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->envelope->body;
    }

    /**
     * @inheritdoc
     */
    public function getRoutingKey()
    {
        return $this->envelope->get('routing_key');
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryTag()
    {
        return $this->envelope->get('delivery_tag');
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryMode()
    {
        return $this->envelope->get('delivery_mode');
    }

    /**
     * @inheritdoc
     */
    public function getExchangeName()
    {
        return $this->envelope->get('exchange');
    }

    /**
     * @inheritdoc
     */
    public function isRedelivery()
    {
        return $this->envelope->get('redelivered');
    }

    /**
     * @inheritdoc
     */
    public function getContentType()
    {
        return $this->envelope->get('content_type');
    }

    /**
     * @inheritdoc
     */
    public function getContentEncoding()
    {
        return $this->envelope->get('content_encoding');
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->envelope->get('type');
    }

    /**
     * @inheritdoc
     */
    public function getTimeStamp()
    {
        return $this->envelope->get('timestamp');
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return $this->envelope->get('priority');
    }

    /**
     * @inheritdoc
     */
    public function getExpiration()
    {
        return $this->envelope->get('expiration');
    }

    /**
     * @inheritdoc
     */
    public function getUserId()
    {
        return $this->envelope->get('user_id');
    }

    /**
     * @inheritdoc
     */
    public function getAppId()
    {
        return $this->envelope->get('app_id');
    }

    /**
     * @inheritdoc
     */
    public function getMessageId()
    {
        return $this->envelope->get('message_id');
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo()
    {
        return $this->envelope->get('reply_to');
    }

    /**
     * @inheritdoc
     */
    public function getCorrelationId()
    {
        return $this->envelope->get('correlation_id');
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->envelope->get('application_headers');
    }

    /**
     * @inheritdoc
     */
    public function getHeader($headerKey)
    {
        $headers = $this->getHeaders();

        return isset($headers[$headerKey]) ? $headers[$headerKey] : false;
    }
}
