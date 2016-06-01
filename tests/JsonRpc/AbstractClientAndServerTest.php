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

namespace HumusTest\Amqp\JsonRpc;

use Humus\Amqp\Constants;
use Humus\Amqp\Envelope;
use Humus\Amqp\JsonRpc\Client;
use Humus\Amqp\JsonRpc\Server;
use Humus\Amqp\JsonRpc\Request;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\CanCreateQueue;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\NullLogger;

/**
 * Class AbstractClientAndServerTest
 * @package HumusTest\Amqp\JsonRpc
 */
abstract class AbstractClientAndServerTest extends TestCase implements
    CanCreateConnection,
    CanCreateExchange,
    CanCreateQueue
{
    use DeleteOnTearDownTrait;

    /**
     * @test
     * @group my
     */
    public function it_sends_requests_and_server_responds()
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $this->createExchange($channel);
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $this->createExchange($channel2);
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $this->createQueue($channel);
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $this->createQueue($channel2);
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new Client($clientQueue, ['rpc-server' => $serverExchange]);

        $time = time();
        $request1 = new Request(1, 'rpc-server', 'request-1', null, 0, 'my_user', 'message-id-1', (string) $time, 'times2');
        $request2 = new Request(2, 'rpc-server', 'request-2', null, 0, 'my_user', 'message-id-2', (string) $time, 'times2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Envelope $envelope) {
            return $envelope->getBody() * 2;
        };

        $server = new Server($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(2);

        $replies = $client->getReplies();

        $this->assertCount(2, $replies);
        $this->assertEquals(true, $replies['request-1']['success']);
        $this->assertEquals(2, $replies['request-1']['result']);
        $this->assertEquals(true, $replies['request-2']['success']);
        $this->assertEquals(4, $replies['request-2']['result']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_server_name_given_to_request()
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid server given, no related exchange "invalid-rpc-server" found.');
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

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);

        $client = new Client($clientQueue, ['rpc-server' => $serverExchange]);

        $client->addRequest(new Request(1, 'invalid-rpc-server', 'request-1'));
    }
}
