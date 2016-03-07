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

use Humus\Amqp\AmqpChannel as AmqpChannelInterface;
use Humus\Amqp\AmqpConnection as AmqpConnectionInterface;
use Humus\Amqp\Driver\PhpAmqpLib\AmqpChannel;
use Humus\Amqp\Driver\PhpAmqpLib\AmqpStreamConnection;
use Humus\Amqp\Exception\BadMethodCallException;
use HumusTest\Amqp\AbstractChannelTest;

/**
 * Class ChannelTest
 * @package HumusTest\Amqp\PhpAmqpLib
 */
final class ChannelTest extends AbstractChannelTest
{
    protected function setUp()
    {
        $this->connection = $this->getNewConnection();
        $this->channel = $this->getNewChannel($this->connection);
    }

    /**
     * @test
     */
    public function it_changes_qos()
    {
        $channel = $this->getNewChannel($this->connection);

        $channel->qos(0, 5);
        $channel->setPrefetchSize(0);
        $channel->setPrefetchCount(20);
    }

    /**
     * @test
     */
    public function it_throws_exception_on_isConnected()
    {
        $this->expectException(BadMethodCallException::class);

        $channel = $this->getNewChannel($this->connection);
        $channel->isConnected();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_getPrefetchSize()
    {
        $this->expectException(BadMethodCallException::class);

        $channel = $this->getNewChannel($this->connection);
        $channel->getPrefetchSize();
    }

    /**
     * @test
     */
    public function it_throws_exception_on_getPrefetchCount()
    {
        $this->expectException(BadMethodCallException::class);

        $channel = $this->getNewChannel($this->connection);
        $channel->getPrefetchCount();
    }

    protected function getNewConnection() : AmqpConnectionInterface
    {
        return new AmqpStreamConnection($this->validCredentials());
    }

    /**
     * @return AmqpChannelInterface
     */
    protected function getNewChannel(AmqpConnectionInterface $connection) : AmqpChannelInterface
    {
        return new AmqpChannel($connection);
    }
}
