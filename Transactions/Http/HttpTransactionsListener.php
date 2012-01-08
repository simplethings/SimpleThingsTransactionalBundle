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

namespace SimpleThings\TransactionalBundle\Transactions\Http;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exceptions\NotFoundHttpException;
use SimpleThings\TransactionalBundle\Transactions\TransactionsRegistry;

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
    private $registry;
    private $matcher;
    private $logger;

    public function __construct(TransactionsRegistry $registry, TransactionalMatcher $matcher, LoggerInterface $logger = null)
    {
        $this->registry = $registry;
        $this->matcher = $matcher;
        $this->logger = $logger;
    }

    public function onCoreController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $definitions = $this->matcher->match($request->getMethod(), $event->getController());
        $txManagers = $this->registry->getTransactions($definitions);

        if ($txManagers && $this->logger) {
            $this->logger->info("[TransactionBundle] Started transactions for " . implode(", ", array_keys($txManagers)));
        }

        $request->attributes->set('_transactions', $txManagers);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->attributes->has('_transactions')) {
            return;
        }

        $txManagers = $request->attributes->get('_transactions');
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() != 404) {
            $this->rollBack($txManagers);
        } else {
            $this->commit($txManagers);
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $ex = $event->getException();

        if (!$request->attributes->has('_transactions')) {
            return;
        }

        $txManagers = $request->attributes->get('_transactions');

        if ($ex instanceof NotFoundHttpException) {
            $this->registry->commit($txManagers);
        } else {
            $this->registry->rollBack($txManagers);
        }
    }

    private function commit($txManagers)
    {
        $this->registry->commit($txManagers);

        if ($txManagers && $this->logger) {
            $this->logger->info("[TransactionBundle] Committed transactions for " . implode(", ", array_keys($txManagers)));
        }
    }

    private function rollBack($txManagers)
    {
        $this->registry->rollBack($txManagers);

        if ($txManagers && $this->logger) {
            $this->logger->info("[TransactionBundle] Aborted transactions for " . implode(", ", array_keys($txManagers)));
        }
    }
}

