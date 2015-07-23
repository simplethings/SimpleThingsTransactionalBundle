<?php

/**
 * SimpleThings TransactionalBundle
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace SimpleThings\TransactionalBundle\Controller;

use Symfony\Component\HttpKernel\Controller\TraceableControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpFoundation\Request;

class TraceableControllerResolver extends BaseControllerResolver
{
    public function getArguments(Request $request, $controller)
    {
        if (is_array($controller) && $controller[0] instanceof TransactionalControllerWrapper) {
            $controller[0] = $controller[0]->getController();
        }

        return parent::getArguments($request, $controller);
    }
}
