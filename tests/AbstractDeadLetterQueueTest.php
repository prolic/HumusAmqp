<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
use Humus\Amqp\Constants;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit\Framework\TestCase;

abstract class AbstractDeadLetterQueueTest extends TestCase implements CanCreateConnection
{
    use DeleteOnTearDownTrait;

    protected Channel $channel;

    protected Exchange $exchange;

    protected Exchange $deadExchange;

    protected Queue $queue;

    protected Queue $deadQueue;

    protected function setUp(): void
    {
        $connection = $this->createConnection();
        $this->channel = $connection->newChannel();

        $this->deadExchange = $this->channel->newExchange();
        $this->deadExchange->setType('topic');
        $this->deadExchange->setName('dead-exchange');
        $this->deadExchange->declareExchange();

        $this->deadQueue = $this->channel->newQueue();
        $this->deadQueue->setName('dead-queue');
        $this->deadQueue->setFlags(Constants::AMQP_DURABLE);
        $this->deadQueue->declareQueue();
        $this->deadQueue->bind('dead-exchange', '#');

        $this->exchange = $this->channel->newExchange();
        $this->exchange->setType('topic');
        $this->exchange->setName('test-exchange');
        $this->exchange->declareExchange();

        $this->queue = $this->channel->newQueue();
        $this->queue->setName('test-queue');
        $this->queue->setArguments([
            'x-dead-letter-exchange' => 'dead-exchange',
        ]);
        $this->queue->setFlags(Constants::AMQP_DURABLE);
        $this->queue->declareQueue();
        $this->queue->bind('test-exchange', '#');

        $this->addToCleanUp($this->exchange);
        $this->addToCleanUp($this->queue);
        $this->addToCleanUp($this->deadExchange);
        $this->addToCleanUp($this->deadQueue);
    }

    /**
     * @test
     */
    public function it_returns_death_lettered_envelope_information(): void
    {
        $this->exchange->publish('foo', '', Constants::AMQP_NOPARAM, [
            'content_type' => 'text/plain',
            'content_encoding' => 'UTF-8',
            'user_id' => 'testuser', // must be same as login data
            'delivery_mode' => 1,
            'priority' => 5,
            'timestamp' => 25,
            'expiration' => 1000,
        ]);

        usleep(1000000);

        $msg = $this->queue->get(Constants::AMQP_AUTOACK);
        $this->assertNull($msg); // message expired
        $msg = $this->deadQueue->get(Constants::AMQP_AUTOACK);
        $this->assertNotNull($msg); // message on dead letter queue

        $this->assertArrayHasKey('x-death', $msg->getHeaders());
        $this->assertNotNull($msg->getHeader('x-death'));
    }
}
