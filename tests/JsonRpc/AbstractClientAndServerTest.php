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
use Humus\Amqp\JsonRpc\Error;
use Humus\Amqp\JsonRpc\Response;
use Humus\Amqp\JsonRpc\Server;
use Humus\Amqp\JsonRpc\Request;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\CanCreateExchange;
use HumusTest\Amqp\Helper\CanCreateQueue;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use HumusTest\Amqp\TestAsset\ArrayLogger;
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

        $request1 = new Request('rpc-server', 'time2', 1, 'request-1');
        $request2 = new Request('rpc-server', 'time2', 2, 'request-2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
            return Response::withResult($request->id(), $request->params() * 2);
        };

        $logger = new ArrayLogger();
        $server = new Server($serverQueue, $callback, $logger, 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection();

        $this->assertCount(2, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertFalse($response1->hasError());
        $this->assertEquals(2, $response1->result());

        $response2 = $responses->getResponse('request-2');
        $this->assertFalse($response2->hasError());
        $this->assertEquals(4, $response2->result());

        $loggerResult = $logger->loggerResult();

        $this->assertCount(4, $loggerResult);

        $this->assertEquals('info', $loggerResult[0]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[0]['message']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[1]['message']);
        $this->assertEquals('1', $loggerResult[1]['context']['body']);

        $this->assertEquals('info', $loggerResult[2]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[2]['message']);

        $this->assertEquals('debug', $loggerResult[3]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[3]['message']);
        $this->assertEquals('2', $loggerResult[3]['context']['body']);
    }

    /**
     * @test
     */
    public function it_sends_requests_and_server_responds_and_handles_exception()
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

        $request1 = new Request('rpc-server', 'time2', 1, 'request-1');
        $request2 = new Request('rpc-server', 'time2', 2, 'request-2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
            $params = $request->params();
            if (1 == $params) {
                throw new \Exception('invalid body');
            }
            return $params * 2; // return no response but the result instead
        };

        $logger = new ArrayLogger();
        $server = new Server($serverQueue, $callback, $logger, 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection();

        $this->assertCount(2, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertTrue($response1->hasError());
        $this->assertEquals(Error::ERROR_CODE_32603, $response1->error()->code());
        $this->assertEquals('Internal error', $response1->error()->message());

        $response2 = $responses->getResponse('request-2');
        $this->assertFalse($response2->hasError());
        $this->assertEquals(4, $response2->result());

        $loggerResult = $logger->loggerResult();

        $this->assertCount(5, $loggerResult);

        $this->assertEquals('info', $loggerResult[0]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[0]['message']);

        $this->assertEquals('debug', $loggerResult[1]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[1]['message']);
        $this->assertEquals('1', $loggerResult[1]['context']['body']);

        $this->assertEquals('error', $loggerResult[2]['level']);
        $this->assertEquals('Exception occurred', $loggerResult[2]['message']);
        $this->assertEquals('invalid body', $loggerResult[2]['context']['exception_message']);

        $this->assertEquals('info', $loggerResult[3]['level']);
        $this->assertRegExp('/^Acknowledged 1 messages at.+/', $loggerResult[3]['message']);

        $this->assertEquals('debug', $loggerResult[4]['level']);
        $this->assertEquals('Handling delivery of message', $loggerResult[4]['message']);
        $this->assertEquals('2', $loggerResult[4]['context']['body']);
    }

    /**
     * @test
     */
    public function it_sends_requests_and_server_times_out()
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

        $request1 = new Request('rpc-server', 'time2', 1, 'request-1');
        $request2 = new Request('rpc-server', 'time2', 2, 'request-2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
            return Response::withResult($request->id(), $request->params() * 2);
        };

        $server = new Server($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(1);

        $responses = $client->getResponseCollection(1);

        $this->assertCount(1, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertFalse($response1->hasError());
        $this->assertEquals(2, $response1->result());
    }

    /**
     * @test
     */
    public function it_sends_ttl_requests_and_server_responds_late()
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

        $request1 = new Request('rpc-server', 'time2', 1, 'request-1', '', 100);
        $request2 = new Request('rpc-server', 'time2', 2, 'request-2', '', 100);

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Envelope $envelope) {
            return $envelope->getBody() * 2;
        };

        sleep(1);

        $serverExchange->publish('shutdown', null, Constants::AMQP_NOPARAM, [
            'type' => 'shutdown',
            'app_id' => 'Humus\Amqp',
        ]);

        $server = new Server($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(1);

        $responses = $client->getResponseCollection(0.2);

        $this->assertCount(0, $responses);
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

        $client->addRequest(new Request('invalid-rpc-server', 'time2', 1, 'request-1', '', 100));
    }
}
