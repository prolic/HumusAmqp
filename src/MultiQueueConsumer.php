<?php
/*
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

namespace Humus\Amqp;

use AMQPQueue;
use ArrayIterator;
use Assert\Assertion;
use InfiniteIterator;

/**
 * The consumer attaches to a single queue
 *
 * The used block size is the configured prefetch size of the queue's channel
 *
 * Class MultiQueueConsumer
 * @package Humus\Amqp
 */
final class MultiQueueConsumer extends AbstractMultiQueueConsumer
{
    /**
     * Constructor
     *
     * @param AMQPQueue[] $queues
     * @param float $idleTimeout in seconds
     * @param int $waitTimeout in microseconds
     * @param callable $deliveryCallback,
     * @param callable|null $flushCallback,
     * @param callable|null $errorCallback
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        array $queues,
        $idleTimeout,
        $waitTimeout,
        callable $deliveryCallback,
        callable $flushCallback = null,
        callable $errorCallback = null
    ) {
        Assertion::float($idleTimeout);
        Assertion::integer($waitTimeout);

        if (function_exists('pcntl_signal_dispatch')) {
            $this->usePcntlSignalDispatch = true;
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGHUP, [$this, 'shutdown']);
        }

        if (empty($queues)) {
            throw new Exception\InvalidArgumentException(
                'No queues given'
            );
        }

        $q = [];
        foreach ($queues as $queue) {
            if (!$queue instanceof AMQPQueue) {
                throw new Exception\InvalidArgumentException(
                    'Queue must be an instance of AMQPQueue, '
                    . is_object($queue) ? get_class($queue) : gettype($queue) . ' given'
                );
            }
            if (null === $this->blockSize) {
                $this->blockSize = $queue->getChannel()->getPrefetchCount();
            }
            $q[] = $queue;
        }
        $this->idleTimeout = (float) $idleTimeout;
        $this->waitTimeout = (int) $waitTimeout;
        $this->queues = new InfiniteIterator(new ArrayIterator($q));
    }
}
