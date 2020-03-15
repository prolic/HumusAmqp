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

namespace HumusTest\Amqp\Container;

use Humus\Amqp\Container\DriverFactory;
use Humus\Amqp\Driver\Driver;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DriverFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_amqp_extension_driver(): void
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'driver' => 'amqp-extension',
                ],
            ],
        ])->shouldBeCalled();

        $factory = new DriverFactory();
        $driver = $factory($container->reveal());

        $this->assertInstanceOf(Driver::class, $driver);
        $this->assertTrue($driver->is(Driver::AMQP_EXTENSION()));
    }

    /**
     * @test
     */
    public function it_returns_php_amqplib_driver(): void
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'humus' => [
                'amqp' => [
                    'driver' => 'php-amqplib',
                ],
            ],
        ])->shouldBeCalled();

        $factory = new DriverFactory();
        $driver = $factory($container->reveal());

        $this->assertInstanceOf(Driver::class, $driver);
        $this->assertTrue($driver->is(Driver::PHP_AMQP_LIB()));
    }
}
