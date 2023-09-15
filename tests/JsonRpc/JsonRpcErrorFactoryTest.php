<?php
/**
 * Copyright (c) 2016-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
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
use Humus\Amqp\JsonRpc\Error;
use Humus\Amqp\JsonRpc\JsonRpcErrorFactory;
use PHPUnit\Framework\TestCase;

class JsonRpcErrorFactoryTest extends TestCase
{
    private ?JsonRpcErrorFactory $factory = null;

    private array $customMessages = [
        -32000 => 'upper message test',
        -32099 => 'lower message test',
    ];

    protected function setUp(): void
    {
        $this->factory = new JsonRpcErrorFactory($this->customMessages);
    }

    /**
     * @test
     * @dataProvider predefinedCodeDataProvider
     */
    public function it_creates_valid_predefined_error(int $code, string $message, $data = null): void
    {
        $error = $this->factory->create($code, null, $data);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($code, $error->code());
        $this->assertEquals($message, $error->message());
        $this->assertEquals($data, $error->data());
    }

    /**
     * @test
     * @dataProvider customCodeDataProvider
     */
    public function it_creates_valid_custom_error(int $code, string $message, $data = null): void
    {
        $error = $this->factory->create($code, null, $data);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($code, $error->code());
        $this->assertEquals($message, $error->message());
        $this->assertEquals($data, $error->data());
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->create(-1);
    }

    public function predefinedCodeDataProvider(): array
    {
        return [
            [-32700, 'Parse error', null],
            [-32600, 'Invalid JsonRpcRequest', ['test' => 1]],
            [-32601, 'Method not found', ['test' => 2]],
            [-32602, 'Invalid params', 'test'],
            [-32603, 'Internal error', ['ex' => 'exception']],
        ];
    }

    public function customCodeDataProvider(): array
    {
        $set = [];

        foreach ($this->customMessages as $code => $message) {
            $set[] = [$code, $message, ['test' => 1]];
        }

        return $set;
    }
}
