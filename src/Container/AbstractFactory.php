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

namespace Humus\Amqp\Container;

use ArrayAccess;
use Humus\Amqp\Exception;

/**
 * Class AbstractFactory
 * @package Humus\Amqp\Container
 */
abstract class AbstractFactory
{
    /**
     * @return string
     */
    public function vendorName()
    {
        return 'humus';
    }

    /**
     * @return string
     */
    public function packageName()
    {
        return 'amqp';
    }

    /**
     * @return string
     */
    abstract public function componentName();

    /**
     * @return string
     */
    abstract public function elementName();

    /**
     * Returns a list of mandatory options which must be available
     *
     * @return string[] List with mandatory options
     */
    abstract public function mandatoryOptions();

    /**
     * Returns a list of default options, which are merged in \Interop\Config\RequiresConfig::options
     *
     * @return string[] List with default options and values
     */
    abstract public function defaultOptions();

    /**
     * @return array
     */
    public function options($config)
    {
        if (!is_array($config) && !$config instanceof ArrayAccess) {
            throw new Exception\InvalidArgumentException(
                sprintf('Provided parameter $config  must either be of type "array" or implement "\ArrayAccess".')
            );
        }

        if (!isset($config[$this->vendorName()][$this->packageName()][$this->componentName()][$this->elementName()])) {
            throw new Exception\InvalidArgumentException(sprintf(
                'No options set for configuration [\'%s\'][\'%s\'][\'%s\'][\'%s\']',
                $this->vendorName(),
                $this->packageName(),
                $this->componentName(),
                $this->elementName()
            ));
        }

        $options = $config[$this->vendorName()][$this->packageName()][$this->componentName()][$this->elementName()];

        if (!is_array($options) && !$options instanceof ArrayAccess) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Options of configuration for [\'%s\'][\'%s\'][\'%s\'][\'%s\']'
                . ' must either be of type "array" or implement "\ArrayAccess".',
                $this->vendorName(),
                $this->packageName(),
                $this->componentName(),
                $this->elementName()
            ));
        }

        $this->checkMandatoryOptions($this->mandatoryOptions(), $options);
        $options = array_replace_recursive($this->defaultOptions(), $options);

        return $options;
    }

    /**
     * Checks if a mandatory param is missing, supports recursion
     *
     * @param array|ArrayAccess $mandatoryOptions
     * @param array|ArrayAccess $options
     * @throws Exception\InvalidArgumentException
     */
    private function checkMandatoryOptions($mandatoryOptions, $options)
    {
        foreach ($mandatoryOptions as $key => $mandatoryOption) {
            $useRecursion = !is_scalar($mandatoryOption);

            if ($useRecursion && isset($options[$key])) {
                $this->checkMandatoryOptions($mandatoryOption, $options[$key]);
                return;
            }
            if (!$useRecursion && isset($options[$mandatoryOption])) {
                continue;
            }
        }
    }
}
