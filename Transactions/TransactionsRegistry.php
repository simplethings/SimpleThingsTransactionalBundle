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
    private $transactions;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getTransaction(TransactionDefinition $definition)
    {
        $connectionName = $definition->getConnectionName();
        if ( ! isset($this->transactions[$connectionName] )) {
            $status = $this->getTransactionProvider($connectionName)->createTransaction($definition);
            $this->transactions[$connectionName] = $status;
        }

        if ($definition->isReadOnly() !== $this->transactions[$connectionName]->isReadOnly()) {
            throw new \RuntimeException("Cannot switch from read-only to write/read-transaction or vice-versa.");
        }

        return $this->transactions[$connectionName];
    }

    public function commit(TransactionStatus $status)
    {
        $status->commit();
    }

    public function rollBack(TransactionStatus $status)
    {
        $status->rollBack();
    }

    private function getTransactionProvider($name)
    {
        $id = "simple_things_transactional.tx.".$name;
        if (!$this->container->has($id)) {
            throw new \InvalidArgumentException(
                "A transactional connection by name of '".$name."' was requested, but does not exist."
            );
        }
        return $this->container->get($id);
    }
}

