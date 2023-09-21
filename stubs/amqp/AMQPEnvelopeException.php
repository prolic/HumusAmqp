<?php

declare(strict_types=1);

/**
 * stub class representing AMQPEnvelopeException from pecl-amqp
 */
class AMQPEnvelopeException extends AMQPException
{
    private AMQPEnvelope $envelope;

    public function getEnvelope(): AMQPEnvelope
    {
    }
}
