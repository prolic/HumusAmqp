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

use Humus\Amqp\Channel;
use Humus\Amqp\Connection;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exchange;
use Humus\Amqp\Constants;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractExchangeTest
 * @package HumusTest\Amqp
 */
abstract class AbstractExchangeTest extends TestCase implements CanCreateExchange
{
    use DeleteOnTearDownTrait;

    /**
     * @var Exchange
     */
    protected $exchange;

    protected function setUp()
    {
        $connection = $this->createConnection();
        $channel = $this->createChannel($connection);
        $this->exchange = $this->createExchange($channel);
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
            'baz' => 'bam'
        ]);

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => 'bam'
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
        $channel = $this->createChannel($connection);
        $exchange2 = $this->createExchange($channel);
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
     * @group my
     */
    public function it_publishes_with_confirms()
    {
        $result = [];

        $connection = $this->createConnection(['read_timeout' => 2]);
        $channel = $this->createChannel($connection);
        $channel->confirmSelect();

        try {
            $channel->waitForConfirm(1);
        } catch (\Exception $e) {
            //$result[] = get_class($e) . ': ' . $e->getMessage(); //@todo: make php amqplib throw these exceptions
        }

        $this->exchange = $this->createExchange($channel);
        $this->exchange->setName('test');
        $this->exchange->setType('fanout');
        $this->exchange->setFlags(Constants::AMQP_AUTODELETE);
        $this->exchange->declareExchange();

        $this->exchange->publish('message 1', 'routing.key');
        $this->exchange->publish('message 1', 'routing.key', Constants::AMQP_MANDATORY);

        try {
            $channel->waitForConfirm();
        } catch (\Exception $e) {
            //$result[] = get_class($e) . ': ' . $e->getMessage(); //@todo: make php amqplib throw these exceptions
        }

        try {
            $channel->waitForConfirm();
        } catch (\Exception $e) {
            //$result[] = get_class($e) . ': ' . $e->getMessage(); //@todo: make php amqplib throw these exceptions
        }

        try {
            $channel->waitForConfirm(1);
        } catch (\Exception $e) {
            //$result[] = get_class($e) . ': ' . $e->getMessage(); //@todo: make php amqplib throw these exceptions
        }

        $this->exchange->publish('message 1', 'routing.key');
        $this->exchange->publish('message 1', 'routing.key', Constants::AMQP_MANDATORY);

        $channel->setReturnCallback(function (
            int $replyCode,
            string $replyText,
            string $exchange,
            string $routingKey,
            Envelope $envelope,
            string $body
        ) {
            $result[] = 'Message returned: ' . $replyText . ', message body:' . $body;
        });

        $cnt = 2;

        $channel->setConfirmCallback(
            function (
                string $delivery_tag,
                bool $multiple = false
            ) use (&$cnt, &$result) {
                $result[] = 'Message acked';
                $result[] = func_get_args();
                return --$cnt > 0;
            },
            function (
                string $delivery_tag,
                bool $multiple,
                bool $requeue
            ) use (&$result) {
                $result[] = 'Message nacked';
                $result[] = func_get_args();
                return false;
            }
        );

        try {
            $channel->waitForConfirm();
        } catch (\Exception $e) {
            //$result[] = get_class($e) . ': ' . $e->getMessage(); //@todo: make php amqplib throw these exceptions
        }

        $this->exchange->delete();

        $exchange2 = $this->createExchange($channel);
        $exchange2->setName('non-existent');
        $exchange2->publish('message 2', 'routing.key');

        try {
            $channel->waitForConfirm(1);
        } catch (\Exception $e) {
            $result[] = $e->getMessage();
        }

        $this->assertCount(5, $result);
        $this->assertEquals('Message acked', $result[0]);
        $this->assertEquals('3', $result[1][0]);
        $this->assertEquals('Message acked', $result[2]);
        $this->assertEquals('4', $result[3][0]);
        $this->assertRegExp("/.+no exchange 'non-existent' in vhost '.+'/", $result[4]);
    }
}
