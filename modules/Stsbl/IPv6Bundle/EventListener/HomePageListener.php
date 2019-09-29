<?php
declare(strict_types = 1);

namespace Stsbl\IPv6Bundle\EventListener;

use IServ\ComputerRequestBundle\Entity\ComputerRequest;
use IServ\CoreBundle\Event\HomePageEvent;
use IServ\CoreBundle\EventListener\HomePageListenerInterface;
use IServ\CoreBundle\Service\BundleDetector;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Shell;
use IServ\CoreBundle\Service\Sudo as SudoService;
use IServ\CoreBundle\Util\Sudo;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Util\Network;
use Psr\Container\ContainerInterface;
use Stsbl\IPv6Bundle\Util\Network6;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/*
 * The MIT License
 *
 * Copyright 2019 Felix Jacobi.
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
final class HomePageListener implements HomePageListenerInterface, ServiceSubscriberInterface
{

    /**
     * @var BundleDetector
     */
    private $bundleDetector;

    /**
     * @var ContainerInterface
     */
    private $container;

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
     * Creates sso link to ipv4.mein-iserv.de
     *
     * @return string
     */
    private function generateSsoLink(): string
    {
        $this->container->get(SudoService::class);
        $link = trim(Sudo::shell_exec('sudo /usr/lib/iserv/ipv6_generate_sso_link'));

        return $link;
    }

    public function __construct(
        BundleDetector $bundleDetector,
        Config $config,
        ContainerInterface $container,
        Network6 $network6,
        RegistryInterface $doctrine,
        RequestStack $requestStack,
        Shell $shell
    ) {
        $this->bundleDetector = $bundleDetector;
        $this->config = $config;
        $this->container = $container;
        $this->doctrine = $doctrine->getManager();
        $this->network6 = $network6;
        $this->request = $requestStack->getCurrentRequest();
        $this->shell = $shell;
    }

    public function onBuildHomePage(HomePageEvent $event): void
    {
        $ip = $this->request->getClientIp();
        $lan = $this->config->get('lan');

        // Skip if ip is not in LAN, computer request module is not installed or activation is disabled
        if (!Network::ipInLan($ip, $lan) ||
            !$this->config->get('Activation') ||
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ||
            !$this->bundleDetector->isLoaded('IServComputerRequestBundle')) {
            return;
        }

        // Get the MAC for the ip address
        $mac = $this->network6->queryMac($ip);

        // exit if mac is not available
        if (null === $mac) {
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
        $computerRequest = $this->doctrine->getRepository(ComputerRequest::class)->findOneBy(['mac' => $mac]);
        $event->addContent(
            'computer-request-ipv6',
            '@StsblIPv6/idesk.html.twig',
            ['action' => $computerRequest !== null ? 'pending' : 'request', 'link' => $this->generateSsoLink()],
            -600 // keep in sync with \IServ\ComputerRequestBundle\EventListener::onBuildIDesk()!
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            SudoService::class, // fetch sudo lazily to prevent eager util initialization!
        ];
    }
}
