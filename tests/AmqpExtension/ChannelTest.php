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

use Humus\Amqp\AmqpChannel as AmqpChannelInterface;
use Humus\Amqp\AmqpConnection as AmqpConnectionInterface;
use Humus\Amqp\Driver\AmqpExtension\AmqpChannel;
use Humus\Amqp\Driver\AmqpExtension\AmqpConnection;
use Humus\Amqp\Exception\AmqpConnectionException;
use HumusTest\Amqp\AbstractChannelTest;

/**
 * Class ChannelTest
 * @package HumusTest\Amqp\AmqpExtension
 */
final class ChannelTest extends AbstractChannelTest
{
    protected function setUp()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $this->connection = $this->getNewConnection();
        $this->connection->connect();
        $this->channel = $this->getNewChannel($this->connection);
    }

    /**
     * @test
     */
    public function it_connects_the_channel()
    {
        $this->assertTrue($this->channel->isConnected());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_cannot_create_channel()
    {
        $this->expectException(AmqpConnectionException::class);
        $this->expectExceptionMessage('Could not create channel. No connection available.');

        $this->getNewChannel($this->getNewConnection($this->getNewConnection()));
    }

    /**
     * @test
     */
    public function it_changes_qos()
    {
        $channel = $this->getNewChannel($this->connection);
        $channel->qos(0, 5);

        $this->assertEquals(0, $channel->getPrefetchSize());
        $this->assertEquals(5, $channel->getPrefetchCount());

        $channel->setPrefetchSize(0);

        $this->assertEquals(0, $channel->getPrefetchSize());

        $channel->setPrefetchCount(20);

        $this->assertEquals(20, $channel->getPrefetchCount());
    }

    protected function getNewConnection() : AmqpConnectionInterface
    {
        return new AmqpConnection($this->validCredentials());
    }

    protected function getNewChannel(AmqpConnectionInterface $connection) : AmqpChannelInterface
    {
        return new AmqpChannel($connection);
    }
}
