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

namespace HumusTest\Amqp;

use Humus\Amqp\AmqpChannel;
use Humus\Amqp\AmqpEnvelope;
use Humus\Amqp\AmqpExchange;
use Humus\Amqp\AmqpQueue;
use Humus\Amqp\CallbackConsumer;
use Humus\Amqp\Constants;
use Humus\Amqp\PlainProducer;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractBasicPublishConsumeTest
 * @package HumusTest\Amqp
 */
abstract class AbstractBasicPublishConsumeTest extends TestCase
{
    /**
     * @var AmqpChannel
     */
    protected $channel;

    /**
     * @var AmqpExchange
     */
    protected $exchange;

    /**
     * @var AmqpQueue
     */
    protected $queue;

    /**
     * @var PlainProducer
     */
    protected $producer;

    /**
     * @var PlainProducer
     */
    protected $transactionalProducer;

    /**
     * @var CallbackConsumer
     */
    protected $consumer;

    /**
     * @var array
     */
    protected $results = [];

    /**
     * @test
     */
    public function it_produces_and_get_messages_from_queue()
    {
        $this->producer->publish('foo');
        $this->producer->publish('bar');

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);
        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertSame('foo', $msg1->getBody());
        $this->assertSame('bar', $msg2->getBody());
    }

    /**
     * @test
     */
    public function it_produces_transactional_and_get_messages_from_queue()
    {
        $this->transactionalProducer->publish('foo');
        $this->transactionalProducer->publish('bar');

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);
        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertSame('foo', $msg1->getBody());
        $this->assertSame('bar', $msg2->getBody());
    }

    /**
     * @test
     */
    public function it_purges_messages_from_queue()
    {
        $this->producer->publish('foo');
        $this->producer->publish('bar');

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertInstanceOf(AmqpEnvelope::class, $msg1);

        $this->queue->purge();

        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertFalse($msg2);
    }

    /**
     * @test
     */
    public function it_returns_envelope_information()
    {
        $this->producer->publish('foo');

        $msg = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertEquals('UTF-8', $msg->getContentEncoding());
        $this->assertEquals('text/plain', $msg->getContentType());
        $this->assertEquals('test-exchange', $msg->getExchangeName());
        $this->assertEquals(2, $msg->getDeliveryMode());
        $this->assertEquals(1, $msg->getDeliveryTag());
        $this->assertEmpty($msg->getHeaders());
    }

    /**
     * @test
     */
    public function it_publishes_with_headers()
    {
        $this->producer->publish('foo', null, Constants::AMQP_NOPARAM, [
            'headers' => [
                'header1' => 'value1',
                'header2' => 'value2'
            ]
        ]);

        $msg = $this->queue->get(Constants::AMQP_AUTOACK);

        $this->assertEquals(
            [
                'header1' => 'value1',
                'header2' => 'value2'
            ],
            $msg->getHeaders()
        );
    }

    protected function tearDown()
    {
        $this->queue->delete();
        $this->exchange->delete();
    }
}
