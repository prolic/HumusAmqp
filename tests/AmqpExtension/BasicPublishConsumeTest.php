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

namespace HumusTest\Amqp\AmqpExtension;

use Humus\Amqp\CallbackConsumer;
use Humus\Amqp\AmqpEnvelope;
use Humus\Amqp\Driver\AmqpExtension\AmqpChannel;
use Humus\Amqp\Driver\AmqpExtension\AmqpConnection;
use Humus\Amqp\Driver\AmqpExtension\AmqpExchange;
use Humus\Amqp\Driver\AmqpExtension\AmqpQueue;
use Humus\Amqp\PlainProducer;
use HumusTest\Amqp\AbstractBasicPublishConsumeTest;

/**
 * Class BasicPublishConsumeTest
 * @package HumusTest\Amqp\AmqpExtension
 */
final class BasicPublishConsumeTest extends AbstractBasicPublishConsumeTest
{
    protected function setUp()
    {
        $connection = new AmqpConnection([
            'vhost' => '/humus-amqp-test'
        ]);
        $connection->connect();

        $channel = new AmqpChannel($connection);

        $exchange = new AmqpExchange($channel);
        $exchange->setType('direct');
        $exchange->setName('test-exchange');
        $exchange->declareExchange();

        $queue = new AmqpQueue($channel);
        $queue->setName('test-queue');
        $queue->declareQueue();
        $queue->bind('test-exchange');

        $this->channel = $channel;
        $this->exchange = $exchange;
        $this->queue = $queue;

        $this->producer = new PlainProducer($exchange, false, false, null);
        $this->transactionalProducer = new PlainProducer($exchange, false, true, null);

        $callback = function (AmqpEnvelope $envelope, \Humus\Amqp\AmqpQueue $queue) {
              $this->results[] = $envelope->getBody();
        };

        $this->consumer = new CallbackConsumer($queue, 1.0, $callback);
    }
}
