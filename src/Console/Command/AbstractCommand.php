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

namespace Humus\Amqp\Console\Command;

use Interop\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Traversable;

/**
 * Class AbstractCommand
 * @package Humus\Amqp\Console\Command
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * @return array
     */
    public function getHumusAmqpConfig() : array
    {
        $config = $this->getContainer()->get('config');
        
        if ($config instanceof Traversable) {
            $config = iterator_to_array($config);
        }
        
        return $config['humus']['amqp'] ?? [];
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer() : ContainerInterface
    {
        if (null === $this->container) {
            $this->container = $this->getHelper('container')->getContainer();
        }

        return $this->container; 
    }

    /**
     * @param array|Traversable $var
     */
    public function dump($var)
    {
        $html = ini_get('html_errors');

        if ($html !== true) {
            ini_set('html_errors', true);
        }

        if (extension_loaded('xdebug')) {
            ini_set('xdebug.var_display_max_depth', 2);
        }

        $var = $this->export($var);

        ob_start();
        var_dump($var);

        $dump = ob_get_contents();

        ob_end_clean();

        $dumpText = strip_tags(html_entity_decode($dump));

        ini_set('html_errors', $html);

        echo $dumpText;
    }

    /**
     * @param array|Traversable $var
     * @return array
     */
    public function export($var) : array
    {
        if ($var instanceof Traversable) {
            $var = iterator_to_array($var);
        }

        if (is_array($var)) {
            $return = [];

            foreach ($var as $k => $v) {
                $return[$k] = $this->export($v);
            }    
        } else {
            $return = $var;
        }
        

        return $return;
    }
}
