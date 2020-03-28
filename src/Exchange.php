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

namespace Humus\Amqp;

/**
 * Represents a AMQP exchange
 */
interface Exchange
{
    public function getName(): string;

    public function setName(string $exchangeName): void;

    public function getType(): string;

    public function setType(string $exchangeType): void;

    /**
     * Get all the flags currently set on the given exchange.
     */
    public function getFlags(): int;

    /**
     * Set the flags on an exchange.
     *
     * @param int $flags     A bitmask of flags. This call currently only
     *                       considers the following flags:
     *                       Constants::AMQP_DURABLE, Constants::AMQP_PASSIVE
     *                       and Constants::AMQP_DURABLE (needs librabbitmq version >= 0.5.3 when using with ext-amqp)
     * @return void
     */
    public function setFlags(int $flags): void;

    /**
     * Get the argument associated with the given key.
     *
     * @param string $key The key to look up.
     * @return string|int|bool The string or integer value associated
     *                                with the given key, or FALSE if the key
     *                                is not set.
     */
    public function getArgument(string $key);

    /**
     * Get all arguments set on the given exchange.
     *
     * @return array An array containing all of the set key/value pairs.
     */
    public function getArguments(): array;

    /**
     * Set the value for the given key.
     *
     * @param string         $key   Name of the argument to set.
     * @param string|integer $value Value of the argument to set.
     * @return void
     */
    public function setArgument(string $key, $value): void;

    /**
     * Set all arguments on the exchange.
     */
    public function setArguments(array $arguments): void;

    public function declareExchange(): void;

    /**
     * Delete the exchange from the broker.
     *
     * @param string  $exchangeName Optional name of exchange to delete.
     * @param integer $flags        Optionally Constants::AMQP_IFUNUSED can be specified
     *                              to indicate the exchange should not be
     *                              deleted until no clients are connected to
     *                              it.
     * @return void
     */
    public function delete(string $exchangeName = '', int $flags = Constants::AMQP_NOPARAM): void;

    /**
     * Bind to another exchange.
     *
     * Bind an exchange to another exchange using the specified routing key.
     *
     * @param string $exchangeName Name of the exchange to bind.
     * @param string $routingKey   The routing key to use for binding.
     * @param array  $arguments     Additional binding arguments.
     *
     * @return void
     */
    public function bind(string $exchangeName, string $routingKey = '', array $arguments = []): void;

    /**
     * Remove binding to another exchange.
     *
     * Remove a routing key binding on an another exchange from the given exchange.
     *
     * @param string $exchangeName Name of the exchange to bind.
     * @param string $routingKey   The routing key to use for binding.
     * @param array  $arguments     Additional binding arguments.
     *
     * @return void
     */
    public function unbind(string $exchangeName, string $routingKey = '', array $arguments = []): void;

    /**
     * Publish a message to an exchange.
     *
     * @param string  $message     The message to publish.
     * @param string  $routingKey The optional routing key to which to
     *                             publish to.
     * @param integer $flags       One or more of Constants::AMQP_MANDATORY and
     *                             Constants::AMQP_IMMEDIATE.
     * @param array   $attributes  One of content_type, content_encoding,
     *                             message_id, user_id, app_id, delivery_mode,
     *                             priority, timestamp, expiration, type
     *                             or reply_to, headers.
     * @return void
     */
    public function publish(
        string $message,
        string $routingKey = '',
        int $flags = Constants::AMQP_NOPARAM,
        array $attributes = []
    ): void;

    public function getChannel(): Channel;

    public function getConnection(): Connection;
}
