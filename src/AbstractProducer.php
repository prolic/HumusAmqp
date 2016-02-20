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

namespace Humus\Amqp;

use Assert\Assertion;
use Humus\Amqp\Driver\AmqpExchange;
use Humus\Amqp\Exception\AmqpChannelException;

/**
 * Class AbstractProducer
 * @package Humus\Amqp
 */
abstract class AbstractProducer implements Producer
{
    /**
     * @var AmqpExchange
     */
    protected $exchange;

    /**
     * @var array
     */
    protected $defaultAttributes;

    /**
     * @var bool
     */
    protected $confirm;

    /**
     * @var bool
     */
    protected $transactional;

    /**
     * Constructor
     *
     * @param AmqpExchange $exchange
     * @param bool $confirm
     * @param bool $transactional
     * @param array|null $defaultAttributes
     * @throws AmqpChannelException
     */
    public function __construct(AmqpExchange $exchange, $confirm, $transactional, array $defaultAttributes = null)
    {
        Assertion::boolean($confirm);
        Assertion::boolean($transactional);

        if ($confirm && $transactional) {
            throw new AmqpChannelException('Only non-transactional channels can be put in confirm mode');
        }

        $this->confirm = $confirm;
        $this->exchange = $exchange;

        if (null !== $defaultAttributes) {
            $this->defaultAttributes = $defaultAttributes;
        } else {
            $this->defaultAttributes = static::defaultAttributes();
        }
        
        if ($confirm) {
            $this->exchange->getChannel()->confirmSelect();
        }
    }

    /**
     * @inheritdoc
     */
    public function startTransaction()
    {
        if ($this->confirm) {
            throw new AmqpChannelException('Channel is in confirm mode and transaction cannot be started');
        }

        return $this->exchange->getChannel()->startTransaction();
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        return $this->exchange->getChannel()->commitTransaction();
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        return $this->exchange->getChannel()->rollbackTransaction();
    }
}
