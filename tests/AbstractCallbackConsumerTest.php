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

use Humus\Amqp\Constants;
use Humus\Amqp\Consumer;
use Humus\Amqp\CallbackConsumer;
use Humus\Amqp\Envelope;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\CanCreateQueue;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use Prophecy\Argument;

/**
 * Class AbstractCallbackConsumer
 * @package HumusTest\Amqp
 */
abstract class AbstractCallbackConsumerTest extends \PHPUnit_Framework_TestCase implements CanCreateExchange, CanCreateQueue
{
    use DeleteOnTearDownTrait;

    /**
     * @test
     */
    public function it_processes_messages_and_acks()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer($queue, 3, function (Envelope $envelope, Queue $queue) use (&$result) {
            $result[] = $envelope->getBody();
            return Consumer::MSG_ACK;
        });

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
    }

    /**
     * @test
     */
    public function it_processes_messages_and_rejects()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer(
            $queue,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();
                return Consumer::MSG_REJECT;
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
    }

    /**
     * @test
     */
    public function it_processes_messages_rejects_and_requeues()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
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
            7,
            function (Envelope $envelope, Queue $queue) use (&$result, &$i) {
                $i++;
                $result[] = $envelope->getBody();
                if ($i % 2 === 0 && ! $envelope->isRedelivery()) {
                    return Consumer::MSG_REJECT_REQUEUE;
                } else {
                    return Consumer::MSG_ACK;
                }
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
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer(
            $queue,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();
                return Consumer::MSG_DEFER;
            },
            null,
            null,
            null,
            3
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
    }

    /**
     * @test
     */
    public function it_uses_custom_flush_deferred_callback()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 8; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer(
            $queue,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();
                return Consumer::MSG_DEFER;
            },
            function () use (&$result) {
                $result[] = 'flushed';
            },
            null,
            null,
            3
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
                'flushed'
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_delivery_exception()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer(
            $queue,
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
                'foo'
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_flush_deferred_exception()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer(
            $queue,
            3,
            function (Envelope $envelope, Queue $queue) use (&$result) {
                $result[] = $envelope->getBody();
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
    }

    /**
     * @test
     */
    public function it_handles_shutdown_message()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $exchange->publish('stop!!!', null, Constants::AMQP_NOPARAM, [
            'app_id' => 'Humus\Amqp',
            'type' => 'shutdown',
        ]);

        for ($i = 4; $i < 7; $i++) {
            $exchange->publish('message #' . $i);
        }

        $result = [];

        $consumer = new CallbackConsumer($queue, 3, function (Envelope $envelope, Queue $queue) use (&$result) {
            $result[] = $envelope->getBody();
            return Consumer::MSG_ACK;
        });

        $consumer->consume(7);

        $this->assertEquals(
            [
                'message #1',
                'message #2',
                'message #3',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_handles_reconfigure_message()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);

        $exchange = $this->createExchange($channel);
        $exchange->setName('test-exchange');
        $exchange->setType('direct');
        $exchange->declareExchange();

        $this->addToCleanUp($exchange);

        $queue = $this->createQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->addToCleanUp($queue);

        for ($i = 1; $i < 4; $i++) {
            $exchange->publish('message #' . $i);
        }

        $exchange->publish(
            json_encode([
                1,
                5,
                8,
                0,
                1
            ]),
            null,
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

        $consumer = new CallbackConsumer($queue, 3, function (Envelope $envelope, Queue $queue) use (&$result) {
            $result[] = $envelope->getBody();
            return Consumer::MSG_ACK;
        });

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
    }

    /*
    public function testHandleDeliveryExceptionWithLogger()
    {
        $amqpChannel = $this->getMockBuilder('AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel->expects($this->once())->method('getPrefetchCount')->willReturn(3);

        $amqpQueue = $this->getMockBuilder('AMQPQueue')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpQueue->expects($this->once())->method('getChannel')->willReturn($amqpChannel);

        $logger = $this->prophesize('Psr\Log\LoggerInterface');
        $logger->error(Argument::any())->shouldBeCalled();

        $exception = new \Exception('Test Exception');

        $consumer = new Consumer([$amqpQueue], 1, 1 * 1000 * 500);
        $consumer->setLogger($logger->reveal());
        $consumer->handleDeliveryException($exception);
    }
*/

    /*
    public function testHandleFlushDeferredExceptionWithLogger()
    {
        $amqpChannel = $this->getMockBuilder('AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel->expects($this->once())->method('getPrefetchCount')->willReturn(3);

        $amqpQueue = $this->getMockBuilder('AMQPQueue')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpQueue->expects($this->once())->method('getChannel')->willReturn($amqpChannel);

        $logger = $this->prophesize('Psr\Log\LoggerInterface');
        $logger->error(Argument::any())->shouldBeCalled();

        $exception = new \Exception('Test Exception');

        $consumer = new Consumer([$amqpQueue], 1, 1 * 1000 * 500);
        $consumer->setLogger($logger->reveal());
        $consumer->handleDeliveryException($exception);
    }
*/
}
