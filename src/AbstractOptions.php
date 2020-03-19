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

use Traversable;

abstract class AbstractOptions
{
    /**
     * We use the __ prefix to avoid collisions with properties in
     * user-implementations.
     */
    protected bool $__strictMode__ = true;

    /**
     * @param array|Traversable|null $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setFromArray($options);
        }
    }

    /**
     * Set one or more configuration properties
     *
     * @param  array|Traversable|AbstractOptions $options
     *
     * @return AbstractOptions Provides fluent interface
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setFromArray($options): AbstractOptions
    {
        if ($options instanceof self) {
            $options = $options->toArray();
        }

        if (! \is_array($options) && ! $options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Parameter provided to %s must be an %s, %s or %s',
                    __METHOD__,
                    'array',
                    'Traversable',
                    'Laminas\Stdlib\AbstractOptions'
                )
            );
        }

        foreach ($options as $key => $value) {
            $this->__set($key, $value);
        }

        return $this;
    }

    public function toArray(): array
    {
        $array = [];

        $transform = function ($letters): string {
            $letter = \array_shift($letters);

            return '_' . \strtolower($letter);
        };

        foreach ($this as $key => $value) {
            if ($key === '__strictMode__') {
                continue;
            }
            $normalizedKey = \preg_replace_callback('/([A-Z])/', $transform, $key);
            $array[$normalizedKey] = $value;
        }

        return $array;
    }

    /**
     * Set a configuration property
     *
     * @see ParameterObject::__set()
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     *
     * @throws Exception\BadMethodCallException
     */
    public function __set(string $key, $value): void
    {
        $setter = 'set' . str_replace('_', '', $key);

        if (\is_callable([$this, $setter])) {
            $this->{$setter}($value);

            return;
        }

        if ($this->__strictMode__) {
            throw new Exception\BadMethodCallException(sprintf(
                'The option "%s" does not have a callable "%s" ("%s") setter method which must be defined',
                $key,
                'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))),
                $setter
            ));
        }
    }

    /**
     * Get a configuration property
     *
     * @see ParameterObject::__get()
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws Exception\BadMethodCallException
     */
    public function __get(string $key)
    {
        $getter = 'get' . str_replace('_', '', $key);

        if (\is_callable([$this, $getter])) {
            return $this->{$getter}();
        }

        throw new Exception\BadMethodCallException(sprintf(
            'The option "%s" does not have a callable "%s" getter method which must be defined',
            $key,
            'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))
        ));
    }

    /**
     * Test if a configuration property is null
     * @see ParameterObject::__isset()
     */
    public function __isset(string $key): bool
    {
        $getter = 'get' . str_replace('_', '', $key);

        return method_exists($this, $getter) && null !== $this->__get($key);
    }

    /**
     * Set a configuration property to NULL
     *
     * @see ParameterObject::__unset()
     */
    public function __unset(string $key): void
    {
        try {
            $this->__set($key, null);
        } catch (Exception\BadMethodCallException $e) {
            throw new Exception\InvalidArgumentException(
                'The class property $' . $key . ' cannot be unset as'
                . ' NULL is an invalid value for it',
                0,
                $e
            );
        }
    }
}
