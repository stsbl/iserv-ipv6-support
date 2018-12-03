<?php declare(strict_types = 1);

namespace Stsbl\IPv6Bundle\Controller;

use IServ\CoreBundle\Controller\AbstractPageController;
use IServ\CoreBundle\Service\Config;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends AbstractPageController
{
    /**
     * Action to redirect mdm request to IPv4 host
     */
    public function redirectMdmAction(Request $request, Config $config): RedirectResponse
    {
        $newUrl = sprintf('https://ipv4.%s/iserv%s', $config->get('Domain'), $request->getPathInfo());

        return new RedirectResponse($newUrl, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
