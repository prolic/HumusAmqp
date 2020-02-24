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

namespace HumusTest\Amqp;

use Humus\Amqp\Channel;
use Humus\Amqp\Connection;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateConnection;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractChannelTest
 * @package HumusTest\Amqp
 */
abstract class AbstractChannelTest extends TestCase implements CanCreateConnection
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $this->connection = $this->createConnection();
        $this->channel = $this->connection->newChannel();
    }

    /**
     * @test
     */
    public function it_returns_channel_id()
    {
        $this->assertEquals(1, $this->channel->getChannelId());
    }

    /**
     * @test
     */
    public function it_returns_connection()
    {
        $connection = $this->channel->getConnection();

        $this->assertSame($this->connection, $connection);
        $this->assertNotSame($this->createConnection(), $connection);
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function it_creates_multiple_channels()
    {
        $this->connection->newChannel();
    }

    /**
     * @test
     */
    public function it_creates_new_exchange()
    {
        $channel = $this->connection->newChannel();

        $exchange = $channel->newExchange();

        $this->assertInstanceOf(Exchange::class, $exchange);
    }

    /**
     * @test
     */
    public function it_creates_new_queue()
    {
        $channel = $this->connection->newChannel();

        $queue = $channel->newQueue();

        $this->assertInstanceOf(Queue::class, $queue);
    }

    /**
     * @test
     */
    public function it_changes_qos()
    {
        $channel = $this->connection->newChannel();
        $channel->qos(0, 5);

        $this->assertEquals(0, $channel->getPrefetchSize());
        $this->assertEquals(5, $channel->getPrefetchCount());

        $channel->setPrefetchSize(0);

        $this->assertEquals(0, $channel->getPrefetchSize());

        $channel->setPrefetchCount(20);

        $this->assertEquals(20, $channel->getPrefetchCount());
    }
}
