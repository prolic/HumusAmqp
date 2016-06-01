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
     * @var int
     */
    private $requests = 0;

    /**
     * @var array
     */
    private $replies = [];

    /**
     * Microseconds to wait between two tries when reply is not yet there
     *
     * @var int
     */
    private $waitMicros;

    /**
     * @var Exchange[]
     */
    private $exchanges = [];

    /**
     * @var string|null
     */
    private $appId;

    /**
     * Constructor
     *
     * @param Queue $queue
     * @param Exchange[] $exchanges
     * @param int $waitMicros
     * @param string $appId
     */
    public function __construct(Queue $queue, array $exchanges, int $waitMicros = 5000, string $appId = '')
    {
        Assertion::min($waitMicros, 1);
        Assertion::notEmpty($exchanges, 'No exchanges given');
        Assertion::allIsInstanceOf($exchanges, Exchange::class);

        $this->queue = $queue;
        $this->exchanges = $exchanges;
        $this->waitMicros = $waitMicros;
        $this->appId = $appId;
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
        $exchange->publish(json_encode($request->payload()), $request->routingKey(), Constants::AMQP_NOPARAM, $attributes);

        $this->requests++;
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
     * @param float $timeout in seconds
     * @return array
     */
    public function getReplies(float $timeout = 0) : array
    {
        $now = microtime(true);
        $this->replies = [];

        do {
            $message = $this->queue->get(Constants::AMQP_AUTOACK);

            if ($message instanceof Envelope) {
                $this->replies[$message->getCorrelationId()] = json_decode($message->getBody(), true);
            } else {
                usleep($this->waitMicros);
            }

            $time = microtime(true);
        } while (
            count($this->replies) < $this->requests
            || ($timeout > 0 && (($time - $now) < $timeout))
        );

        $this->requests = 0;

        return $this->replies;
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
            'correlation_id' => $request->requestId(),
            'reply_to' => $this->queue->getName(),
            'app_id' => $this->appId,
            'user_id' => $this->queue->getConnection()->getOptions()->getLogin(),
        ];

        if (0 < $request->expiration()) {
            $attributes['expiration'] = (int) floor($request->expiration() * 1000); // in microseconds
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
