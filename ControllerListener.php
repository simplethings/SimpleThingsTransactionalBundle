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

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ControllerListener
{
    private $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function onCoreController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if ($request->attributes->has('_tx')) {
            if ($request->attributes->has('_tx_methods')) {
                $methods = (array)$request->attributes->has('_tx_methods');
            } else {
                $methods = array("POST", "PUT", "DELETE", "PATCH");
            }
            if (!in_array($request->getMethod(), $methods)) {
                return;
            }
            
            // TODO: This only handles object+method, not closures or functions
            list($controller, $action) = $event->getController();
            
            $txManagers = array();
            foreach ((array)$request->attributes->get('_tx') AS $txManagerName) {
                $id = "simple_things_transactional.tx.".$txManagerName;
                if (!$this->container->has($id)) {
                    throw new \InvalidArgumentException(
                        "A transactional manager by name of '".$txManagerName."' was requested, but does not exist."
                    );
                }
                $txManagers[] = $this->container->get($id);
            }
            
            $controller = new TransactionalController($controller, $txManagers);
            $event->setController(array($controller, $action));
        }
    }
}