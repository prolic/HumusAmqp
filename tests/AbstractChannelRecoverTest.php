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

use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exception\QueueException;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use Humus\Amqp\Constants;
use HumusTest\Amqp\Helper\CanCreateChannel;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\CanCreateQueue;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractChannelRecoverTest
 * @package HumusTest\Amqp
 */
abstract class AbstractChannelRecoverTest extends TestCase implements
    CanCreateConnection,
    CanCreateChannel,
    CanCreateExchange,
    CanCreateQueue
{
    /**
     * @var Exchange
     */
    private $exchange;

    /**
     * @var Queue
     */
    private $queue;

    protected function tearDown()
    {
        $this->queue->delete();
        $this->exchange->delete();
    }

    /**
     * @test
     */
    public function it_recovers()
    {
        $result = [];

        $channel1 = $this->createChannel($this->createConnection());
        $channel1->setPrefetchCount(5);

        $exchange1 = $this->createExchange($channel1);
        $exchange1->setType('topic');
        $exchange1->setName('test');
        $exchange1->setFlags(Constants::AMQP_AUTODELETE);
        $exchange1->declareExchange();

        $queue1 = $this->createQueue($channel1);
        $queue1->setName('test');
        $queue1->setFlags(Constants::AMQP_DURABLE);
        $queue1->declareQueue();

        $this->exchange = $exchange1;
        $this->queue = $queue1;

        $queue1->bind($exchange1->getName(), 'test');

        $messagesCount = 0;

        while ($messagesCount++ < 10) {
            $exchange1->publish('test message #' . $messagesCount, 'test');
        }

        $consume = 2;   // NOTE: by default prefetch-count=3, so in consumer below we will ignore prefetched messages 3-5,
                        //       and they will not seen by other consumers until we redeliver it.

        $queue1->consume(function (Envelope $envelope, Queue $queue) use (&$consume, &$result) {
            $result[] = 'consumed ' . $envelope->getBody() . ' '
                . ($envelope->isRedelivery() ? '(redeliverd)' : '(original)');
            $queue->ack($envelope->getDeliveryTag());

            return (--$consume > 0);
        });

        $queue1->cancel(); // we have to do that to prevent redelivering to the same consumer

        $newConnection = $this->createConnection(new ConnectionOptions(['read_timeout' => 1]));
        $channel2 = $this->createChannel($newConnection);
        $channel2->setPrefetchCount(8);

        $queue2 = $this->createQueue($channel2);
        $queue2->setName('test');

        $consume = 10;

        try {
            $queue2->consume(function (Envelope $envelope, Queue $queue) use (&$consume, &$result) {
                $result[] =  'consumed ' . $envelope->getBody() . ' '
                    . ($envelope->isRedelivery() ? '(redeliverd)' : '(original)');
                $queue->ack($envelope->getDeliveryTag());

                return (--$consume > 0);
            });
        } catch (\Exception $e) {
            $result[] = get_class($e);
        }
        $queue2->cancel();

        // yes, we do it repeatedly, basic.recover works in a slightly different way than it looks like. As it said,
        // it "asks the server to redeliver all unacknowledged messages on a specified channel.
        // ZERO OR MORE messages MAY BE redelivered"

        $channel1->basicRecover();
        $result[] = 'redelivered';

        $consume = 10;
        try {
            $queue2->consume(function (Envelope $e, Queue $q) use (&$consume, &$result) {
                $result[] = 'consumed ' . $e->getBody() . ' ' . ($e->isRedelivery() ? '(redelivered)' : '(original)');
                $q->ack($e->getDeliveryTag());

                return (--$consume > 0);
            });
        } catch (\Exception $e) {
            $result[] = get_class($e);
        }

        $expected = [
            'consumed test message #1 (original)',
            'consumed test message #2 (original)',
            'consumed test message #8 (original)',
            'consumed test message #9 (original)',
            'consumed test message #10 (original)',
            QueueException::class,
            'redelivered',
            'consumed test message #3 (redelivered)',
            'consumed test message #4 (redelivered)',
            'consumed test message #5 (redelivered)',
            'consumed test message #6 (redelivered)',
            'consumed test message #7 (redelivered)',
            QueueException::class,
        ];

        $this->assertEquals($expected, $result);
    }
}
