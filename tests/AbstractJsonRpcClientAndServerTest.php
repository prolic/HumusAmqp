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

use Humus\Amqp\Constants;
use Humus\Amqp\Envelope;
use Humus\Amqp\JsonRpcClient;
use Humus\Amqp\JsonRpcServer;
use Humus\Amqp\RpcClientRequest;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\CanCreateQueue;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\NullLogger;

/**
 * Class AbstractJsonRpcClientAndServerTest
 * @package HumusTest\Amqp
 */
abstract class AbstractJsonRpcClientAndServerTest extends TestCase implements
    CanCreateConnection,
    CanCreateExchange,
    CanCreateQueue
{
    use DeleteOnTearDownTrait;

    /**
     * @test
     */
    public function it_sends_requests_and_server_responds()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $clientExchange = $this->createExchange($channel);
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $this->createExchange($channel);
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $this->createQueue($channel);
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $this->createQueue($channel);
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $producer = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $producer->addRequest(new RpcClientRequest(1, 'rpc-server', 'request-1'));
        $producer->addRequest(new RpcClientRequest(2, 'rpc-server', 'request-2'));

        $callback = function (Envelope $envelope) {
            return $envelope->getBody() * 2;
        };

        $server = new JsonRpcServer($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(2);

        $replies = $producer->getReplies();

        $this->assertCount(2, $replies);
        $this->assertEquals(true, $replies['request-1']['success']);
        $this->assertEquals(2, $replies['request-1']['result']);
        $this->assertEquals(true, $replies['request-2']['success']);
        $this->assertEquals(4, $replies['request-2']['result']);
    }
}
