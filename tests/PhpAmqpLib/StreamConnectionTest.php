<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 *  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 *  A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 *  OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 *  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 *  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 *  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *  
 *  This software consists of voluntary contributions made by many individuals
 *  and is licensed under the MIT license.
 */

declare (strict_types=1);

namespace HumusTest\Amqp\PhpAmqpLib;

use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use Humus\Amqp\Exception\BadMethodCallException;
use HumusTest\Amqp\AbstractConnectionTest;

/**
 * Class StreamConnectionTest
 * @package HumusTest\Amqp\PhpAmqpLib
 */
final class StreamConnectionTest extends AbstractConnectionTest
{
    /**
     * @test
     */
    public function it_throws_exception_with_invalid_credentials()
    {
        $this->expectException(\Exception::class);

        new StreamConnection($this->invalidCredentials());
    }

    /**
     * @test
     */
    public function it_connects_with_valid_credentials()
    {
        $connection = new StreamConnection($this->validCredentials());

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @test
     */
    public function it_returns_internal_connection()
    {
        $connection = new StreamConnection($this->validCredentials());

        $this->assertInstanceOf(\PhpAmqpLib\Connection\AMQPStreamConnection::class, $connection->getResource());
    }

    /**
     * @test
     */
    public function it_reconnects()
    {
        $connection = new StreamConnection($this->validCredentials());
        $connection->reconnect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_connect()
    {
        $this->expectException(BadMethodCallException::class);

        $connection = new StreamConnection($this->validCredentials());
        $connection->connect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_pconnect()
    {
        $this->expectException(BadMethodCallException::class);

        $connection = new StreamConnection($this->validCredentials());
        $connection->pconnect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_pdisconnect()
    {
        $this->expectException(BadMethodCallException::class);

        $connection = new StreamConnection($this->validCredentials());
        $connection->pdisconnect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_disconnect()
    {
        $this->expectException(BadMethodCallException::class);

        $connection = new StreamConnection($this->validCredentials());
        $connection->disconnect();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_preconnect()
    {
        $this->expectException(BadMethodCallException::class);

        $connection = new StreamConnection($this->validCredentials());
        $connection->preconnect();
    }
}
