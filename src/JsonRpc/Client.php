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

namespace Humus\Amqp\JsonRpc;

use Assert\Assertion;
use Humus\Amqp\Constants;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;

/**
 * Class Client
 * @package Humus\Amqp\JsonRpc
 */
class Client
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var string[]
     */
    private $requestIds = [];

    /**
     * @var ResponseCollection
     */
    private $responseCollection;

    /**
     * Milliseconds to wait between two tries when reply is not yet there
     *
     * @var int
     */
    private $waitMillis;

    /**
     * @var Exchange[]
     */
    private $exchanges = [];

    /**
     * @var string|null
     */
    private $appId;

    /**
     * @var int
     */
    private $timeout = 0;

    /**
     * Constructor
     *
     * @param Queue $queue
     * @param Exchange[] $exchanges
     * @param int $waitMillis
     * @param string $appId
     */
    public function __construct(Queue $queue, array $exchanges, int $waitMillis = 100, string $appId = '')
    {
        Assertion::min($waitMillis, 1);
        Assertion::notEmpty($exchanges, 'No exchanges given');
        Assertion::allIsInstanceOf($exchanges, Exchange::class);

        $this->queue = $queue;
        $this->exchanges = $exchanges;
        $this->waitMillis = $waitMillis;
        $this->appId = $appId;
        $this->responseCollection = new ResponseCollection();
    }

    /**
     * Add a request to rpc client
     *
     * @param Request $request
     * @throws Exception\InvalidArgumentException
     */
    public function addRequest(Request $request)
    {
        $attributes = $this->createAttributes($request);

        $exchange = $this->getExchange($request->server());
        $exchange->publish(json_encode($request->params()), $request->routingKey(), Constants::AMQP_NOPARAM, $attributes);

        if (null !== $request->id()) {
            $this->requestIds[] = $request->id();
        }

        if (0 != $request->expiration() && ceil($request->expiration() / 1000) > $this->timeout ) {
            $this->timeout = ceil($request->expiration() / 1000);
        }
    }

    /**
     * Get response collection
     *
     * @param float $timeout in seconds
     * @return ResponseCollection
     */
    public function getResponseCollection(float $timeout = 0) : ResponseCollection
    {
        if ($timeout < $this->timeout) {
            $timeout = $this->timeout;
        }

        $now = microtime(true);
        $this->responseCollection = new ResponseCollection();

        do {
            $message = $this->queue->get(Constants::AMQP_AUTOACK);

            if ($message instanceof Envelope) {
                $this->responseCollection->addResponse($this->responseFromEnvelope($message));
            } else {
                usleep($this->waitMillis * 1000);
            }
            $time = microtime(true);
        } while (
            $this->responseCollection->count() < count($this->requestIds)
            && (0 == $timeout || ($timeout > 0 && (($time - $now) < $timeout)))
        );

        $this->requestIds = [];
        $this->timeout = 0;

        return $this->responseCollection;
    }

    /**
     * @param string $server
     * @return Exchange
     */
    private function getExchange(string $server)
    {
        if (! isset($this->exchanges[$server])) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid server given, no related exchange "%s" found.',
                $server
            ));
        }

        return $this->exchanges[$server];
    }

    /**
     * @param Request $request
     * @return array
     */
    private function createAttributes(Request $request) : array
    {
        $attributes = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'type' => $request->method(),
            'timestamp' => $request->timestamp(),
            'reply_to' => $this->queue->getName(),
            'app_id' => $this->appId,
            'user_id' => $this->queue->getConnection()->getOptions()->getLogin(),
            'headers' => [
                'jsonrpc' => $request::JSONRPC,
            ],
        ];

        if (null !== $request->id()) {
            $attributes['correlation_id'] = $request->id();
        }

        if (0 < $request->expiration()) {
            $attributes['expiration'] = $request->expiration();
        }

        return $attributes;
    }

    /**
     * @param Envelope $envelope
     * @return Response
     */
    private function responseFromEnvelope(Envelope $envelope) : Response
    {
        if ($envelope->getHeader('jsonrpc') !== Request::JSONRPC
            || $envelope->getContentEncoding() !== 'UTF-8'
            || $envelope->getContentType() !== 'application/json'
        ) {
            return new Response(null, new Error(Error::ERROR_CODE_32603, 'Invalid JSON-RPC response'));
        }

        $payload = json_decode($envelope->getBody(), true);

        if (null === $payload) {
            $response = new Response(
                null,
                new Error(Error::ERROR_CODE_32603, 'JSON cannot be decoded'),
                $envelope->getCorrelationId()
            );
        } elseif (! in_array($envelope->getCorrelationId(), $this->requestIds)) {
            $response = new Response(
                null,
                new Error(Error::ERROR_CODE_32603, 'Mismatched JSON-RPC IDs'),
                $envelope->getCorrelationId()
            );
        } elseif (isset($payload['result'])) {
            $response = new Response($payload['result'], null, $envelope->getCorrelationId());
        } elseif (! isset($payload['error']['code'])
            || ! isset($payload['error']['message'])
            || ! is_int($payload['error']['code'])
            || ! is_string($payload['error']['message'])
        ) {
            $response = new Response(null, new Error(Error::ERROR_CODE_32603, 'Invalid JSON-RPC response'));
        } else {
            $response = new Response(
                null,
                new Error($payload['error']['code'], $payload['error']['message']),
                $envelope->getCorrelationId(),
                $payload['data'] ?? null
            );
        }

        return $response;
    }
}
