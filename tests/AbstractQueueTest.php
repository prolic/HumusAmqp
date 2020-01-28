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
use Humus\Amqp\Constants;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exception\QueueException;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractQueueTest
 * @package HumusTest\Amqp
 */
abstract class AbstractQueueTest extends TestCase implements CanCreateConnection
{
    use DeleteOnTearDownTrait;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var Exchange
     */
    protected $exchange;

    /**
     * @var Queue
     */
    protected $queue;

    protected function setUp()
    {
        $connection = $this->createConnection();
        $this->channel = $connection->newChannel();

        $this->exchange = $this->channel->newExchange();
        $this->exchange->setType('topic');
        $this->exchange->setName('test-exchange');
        $this->exchange->declareExchange();

        $this->queue = $this->channel->newQueue();
        $this->queue->setName('test-queue');
        $this->queue->setArguments([
            'foo' => 'bar',
            'baz' => 1,
            'bam' => true,
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
        ]);
        $this->queue->setFlags(Constants::AMQP_DURABLE);
        $this->queue->declareQueue();
        $this->queue->bind('test-exchange', '#');

        $this->addToCleanUp($this->exchange);
        $this->addToCleanUp($this->queue);
    }

    /**
     * @test
     */
    public function it_sets_argument()
    {
        $this->queue->setArgument('key', 'value');

        $this->assertEquals('value', $this->queue->getArgument('key'));

        $this->queue->setArguments([
            'foo' => 'bar',
            'baz' => 1,
            'bam' => true,
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
        ]);

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => 1,
                'bam' => true,
                'table' => [
                    'foo' => 'bar',
                ],
                'array' => [
                    'baz',
                ],
            ],
            $this->queue->getArguments()
        );

        $this->assertFalse($this->queue->getArgument('invalid key'));
    }

    /**
     * @test
     */
    public function it_sets_name_flags_and_type()
    {
        $this->assertEquals('test-queue', $this->queue->getName());

        $this->queue->setName('test');

        $this->assertEquals('test', $this->queue->getName());

        $this->queue->setFlags(Constants::AMQP_DURABLE);

        $this->assertEquals(2, $this->queue->getFlags());

        $this->queue->setFlags(Constants::AMQP_PASSIVE | Constants::AMQP_DURABLE);

        $this->assertEquals(6, $this->queue->getFlags());
    }

    /**
     * @test
     */
    public function it_binds_with_arguments()
    {
        $this->queue->unbind('test-exchange', '#', [
            'foo' => 'bar',
            'baz' => 1,
            'bam' => true,
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
        ]);
        $this->queue->bind('test-exchange', '', [
            'foo' => 'bar',
            'baz' => 1,
            'bam' => true,
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_unbinds_queue()
    {
        $this->queue->unbind('test-exchange');
    }

    /**
     * @test
     */
    public function it_returns_channel_and_connection()
    {
        $this->assertInstanceOf(Channel::class, $this->queue->getChannel());
        $this->assertInstanceOf(Connection::class, $this->queue->getConnection());
    }

    /**
     * @test
     */
    public function it_consumes_with_callback()
    {
        $this->exchange->publish('foo');
        $this->exchange->publish('bar');

        $result = [];
        $cnt = 2;
        $this->queue->consume(function (Envelope $envelope, Queue $queue) use (&$result, &$cnt) {
            $result[] = $envelope->getBody();
            $result[] = $queue->getName();
            $cnt--;

            return $cnt > 0;
        });

        $this->assertEquals(
            [
                'foo',
                'test-queue',
                'bar',
                'test-queue',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_consumes_without_callback()
    {
        $this->exchange->publish('foo');
        $this->exchange->publish('bar');

        $this->queue->consume(null);
    }

    /**
     * @test
     */
    public function it_produces_and_get_messages_from_queue()
    {
        $this->exchange->publish('foo', '', Constants::AMQP_NOPARAM, [
            'priority' => 5,
            'expiration' => 100000,
            'delivery_mode' => 2,
        ]);
        $this->exchange->publish('bar');

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);
        $msg2 = $this->queue->get(Constants::AMQP_NOPARAM);
        $this->queue->reject($msg2->getDeliveryTag(), Constants::AMQP_REQUEUE);
        $msg3 = $this->queue->get(Constants::AMQP_NOPARAM);

        $this->assertSame('foo', $msg1->getBody());
        $this->assertFalse($msg1->isRedelivery());
        $this->assertEquals(5, $msg1->getPriority());
        $this->assertEmpty($msg1->getHeaders());
        $this->assertEquals(100000, $msg1->getExpiration());
        $this->assertEquals(2, $msg1->getDeliveryMode());

        $this->assertSame('bar', $msg2->getBody());
        $this->assertFalse($msg2->isRedelivery());
        $this->assertEquals(0, $msg2->getPriority());
        $this->assertEmpty($msg2->getHeaders());
        $this->assertEquals(0, $msg2->getExpiration());
        $this->assertEquals(1, $msg2->getDeliveryMode());

        $this->assertSame('bar', $msg3->getBody());
        $this->assertTrue($msg3->isRedelivery());
        $this->assertEquals(0, $msg3->getPriority());
        $this->assertEmpty($msg3->getHeaders());
        $this->assertEquals(0, $msg3->getExpiration());
        $this->assertEquals(1, $msg3->getDeliveryMode());
    }

    /**
     * @test
     */
    public function it_produces_transactional_and_get_messages_from_queue()
    {
        $this->channel->startTransaction();
        $this->exchange->publish('foo');
        $this->channel->commitTransaction();

        $this->channel->startTransaction();
        $this->exchange->publish('bar');
        $this->channel->commitTransaction();

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);
        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertSame('foo', $msg1->getBody());
        $this->assertSame('bar', $msg2->getBody());
    }

    /**
     * @test
     */
    public function it_rolls_back_transation()
    {
        $this->channel->startTransaction();
        $this->exchange->publish('foo');
        $this->channel->rollbackTransaction();

        $msg = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertFalse($msg);
    }

    /**
     * @test
     */
    public function it_purges_messages_from_queue()
    {
        $this->channel->startTransaction();
        $this->exchange->publish('foo');
        $this->exchange->publish('bar');
        $this->channel->commitTransaction();

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertInstanceOf(Envelope::class, $msg1);

        $this->queue->purge();

        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertFalse($msg2);
    }

    /**
     * @test
     */
    public function it_returns_envelope_information()
    {
        $this->exchange->publish('foo', 'routingKey', Constants::AMQP_NOPARAM, [
            'content_type' => 'text/plain',
            'content_encoding' => 'UTF-8',
            'message_id' => 'some message id',
            'app_id' => 'app id',
            'user_id' => 'guest', // must be same as login data
            'delivery_mode' => 1,
            'priority' => 5,
            'timestamp' => 25,
            'expiration' => 1000,
            'type' => 'message type',
            'headers' => [
                'header1' => 'value1',
                'header2' => 'value2',
            ],
        ]);

        $msg = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertFalse($msg->isRedelivery());
        $this->assertEquals('test-exchange', $msg->getExchangeName());
        $this->assertEquals('text/plain', $msg->getContentType());
        $this->assertEquals('UTF-8', $msg->getContentEncoding());
        $this->assertEquals('some message id', $msg->getMessageId());
        $this->assertEquals('app id', $msg->getAppId());
        $this->assertEquals('guest', $msg->getUserId());
        $this->assertEquals(1, $msg->getDeliveryMode());
        $this->assertEquals(1, $msg->getDeliveryTag());
        $this->assertEquals(5, $msg->getPriority());
        $this->assertEquals(25, $msg->getTimestamp());
        $this->assertEquals(1000, $msg->getExpiration());
        $this->assertEquals('message type', $msg->getType());
        $this->assertEquals('routingKey', $msg->getRoutingKey());
        $this->assertEquals(
            [
                'header1' => 'value1',
                'header2' => 'value2',
            ],
            $msg->getHeaders()
        );
        $this->assertTrue($msg->hasHeader('header1'));
        $this->assertFalse($msg->hasHeader('invalid header'));
        $this->assertEquals('value1', $msg->getHeader('header1'));
    }

    /**
     * @test
     */
    public function it_acks()
    {
        $this->exchange->publish('foo');

        $msg = $this->queue->get(Constants::AMQP_NOPARAM);

        $this->queue->ack($msg->getDeliveryTag());

        $msg = $this->queue->get(Constants::AMQP_NOPARAM);

        $this->assertFalse($msg);
    }

    /**
     * @test
     */
    public function it_nacks_and_rejects_message()
    {
        $this->exchange->publish('foo');

        $msg = $this->queue->get(Constants::AMQP_NOPARAM);

        $this->queue->reject($msg->getDeliveryTag(), Constants::AMQP_REQUEUE);

        $msg = $this->queue->get(Constants::AMQP_NOPARAM);

        $this->assertEquals('foo', $msg->getBody());
        $this->assertTrue($msg->isRedelivery());

        $this->queue->nack($msg->getDeliveryTag(), Constants::AMQP_NOPARAM);

        $msg = $this->queue->get(Constants::AMQP_NOPARAM);

        $this->assertFalse($msg);
    }

    /**
     * @test
     */
    public function it_cannot_declare_queue_with_reserved_name_prefix()
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('ACCESS_REFUSED - queue name \'amq.foo\' contains reserved prefix \'amq.*\'');
        $this->expectExceptionCode(403);

        $this->queue->setName('amq.foo');
        $this->queue->declareQueue();
    }

    /**
     * @test
     */
    public function it_cannot_declare_queue_with_closed_channel()
    {
        $this->expectException(ChannelException::class);

        $this->channel->getConnection()->reconnect();

        $this->queue->declareQueue();
    }

    /**
     * @test
     */
    public function it_cannot_redeclare_with_other_arguments()
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage(
            'PRECONDITION_FAILED - inequivalent arg \'durable\' for queue \'test-queue\' in vhost \'/humus-amqp-test\': received \'false\' but current is \'true\''
        );
        $this->expectExceptionCode(406);

        $this->queue->setFlags(Constants::AMQP_AUTODELETE);
        $this->queue->declareQueue();
    }

    /**
     * @test
     */
    public function it_cannot_access_an_exclusive_queue_from_another_channel()
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage(
            'RESOURCE_LOCKED - cannot obtain exclusive access to locked queue \'test-exclusive-queue\' in vhost \'/humus-amqp-test\''
        );
        $this->expectExceptionCode(405);

        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setType('topic');
        $exchange->setName('test-exchange2');
        $exchange->declareExchange();

        $queue = $channel->newQueue();
        $queue->setName('test-exclusive-queue');
        $queue->setArguments([
            'foo' => 'bar',
            'baz' => 1,
            'bam' => true,
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
        ]);
        $queue->setFlags(Constants::AMQP_EXCLUSIVE | Constants::AMQP_AUTODELETE);
        $queue->declareQueue();
        $queue->bind('test-exchange2', '#');

        $this->addToCleanUp($exchange);
        $this->addToCleanUp($queue);

        $connection2 = $this->createConnection();
        $channel2 = $connection2->newChannel();
        $queue2 = $channel2->newQueue();
        $queue2->setName('test-exclusive-queue');
        $queue2->setFlags(Constants::AMQP_EXCLUSIVE | Constants::AMQP_AUTODELETE);
        $queue2->declareQueue();
    }

    /**
     * @test
     */
    public function it_throws_exception_in_passive_mode_when_queues_does_not_exist()
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage(
            'NOT_FOUND - no queue \'unknown-queue\' in vhost \'/humus-amqp-test\''
        );
        $this->expectExceptionCode(404);

        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $queue = $channel->newQueue();
        $queue->setName('unknown-queue');
        $queue->setFlags(Constants::AMQP_PASSIVE);
        $queue->declareQueue();
    }
}
