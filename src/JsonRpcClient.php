<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic
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

namespace Humus\Amqp;

use AMQPEnvelope;
use AMQPExchange;
use AMQPQueue;
use Assert\Assertion;

/**
 * Class JsonRpcClient
 * @package Humus\Amqp
 */
final class JsonRpcClient
{
    /**
     * @var AMQPQueue
     */
    private $queue;

    /**
     * @var int
     */
    private $requests;

    /**
     * @var array
     */
    private $replies = [];

    /**
     * @var int
     */
    private $timeout = 0;

    /**
     * Microseconds to wait between two tries when reply is not yet there
     *
     * @var int
     */
    private $waitMicros;

    /**
     * @var AMQPExchange[]
     */
    private $exchanges = [];

    /**
     * @var string|null
     */
    private $appId;

    /**
     * Constructor
     *
     * @param AMQPQueue $queue
     * @param int $waitMicros
     * @param string|null $appId
     */
    public function __construct(AMQPQueue $queue, $waitMicros = 1000, $appId = null)
    {
        Assertion::min($waitMicros, 1);

        $this->queue = $queue;
        $this->waitMicros = $waitMicros;

        if (null !== $appId) {
            Assertion::minLength($appId, 1);
            $this->appId = $appId;
        }
    }

    /**
     * Add a request to rpc client
     *
     * @param RpcClientRequest $request
     * @throws Exception\InvalidArgumentException
     */
    public function addRequest(RpcClientRequest $request)
    {
        $attributes = $this->createAttributes($request);

        $exchange = $this->getExchange($request->server());
        $exchange->publish(json_encode($request->payload()), $request->routingKey(), Constants::AMQP_NOPARAM, $attributes);

        $this->requests++;

        if ($request->expiration() > $this->timeout) {
            $this->timeout = $request->expiration();
        }
    }

    /**
     * Get rpc client replies
     *
     * Example:
     *
     * array(
     *     'message_id_1' => ['success' => true, 'result' => 'foo'],
     *     'message_id_2' => ['success' => false, 'error' => 'invalid parameters']
     * )
     *
     * @return array
     */
    public function getReplies()
    {
        $now = microtime(1);
        $this->replies = [];
        do {
            $message = $this->queue->get(Constants::AMQP_AUTOACK);

            if ($message instanceof AMQPEnvelope) {
                $this->replies[$message->getCorrelationId()] = json_decode($message->getBody());
            } else {
                usleep($this->waitMicros);
            }

            $time = microtime(1);
        } while (
            count($this->replies) < $this->requests
            || ($time - $now) < $this->timeout
        );

        $this->requests = 0;
        $this->timeout = 0;

        return $this->replies;
    }

    /**
     * @param string $name
     * @return AMQPExchange
     */
    private function getExchange($name)
    {
        if (! isset($this->exchanges[$name])) {
            $channel = $this->queue->getChannel();

            $exchange = new AMQPExchange($channel);
            $exchange->setName($name);

            $this->exchanges[$name] = $exchange;
        }

        return $this->exchanges[$name];
    }

    /**
     * @param RpcClientRequest $request
     * @return array
     */
    private function createAttributes(RpcClientRequest $request)
    {
        $attributes = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => $request->requestId(),
            'reply_to' => $this->queue->getName(),
        ];

        if (null !== $this->appId) {
            $attributes['app_id'] = $this->appId;
        }

        if (0 !== $request->expiration()) {
            $attributes['expiration'] = $request->expiration();
        }

        if (null !== $request->userId()) {
            $attributes['user_id'] = $request->userId();
        }

        if (null !== $request->messageId()) {
            $attributes['message_id'] = $request->messageId();
        }

        if (null !== $request->timestamp()) {
            $attributes['timestamp'] = $request->timestamp();
        }

        if (null !== $request->type()) {
            $attributes['type'] = $request->type();
        }

        return $attributes;
    }
}
