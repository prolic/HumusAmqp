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

namespace HumusTest\Amqp\JsonRpc;

use Humus\Amqp\JsonRpc\Response;
use Humus\Amqp\JsonRpc\ResponseCollection;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class ResponseCollectionTest
 * @package HumusTest\Amqp\JsonRpc
 */
class ResponseCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_iterates_and_accesses_correctly()
    {
        $responseCollection = new ResponseCollection();
        $responseCollection->addResponse(Response::withResult('id1', ['foo' => 'bar']));
        $responseCollection->addResponse(Response::withResult('id2', ['foo' => 'bam']));

        $i = 0;
        foreach ($responseCollection as $response) {
            $this->assertEquals('id' . ++$i, $response->id());
        }

        $this->assertEquals(2, $i);
        $this->assertCount(2, $responseCollection);

        $this->assertTrue($responseCollection->hasResponse('id2'));
        $this->assertFalse($responseCollection->hasResponse('unknown id'));

        $response = $responseCollection->getResponse('id1');
        $this->assertEquals('id1', $response->id());
        $this->assertEquals(['foo' => 'bar'], $response->result());

        $this->assertNull($responseCollection->getResponse('unknown id'));
    }
}
