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
use Humus\Amqp\AmqpExchange;
use Humus\Amqp\AmqpQueue;
use Humus\Amqp\Constants;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\CanCreateQueue;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractQueueTest
 * @package HumusTest\Amqp
 */
abstract class AbstractQueueTest extends TestCase implements
    CanCreateExchange,
    CanCreateQueue
{
    use DeleteOnTearDownTrait;

    /**
     * @var AmqpExchange
     */
    protected $exchange;

    /**
     * @var AmqpQueue
     */
    protected $queue;

    protected function setUp()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $this->exchange = $this->createExchange($channel);
        $this->queue = $this->createQueue($channel);
    }

    /**
     * @test
     */
    public function it_sets_name_flags_type_and_arguments()
    {
        $this->assertEquals('', $this->queue->getName());
        $this->assertEmpty($this->queue->getArguments());

        $this->queue->setName('test');

        $this->assertEquals('test', $this->queue->getName());

        $this->queue->setFlags(Constants::AMQP_DURABLE);

        $this->assertEquals(2, $this->queue->getFlags());

        $this->queue->setFlags(Constants::AMQP_PASSIVE | Constants::AMQP_DURABLE);

        $this->assertEquals(6, $this->queue->getFlags());

        $this->queue->setArgument('key', 'value');

        $this->assertEquals('value', $this->queue->getArgument('key'));
        $this->assertFalse($this->queue->getArgument('invalid key'));

        $this->queue->setArguments([
            'foo' => 'bar',
            'baz' => 'bam'
        ]);

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => 'bam'
            ],
            $this->queue->getArguments()
        );
    }

    /**
     * @test
     */
    public function it_declares_and_binds_queue()
    {
        $this->addToCleanUp($this->exchange);
        $this->addToCleanUp($this->queue);

        $this->exchange->setType('direct');
        $this->exchange->setName('test');
        $this->exchange->declareExchange();

        $this->queue->setName('test2');
        $this->queue->declareQueue();
        $this->queue->bind('test');

        $this->queue->unbind('test');
    }

    /**
     * @test
     */
    public function it_returns_channel_and_connection()
    {
        $this->assertInstanceOf(AmqpChannel::class, $this->queue->getChannel());
        $this->assertInstanceOf(AmqpConnection::class, $this->queue->getConnection());
    }
}
