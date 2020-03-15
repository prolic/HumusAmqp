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

namespace Humus\Amqp;

use Humus\Amqp\Exception\ChannelException;

abstract class AbstractProducer implements Producer
{
    protected Exchange $exchange;
    protected array $defaultAttributes;

    public function __construct(Exchange $exchange, ?array $defaultAttributes = null)
    {
        $this->exchange = $exchange;

        if (null !== $defaultAttributes) {
            $this->defaultAttributes = $defaultAttributes;
        }
    }

    public function startTransaction(): void
    {
        $this->exchange->getChannel()->startTransaction();
    }

    public function commitTransaction(): void
    {
        $this->exchange->getChannel()->commitTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->exchange->getChannel()->rollbackTransaction();
    }

    public function confirmSelect(): void
    {
        $this->exchange->getChannel()->confirmSelect();
    }

    public function setConfirmCallback(callable $ackCallback = null, callable $nackCallback = null): void
    {
        $this->exchange->getChannel()->setConfirmCallback($ackCallback, $nackCallback);
    }

    public function waitForConfirm(float $timeout = 0.0): void
    {
        $this->exchange->getChannel()->waitForConfirm($timeout);
    }

    public function setReturnCallback(callable $returnCallback = null): void
    {
        $this->exchange->getChannel()->setReturnCallback($returnCallback);
    }

    public function waitForBasicReturn(float $timeout = 0.0): void
    {
        $this->exchange->getChannel()->waitForBasicReturn($timeout);
    }
}
