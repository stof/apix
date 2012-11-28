<?php
/**
 * Copyright (c) 2011 Franck Cassedanne, Ouarz.net
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Zenya nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Ouarz\Console
 * @author      Franck Cassedanne <htttp://franck.cassedanne.com>
 * @copyright   2011 Franck Cassedanne
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://frqnck.gikthub.com/Console
 * @version     @@PACKAGE_VERSION@@
 */

namespace Apix;

class Console
{
    protected $args;

    protected $options = null;

    protected $switches = array(
        'no_colors' => array('--no-colors', '--no-colours'),
        'verbose'   => array('--verbose', '-vv'),
        'verbos3'  => array('--verbos3', '-vvv')
    );

    public $no_colors = false;

    public $verbose = false;
    public $verbos3 = false;

#    private $prompt = "\033[%sm%s\033[0m";
   private $prompt = "\x1b[%sm%s\x1b[0m";

    public function __construct(array $options = null)
    {
        $this->setArgs();
        $this->options = null === $options ? $this->getOptions() : $options;
    }

    public function setArgs(array $args = null)
    {
        $this->args = array_unique(
            null === $args ? $_SERVER['argv'] : $args
        );
        $this->initSwitches();
    }

    public function initSwitches()
    {
        foreach ($this->switches as $key => $values) {
            if ($this->hasArgs($values)) {
                $this->args = array_diff($this->args, $values);
                reset($this->args); // due to a php bug?
                $this->$key = true;
            }
        }
        if($this->verbos3) {
           $this->verbose = true; 
        }
        // check env variables.
        if (false === $this->no_colors) {
            $this->no_colors = exec('tput colors 2> /dev/null') > 2 ? 0 : 1;
        }
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function hasArgs(array $args)
    {
        return array_intersect($args, $this->args);
    }

    public function getOptions()
    {
        if (true === $this->no_colors) {
            return null;
        }
        $ansi = array_merge(
            // foreground colors @ level 0.
            array_combine(
                array('grey', 'red', 'green', 'brown', 'blue', 'purple', 'cyan',
                      'light_grey'),
                range(30, 37)
            ),
            // foreground colors @ level 1.
            array_combine(
                array('dark_gray', 'light_red', 'light_green', 'yellow',
                    'light_blue', 'pink', 'light_cyan', 'white'),
                array_map(function($k){return '1;' . $k;}, range(30, 37))
            ),
            // background colors.
            array_combine(
                array('on_red', 'on_green', 'on_brown', 'on_blue',
                      'on_purple', 'on_cyan', 'on_grey'),
                range(41, 47)
            ),
            // text style attributes (italics & outline not predictable).
            array_combine(
                array('normal', 'bold', 'dark', 'italics', 'underline', 'blink',
                    'outline', 'inverse', 'invisible', 'striked'),
                range(0, 9)
            )
        );

        $ansi['dark_gray'] = 90;
        #print_r($ansi);exit;
        return $ansi;
    }

    public function out($msg, $styles=null)
    {
        $styles = is_array($msg) ? $msg : func_get_args();
        $msg = array_shift($styles);

        echo $this->_out($msg, $styles);
    }

    public function outRegex($msg)
    {
        $pat = '@<(?<name>[^>]+)>(?<value>[^<]+)</\1>@';

        if (true === $this->no_colors) {
            echo preg_replace($pat, '\2', $msg);
        } else {
            preg_match_all($pat, $msg, $tags, PREG_SET_ORDER);
            foreach($tags as $tag) {
                $msg = str_replace(
                    $tag[0],
                    $this->_out($tag['value'], $tag['name']),
                    $msg
                );
                #$help = preg_replace($pat, $this->_out("$2 $1", "$1", 'bold'), $msg);
            }
            echo $msg;
        }
    }

    public function _out($msg, $styles=null)
    {
        if (!is_array($styles)) {
            $styles = is_array($msg) ? $msg : func_get_args();
            $msg = array_shift($styles);
        }

        if (true !== $this->no_colors) {
            foreach ($styles as $style) {
                if (isset($this->options[$style])) {
                    $msg = sprintf($this->prompt, $this->options[$style], $msg);
                }
            }
        }

        return $msg;
    }





        // $obj = new \stdClass;
        // $obj->name = 'name';
        // $obj->params = 'params';
        // $obj->summary = 'summary';
        // $obj->since = 'since';
        // $obj->group = 'group';

        // $this->cliOutputCommandHelp($obj);
    #private $prompt = "\x1b[%sm%s\x1b[0m";
    public function cliOutputCommandHelp($help)
    {
        echo "not connected> help keys\r\n";
        printf("\r\n  \x1b[1m%s\x1b[0m \x1b[90m%s\x1b[0m\r\n", ucfirst($help->name), $help->params);
        printf("  \x1b[33msummary:\x1b[0m %s\r\n", $help->summary);
        printf("  \x1b[33msince:\x1b[0m %s\r\n", $help->since);
        printf("  \x1b[33mgroup:\x1b[0m %s\r\n", $help->group);
        echo "\r\nnot connected> help keys\r\n";
    }

}