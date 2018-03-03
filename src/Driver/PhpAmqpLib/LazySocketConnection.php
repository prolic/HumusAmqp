<?php
/**
 * Copyright (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\Channel as ChannelInterface;
use Humus\Amqp\ConnectionOptions;
use PhpAmqpLib\Connection\AMQPLazySocketConnection as BaseLazySocketConnection;
use Traversable;

/**
 * Class LazySocketConnection
 * @package Humus\Amqp\Driver\PhpAmqpLib
 */
final class LazySocketConnection extends AbstractConnection
{
    /**
     * SocketConnection constructor.
     * @param ConnectionOptions|array|Traversable $options
     */
    public function __construct($options)
    {
        if (! $options instanceof ConnectionOptions) {
            $options = new ConnectionOptions($options);
        }

        $this->options = $options;
        $this->connection = new BaseLazySocketConnection(
            $options->getHost(),
            $options->getPort(),
            $options->getLogin(),
            $options->getPassword(),
            $options->getVhost(),
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $options->getReadTimeout() ?: $options->getWriteTimeout(),
            $options->getHeartbeat() > 0
        );
    }

    /**
     * @return ChannelInterface
     */
    public function newChannel(): ChannelInterface
    {
        $method = new \ReflectionMethod($this->connection, 'connect');
        $method->setAccessible(true);
        $method->invoke($this->connection);

        return parent::newChannel();
    }
}
