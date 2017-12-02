<?php

namespace Stsbl\IPv6Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('StsblIPv6Bundle:Default:index.html.twig');
    }
}
