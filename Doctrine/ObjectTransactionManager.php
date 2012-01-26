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

namespace SimpleThings\TransactionalBundle\Doctrine;

use SimpleThings\TransactionalBundle\TransactionsRegistry;

/**
 * Doctrine Object TransactionManager for any Doctrine ObjectManager.
 *
 * The commit operation of this manager directly translates to the flush
 * operation of the Object Manager, synchronizing any pending changes to the
 * database. Creating a new transaction when one already exists translates to
 * resetting the service and reconstituting the "previous" manager when the
 * transaction is committed or rolled back.
 */
class ObjectTransactionManager extends AbstractTransactionManager
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function createTxStatus($manager, $def)
    {
        return new ObjectTransactionStatus($manager, $def);
    }

    protected function doBeginTransaction(TransactionDefinition $def)
    {
        $manager = $this->container->get('simple_things_transactional.connections.' . $def->getConnectionName());
        return $this->createTxStatus($manager, $def);
    }

    protected function doCommit(TransactionStatus $status)
    {
        $manager = $status->getWrappedConnection();
        $manager->flush();
        $status->markCompleted();
    }

    protected function doRollBack(TransactionStatus $status)
    {
        $status->markCompleted();
    }
}

