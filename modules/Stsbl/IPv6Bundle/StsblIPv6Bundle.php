<?php declare(strict_types = 1);

namespace Stsbl\IPv6Bundle;

use IServ\CoreBundle\Routing\AutoloadRoutingBundleInterface;
use Stsbl\IPv6Bundle\DependencyInjection\StsblIPv6Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT License <https://opensource.org/licenses/MIT>
 */
class StsblIPv6Bundle extends Bundle implements AutoloadRoutingBundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new StsblIPv6Extension();
    }
}
