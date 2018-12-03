<?php declare(strict_types = 1);

namespace Stsbl\IPv6Bundle\EventListener;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use IServ\CoreBundle\Service\BundleDetector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Annotation\Route;
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
class KernelControllerSubscriber implements EventSubscriberInterface
{
    /**
     * @var ControllerResolverInterface
     */
    private $resolver;

    /**
     * @var RouterInterface;
     */
    private $router;

    /**
     * @var BundleDetector
     */
    private $bundleDetector;

    public function __construct(BundleDetector $bundleDetector, ControllerResolverInterface $resolver, RouterInterface $router)
    {
        $this->bundleDetector = $bundleDetector;
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
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        // do nothing if MDM is not installed
        if (!$this->bundleDetector->isLoaded('IServMdmBundle')) {
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
            try {
                $reflectionMethod = new \ReflectionMethod($controller, $action);
            } catch (\ReflectionException $e) {
                throw new \RuntimeException('Failed to reflect controller action!', 0, $e);
            }

            try {
                $annotationReader = new AnnotationReader();
            } catch (AnnotationException $e) {
                throw new \RuntimeException('Failed to create annotation reader!', 0, $e);
            }

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