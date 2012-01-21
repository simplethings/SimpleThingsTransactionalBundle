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
        $definition = $this->matcher->match($request->getMethod(), $event->getController());
        if (!$definition) {
            return;
        }

        $txManager = $this->registry->getTransaction($definition);
        $request->attributes->set('_transaction', $txManager);
        $request->attributes->set('_transaction_def', $definition);

        if ($txManager && $this->logger) {
            $this->logger->info("[TransactionBundle] Started transaction for " . $definition->getManagerName());
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $txStatus = $request->attributes->get('_transaction');
        if ($txStatus === null) {
            return;
        }
        $txDef = $request->attributes->get('_transaction_def');

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() != 404) {
            $this->rollBack($txStatus, $txDef);
        } else {
            $this->commit($txStatus, $txDef);
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $ex = $event->getException();

        $txStatus = $request->attributes->get('_transaction');
        if ($txStatus === null) {
            return;
        }
        $txDef = $request->attributes->get('_transaction_def');

        if ($ex instanceof NotFoundHttpException) {
            $this->registry->commit($txStatus);
        } else {
            $this->registry->rollBack($txStatus);
        }
    }

    private function commit($txStatus, $txDefinition)
    {
        $this->registry->commit($txStatus);

        if ($this->logger) {
            $this->logger->info("[TransactionBundle] Committed transaction for " . $txDefinition->getManagerName());
        }
    }

    private function rollBack($txStatus, $txDefinition)
    {
        $this->registry->rollBack($txStatus);

        if ($this->logger) {
            $this->logger->info("[TransactionBundle] Aborted transaction for " . $txDefinition->getManagerName());
        }
    }
}

