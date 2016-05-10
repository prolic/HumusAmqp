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

namespace HumusTest\Amqp\PhpAmqpLib;

use Humus\Amqp\Channel as AmqpChannelInterface;
use Humus\Amqp\Exchange as AmqpExchangeInterface;
use Humus\Amqp\Queue as AmqpQueueInterface;
use Humus\Amqp\Driver\PhpAmqpLib\Channel;
use Humus\Amqp\Driver\PhpAmqpLib\Exchange;
use Humus\Amqp\Driver\PhpAmqpLib\Queue;
use Humus\Amqp\Driver\PhpAmqpLib\StreamConnection;
use HumusTest\Amqp\AbstractChannelRecoverTest;

/**
 * Class ChannelRecoverTest
 * @package HumusTest\Amqp\PhpAmqpLib
 */
final class ChannelRecoverTest extends AbstractChannelRecoverTest
{
    protected function setUp()
    {
        $this->markTestSkipped('channel recover test not yet working for php amqp lib');
    }

    /**
     * @return AmqpChannelInterface
     */
    protected function getNewChannelWithNewConnection() : AmqpChannelInterface
    {
        return new Channel(new StreamConnection($this->credentials()));
    }

    protected function getNewExchange(AmqpChannelInterface $channel) : AmqpExchangeInterface
    {
        return new Exchange($channel);
    }

    protected function getNewQueue(AmqpChannelInterface $channel) : AmqpQueueInterface
    {
        return new Queue($channel);
    }
}
