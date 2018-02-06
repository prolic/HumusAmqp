<?php
/**
 * Copyright (c) 2016-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
 * Class JsonRpcServer
 * @package Humus\Amqp\JsonRpc
 */
final class JsonRpcServer extends AbstractConsumer
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
     * @param callable(JsonRpcRequest):JsonRpcResponse $deliveryCallback
     * @param LoggerInterface $logger
     * @param float $idleTimeout in seconds
     * @param string $consumerTag
     * @param string $appId
     * @praram bool $returnTrace
     */
    public function __construct(
        Queue $queue,
        callable $deliveryCallback,
        LoggerInterface $logger,
        float $idleTimeout,
        string $consumerTag = '',
        string $appId = '',
        bool $returnTrace = false
    ) {
        if ('' === $consumerTag) {
            $consumerTag = bin2hex(random_bytes(24));
        }

        if (extension_loaded('pcntl')) {
            declare(ticks=1);

            $this->usePcntlSignalDispatch = true;

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
    protected function handleDelivery(Envelope $envelope, Queue $queue): DeliveryResult
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
            $callback = $this->deliveryCallback;
            $response = $callback($request);

            if ('' === $request->id()) {
                // notifications have no reply
                return DeliveryResult::MSG_ACK();
            }

            if (! $response instanceof JsonRpcResponse) {
                $response = JsonRpcResponse::withResult($envelope->getCorrelationId(), $response);
            }
        } catch (Exception\InvalidJsonRpcVersion $e) {
            $this->logger->error('Invalid json rpc version', $this->extractMessageInformation($envelope));
            $response = JsonRpcResponse::withError(
                $envelope->getCorrelationId(),
                new JsonRpcError(
                    JsonRpcError::ERROR_CODE_32600,
                    null,
                    $this->returnTrace ? $e->getTraceAsString() : null
                ),
                $this->returnTrace ? $e->getTraceAsString() : null
            );
        } catch (Exception\InvalidJsonRpcRequest $e) {
            $this->logger->error('Invalid json rpc request', $this->extractMessageInformation($envelope));
            $response = JsonRpcResponse::withError(
                $envelope->getCorrelationId(),
                new JsonRpcError(
                    JsonRpcError::ERROR_CODE_32600,
                    null,
                    $this->returnTrace ? $e->getTraceAsString() : null
                ),
                $this->returnTrace ? $e->getTraceAsString() : null
            );
        } catch (Exception\JsonParseError $e) {
            $this->logger->error('Json parse error', $this->extractMessageInformation($envelope));
            $response = JsonRpcResponse::withError(
                $envelope->getCorrelationId(),
                new JsonRpcError(
                    JsonRpcError::ERROR_CODE_32700,
                    null,
                    $this->returnTrace ? $e->getTraceAsString() : null
                ),
                $this->returnTrace ? $e->getTraceAsString() : null
            );
        } catch (\Throwable $e) {
            $extra = $this->extractMessageInformation($envelope);
            $extra['exception_class'] = get_class($e);
            $extra['exception_message'] = $e->getMessage();
            $extra['exception_trace'] = $e->getTraceAsString();
            $this->logger->error('Exception occurred', $extra);
            $response = JsonRpcResponse::withError(
                $envelope->getCorrelationId(),
                new JsonRpcError(
                    JsonRpcError::ERROR_CODE_32603,
                    null,
                    $this->returnTrace ? $e->getTraceAsString() : null
                ),
                $this->returnTrace ? $e->getTraceAsString() : null
            );
        }

        $this->sendReply($response, $envelope);

        return DeliveryResult::MSG_ACK();
    }

    /**
     * Send reply to rpc client
     *
     * @param Response $response
     * @param Envelope $envelope
     */
    protected function sendReply(Response $response, Envelope $envelope)
    {
        $attributes = [
            'content_type' => 'application/json',
            'content_encoding' => 'UTF-8',
            'delivery_mode' => 2,
            'correlation_id' => $envelope->getCorrelationId(),
            'app_id' => $this->appId,
            'headers' => [
                'jsonrpc' => JsonRpcResponse::JSONRPC_VERSION,
            ],
        ];

        if ($response->isError()) {
            $payload = [
                'error' => [
                    'code' => $response->error()->code(),
                    'message' => $response->error()->message(),
                ],
                'data' => $response->data(),
            ];
        } else {
            $payload = [
                'result' => $response->result(),
            ];
        }

        $message = json_encode($payload);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = json_encode([
                'error' => [
                    'code' => JsonRpcError::ERROR_CODE_32603,
                    'message' => 'Internal error',
                ],
            ]);
        }

        $this->exchange->publish($message, $envelope->getReplyTo(), Constants::AMQP_NOPARAM, $attributes);
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
    protected function requestFromEnvelope(Envelope $envelope): Request
    {
        if ($envelope->getHeader('jsonrpc') !== JsonRpcRequest::JSONRPC_VERSION) {
            throw new Exception\InvalidJsonRpcVersion();
        }

        if ($envelope->getContentEncoding() !== 'UTF-8'
            || $envelope->getContentType() !== 'application/json'
        ) {
            throw new Exception\InvalidJsonRpcRequest();
        }

        $payload = json_decode($envelope->getBody(), true);

        if (0 !== json_last_error()) {
            throw new Exception\JsonParseError();
        }

        return new JsonRpcRequest(
            $envelope->getExchangeName(),
            $envelope->getType(),
            $payload,
            $envelope->getCorrelationId(),
            $envelope->getRoutingKey(),
            (int) $envelope->getExpiration(),
            $envelope->getTimestamp()
        );
    }
}
