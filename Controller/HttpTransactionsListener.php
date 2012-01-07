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
use Symfony\Component\HttpKernel\Log\LoggerInterface;

/**
 * Transactional controller listener.
 *
 * Checks if the request should run in transactional scope during the
 * onCoreController event. Transactions are opened for every manager matches
 * the criteria configured.
 *
 * Depending on the success or failure of the request the open transactions are
 * either rolled back or committed.
 */
class HttpTransactionsListener
{
    private $container;
    private $matcher;
    private $logger;

    public function __construct(ContainerInterface $container, TransactionalMatcher $matcher, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->matcher = $matcher;
        $this->logger = $logger;
    }

    public function onCoreController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $definitions = $this->matcher->match($request->getMethod(), $event->getController());

        if ($definitions) {
            $txManagers = array();
            foreach ($definitions as $def) {
                $managerName = $def->getManagerName();
                if ($txStatus = $this->getTransactionManager($managerName)->getTransaction($def)) {
                    $txManagers[$managerName] = $txStatus;
                }
            }

            if ($txManagers && $this->logger) {
                $this->logger->info("[TransactionBundle] Started transactions for " . implode(", ", array_keys($txManagers)));
            }

            $request->attributes->set('_transactions', $txManagers);
        }
    }

    private function getTransactionManager($name)
    {
        $id = "simple_things_transactional.tx.".$name;
        if (!$this->container->has($id)) {
            throw new \InvalidArgumentException(
                "A transactional manager by name of '".$name."' was requested, but does not exist."
            );
        }
        return $this->container->get($id);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->attributes->has('_transactions')) {
            return;
        }

        $txManagers = $request->attributes->get('_transactions');
        if ($response->getStatusCode() >= 500) {
            $this->rollBack($txManagers);
        } else {
            $this->commit($txManagers);
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_transactions')) {
            return;
        }

        $txManagers = $request->attributes->get('_transactions');
        $this->rollBack($txManagers);
    }

    private function commit($txManagers)
    {
        foreach ($txManagers AS $managerName => $txStatus) {
            $this->getTransaction($managerName)->commit($txStatus);
        }

        if ($this->logger) {
            $this->logger->info("[TransactionBundle] Committed transactions for " . implode(", ", array_keys($txManagers)));
        }
    }

    private function rollBack($txManagers)
    {
        foreach ($txManagers AS $managerName => $txStatus) {
            $this->getTransaction($managerName)->rollBack($txStatus);
        }

        if ($this->logger) {
            $this->logger->info("[TransactionBundle] Aborted transactions for " . implode(", ", array_keys($txManagers)));
        }
    }
}
