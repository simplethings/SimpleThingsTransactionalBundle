<?php

/**
 * SimpleThings Transactional
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

use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class TransactionalControllerWrapper
{
    private $controller;
    private $txManagers = array();
    private $def;
    private $logger;
    
    /**
     * @param array $controller
     * @param array $txManagers
     */
    public function __construct($controller, array $txManagers, TransactionDefinition $definition, LoggerInterface $logger = null)
    {
        $this->controller = $controller;
        $this->txManagers = $txManagers;
        $this->def = $definition;
        $this->logger = $logger;
    }
    
    public function getController()
    {
        return $this->controller;
    }
    
    public function __call($method, $args)
    {
        foreach ($this->txManagers AS $txManager) {
            $txManager->beginTransaction();
        }
        if ($this->logger) {
            $this->logger->info("[TransactionBundle] Started transactions for " . implode(", ", array_keys($this->txManagers)));
        }
        
        try {
            $response = call_user_func_array(array($this->controller, $method), $args);
            
            foreach ($this->txManagers AS $txName => $txManager) {
                $txManager->commit();
            }

            if ($this->logger) {
                $this->logger->info("[TransactionBundle] Committed transactions for " . implode(", ", array_keys($this->txManagers)));
            }

            return $response;
        } catch(\Exception $e) {
            foreach ($this->txManagers AS $txName => $txManager) {
                if (!in_array(get_class($e), $this->def->getNoRollbackFor($txName))) {
                    $txManager->rollBack();
                }
            }
            if ($this->logger) {
                $this->logger->info("[TransactionBundle] Aborted transactions for " . implode(", ", array_keys($this->txManagers)));
            }
            throw $e;
        }
    }
}