<?php

declare(strict_types=1);
/**
 * Interface representing AMQP values
 */
interface AMQPValue
{
    /**
     * @return bool|int|double|string|null|array|AMQPValue|AMQPDecimal|AMQPTimestamp
     */
    public function toAmqpValue();
}
