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

namespace HumusTest\Amqp\JsonRpc;

use Humus\Amqp\Exception\InvalidArgumentException;
use Humus\Amqp\JsonRpc\JsonRpcError;
use Humus\Amqp\JsonRpc\JsonRpcResponse;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseTest
 * @package HumusTest\Amqp\JsonRpc
 */
class ResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_valid_result()
    {
        $response = JsonRpcResponse::withResult('id', ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $response->result());
        $this->assertEquals('id', $response->id());
    }

    /**
     * @test
     */
    public function it_creates_valid_error()
    {
        $response = JsonRpcResponse::withError('id', new JsonRpcError(JsonRpcError::ERROR_CODE_32603));
        $this->assertInstanceOf(JsonRpcError::class, $response->error());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response->error()->code());
        $this->assertEquals('Internal error', $response->error()->message());
        $this->assertEquals('id', $response->id());
        $this->assertNull($response->error()->data());
    }

    /**
     * @test
     */
    public function it_creates_valid_error_with_data()
    {
        $response = JsonRpcResponse::withError('id', new JsonRpcError(JsonRpcError::ERROR_CODE_32603, ['foo' => 'bar']));
        $this->assertInstanceOf(JsonRpcError::class, $response->error());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response->error()->code());
        $this->assertEquals('Internal error', $response->error()->message());
        $this->assertEquals('id', $response->id());
        $this->assertEquals(['foo' => 'bar'], $response->error()->data());
    }

    /**
     * @test
     */
    public function it_creates_valid_error_with_custom_message()
    {
        $response = JsonRpcResponse::withError('id', new JsonRpcError(JsonRpcError::ERROR_CODE_32603, 'custom message'));
        $this->assertInstanceOf(JsonRpcError::class, $response->error());
        $this->assertEquals(JsonRpcError::ERROR_CODE_32603, $response->error()->code());
        $this->assertEquals('custom message', $response->error()->message());
        $this->assertEquals('id', $response->id());
    }

    /**
     * @test
     * @dataProvider customCodeDateProvider
     */
    public function it_creates_valid_error_with_custom_error_code(int $code, string $message)
    {
        $response = JsonRpcResponse::withError('id', new JsonRpcError($code, $message));
        $this->assertInstanceOf(JsonRpcError::class, $response->error());
        $this->assertEquals($code, $response->error()->code());
        $this->assertEquals($message, $response->error()->message());
        $this->assertEquals('id', $response->id());
    }
    
    public function customCodeDateProvider(): array
    {
        return [
            [-32000, 'custom error message lower'],
            [-32050, 'custom error message middle'],
            [-32099, 'custom error message upper'],
        ];
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_result_given()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('result must only contain arrays and scalar values');

        JsonRpcResponse::withResult('id', new \stdClass());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_sub_result_given()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('result must only contain arrays and scalar values');

        JsonRpcResponse::withResult('id', ['foo' => new \stdClass()]);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_error_code_given()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status code provided: 100');

        JsonRpcResponse::withError('id', new JsonRpcError(100));
    }
}
