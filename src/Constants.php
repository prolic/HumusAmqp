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

namespace Humus\Amqp;

/**
 * Class Constants
 * @package Humus\Amqp
 */
abstract class Constants
{
    /**
     * Passing in this constant as a flag will forcefully disable all other flags.
     * Use this if you want to temporarily disable the amqp.auto_ack ini setting.
     */
    const AMQP_NOPARAM = 0;

    /**
     * Durable exchanges and queues will survive a broker restart, complete with all of their data.
     */
    const AMQP_DURABLE = 2;

    /**
     * Passive exchanges and queues will not be redeclared, but the broker will throw an error if the exchange or queue does not exist.
     */
    const AMQP_PASSIVE = 4;

    /**
     * Valid for queues only, this flag indicates that only one client can be listening to and consuming from this queue.
     */
    const AMQP_EXCLUSIVE = 8;

    /**
     * For exchanges, the auto delete flag indicates that the exchange will be deleted as soon as no more queues are bound
     * to it. If no queues were ever bound the exchange, the exchange will never be deleted. For queues, the auto delete
     * flag indicates that the queue will be deleted as soon as there are no more listeners subscribed to it. If no
     * subscription has ever been active, the queue will never be deleted. Note: Exclusive queues will always be
     * automatically deleted with the client disconnects.
     */
    const AMQP_AUTODELETE = 16;

    /**
     * Clients are not allowed to make specific queue bindings to exchanges defined with this flag.
     */
    const AMQP_INTERNAL = 32;

    /**
     * When passed to the consume method for a clustered environment, do not consume from the local node.
     */
    const AMQP_NOLOCAL = 64;

    /**
     * When passed to the {@link \Humus\Amqp\Queue::get()} and {@link \Humus\Amqp\Queue::consume()} methods as a flag,
     * the messages will be immediately marked as acknowledged by the server upon delivery.
     */
    const AMQP_AUTOACK = 128;

    /**
     * Passed on queue creation, this flag indicates that the queue should be deleted if it becomes empty.
     */
    const AMQP_IFEMPTY = 256;

    /**
     * Passed on queue or exchange creation, this flag indicates that the queue or exchange should be
     * deleted when no clients are connected to the given queue or exchange.
     */
    const AMQP_IFUNUSED = 512;

    /**
     * When publishing a message, the message must be routed to a valid queue. If it is not, an error will be returned.
     */
    const AMQP_MANDATORY = 1024;

    /**
     * When publishing a message, mark this message for immediate processing by the broker. (High priority message.)
     */
    const AMQP_IMMEDIATE = 2048;

    /**
     * If set during a call to {@link \Humus\Amqp\Queue::ack()}, the delivery tag is treated as "up to and including", so that multiple
     * messages can be acknowledged with a single method. If set to zero, the delivery tag refers to a single message.
     * If the AMQP_MULTIPLE flag is set, and the delivery tag is zero, this indicates acknowledgement of all outstanding
     * messages.
     */
    const AMQP_MULTIPLE = 4096;

    /**
     * If set during a call to {@link \Humus\Amqp\Exchange::bind()}, the server will not respond to the method.The client should not wait
     * for a reply method. If the server could not complete the method it will raise a channel or connection exception.
     */
    const AMQP_NOWAIT = 8192;

    /**
     * If set during a call to {@link \Humus\Amqp\Queue::nack()}, the message will be placed back to the queue.
     */
    const AMQP_REQUEUE = 16384;

    /**
     * Constructor disabled
     */
    final private function __construct()
    {
    }
}
