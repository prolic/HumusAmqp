<?php
/**
 * Copyright (c) 2016-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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

namespace HumusTest\Amqp\JsonRpc;

use Humus\Amqp\Connection;
use Humus\Amqp\ConnectionOptions;
use Humus\Amqp\Constants;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exception\InvalidArgumentException;
use Humus\Amqp\Exchange;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\JsonRpc\JsonRpcError;
use Humus\Amqp\JsonRpc\JsonRpcErrorFactory;
use Humus\Amqp\JsonRpc\JsonRpcRequest;
use Humus\Amqp\JsonRpc\JsonRpcResponse;
use Humus\Amqp\JsonRpc\JsonRpcServer;
use Humus\Amqp\JsonRpc\Request;
use Humus\Amqp\Queue;
use HumusTest\Amqp\Helper\CanCreateConnection;
use HumusTest\Amqp\Helper\DeleteOnTearDownTrait;
use HumusTest\Amqp\TestAsset\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

abstract class AbstractJsonRpcClientAndServerTest extends TestCase implements CanCreateConnection
{
    use DeleteOnTearDownTrait;

    private JsonRpcErrorFactory $errorFactory;

    protected function setUp(): void
    {
        $this->errorFactory = new JsonRpcErrorFactory();
    }

    /**
     * @test
     */
    public function it_sends_requests_and_server_responds(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'time2', 1, 'request-1');
        $request2 = new JsonRpcRequest('rpc-server', 'time2', 2, 'request-2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
            return JsonRpcResponse::withResult($request->id(), $request->params() * 2);
        };

        $logger = new ArrayLogger();
        $server = new JsonRpcServer($serverQueue, $callback, $logger, 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection();

        $this->assertCount(2, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertFalse($response1->isError());
        $this->assertEquals(2, $response1->result());

        $response2 = $responses->getResponse('request-2');
        $this->assertFalse($response2->isError());
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
    public function it_sends_shutdown_notifications(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange], 100, 'Humus\Amqp');

        $request1 = new JsonRpcRequest('rpc-server', 'shutdown', 1);
        $request2 = new JsonRpcRequest('rpc-server', 'shutdown', 2);

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
        };

        $server = new JsonRpcServer($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection(0.2);

        // we should have only one message, because we shutdown after first one
        $this->assertCount(1, $responses);
        $response = $responses->getIterator()->current();
        $this->assertNull($response->id());
        $this->assertSame('OK', $response->result());
    }

    /**
     * @test
     */
    public function it_responds_to_invalid_notifications(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange], 100, 'Humus\Amqp');

        $request1 = new JsonRpcRequest('rpc-server', 'what', 1);
        $request2 = new JsonRpcRequest('rpc-server', 'up', 2);

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
        };

        $server = new JsonRpcServer($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection();

        // we should have only one message, because we shutdown after first one
        $this->assertCount(2, $responses);

        foreach ($responses as $response) {
            $this->assertTrue($response->isError());
        }
    }

    /**
     * @test
     */
    public function it_responds_to_request_without_id_with_error(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange], 100);

        $request1 = new JsonRpcRequest('rpc-server', 'unknown', 1);
        $request2 = new JsonRpcRequest('rpc-server', 'stuff', 2);

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
        };

        $server = new JsonRpcServer($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection();

        // we should have only one message, because we shutdown after first one
        $this->assertCount(2, $responses);

        foreach ($responses as $response) {
            $this->assertTrue($response->isError());
        }
    }

    /**
     * @test
     */
    public function it_sends_requests_and_server_responds_and_handles_exception(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'time2', 1, 'request-1');
        $request2 = new JsonRpcRequest('rpc-server', 'time2', 2, 'request-2');

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
        $server = new JsonRpcServer($serverQueue, $callback, $logger, 1.0);

        $server->consume(2);

        $responses = $client->getResponseCollection();

        $this->assertCount(2, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertTrue($response1->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response1->error()->code());
        $this->assertEquals('Internal error', $response1->error()->message());

        $response2 = $responses->getResponse('request-2');
        $this->assertFalse($response2->isError());
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
    public function it_sends_requests_and_server_times_out(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'time2', 1, 'request-1');
        $request2 = new JsonRpcRequest('rpc-server', 'time2', 2, 'request-2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Request $request) {
            return JsonRpcResponse::withResult($request->id(), $request->params() * 2);
        };

        $server = new JsonRpcServer($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(1);

        $responses = $client->getResponseCollection(1);

        $this->assertCount(1, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertFalse($response1->isError());
        $this->assertEquals(2, $response1->result());
    }

    /**
     * @test
     */
    public function it_sends_ttl_requests_and_server_responds_late(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'time2', 1, 'request-1', '', 100);
        $request2 = new JsonRpcRequest('rpc-server', 'time2', 2, 'request-2', '', 100);

        $client->addRequest($request1);
        $client->addRequest($request2);

        $callback = function (Envelope $envelope) {
            return $envelope->getBody() * 2;
        };

        sleep(1);

        $serverExchange->publish('shutdown', '', Constants::AMQP_NOPARAM, [
            'type' => 'shutdown',
            'app_id' => 'Humus\Amqp',
        ]);

        $server = new JsonRpcServer($serverQueue, $callback, new NullLogger(), 1.0);

        $server->consume(1);

        $responses = $client->getResponseCollection(0.2);

        $this->assertCount(0, $responses);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_server_name_given_to_request(): void
    {
        $this->expectException(\Humus\Amqp\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid server given, no related exchange "invalid-rpc-server" found.');
        $connection = $this->createConnection();
        $channel = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $client->addRequest(new JsonRpcRequest('invalid-rpc-server', 'time2', 1, 'request-1', '', 100));
    }

    /**
     * @test
     */
    public function it_handles_invalid_requests_and_responses(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'time2', 1, 'request-1');
        $request2 = new JsonRpcRequest('rpc-server', 'time3', 2, 'request-2');

        $client->addRequest($request1);
        $client->addRequest($request2);

        $serverExchange->publish('{]kk]}', '', Constants::AMQP_NOPARAM, [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => 'request-3',
            'type' => 'time2',
            'reply_to' => $clientQueue->getName(),
            'user_id' => $clientQueue->getConnection()->getOptions()->login(),
            'headers' => [
                'jsonrpc' => JsonRpcRequest::JSONRPC_VERSION,
            ],
        ]);

        $serverExchange->publish('2', '', Constants::AMQP_NOPARAM, [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => 'request-4',
            'type' => 'time2',
            'reply_to' => $clientQueue->getName(),
            'user_id' => $clientQueue->getConnection()->getOptions()->login(),
        ]);

        $serverExchange->publish('2', '', Constants::AMQP_NOPARAM, [
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'type' => 'time2',
            'correlation_id' => 'request-5',
            'reply_to' => $clientQueue->getName(),
            'user_id' => $clientQueue->getConnection()->getOptions()->login(),
            'headers' => [
                'jsonrpc' => JsonRpcRequest::JSONRPC_VERSION,
            ],
        ]);

        $serverExchange->publish('2', '', Constants::AMQP_NOPARAM, [
            'content_type' => 'application/json',
            'delivery_mode' => 2,
            'type' => 'time2',
            'correlation_id' => 'request-6',
            'reply_to' => $clientQueue->getName(),
            'user_id' => $clientQueue->getConnection()->getOptions()->login(),
            'headers' => [
                'jsonrpc' => JsonRpcRequest::JSONRPC_VERSION,
            ],
        ]);

        // manipulate client to receive more responses
        $reflectionProperty = new \ReflectionProperty(get_class($client), 'requestIds');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($client, [
            'request-1',
            'request-2',
            'request-3',
            'request-4',
            'request-5',
            'request-6',
            'request-7',
            'request-8',
            'request-9',
        ]);

        $reflectionProperty2 = new \ReflectionProperty(get_class($client), 'countRequests');
        $reflectionProperty2->setAccessible(true);
        $reflectionProperty2->setValue($client, 9);

        $callback = function (Request $request) {
            if ('time2' === $request->method()) {
                return JsonRpcResponse::withResult($request->id(), $request->params() * 2);
            }

            return JsonRpcResponse::withError($request->id(), $this->errorFactory->create(JsonRpcError::ERROR_CODE_32601));
        };

        $logger = new ArrayLogger();
        $server = new JsonRpcServer($serverQueue, $callback, $logger, 1.0);

        $server->consume(6);

        $clientExchange->publish('invalid response', '', Constants::AMQP_NOPARAM, [
            'reply_to' => $clientQueue->getName(),
            'correlation_id' => 'invalid_id',
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'headers' => [
                'jsonrpc' => JsonRpcResponse::JSONRPC_VERSION,
            ],
        ]);

        $clientExchange->publish('invalid response', '', Constants::AMQP_NOPARAM, [
            'reply_to' => $clientQueue->getName(),
            'correlation_id' => 'request-8',
        ]);

        $clientExchange->publish('{"foo":"bar"}', '', Constants::AMQP_NOPARAM, [
            'reply_to' => $clientQueue->getName(),
            'correlation_id' => 'request-9',
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'headers' => [
                'jsonrpc' => JsonRpcResponse::JSONRPC_VERSION,
            ],
        ]);

        $responses = $client->getResponseCollection();

        $this->assertCount(9, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertFalse($response1->isError());
        $this->assertEquals(2, $response1->result());

        $response2 = $responses->getResponse('request-2');
        $this->assertTrue($response2->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32601, $response2->error()->code());
        $this->assertEquals('Method not found', $response2->error()->message());

        $response3 = $responses->getResponse('request-3');
        $this->assertTrue($response3->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32700, $response3->error()->code());
        $this->assertEquals('Parse error', $response3->error()->message());

        $response4 = $responses->getResponse('request-4');
        $this->assertTrue($response4->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32600, $response4->error()->code());
        $this->assertEquals('Invalid JsonRpcRequest', $response4->error()->message());

        $response5 = $responses->getResponse('request-5');
        $this->assertTrue($response5->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32600, $response5->error()->code());
        $this->assertEquals('Invalid JsonRpcRequest', $response5->error()->message());

        $response6 = $responses->getResponse('request-6');
        $this->assertTrue($response6->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32600, $response6->error()->code());
        $this->assertEquals('Invalid JsonRpcRequest', $response6->error()->message());

        $response6 = $responses->getResponse('invalid_id');
        $this->assertTrue($response6->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response6->error()->code());
        $this->assertEquals('Mismatched JSON-RPC IDs', $response6->error()->message());

        $response8 = $responses->getResponse('request-8');
        $this->assertTrue($response6->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response8->error()->code());
        $this->assertEquals('Invalid JSON-RPC response', $response8->error()->message());

        $response9 = $responses->getResponse('request-9');
        $this->assertTrue($response6->isError());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response9->error()->code());
        $this->assertEquals('Invalid JSON-RPC response', $response9->error()->message());
    }

    /**
     * @test
     */
    public function it_throws_exception_on_client_when_data_could_not_be_encoded_to_json(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Error during json encoding');

        $options = $this->prophesize(ConnectionOptions::class);
        $options->getLogin()->willReturn('user123')->shouldBeCalled();

        $connection = $this->prophesize(Connection::class);
        $connection->getOptions()->willReturn($options->reveal())->shouldBeCalled();

        $queue = $this->prophesize(Queue::class);
        $queue->getName()->willReturn('test-queue')->shouldBeCalled();
        $queue->getConnection()->willReturn($connection->reveal())->shouldBeCalled();

        $exchane = $this->prophesize(Exchange::class);

        $client = new JsonRpcClient($queue->reveal(), ['rpc-server' => $exchane->reveal()]);

        $client->addRequest(new JsonRpcRequest(
            'rpc-server',
            'something',
            "\xB1\x31"
        ));
    }

    /**
     * @test
     */
    public function it_returns_error_on_server_when_data_could_not_be_encoded_to_json(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'first', 1, 'request-1');

        $client->addRequest($request1);

        $callback = function (Request $request) {
            return JsonRpcResponse::withResult($request->id(), "\xB1\x31");
        };

        $logger = new NullLogger();
        $server = new JsonRpcServer($serverQueue, $callback, $logger, 1.0);

        $server->consume(1);

        $responses = $client->getResponseCollection(2);

        $this->assertCount(1, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertTrue($response1->isError());
        $this->assertSame(JsonRpcError::ERROR_CODE_32603, $response1->error()->code());
        $this->assertSame('Internal error', $response1->error()->message());
    }

    /**
     * @test
     */
    public function it_returns_trace_when_enabled(): void
    {
        $connection = $this->createConnection();
        $channel = $connection->newChannel();
        $channel2 = $connection->newChannel();

        $clientExchange = $channel->newExchange();
        $clientExchange->setType('direct');
        $clientExchange->setName('rpc-client');
        $clientExchange->delete();
        $clientExchange->declareExchange();

        $serverExchange = $channel2->newExchange();
        $serverExchange->setType('direct');
        $serverExchange->setName('rpc-server');
        $serverExchange->delete();
        $serverExchange->declareExchange();

        $clientQueue = $channel->newQueue();
        $clientQueue->setFlags(Constants::AMQP_AUTODELETE | Constants::AMQP_EXCLUSIVE);
        $clientQueue->declareQueue();
        $clientQueue->bind($clientExchange->getName());

        $serverQueue = $channel2->newQueue();
        $serverQueue->setName('rpc-server-queue');
        $serverQueue->delete();
        $serverQueue->declareQueue();
        $serverQueue->bind($serverExchange->getName());

        $this->addToCleanUp($clientExchange);
        $this->addToCleanUp($serverExchange);
        $this->addToCleanUp($clientQueue);
        $this->addToCleanUp($serverQueue);

        $client = new JsonRpcClient($clientQueue, ['rpc-server' => $serverExchange]);

        $request1 = new JsonRpcRequest('rpc-server', 'first', 1, 'request-1');

        $client->addRequest($request1);

        $callback = function (Request $request) {
            throw new \Exception('foo');
        };

        $logger = new NullLogger();
        $server = new JsonRpcServer($serverQueue, $callback, $logger, 1.0, '', '', true);

        $server->consume(1);

        $responses = $client->getResponseCollection(2);

        $this->assertCount(1, $responses);

        $response1 = $responses->getResponse('request-1');
        $this->assertTrue($response1->isError());
        $this->assertIsString($response1->error()->data());
        $this->assertNotEmpty($response1->error()->data());
    }
}
