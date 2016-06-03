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

use Humus\Amqp\AbstractConsumer;
use Humus\Amqp\Constants;
use Humus\Amqp\DeliveryResult;
use Humus\Amqp\Envelope;
use Humus\Amqp\Exception;
use Humus\Amqp\Exchange;
use Humus\Amqp\Queue;
use Psr\Log\LoggerInterface;

/**
 * Class Server
 * @package Humus\Amqp\JsonRpc
 */
final class Server extends AbstractConsumer
{
    /**
     * @var Exchange
     */
    private $exchange;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var bool
     */
    private $returnTrace;

    /**
     * Constructor
     *
     * @param Queue $queue
     * @param callable $deliveryCallback
     * @param LoggerInterface $logger
     * @param float $idleTimeout in seconds
     * @param string|null $consumerTag
     * @param string|null $appId
     * @param bool $returnTrace
     */
    public function __construct(
        Queue $queue,
        callable $deliveryCallback,
        LoggerInterface $logger,
        float $idleTimeout,
        string $consumerTag = null,
        string $appId = '',
        bool $returnTrace = false
    ) {
        if (null === $consumerTag) {
            $consumerTag = bin2hex(random_bytes(24));
        }

        if (function_exists('pcntl_signal_dispatch')) {
            $this->usePcntlSignalDispatch = true;
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGHUP, [$this, 'shutdown']);
        }

        $this->queue = $queue;
        $this->exchange = $queue->getChannel()->newExchange();
        $this->exchange->setType('direct');
        $this->deliveryCallback = $deliveryCallback;
        $this->logger = $logger;
        $this->idleTimeout = $idleTimeout;
        $this->consumerTag = $consumerTag;
        $this->appId = $appId;
        $this->returnTrace = $returnTrace;
    }

    /**
     * @param Envelope $envelope
     * @param Queue $queue
     * @return DeliveryResult
     */
    protected function handleDelivery(Envelope $envelope, Queue $queue) : DeliveryResult
    {
        $this->countMessagesConsumed++;
        $this->countMessagesUnacked++;
        $this->lastDeliveryTag = $envelope->getDeliveryTag();
        $this->timestampLastMessage = microtime(true);
        $this->ack();

        $this->logger->debug('Handling delivery of message', $this->extractMessageInformation($envelope));

        if ($envelope->getAppId() === 'Humus\Amqp') {
            $this->handleInternalMessage($envelope);
            return DeliveryResult::MSG_ACK();
        }

        try {
            $request = $this->requestFromEnvelope($envelope);
            $e = null;

            $callback = $this->deliveryCallback;
            $response = $callback($request);

            if (!$response instanceof Response) {
                $response = new Response($response, null, $envelope->getCorrelationId());
            }
        } catch (Exception\InvalidJsonRpcVersion $e) {
            $response = new Response(null, new Error(Error::ERROR_CODE_32600), $envelope->getCorrelationId());
        } catch (Exception\InvalidJsonRpcRequest $e) {
            $response = new Response(null, new Error(Error::ERROR_CODE_32600), $envelope->getCorrelationId());
        } catch (Exception\JsonParseError $e) {
            $response = new Response(null, new Error(Error::ERROR_CODE_32700), $envelope->getCorrelationId());
        } catch (\Exception $e) {
            $response = new Response(null, new Error(Error::ERROR_CODE_32603), $envelope->getCorrelationId());
        } finally {
            $this->sendReply($response, $envelope, $e);
        }

        return DeliveryResult::MSG_ACK();
    }

    /**
     * Send reply to rpc client
     * 
     * @param Response $response
     * @param Envelope $envelope
     * @param \Exception|null $e
     */
    protected function sendReply(Response $response, Envelope $envelope, \Exception $e = null)
    {
        $attributes = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => $envelope->getCorrelationId(),
            'app_id' => $this->appId,
            'headers' => [
                'jsonrpc' => Response::JSONRPC,
            ]
        ];

        if ($response->hasError()) {
            $payload = [
                'error' => [
                    'code' => $response->error()->code(),
                    'message' => $response->error()->message(),
                ],
                'data' => $response->error()->data(),
            ];
        } else {
            $payload = [
                'result' => $response->result(),
            ];
        }

        $this->exchange->publish(json_encode($payload), $envelope->getReplyTo(), Constants::AMQP_NOPARAM, $attributes);
    }

    /**
     * Handle process flag
     *
     * @param Envelope $envelope
     * @param DeliveryResult $flag
     * @return void
     */
    protected function handleProcessFlag(Envelope $envelope, DeliveryResult $flag)
    {
        // do nothing, message was already acknowledged
    }

    /**
     * @param Envelope $envelope
     * @return Request
     * @throws Exception\InvalidJsonRpcVersion
     * @throws Exception\JsonParseError
     */
    protected function requestFromEnvelope(Envelope $envelope) : Request
    {
        if ($envelope->getHeader('jsonrpc') !== Request::JSONRPC) {
            throw new Exception\InvalidJsonRpcVersion();
        }

        if ($envelope->getContentEncoding() !== 'UTF-8'
            || $envelope->getContentType() !== 'application/json') {
            throw new Exception\InvalidJsonRpcRequest();
        }

        $payload = json_decode($envelope->getBody(), true);

        if (0 != json_last_error()) {
            throw new Exception\JsonParseError();
        }

        return new Request(
            $envelope->getExchangeName(),
            $envelope->getType(),
            $payload,
            $envelope->getCorrelationId(),
            $envelope->getRoutingKey(),
            $envelope->getExpiration(),
            $envelope->getTimestamp()
        );
    }
}
