<?php
// src/Stsbl/IPv6Bundle/Controller/RedirectController.php
namespace Stsbl\IPv6Bundle\Controller;

use IServ\CoreBundle\Controller\PageController;
use IServ\CoreBundle\Service\Config;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class RedirectController extends PageController
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Action to redirect mdm request to IPv4 host
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirectMdmAction(Request $request)
    {
        $newUrl = sprintf('https://ipv4.%s/iserv%s', $this->getConfig()->get('Domain'), $request->getPathInfo());

        return new RedirectResponse($newUrl, 308);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     * @required
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }
}
