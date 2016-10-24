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

declare(strict_types=1);

namespace Humus\Amqp\Driver\PhpAmqpLib;

use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Exception;
use PhpAmqpLib\Connection\AMQPSSLConnection as BaseAMQPSSLConnection;
use Traversable;

/**
 * Class SslConnection
 * @package Humus\Amqp\Driver\PhpAmqpLib
 */
final class SslConnection extends AbstractConnection
{
    /**
     * SslConnection constructor.
     * @param ConnectionOptions|array|Traversable $options
     */
    public function __construct($options)
    {
        if (! $options instanceof ConnectionOptions) {
            $options = new ConnectionOptions($options);
        }

        if (! $options->getCACert()) {
            throw new Exception\InvalidArgumentException('Ca cert file missing in connection options');
        }

        if (! $options->getCert()) {
            throw new Exception\InvalidArgumentException('Cert file missing in connection options');
        }

        if (null === $options->getVerify()) {
            throw new Exception\InvalidArgumentException('SSL verification option is missing connection options');
        }

        $sslOptions = [
            'cafile' => $options->getCACert(),
            'local_cert' => $options->getCert(),
            'verify_peer' => $options->getVerify(),
            'verify_peer_name' => $options->getVerify()
        ];

        if ($key = $options->getKey()) {
            $sslOptions['local_pk'] = $key;
        }

        $this->options = $options;
        $this->connection = new BaseAMQPSSLConnection(
            $options->getHost(),
            $options->getPort(),
            $options->getLogin(),
            $options->getPassword(),
            $options->getVhost(),
            $sslOptions,
            [
                'connection_timeout' => $options->getReadTimeout(),
                'read_write_timeout' => $options->getWriteTimeout(),
                'heartbeat'          => $options->getHeartbeat(),
            ]
        );
    }
}
