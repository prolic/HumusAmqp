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
use Humus\Amqp\AmqpEnvelope;
use Humus\Amqp\AmqpExchange;
use Humus\Amqp\AmqpQueue;
use Humus\Amqp\Constants;
use Humus\Amqp\JsonProducer;
use Humus\Amqp\PlainProducer;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractPlainProducerTest
 * @package HumusTest\Amqp
 */
abstract class AbstractJsonProducerTest extends TestCase
{
    use ValidCredentialsTrait;

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
     * @var callable
     */
    protected $callback;

    /**
     * @var array
     */
    protected $results = [];

    protected function setUp()
    {
        $this->callback = function (AmqpEnvelope $envelope) {
            $this->results[] = $envelope->getBody();
        };
    }

    /**
     * @test
     */
    public function it_produces_and_get_messages_from_queue()
    {
        $producer = new JsonProducer($this->exchange);
        $producer->publish(['foo' => 'bar']);
        $producer->publish(['baz' => 'bam']);

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);
        $this->assertEquals('UTF-8', $msg1->getContentEncoding());
        $this->assertEquals('application/json', $msg1->getContentType());
        $body = json_decode($msg1->getBody(), true);

        $this->assertEquals(['foo' => 'bar'], $body);

        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);
        $this->assertEquals('UTF-8', $msg2->getContentEncoding());
        $this->assertEquals('application/json', $msg2->getContentType());
        $body = json_decode($msg2->getBody(), true);

        $this->assertEquals(['baz' => 'bam'], $body);
    }

    /**
     * @test
     */
    public function it_produces_transactional_and_get_messages_from_queue()
    {
        $producer = new JsonProducer($this->exchange);
        $producer->startTransaction();
        $producer->publish(['foo' => 'bar']);
        $producer->publish(['baz' => 'bam']);
        $producer->commitTransaction();

        $msg1 = $this->queue->get(Constants::AMQP_AUTOACK);
        $msg2 = $this->queue->get(Constants::AMQP_AUTOACK);

        $body = json_decode($msg1->getBody(), true);

        $this->assertEquals(['foo' => 'bar'], $body);

        $body = json_decode($msg2->getBody(), true);

        $this->assertEquals(['baz' => 'bam'], $body);
    }

    /**
     * @test
     */
    public function it_produces_in_confirm_mode()
    {
        $this->exchange->getChannel()->setConfirmCallback(
            function() {
                return false;
            },
            function(int $delivery_tag, bool $multiple, bool $requeue) {
                throw new \Exception('Could not confirm message publishing');
            }
        );
        
        $producer = new JsonProducer($this->exchange);
        $producer->confirmSelect();

        $queue = $this->getNewQueueWithNewChannelAndConnection();
        $queue->setName('text-queue2');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $producer->publish(['foo' => 'bar']);
        $producer->publish(['baz' => 'bam']);

        $this->channel->waitForConfirm();

        $msg1 = $queue->get(Constants::AMQP_NOPARAM);
        $msg2 = $queue->get(Constants::AMQP_AUTOACK);

        $this->assertEquals(['foo' => 'bar'], json_decode($msg1->getBody(), true));
        $this->assertEquals(['baz' => 'bam'], json_decode($msg2->getBody(), true));

        $queue->delete();
    }

    abstract protected function getNewQueueWithNewChannelAndConnection() : AmqpQueue;

    protected function tearDown()
    {
        $this->exchange->delete();
        $this->queue->delete();
    }
}
