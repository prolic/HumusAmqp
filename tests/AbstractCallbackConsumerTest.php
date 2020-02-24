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

use Humus\Amqp\CallbackConsumer;
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Constants;
use Humus\Amqp\DeliveryResult;
use Humus\Amqp\Envelope;
use Humus\Amqp\FlushDeferredResult;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use HumusTest\Amqp\TestAsset\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class AbstractCallbackConsumer
 * @package HumusTest\Amqp
 */
abstract class AbstractCallbackConsumerTest extends TestCase implements CanCreateConnection
{
    use DeleteOnTearDownTrait;

    /**
     * @test
     */
    public function it_processes_messages_and_acks()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_ACK();
            }
        );

        $consumer->consume(7);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
                'message #4',
                'message #5',
                'message #6',
                'message #7',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();
        $this->assertCount(14, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('info', $loggerResult[1]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #2', $loggerResult[2]['context']['body']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('message #3', $loggerResult[4]['context']['body']);

        $this->assertEquals('info', $loggerResult[5]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[5]['message']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('message #4', $loggerResult[6]['context']['body']);

        $this->assertEquals('info', $loggerResult[7]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[7]['message']);

        $this->assertEquals('debug', $loggerResult[8]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[8]['message']);
        $this->assertEquals('message #5', $loggerResult[8]['context']['body']);

        $this->assertEquals('info', $loggerResult[9]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[9]['message']);

        $this->assertEquals('debug', $loggerResult[10]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[10]['message']);
        $this->assertEquals('message #6', $loggerResult[10]['context']['body']);

        $this->assertEquals('info', $loggerResult[11]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[11]['message']);

        $this->assertEquals('debug', $loggerResult[12]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[12]['message']);
        $this->assertEquals('message #7', $loggerResult[12]['context']['body']);

        $this->assertEquals('info', $loggerResult[13]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[13]['message']);
    }

    /**
     * @test
     */
    public function it_processes_messages_and_rejects()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_REJECT();
            }
        );

        $consumer->consume(7);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
                'message #4',
                'message #5',
                'message #6',
                'message #7',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();
        $this->assertCount(14, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Rejected message', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #2', $loggerResult[2]['context']['body']);

        $this->assertEquals('debug', $loggerResult[3]['level']);
        $this->assertEquals('Rejected message', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('message #3', $loggerResult[4]['context']['body']);

        $this->assertEquals('debug', $loggerResult[5]['level']);
        $this->assertEquals('Rejected message', $loggerResult[5]['message']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('message #4', $loggerResult[6]['context']['body']);

        $this->assertEquals('debug', $loggerResult[7]['level']);
        $this->assertEquals('Rejected message', $loggerResult[7]['message']);

        $this->assertEquals('debug', $loggerResult[8]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[8]['message']);
        $this->assertEquals('message #5', $loggerResult[8]['context']['body']);

        $this->assertEquals('debug', $loggerResult[9]['level']);
        $this->assertEquals('Rejected message', $loggerResult[9]['message']);

        $this->assertEquals('debug', $loggerResult[10]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[10]['message']);
        $this->assertEquals('message #6', $loggerResult[10]['context']['body']);

        $this->assertEquals('debug', $loggerResult[11]['level']);
        $this->assertEquals('Rejected message', $loggerResult[11]['message']);

        $this->assertEquals('debug', $loggerResult[12]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[12]['message']);
        $this->assertEquals('message #7', $loggerResult[12]['context']['body']);

        $this->assertEquals('debug', $loggerResult[13]['level']);
        $this->assertEquals('Rejected message', $loggerResult[13]['message']);
    }

    /**
     * @test
     */
    public function it_processes_messages_rejects_and_requeues()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $i = 0;

        $consumer = new CallbackConsumer(
            $queue,
            new NullLogger(),
            7,
            function (Envelope $envelope, Queue $queue) use (&$result, &$i) {
                $i++;
                if ((int) $envelope->getBody() % 2 === 0 && ! $envelope->isRedelivery()) {
                    $result[] = $envelope->getBody();

                    return DeliveryResult::MSG_REJECT_REQUEUE();
                }
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_ACK();
            }
        );

        $consumer->consume(10);

        $this->assertCount(10, $result);
    }

    /**
     * @test
     */
    public function it_processes_messages_defers_and_acks_block()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_DEFER();
            },
            null,
            null,
            ''
        );

        $consumer->consume(5);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
                'message #4',
                'message #5',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();
        $this->assertCount(7, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[1]['message']);
        $this->assertEquals('message #2', $loggerResult[1]['context']['body']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #3', $loggerResult[2]['context']['body']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Acknowledged 3 messages at.+/', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('message #4', $loggerResult[4]['context']['body']);

        $this->assertEquals('debug', $loggerResult[5]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[5]['message']);
        $this->assertEquals('message #5', $loggerResult[5]['context']['body']);

        $this->assertEquals('info', $loggerResult[6]['level']);
        $this->assertRegExp('/^Acknowledged 2 messages at.+/', $loggerResult[6]['message']);
    }

    /**
     * @test
     * @group failing_test
     */
    public function it_handles_flush_deferred_after_timeout()
    {
        $connection = $this->createConnection(new ConnectionOptions(['read_timeout' => 1]));
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->setFlags(Constants::AMQP_DURABLE);
        $queue->declareQueue();
        $queue->bind('test-exchange');

        for ($i = 1; $i < 3; $i++) {
            $exchange->publish('message #' . $i);
        }

        $exchange->delete();

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_DEFER();
            },
            function (Queue $queue) {
                return FlushDeferredResult::MSG_REJECT_REQUEUE();
            },
            null,
            ''
        );

        $consumer->consume(3);

        $ch = $connection->newChannel(); // create new channel, old one is closed
        $queue = $ch->newQueue();
        $queue->setName('test-queue');

        $envelope = $queue->get(Constants::AMQP_AUTOACK);
        $this->assertEquals('message #1', $envelope->getBody());
        $this->assertTrue($envelope->isRedelivery());

        $envelope = $queue->get(Constants::AMQP_AUTOACK);
        $this->assertEquals('message #2', $envelope->getBody());
        $this->assertTrue($envelope->isRedelivery());

        $queue->delete();

        $this->assertEquals(
            [
                'message #1',
                'message #2',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(4, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[1]['message']);
        $this->assertEquals('message #2', $loggerResult[1]['context']['body']);

        $this->assertEquals('error', $loggerResult[2]['level']);
        $this->assertRegExp('/^Exception.+/', $loggerResult[2]['message']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Not acknowledged 2 messages at.+/', $loggerResult[3]['message']);
    }

    /**
     * @test
     */
    public function it_uses_custom_flush_deferred_callback()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_DEFER();
            },
            function () use (&$result) {
                $result[] = 'flushed';

                return FlushDeferredResult::MSG_REJECT();
            },
            null,
            ''
        );

        $consumer->consume(7);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
                'flushed',
                'message #4',
                'message #5',
                'message #6',
                'flushed',
                'message #7',
                'flushed',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[1]['message']);
        $this->assertEquals('message #2', $loggerResult[1]['context']['body']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #3', $loggerResult[2]['context']['body']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Not acknowledged 3 messages at.+/', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('message #4', $loggerResult[4]['context']['body']);

        $this->assertEquals('debug', $loggerResult[5]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[5]['message']);
        $this->assertEquals('message #5', $loggerResult[5]['context']['body']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('message #6', $loggerResult[6]['context']['body']);

        $this->assertEquals('info', $loggerResult[7]['level']);
        $this->assertRegExp('/^Not acknowledged 3 messages at.+/', $loggerResult[7]['message']);

        $this->assertEquals('debug', $loggerResult[8]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[8]['message']);
        $this->assertEquals('message #7', $loggerResult[8]['context']['body']);

        $this->assertEquals('info', $loggerResult[9]['level']);
        $this->assertRegExp('/^Not acknowledged 1 messages at.+/', $loggerResult[9]['message']);
    }

    /**
     * @test
     */
    public function it_rejects_and_requeues_on_flush_deferred()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 7; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $flushes = 0;

        $consumer = new CallbackConsumer(
            $queue,
            new NullLogger(),
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_DEFER();
            },
            function () use (&$result, &$flushes) {
                $flushes++;
                $result[] = 'flushed';
                if (1 === $flushes) {
                    return FlushDeferredResult::MSG_REJECT_REQUEUE();
                }

                return FlushDeferredResult::MSG_ACK();
            },
            null,
            ''
        );

        $consumer->consume(9);

        $this->assertCount(12, $result);
        $this->assertEquals(3, $flushes);
    }

    /**
     * @test
     */
    public function it_handles_delivery_exception()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                throw new \Exception('foo');
            },
            null,
            function (\Exception $e) use (&$result) {
                $result[] = $e->getMessage();
            }
        );

        $consumer->consume(3);

        $this->assertEquals(
            [
                'foo',
                'foo',
                'foo',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(9, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('error', $loggerResult[1]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Rejected and requeued message', $loggerResult[2]['message']);
        $this->assertEquals('message #1', $loggerResult[2]['context']['body']);

        $this->assertEquals('debug', $loggerResult[3]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[3]['message']);
        $this->assertEquals('message #2', $loggerResult[3]['context']['body']);

        $this->assertEquals('error', $loggerResult[4]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[4]['message']);

        $this->assertEquals('debug', $loggerResult[5]['level']);
        $this->assertEquals('Rejected and requeued message', $loggerResult[5]['message']);
        $this->assertEquals('message #2', $loggerResult[5]['context']['body']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('message #3', $loggerResult[6]['context']['body']);

        $this->assertEquals('error', $loggerResult[7]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[7]['message']);

        $this->assertEquals('debug', $loggerResult[8]['level']);
        $this->assertEquals('Rejected and requeued message', $loggerResult[8]['message']);
        $this->assertEquals('message #3', $loggerResult[8]['context']['body']);
    }

    /**
     * @test
     */
    public function it_handles_delivery_exception_when_error_callback_returns_true()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                throw new \Exception('foo');
            },
            null,
            function (\Exception $e) use (&$result) {
                $result[] = $e->getMessage();

                return true;
            }
        );

        $consumer->consume(3);

        $this->assertEquals(
            [
                'foo',
                'foo',
                'foo',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(9, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('error', $loggerResult[1]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Rejected and requeued message', $loggerResult[2]['message']);
        $this->assertEquals('message #1', $loggerResult[2]['context']['body']);

        $this->assertEquals('debug', $loggerResult[3]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[3]['message']);
        $this->assertEquals('message #2', $loggerResult[3]['context']['body']);

        $this->assertEquals('error', $loggerResult[4]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[4]['message']);

        $this->assertEquals('debug', $loggerResult[5]['level']);
        $this->assertEquals('Rejected and requeued message', $loggerResult[5]['message']);
        $this->assertEquals('message #2', $loggerResult[5]['context']['body']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('message #3', $loggerResult[6]['context']['body']);

        $this->assertEquals('error', $loggerResult[7]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[7]['message']);

        $this->assertEquals('debug', $loggerResult[8]['level']);
        $this->assertEquals('Rejected and requeued message', $loggerResult[8]['message']);
        $this->assertEquals('message #3', $loggerResult[8]['context']['body']);
    }

    /**
     * @test
     */
    public function it_handles_delivery_exception_when_error_callback_returns_false()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                throw new \Exception('foo');
            },
            null,
            function (\Exception $e) use (&$result) {
                $result[] = $e->getMessage();

                return false;
            }
        );

        $consumer->consume(3);

        $this->assertEquals(
            [
                'foo',
                'foo',
                'foo',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(9, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('error', $loggerResult[1]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Rejected message', $loggerResult[2]['message']);
        $this->assertEquals('message #1', $loggerResult[2]['context']['body']);

        $this->assertEquals('debug', $loggerResult[3]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[3]['message']);
        $this->assertEquals('message #2', $loggerResult[3]['context']['body']);

        $this->assertEquals('error', $loggerResult[4]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[4]['message']);

        $this->assertEquals('debug', $loggerResult[5]['level']);
        $this->assertEquals('Rejected message', $loggerResult[5]['message']);
        $this->assertEquals('message #2', $loggerResult[5]['context']['body']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('message #3', $loggerResult[6]['context']['body']);

        $this->assertEquals('error', $loggerResult[7]['level']);
        $this->assertEquals('Exception during handleDelivery: foo', $loggerResult[7]['message']);

        $this->assertEquals('debug', $loggerResult[8]['level']);
        $this->assertEquals('Rejected message', $loggerResult[8]['message']);
        $this->assertEquals('message #3', $loggerResult[8]['context']['body']);
    }

    /**
     * @test
     */
    public function it_handles_flush_deferred_exception()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_DEFER();
            },
            function () {
                throw new \Exception('foo');
            },
            function (\Exception $e) use (&$result) {
                $result[] = $e->getMessage();
            }
        );

        $consumer->consume(3);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
                'foo',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(5, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[1]['message']);
        $this->assertEquals('message #2', $loggerResult[1]['context']['body']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #3', $loggerResult[2]['context']['body']);

        $this->assertEquals('error', $loggerResult[3]['level']);
        $this->assertEquals('Exception during flushDeferred: foo', $loggerResult[3]['message']);

        $this->assertEquals('info', $loggerResult[4]['level']);
        $this->assertRegExp('/^Not acknowledged 3 messages at.+/', $loggerResult[4]['message']);
    }

    /**
     * @test
     */
    public function it_handles_shutdown_message()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $exchange->publish('stop!!!', '', Constants::AMQP_NOPARAM, [
            'app_id' => 'Humus\Amqp',
            'type' => 'shutdown',
        ]);

        for ($i = 4; $i < 7; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_ACK();
            }
        );

        $consumer->consume(7);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(9, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('info', $loggerResult[1]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #2', $loggerResult[2]['context']['body']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('message #3', $loggerResult[4]['context']['body']);

        $this->assertEquals('info', $loggerResult[5]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[5]['message']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('stop!!!', $loggerResult[6]['context']['body']);
        $this->assertEquals('shutdown', $loggerResult[6]['context']['type']);

        $this->assertEquals('info', $loggerResult[7]['level']);
        $this->assertEquals('Shutdown message received', $loggerResult[7]['message']);

        $this->assertEquals('info', $loggerResult[8]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[8]['message']);
    }

    /**
     * @test
     */
    public function it_handles_reconfigure_message()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        // $idleTimeout, $target, $prefetchSize, $prefetchCount
        $exchange->publish(
            json_encode([
                1,
                8,
                0,
                1,
            ]),
            '',
            Constants::AMQP_NOPARAM,
            [
                'app_id' => 'Humus\Amqp',
                'type' => 'reconfigure',
            ]
        );

        for ($i = 4; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_ACK();
            }
        );

        $consumer->consume(100);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
                'message #4',
                'message #5',
                'message #6',
                'message #7',
            ],
            $result
        );

        $loggerResult = $logger->loggerResult();

        $this->assertCount(17, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);
        $this->assertEquals('message #1', $loggerResult[0]['context']['body']);

        $this->assertEquals('info', $loggerResult[1]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[2]['message']);
        $this->assertEquals('message #2', $loggerResult[2]['context']['body']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('message #3', $loggerResult[4]['context']['body']);

        $this->assertEquals('info', $loggerResult[5]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[5]['message']);

        $this->assertEquals('debug', $loggerResult[6]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[6]['message']);
        $this->assertEquals('[1,8,0,1]', $loggerResult[6]['context']['body']);
        $this->assertEquals('reconfigure', $loggerResult[6]['context']['type']);

        $this->assertEquals('info', $loggerResult[7]['level']);
        $this->assertEquals('Reconfigure message received', $loggerResult[7]['message']);

        $this->assertEquals('info', $loggerResult[8]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[8]['message']);

        $this->assertEquals('debug', $loggerResult[9]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[9]['message']);
        $this->assertEquals('message #4', $loggerResult[9]['context']['body']);

        $this->assertEquals('info', $loggerResult[10]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[10]['message']);

        $this->assertEquals('debug', $loggerResult[11]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[11]['message']);
        $this->assertEquals('message #5', $loggerResult[11]['context']['body']);

        $this->assertEquals('info', $loggerResult[12]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[12]['message']);

        $this->assertEquals('debug', $loggerResult[13]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[13]['message']);
        $this->assertEquals('message #6', $loggerResult[13]['context']['body']);

        $this->assertEquals('info', $loggerResult[14]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[14]['message']);

        $this->assertEquals('debug', $loggerResult[15]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[15]['message']);
        $this->assertEquals('message #7', $loggerResult[15]['context']['body']);

        $this->assertEquals('info', $loggerResult[16]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[16]['message']);
    }

    /**
     * @test
     */
    public function it_errors_invalid_reconfigure_message()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        $exchange->publish(
            json_encode([
                'invalid',
            ]),
            '',
            Constants::AMQP_NOPARAM,
            [
                'app_id' => 'Humus\Amqp',
                'type' => 'reconfigure',
            ]
        );

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_ACK();
            }
        );

        $consumer->consume(1);

        $this->assertEquals([], $result);

        $loggerResult = $logger->loggerResult();

        $this->assertCount(4, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);

        $this->assertEquals('info', $loggerResult[1]['level']);
        $this->assertEquals('Reconfigure message received', $loggerResult[1]['message']);

        $this->assertEquals('error', $loggerResult[2]['level']);
        $this->assertEquals('Exception during reconfiguration: Undefined offset: 1', $loggerResult[2]['message']);

        $this->assertEquals('debug', $loggerResult[3]['level']);
        $this->assertEquals('Rejected message', $loggerResult[3]['message']);
    }

    /**
     * @test
     */
    public function it_errors_invalid_internal_message()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $exchange = $channel->newExchange();
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $channel->newQueue();
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        $exchange->publish(
            json_encode(['invalid']),
            '',
            Constants::AMQP_NOPARAM,
            [
                'app_id' => 'Humus\Amqp',
                'type' => 'invalid',
            ]
        );

        $result = [];
        $logger = new ArrayLogger();

        $consumer = new CallbackConsumer(
            $queue,
            $logger,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();

                return DeliveryResult::MSG_ACK();
            }
        );

        $consumer->consume(1);

        $this->assertEquals([], $result);

        $loggerResult = $logger->loggerResult();

        $this->assertCount(3, $loggerResult);

        $this->assertEquals('debug', $loggerResult[0]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[0]['message']);

        $this->assertEquals('error', $loggerResult[1]['level']);
        $this->assertEquals('Invalid internal message: invalid', $loggerResult[1]['message']);

        $this->assertEquals('debug', $loggerResult[2]['level']);
        $this->assertEquals('Rejected message', $loggerResult[2]['message']);
    }
}
