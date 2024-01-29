<?php

declare(strict_types=1);

namespace Stsbl\IPv6Bundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use IServ\ComputerRequestBundle\Entity\ComputerRequest;
use IServ\CoreBundle\Event\HomePageEvent;
use IServ\CoreBundle\EventListener\HomePageListenerInterface;
use IServ\CoreBundle\Service\BundleDetector;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Util\Network;
use IServ\Library\Config\Config;
use IServ\Library\Sudo\SudoInterface;
use Stsbl\IPv6Bundle\Util\Network6;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
final class HomePageListener implements HomePageListenerInterface
{
    private EntityManagerInterface $doctrine;

    private ?Request $request = null;

    /**
     * Creates sso link to ipv4.mein-iserv.de
     */
    private function generateSsoLink(): ?string
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $link = $this->sudo->shell_exec('sudo /usr/lib/iserv/ipv6_generate_sso_link');

        // No output => no link available
        if (null === $link) {
            return  null;
        }

        return \trim($link);
    }

    public function __construct(
        private readonly BundleDetector $bundleDetector,
        private readonly Config $config,
        private readonly Network6 $network6,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        private readonly SudoInterface $sudo,
    ) {
        $this->doctrine = $doctrine->getManager();
        $this->request = $requestStack->getCurrentRequest();
    }

    public function onBuildHomePage(HomePageEvent $event): void
    {
        $ip = $this->request?->getClientIp();
        $lan = $this->config->get('LAN');

        // Skip if ip is not in LAN, computer request module is not installed or activation is disabled
        if (null === $ip ||
            !Network::ipInLan($ip, $lan) ||
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

        $ssoLink = $this->generateSsoLink();

        // SSO link could not be generated
        if (null === $ssoLink) {
            return;
        }

        // Check if there is already a pending request
        $computerRequest = $this->doctrine->getRepository(ComputerRequest::class)->findOneBy(['mac' => $mac]);
        $event->addContent(
            'computer-request-ipv6',
            '@StsblIPv6/device_request_homepage.html.twig',
            ['action' => $computerRequest !== null ? 'pending' : 'request', 'link' => $ssoLink],
            -600 // keep in sync with \IServ\ComputerRequestBundle\EventListener::onBuildIDesk()!
        );
    }
}
