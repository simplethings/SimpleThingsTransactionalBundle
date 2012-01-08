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

namespace SimpleThings\TransactionalBundle\Transactions;

use Symfony\Component\DependencyInjection\ContainerInterface;
use SimpleThings\TransactionalBundle\TransactionException;

/**
 * Registry for mass-operations on transactions.
 *
 * In the default use-case there is always only one exception in a HTTP
 * request, however many are supported by this facade. The Transactions passed
 * here should always be in the same request, not from different requests.
 */
class TransactionsRegistry
{
    private $container;
    private $connectionServices;

    public function __construct(ContainerInterface $container, $connectionServices = array())
    {
        $this->container = $container;
        $this->connectionServices = $connectionServices;
    }

    public function getTransactions(array $definitions)
    {
        $txManagers = array();
        $requiresNew = false;
        foreach ($definitions as $def) {
            if ($def->getPropagation() == TransactionDefinition::PROPAGATION_REQUIRES_NEW) {
                if ($requiresNew) {
                    throw new TransactionException("Cannot have more than one connection to require a new transaction per request.");
                }
                $requiresNew = true;
            }

            $managerName = $def->getManagerName();
            if ($txStatus = $this->getTransactionManager($managerName)->getTransaction($def)) {
                $txManagers[$managerName] = $txStatus;
            }
        }
        return $txManagers;
    }

    public function commit(array $statuses)
    {
        foreach (array_reverse($txManagers) AS $managerName => $txStatus) {
            $this->getTransactionManager($managerName)->commit($txStatus);
        }
    }

    public function rollBack(array $statuses)
    {
        foreach (array_reverse($txManagers) AS $managerName => $txStatus) {
            $this->getTransactionManager($managerName)->rollBack($txStatus);
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
}

