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

declare(strict_types=1);

namespace HumusTest\Amqp\PhpAmqpLib;

use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Driver\PhpAmqpLib\LazyConnection;
use HumusTest\Amqp\AbstractConnectionTest;

/**
 * Class LazyConnectionTest
 * @package HumusTest\Amqp\PhpAmqpLib
 */
final class LazyConnectionTest extends AbstractConnectionTest
{
    /**
     * @test
     */
    public function it_returns_internal_connection()
    {
        $connection = $this->createConnection();

        $this->assertInstanceOf(\PhpAmqpLib\Connection\AMQPLazyConnection::class, $connection->getResource());
    }

    /**
     * @param ConnectionOptions|null $options
     * @return \Humus\Amqp\Connection
     */
    public function createConnection(ConnectionOptions $options = null) : \Humus\Amqp\Connection
    {
        if (null === $options) {
            $options = new ConnectionOptions();
        }

        $options->setVhost('/humus-amqp-test');

        return new LazyConnection($options);
    }
}
