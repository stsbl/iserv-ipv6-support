<?php
// src/Stsbl/IPv6Bundle/EventListener/IDeskListener.php
namespace IServ\IPv6Bundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use IServ\CoreBundle\Event\IDeskEvent;
use IServ\CoreBundle\EventListener\IDeskListenerInterface;
use IServ\CoreBundle\Service\Config;
use IServ\HostBundle\Entity\Host;
use IServ\HostBundle\Util\Network;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

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
