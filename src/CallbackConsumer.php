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

namespace Humus\Amqp;

use Assert\Assertion;
use Psr\Log\LoggerInterface;

/**
 * The consumer attaches to a single queue
 *
 * The used block size is the configured prefetch size of the queue's channel
 *
 * Class CallbackConsumer
 * @package Humus\Amqp
 */
final class CallbackConsumer extends AbstractConsumer
{
    /**
     * Constructor
     *
     * @param Queue $queue
     * @param LoggerInterface $logger
     * @param float $idleTimeout in seconds
     * @param callable $deliveryCallback,
     * @param callable|null $flushCallback,
     * @param callable|null $errorCallback
     * @param string|null $consumerTag
     * @param int $blockSize
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        Queue $queue,
        LoggerInterface $logger,
        float $idleTimeout,
        callable $deliveryCallback,
        callable $flushCallback = null,
        callable $errorCallback = null,
        string $consumerTag = null,
        int $blockSize = 50
    ) {
        Assertion::min($blockSize, 1);

        if (null === $consumerTag) {
            $consumerTag = bin2hex(random_bytes(24));
        }

        if (extension_loaded('pcntl')) {
            $this->usePcntlSignalDispatch = true;
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGHUP, [$this, 'shutdown']);
        }

        $this->queue = $queue;
        $this->logger = $logger;
        $this->idleTimeout = $idleTimeout;
        $this->deliveryCallback = $deliveryCallback;
        $this->flushCallback = $flushCallback;
        $this->errorCallback = $errorCallback;
        $this->consumerTag = $consumerTag;
        $this->blockSize = $blockSize;
    }
}
