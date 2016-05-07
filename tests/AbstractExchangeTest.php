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
use Humus\Amqp\AmqpConnection;
use Humus\Amqp\AmqpExchange;
use Humus\Amqp\Constants;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class AbstractExchangeTest
 * @package HumusTest\Amqp
 */
abstract class AbstractExchangeTest extends TestCase
{
    use ValidCredentialsTrait;

    /**
     * @var AmqpExchange
     */
    protected $exchange;

    protected $wasDeclared = false;

    protected function tearDown()
    {
        if ($this->wasDeclared) {
            $this->exchange->delete();
        }
    }

    /**
     * @test
     */
    public function it_sets_name_flags_type_and_arguments()
    {
        $this->wasDeclared = true;

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
        $this->exchange->setType('direct');
        $this->exchange->setName('test');
        $this->exchange->declareExchange();
        $this->wasDeclared = true;
    }

    /**
     * @test
     */
    public function it_returns_channel_and_connection()
    {
        $this->assertInstanceOf(AmqpChannel::class, $this->exchange->getChannel());
        $this->assertInstanceOf(AmqpConnection::class, $this->exchange->getConnection());
    }

    /**
     * @test
     */
    public function it_binds_and_unbinds_to_exchange()
    {
        $this->exchange->setType('direct');
        $this->exchange->setName('test');

        $exchange2 = $this->getNewAmqpExchange();
        $exchange2->setType('direct');
        $exchange2->setName('foo');

        $this->exchange->declareExchange();
        $exchange2->declareExchange();

        $this->exchange->bind($exchange2->getName());

        $this->wasDeclared = true;

        $this->exchange->unbind($exchange2->getName());
        $exchange2->delete();
    }

    abstract protected function getNewAmqpExchange() : AmqpExchange;
}
