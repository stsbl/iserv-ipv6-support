<?php
// src/Stsbl/IPv6Bundle/Network6.php
namespace Stsbl\IPv6Bundle\Util;

use IServ\CoreBundle\Service\Shell;
use IServ\HostBundle\Service\HostManager;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class Network6
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var HostManager
     */
    private $hostManager;

    /**
     * The constructor.
     *
     * @param Shell $shell
     * @param HostManager $hostManager
     */
    public function __construct(Shell $shell, HostManager $hostManager)
    {
        $this->shell = $shell;
        $this->hostManager = $hostManager;
    }

    /**
     * Query mac address for ipv6 address via IPv6 NDP table
     *
     * @param $ip
     * @return bool
     * @throws \IServ\CoreBundle\Exception\ShellExecException
     */
    public function queryMac($ip)
    {
        $this->shell->exec('sudo', ['/usr/lib/iserv/ipv6_neigh_discovery', $ip]);

        foreach ($this->shell->getOutput() as $output) {
            list(, , , , $mac) = explode(' ', $output);

            if (isset($mac) && $this->hostManager->isMac($mac)) {
                return $mac;
            }
        }

        return false;
    }
}