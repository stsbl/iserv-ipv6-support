<?php
// src/Stsbl/IPv6Bundle/EventListener/IDeskListener.php
namespace Stsbl\IPv6Bundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use IServ\CoreBundle\Event\IDeskEvent;
use IServ\CoreBundle\EventListener\IDeskListenerInterface;
use IServ\CoreBundle\Service\Config;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Util\Network;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

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
 * @license MIT License <https://opensource.org/licenses/MIT>
 */
class IDeskListener implements ContainerAwareInterface, IDeskListenerInterface
{
    use ContainerAwareTrait;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $doctrine;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @var Config
     */
    private $config;

    /**
     * Checks if bundle is installed
     *
     * @param $name
     * @return bool
     */
    private function hasBundle($name)
    {
        return array_key_exists($name, $this->container->getParameter('kernel.bundles'));
    }

    /**
     * @param Doctrine $doctrine
     * @param Config $config
     * @param RequestStack $requestStack
     */
    public function __construct(Doctrine $doctrine, Config $config, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine->getManager();
        $this->request = $requestStack->getCurrentRequest();
        $this->config = $config;
    }

    /**
     * @param \IServ\CoreBundle\Event\IDeskEvent $event
     */
    public function onBuildIDesk(IDeskEvent $event)
    {
        $ip = $this->request->getClientIp();
        $lan = $this->config->get('lan');

        // Skip if ip is not in LAN, computer request module is not installed or activation is disabled
        if (!(Network::ipInLan($ip, $lan)) || !($this->config->get('activation')) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || !($this->hasBundle('IServComputerRequestBundle'))) {
            return;
        }

        // Get the MAC for the ip address
        $mac = Network::query_mac($ip);

        // Check if computer is already registered
        /* @var $host Host */
        $host = $this->doctrine->getRepository('IServHostBundle:Host')->findOneBy(array('mac' => $mac));

        // Nothing else to do if computer is already registered
        if ($host !== null) {
            return;
        }

        // Check if there is already a pending request
        $computerRequest = $this->doctrine->getRepository('IServComputerRequestBundle:ComputerRequest')->findOneBy(['mac' => $mac]);
        $event->addContent(
            'computer-request',
            'IServComputerRequestBundle::idesk.html.twig',
            array('action' => $computerRequest !== null ? 'pending' : 'request'),
            -10
        );
    }
}
