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

namespace SimpleThings\TransactionalBundle;

use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpFoundation\Request;

class ControllerResolver extends BaseControllerResolver
{
    public function getArguments(Request $request, $controller)
    {
        if (is_array($controller) && $controller[0] instanceof TransactionalController) {
            $controller[0] = $controller[0]->getController();
        }
        return parent::getArguments($request, $controller);
    }
}