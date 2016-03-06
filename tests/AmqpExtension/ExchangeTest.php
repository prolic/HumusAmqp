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

namespace HumusTest\Amqp\AmqpExtension;

use Humus\Amqp\AmqpExchange as AmqpExchangeInterface;
use Humus\Amqp\Driver\AmqpExtension\AmqpChannel;
use Humus\Amqp\Driver\AmqpExtension\AmqpConnection;
use Humus\Amqp\Driver\AmqpExtension\AmqpExchange;
use HumusTest\Amqp\AbstractExchangeTest;

/**
 * Class ExchangeTest
 * @package HumusTest\Amqp\AmqpExtension
 */
final class ExchangeTest extends AbstractExchangeTest
{
    protected function setUp()
    {
        if (!extension_loaded('amqp')) {
            $this->markTestSkipped('php amqp extension not loaded');
        }

        $this->exchange = $this->getNewAmqpExchange();
    }

    protected function getNewAmqpExchange() : AmqpExchangeInterface
    {
        $connection = new AmqpConnection($this->validCredentials());
        $connection->connect();

        return new AmqpExchange(new AmqpChannel($connection));
    }
}
