<?php

namespace Humus\Amqp\Exception;

/**
 * Interface AmqpException
 * @package Humus\Amqp\Exception
 */
class AmqpException extends \Exception
{
    /**
     * @param \AMQPConnectionException $e
     * @return AmqpConnectionException
     */
    public static function fromAmqpExtension(\AMQPConnectionException $e)
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }

    /**
     * @param $e
     * @return AmqpConnectionException
     */
    public static function fromPhpAmqpLib($e)
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }
}
