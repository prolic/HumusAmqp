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

use Humus\Amqp\Exception\ChannelException;

/**
 * Class AbstractProducer
 * @package Humus\Amqp
 */
abstract class AbstractProducer implements Producer
{
    /**
     * @var Exchange
     */
    protected $exchange;

    /**
     * @var array
     */
    protected $defaultAttributes;

    /**
     * Constructor
     *
     * @param Exchange $exchange
     * @param array|null $defaultAttributes
     * @throws ChannelException
     */
    public function __construct(Exchange $exchange, array $defaultAttributes = null)
    {
        $this->exchange = $exchange;

        if (null !== $defaultAttributes) {
            $this->defaultAttributes = $defaultAttributes;
        }
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        $this->exchange->getChannel()->startTransaction();
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        $this->exchange->getChannel()->commitTransaction();
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        $this->exchange->getChannel()->rollbackTransaction();
    }

    /**
     * @inheritdoc
     */
    public function confirmSelect()
    {
        $this->exchange->getChannel()->confirmSelect();
    }

    /**
     * @inheritdoc
     */
    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null)
    {
        $this->exchange->getChannel()->setConfirmCallback($ackCallback, $nackCallback);
    }

    /**
     * @inheritdoc
     */
    public function waitForConfirm(float $timeout = 0.0)
    {
        $this->exchange->getChannel()->waitForConfirm($timeout);
    }

    /**
     * @inheritdoc
     */
    public function setReturnCallback(callable $returnCallback = null)
    {
        $this->exchange->getChannel()->setReturnCallback($returnCallback);
    }

    /**
     * @inheritdoc
     */
    public function waitForBasicReturn(float $timeout = 0.0)
    {
        $this->exchange->getChannel()->waitForBasicReturn($timeout);
    }
}
