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

namespace HumusTest\Amqp;

use Humus\Amqp\AmqpChannel;
use Humus\Amqp\AmqpConnection;
use HumusTest\Amqp\Helper\ValidCredentialsTrait;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractChannelTest
 * @package HumusTest\Amqp
 */
abstract class AbstractChannelTest extends TestCase
{
    use ValidCredentialsTrait;

    /**
     * @var AmqpConnection
     */
    protected $connection;

    /**
     * @var AmqpChannel
     */
    protected $channel;

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
        $this->assertNotSame($this->getNewConnection(), $connection);
    }

    /**
     * @test
     */
    public function it_creates_multiple_channels()
    {
        $this->getNewChannel($this->connection);
    }

    abstract protected function getNewConnection() : AmqpConnection;

    abstract protected function getNewChannel(AmqpConnection $connection) : AmqpChannel;
}
