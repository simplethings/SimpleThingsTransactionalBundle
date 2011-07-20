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

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SimpleThings\TransactionalBundle\Controller\TransactionalControllerWrapper;
use SimpleThings\TransactionalBundle\Transactions\TransactionalMatcher;

class ControllerListener
{
    private $container;
    private $matcher;
    
    public function __construct(ContainerInterface $container, TransactionalMatcher $matcher)
    {
        $this->container = $container;
        $this->matcher = $matcher;
    }
    
    public function onCoreController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $def = $this->matcher->match($request, $event->getController());

        if ($def) {

            list($controller, $action) = $event->getController();

            $txManagers = array();
            foreach ($def->getConnections() AS $txConnName) {
                if (($def->isInvokedOnSubrequest($txConnName) === true || $event->getRequestType() == HttpKernelInterface::SUB_REQUEST)) {
                    $id = "simple_things_transactional.tx.".$txConnName;
                    if (!$this->container->has($id)) {
                        throw new \InvalidArgumentException(
                            "A transactional manager by name of '".$txConnName."' was requested, but does not exist."
                        );
                    }
                    $txManagers[$txConnName] = $this->container->get($id);
                }
            }

            $controller = new TransactionalControllerWrapper($controller, $txManagers, $def, $this->container->get('logger'));
            $event->setController(array($controller, $action));
        }

    }
}