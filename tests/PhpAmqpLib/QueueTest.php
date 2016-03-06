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

use Humus\Amqp\AmqpExchange as AmqpExchangeInterface;
use Humus\Amqp\AmqpQueue as AmqpQueueInterface;
use Humus\Amqp\Driver\PhpAmqpLib\AmqpChannel;
use Humus\Amqp\Driver\PhpAmqpLib\AmqpExchange;
use Humus\Amqp\Driver\PhpAmqpLib\AmqpQueue;
use Humus\Amqp\Driver\PhpAmqpLib\AmqpStreamConnection;
use HumusTest\Amqp\AbstractQueueTest;

/**
 * Class QueueTest
 * @package HumusTest\Amqp\PhpAmqpLib
 */
final class QueueTest extends AbstractQueueTest
{
    protected function setUp()
    {
        $this->exchange = $this->getNewAmqpExchange();
        $this->queue = $this->getNewAmqpQueue();
    }

    protected function getNewAmqpExchange() : AmqpExchangeInterface
    {
        return new AmqpExchange(new AmqpChannel(new AmqpStreamConnection($this->validCredentials())));
    }

    protected function getNewAmqpQueue() : AmqpQueueInterface
    {
        return new AmqpQueue(new AmqpChannel(new AmqpStreamConnection($this->validCredentials())));
    }
}
