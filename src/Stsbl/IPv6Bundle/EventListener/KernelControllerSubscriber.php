<?php
// src/Stsbl/IPv6Bundle/EventListener/KernelControllerSubscriber.php
namespace Stsbl\IPv6Bundle\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

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
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class KernelControllerSubscriber implements ContainerAwareInterface, EventSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * @var ControllerResolver
     */
    private $resolver;

    /**
     * @var RouterInterface;
     */
    private $router;

    /**
     * The constructor.
     *
     * @param ControllerResolver $resolver
     * @param RouterInterface $router
     */
    public function __construct(ControllerResolver $resolver, RouterInterface $router)
    {
        $this->resolver = $resolver;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    /**
     * Redirects MDM API request to IPv4
     *
     * @param FilterControllerEvent $event
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // do nothing if MDM is not installed
        if (!array_key_exists('IServMdmBundle', $this->container->getParameter('kernel.bundles'))) {
            return;
        }

        $originalRequest = $event->getRequest();
        $pathInfo = $originalRequest->getPathInfo();

        // do nothing if we are on IPv4
        if (false !== filter_var($originalRequest->getClientIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return;
        }

        // prefilter requests by pathinfo to improve speed:
        // 1100ms => 616ms
        if (!preg_match('#^/public/mdm#', $pathInfo)) {
            return;
        }

        $context = new RequestContext();
        $context->fromRequest($originalRequest);
        $matcher = new UrlMatcher($this->router->getRouteCollection(), $context);

        try {
            $originalRequest->attributes->add($matcher->match($pathInfo));
            list($controller, $action) = $this->resolver->getController($originalRequest);
        } catch (ResourceNotFoundException $e) {
            // skip
            return;
        }


        $route = null;

        if (null !== $controller || null !== $action) {
            $reflectionMethod = new \ReflectionMethod($controller, $action);
            $annotationReader = new AnnotationReader();
            $annotations = $annotationReader->getMethodAnnotations($reflectionMethod);
            /* @var $annotation Route */
            list($annotation) = array_filter($annotations, function ($annotation) {
                return $annotation instanceof Route;
            });
            if (!isset($annotation)) {
                // do not handle actions without annotation
                return;
            }

            $route = $annotation->getName();
        } else {
            // skip unresolvable
            return;
        }

        // do not handle non public mdm routes
        if ($route !== 'mdm_ios_dep_enroll' && $route !== 'mdm_ios_api') {
            return;
        }

        // duplicate original request
        $request = $originalRequest->duplicate(null, null, ['_controller' => 'StsblIPv6Bundle:Redirect:redirectMdm']);
        $controller = $this->resolver->getController($request);

        // skip unresolvable controller
        if (!$controller) {
            return;
        }

        $event->setController($controller);
    }
}