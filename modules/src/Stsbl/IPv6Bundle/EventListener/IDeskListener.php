<?php
// src/Stsbl/IPv6Bundle/EventListener/IDeskListener.php
namespace Stsbl\IPv6Bundle\EventListener;

use IServ\CoreBundle\Event\IDeskEvent;
use IServ\CoreBundle\EventListener\IDeskListenerInterface;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Shell;
use IServ\CoreBundle\Util\Sudo;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Util\Network;
use Stsbl\IPv6Bundle\Util\Network6;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
     * @var Container
     */
    protected $container;

    /**
     * @var RegistryInterface
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
     * @var Network6
     */
    private $network6;

    /**
     * @var Shell
     */
    private $shell;

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
     * Creates sso link to ipv4.mein-iserv.de
     *
     * @return string
     * @throws \Exception
     */
    public function generateSsoLink()
    {
        // FIXME service locator?
        $this->container->get('iserv.sudo');
        $link = trim(Sudo::shell_exec('sudo /usr/lib/iserv/ipv6_generate_sso_link'));

        return $link;
    }

    /**
     * @param RegistryInterface $doctrine
     * @param Config $config
     * @param RequestStack $requestStack
     * @param Network6 $network6
     * @param Shell $shell
     */
    public function __construct(RegistryInterface $doctrine, Config $config, RequestStack $requestStack, Network6 $network6, Shell $shell)
    {
        $this->doctrine = $doctrine->getManager();
        $this->request = $requestStack->getCurrentRequest();
        $this->config = $config;
        $this->network6 = $network6;
        $this->shell = $shell;
    }

    /**
     * @param \IServ\CoreBundle\Event\IDeskEvent $event
     * @throws \Exception
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
        $mac = $this->network6->queryMac($ip);

        // exit if mac is not available
        if (!$mac) {
            return;
        }

        // Check if computer is already registered
        /* @var $host Host */
        $host = $this->doctrine->getRepository(Host::class)->findOneBy(['mac' => $mac]);

        // Nothing else to do if computer is already registered
        if ($host !== null) {
            return;
        }

        // Check if there is already a pending request
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        $computerRequest = $this->doctrine->getRepository(\IServ\ComputerRequestBundle\Entity\ComputerRequest::class)->findOneBy(['mac' => $mac]);
        $event->addContent(
            'computer-request',
            'StsblIPv6Bundle::idesk.html.twig',
            ['action' => $computerRequest !== null ? 'pending' : 'request', 'link' => $this->generateSsoLink()],
            -10
        );
    }
}