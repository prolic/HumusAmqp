<?php
/**
 * Copyright (c) 2016-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exception\ChannelException;
use Humus\Amqp\Exchange;
use Humus\Amqp\Constants;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractExchangeTest
 * @package HumusTest\Amqp
 */
abstract class AbstractExchangeTest extends TestCase implements CanCreateConnection
{
    use DeleteOnTearDownTrait;

    /**
     * @var Exchange
     */
    protected $exchange;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Channel
     */
    protected $channel;

    protected function setUp()
    {
        $connection = $this->createConnection();
        $this->channel = $connection->newChannel();
        $this->exchange = $this->channel->newExchange();
        $this->queue = $this->channel->newQueue();
    }

    /**
     * @test
     */
    public function it_sets_name_flags_type_and_arguments()
    {
        $this->assertEquals('', $this->exchange->getName());
        $this->assertEquals('', $this->exchange->getType());
        $this->assertEquals(0, $this->exchange->getFlags());
        $this->assertEmpty($this->exchange->getArguments());

        $this->exchange->setName('test');
        $this->exchange->setType('topic');

        $this->assertEquals('test', $this->exchange->getName());
        $this->assertEquals('topic', $this->exchange->getType());

        $this->exchange->setFlags(Constants::AMQP_AUTODELETE);

        $this->assertEquals(16, $this->exchange->getFlags());

        $this->exchange->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_DURABLE);

        $this->assertEquals(18, $this->exchange->getFlags());

        $this->exchange->setArgument('key', 'value');

        $this->assertEquals('value', $this->exchange->getArgument('key'));
        $this->assertFalse($this->exchange->getArgument('invalid key'));

        $this->exchange->setArguments([
            'foo' => 'bar',
            'baz' => 'bam',
        ]);

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => 'bam',
            ],
            $this->exchange->getArguments()
        );
    }

    /**
     * @test
     */
    public function it_declares_and_deletes_exchange()
    {
        $this->addToCleanUp($this->exchange);
        $this->exchange->setType('direct');
        $this->exchange->setName('test');
        $this->exchange->declareExchange();
    }

    /**
     * @test
     */
    public function it_declares_exchange_with_arguments()
    {
        $this->addToCleanUp($this->exchange);
        $this->exchange->setType('direct');
        $this->exchange->setName('test');
        $this->exchange->setArguments([
            'foo' => 'bar',
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
            'int' => 2,
            'bool' => true,
        ]);
        $this->exchange->declareExchange();
    }

    /**
     * @test
     */
    public function it_returns_channel_and_connection()
    {
        $this->assertInstanceOf(Channel::class, $this->exchange->getChannel());
        $this->assertInstanceOf(Connection::class, $this->exchange->getConnection());
    }

    /**
     * @test
     */
    public function it_binds_and_unbinds_to_exchange()
    {
        $this->addToCleanUp($this->exchange);
        $this->exchange->setType('direct');
        $this->exchange->setName('test');

        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $exchange2 = $channel->newExchange();
        $exchange2->setType('direct');
        $exchange2->setName('foo');
        $this->addToCleanUp($exchange2);

        $this->exchange->declareExchange();
        $exchange2->declareExchange();

        $this->exchange->bind($exchange2->getName());

        $this->exchange->unbind($exchange2->getName());
    }

    /**
     * @test
     */
    public function it_binds_and_unbinds_to_exchange_with_routing_key()
    {
        $this->addToCleanUp($this->exchange);
        $this->exchange->setType('direct');
        $this->exchange->setName('test');

        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $exchange2 = $channel->newExchange();
        $exchange2->setType('direct');
        $exchange2->setName('foo');
        $this->addToCleanUp($exchange2);

        $this->exchange->declareExchange();
        $exchange2->declareExchange();

        $this->exchange->bind($exchange2->getName(), 'routing_key');

        $this->exchange->unbind($exchange2->getName());
    }

    /**
     * @test
     */
    public function it_binds_and_unbinds_to_exchange_with_arguments()
    {
        $this->addToCleanUp($this->exchange);
        $this->exchange->setType('direct');
        $this->exchange->setName('test');

        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $exchange2 = $channel->newExchange();
        $exchange2->setType('direct');
        $exchange2->setName('foo');
        $this->addToCleanUp($exchange2);

        $this->exchange->declareExchange();
        $exchange2->declareExchange();

        $this->exchange->bind($exchange2->getName(), '', [
            'foo' => 'bar',
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
            'int' => 2,
            'bool' => true,
        ]);

        $this->exchange->unbind($exchange2->getName(), '', [
            'foo' => 'bar',
            'table' => [
                'foo' => 'bar',
            ],
            'array' => [
                'baz',
            ],
            'int' => 2,
            'bool' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_binds_and_unbinds_to_exchange_with_routing_key_and_arguments()
    {
        $this->addToCleanUp($this->exchange);
        $this->exchange->setType('direct');
        $this->exchange->setName('test');

        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $exchange2 = $channel->newExchange();
        $exchange2->setType('direct');
        $exchange2->setName('foo');
        $this->addToCleanUp($exchange2);

        $this->exchange->declareExchange();
        $exchange2->declareExchange();

        $this->exchange->bind($exchange2->getName(), 'routing_key', ['foo' => 'bar']);

        $this->exchange->unbind($exchange2->getName());
    }

    /**
     * @test
     */
    public function it_publishes_with_confirms()
    {
        $result = [];

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$result) {
            $result[] = $errstr;
        });

        $connection = $this->createConnection(new ConnectionOptions(['read_timeout' => 2]));
        $channel = $connection->newChannel();
        $channel->confirmSelect();

        $this->exchange = $channel->newExchange();
        $this->exchange->setName('test9');
        $this->exchange->setType('fanout');
        $this->exchange->setFlags(Constants::AMQP_AUTODELETE);
        $this->exchange->declareExchange();

        $this->exchange->publish('message 1', 'routing.key');
        $this->exchange->publish('message 2', 'routing.key', Constants::AMQP_MANDATORY);

        try {
            $channel->waitForConfirm();
        } catch (\Exception $e) {
            $result[] = get_class($e);
            $result[] = $e->getMessage();
        }

        try {
            $channel->waitForConfirm();
        } catch (\Exception $e) {
            $result[] = get_class($e);
            $result[] = $e->getMessage();
        }

        try {
            $channel->waitForConfirm(1);
        } catch (\Exception $e) {
            $result[] = get_class($e);
            $result[] = $e->getMessage();
        }

        $this->exchange->publish('message 3', 'routing.key');
        $this->exchange->publish('message 4', 'routing.key', Constants::AMQP_MANDATORY);

        $channel->setReturnCallback(function (
            int $replyCode,
            string $replyText,
            string $exchange,
            string $routingKey,
            Envelope $envelope,
            string $body
        ) use (&$result) {
            $result[] = 'Message returned: ' . $replyText . ', message body:' . $body;
        });

        $cnt = 2;

        $channel->setConfirmCallback(
            function (
                int $deliveryTag,
                bool $multiple = false
            ) use (&$cnt, &$result) {
                $result[] = 'Message acked';
                return --$cnt > 0;
            },
            function (
                int $deliveryTag,
                bool $multiple,
                bool $requeue
            ) use (&$result) {
                $result[] = 'Message nacked';
                return false;
            }
        );

        try {
            $channel->waitForConfirm();
        } catch (\Exception $e) {
            $result[] = get_class($e);
            $result[] = $e->getMessage() . 'fd';
        }

        $this->exchange->delete();

        $exchange2 = $channel->newExchange();
        $exchange2->setName('non-existent');
        $exchange2->publish('message 2', 'routing.key');

        try {
            $channel->waitForConfirm(1);
        } catch (\Exception $e) {
            $result[] = get_class($e);
            $result[] = $e->getMessage();
        }

        $this->assertCount(8, $result);
        $this->assertStringStartsWith('Unhandled basic.ack method from server received.', $result[0]);
        $this->assertStringStartsWith('Unhandled basic.return method from server received.', $result[1]);
        $this->assertStringStartsWith('Unhandled basic.ack method from server received.', $result[2]);
        $this->assertEquals('Message acked', $result[3]);
        $this->assertEquals('Message returned: NO_ROUTE, message body:message 4', $result[4]);
        $this->assertEquals('Message acked', $result[5]);
        $this->assertEquals(ChannelException::class, $result[6]);
        $this->assertRegExp("/.+no exchange 'non-existent' in vhost '.+'/", $result[7]);

        restore_error_handler();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_negative_wait_confirm_timeout_given()
    {
        $this->expectException(ChannelException::class);
        $this->expectExceptionMessage('Timeout must be greater than or equal to zero.');

        $connection = $this->createConnection(new ConnectionOptions(['read_timeout' => 2]));
        $channel = $connection->newChannel();
        $channel->confirmSelect();

        $channel->waitForConfirm(-1);
    }

    /**
     * @test
     */
    public function it_produces_in_confirm_mode()
    {
        $this->exchange->setType('topic');
        $this->exchange->setName('test-exchange');
        $this->exchange->declareExchange();

        $this->exchange->getChannel()->setConfirmCallback(
            function () {
                return false;
            },
            function (int $deliveryTag, bool $multiple, bool $requeue) {
                throw new \Exception('Could not confirm message publishing');
            }
        );
        $this->exchange->getChannel()->confirmSelect();

        $connection = $this->createConnection();
        $queue = $connection->newChannel()->newQueue();

        $this->addToCleanUp($queue);

        $queue->setName('test-queue23');
        $queue->declareQueue();
        $queue->bind('test-exchange', '#');

        $this->exchange->publish('foo');
        $this->exchange->publish('bar');

        $this->exchange->getChannel()->waitForConfirm();

        $msg1 = $queue->get(Constants::AMQP_AUTOACK);
        $this->assertNotFalse($msg1);
        $msg2 = $queue->get(Constants::AMQP_AUTOACK);
        $this->assertNotFalse($msg2);

        $this->assertSame('foo', $msg1->getBody());
        $this->assertSame('bar', $msg2->getBody());
    }

    /**
     * @test
     */
    public function it_publishes_mandatory()
    {
        $result = [];

        $this->exchange->setType('topic');
        $this->exchange->setName('test-exchange');
        $this->exchange->declareExchange();

        $queue = $this->channel->newQueue();
        $queue->setName('test-mandatory');
        $queue->setFlags(Constants::AMQP_AUTODELETE);
        $queue->declareQueue();

        $this->addToCleanUp($queue);

        $this->assertFalse($queue->get());

        try {
            $this->channel->waitForBasicReturn(1);
        } catch (\Exception $e) {
            $result[] = get_class($e);
        }

        $this->exchange->publish('message #1', 'routing.key', Constants::AMQP_MANDATORY);
        $this->exchange->publish('message #2', 'routing.key', Constants::AMQP_MANDATORY);

        $this->channel->setReturnCallback(
            function (
                int $replyCode,
                string $replyText,
                string $exchange,
                string $routingKey,
                Envelope $envelope,
                string $body
            ) use (&$result) {
                $result[] = 'Message returned';
                $result[] = func_get_args();
                return false;
            }
        );

        try {
            $this->channel->waitForBasicReturn();
        } catch (\Exception $e) {
            $result[] = get_class($e);
        }

        $this->assertCount(3, $result);
        $this->assertEquals(ChannelException::class, $result[0]);
        $this->assertEquals('Message returned', $result[1]);
        $this->assertCount(6, $result[2]);
        $this->assertEquals(312, $result[2][0]);
        $this->assertEquals('NO_ROUTE', $result[2][1]);
        $this->assertEquals('test-exchange', $result[2][2]);
        $this->assertEquals('routing.key', $result[2][3]);
        $this->assertInstanceOf(Envelope::class, $result[2][4]);
        $this->assertEquals('message #1', $result[2][5]);
    }
}
