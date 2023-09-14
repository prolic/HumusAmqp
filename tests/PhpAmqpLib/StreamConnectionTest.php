<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace HumusTest\Amqp\PhpAmqpLib;

use Humus\Amqp\Connection;
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use Humus\Amqp\Exception\BadMethodCallException;
use HumusTest\Amqp\AbstractConnectionTest;

final class StreamConnectionTest extends AbstractConnectionTest
{
    /**
     * @test
     */
    public function it_throws_exception_with_invalid_credentials(): void
    {
        $this->expectException(\Exception::class);

        new StreamConnection($this->invalidCredentials());
    }

    /**
     * @test
     */
    public function it_connects_with_valid_credentials(): void
    {
        $connection = $this->createConnection();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @test
     */
    public function it_returns_internal_connection(): void
    {
        $connection = $this->createConnection();

        $this->assertInstanceOf(\PhpAmqpLib\Connection\AMQPStreamConnection::class, $connection->getResource());
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function it_reconnects(): void
    {
        $connection = $this->createConnection();
        $connection->reconnect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_connect(): void
    {
        $this->expectException(BadMethodCallException::class);

        $connection = $this->createConnection();
        $connection->connect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_disconnect(): void
    {
        $this->expectException(BadMethodCallException::class);

        $connection = $this->createConnection();
        $connection->disconnect();
    }

    public function createConnection(?ConnectionOptions $options = null): Connection
    {
        if (null === $options) {
            $options = new ConnectionOptions(['host' => 'rabbitmq']);
        }

        $options->setVhost('/humus-amqp-test');

        return new StreamConnection($options);
    }
}
