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

namespace HumusTest\Amqp\Helper;

use Humus\Amqp\AmqpExchange;
use Humus\Amqp\AmqpQueue;

/**
 * Class DeleteOnTearDownTrait
 * @package HumusTest\Amqp\Helper
 */
trait DeleteOnTearDownTrait
{
    /**
     * @var AmqpExchange[]|AmqpQueue[]
     */
    protected $toCleanUp = [];

    protected function tearDown()
    {
        foreach ($this->toCleanUp as $resource) {
            $resource->delete();
        }

        $this->toCleanUp = [];
    }

    /**
     * @param AmqpExchange|AmqpQueue $resource
     */
    protected function addToCleanUp($resource)
    {
        $this->toCleanUp[] = $resource;
    }
}
