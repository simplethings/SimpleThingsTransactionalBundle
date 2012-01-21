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
class TransactionsRegistry implements TransactionManagerInterface
{
    private $container;
    private $connectionServices;
    private $transactions;

    public function __construct(ContainerInterface $container, $connectionServices = array())
    {
        $this->container = $container;
        $this->connectionServices = $connectionServices;
    }

    public function getTransaction(TransactionDefinition $definition)
    {
        $managerName = $definition->getManagerName();
        $status = $this->getTransactionManager($managerName)->getTransaction($definition);
        $this->transactions[spl_object_hash($status)] = $managerName;
        return $status;
    }

    public function commit(TransactionStatus $status)
    {
        $managerName = $this->transactions[spl_object_hash($status)];
        $this->getTransactionManager($managerName)->commit($status);
    }

    public function rollBack(TransactionStatus $status)
    {
        $managerName = $this->transactions[spl_object_hash($status)];
        $this->getTransactionManager($managerName)->rollBack($status);
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

