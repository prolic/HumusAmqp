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

namespace HumusTest\Amqp\AmqpExtension;

use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Driver\AmqpExtension\Connection;
use HumusTest\Amqp\AbstractConnectionTest;
use HumusTest\Amqp\AmqpExtension\Helper\CreateConnectionTrait;

/**
 * Class ConnectionTest
 * @package HumusTest\Amqp\AmqpExtension
 */
final class ConnectionTest extends AbstractConnectionTest
{
    use CreateConnectionTrait;

    protected function setUp()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_with_invalid_credentials()
    {
        $this->expectException(\Exception::class);

        $connection = new Connection($this->invalidCredentials());

        $this->assertFalse($connection->isConnected());

        $connection->connect();
    }

    /**
     * @test
     */
    public function it_connects_with_valid_credentials()
    {
        $connection = $this->createConnection();

        $this->assertTrue($connection->isConnected());

        $connection->disconnect();

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @test
     */
    public function it_uses_persistent_connection()
    {
        $connection = $this->createConnection(new ConnectionOptions(['persistent' => true]));

        $this->assertTrue($connection->isConnected());

        $connection->disconnect();

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @test
     */
    public function it_reconnects()
    {
        $connection = $this->createConnection();

        $this->assertTrue($connection->isConnected());

        $connection->reconnect();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @test
     */
    public function it_reconnects_a_persistent_connection()
    {
        $connection = $this->createConnection(new ConnectionOptions(['persistent' => true]));

        $this->assertTrue($connection->isConnected());

        $connection->reconnect();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @test
     */
    public function it_returns_internal_connection()
    {
        $connection = $this->createConnection();

        $this->assertInstanceOf(\AMQPConnection::class, $connection->getResource());
    }

    /**
     * @test
     * @group ssl
     */
    public function it_connects_with_ssl()
    {
        if (true) {
            $this->markTestSkipped('SSL Connection tests have to be run manually');
        }

        $options = new ConnectionOptions();
        $options->setVhost('/humus-amqp-test');
        $options->setPort(5671);
        $options->setCACert(__DIR__ . '/../test_certs/cacert.pem');
        $options->setCert(__DIR__ . '/../test_certs/cert.pem');
        $options->setKey(__DIR__ . '/../test_certs/key.pem');
        $options->setVerify(false);

        $connection = new Connection($options);

        $connection->connect();

        $this->assertTrue($connection->isConnected());

        $connection->disconnect();

        $this->assertFalse($connection->isConnected());
    }
}
