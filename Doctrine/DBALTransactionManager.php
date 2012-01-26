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

use SimpleThings\TransactionalBundle\Transactions\TransactionStatus;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;
use SimpleThings\TransactionalBundle\Transactions\TransactionManagerInterface;

/**
 * Doctrine Object TransactionManager for any Doctrine ObjectManager.
 *
 * The commit operation of this manager directly translates to the flush
 * operation of the Object Manager, synchronizing any pending changes to the
 * database. Creating a new transaction when one already exists translates to
 * resetting the service and reconstituting the "previous" manager when the
 * transaction is committed or rolled back.
 */
class DBALTransactionManager implements TransactionManagerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Get a transaction status object.
     *
     * @return TransactionDefinition
     */
    public function getTransaction(TransactionDefinition $def)
    {
        $conn = $this->container->get('simple_things_transactional.connections.' . $def->getConnectionName());
        $conn->beginTransaction();
        return new DBALTransactionStatus($conn, $def);
    }

    /**
     * Commit the transaction inside the status object.
     *
     * Depending on the Transaction#isRollBackOnly status this method commits
     * or rollbacks the transaction wrapped inside the status. If an error
     * happens during commit the original exception of the underlying
     * connection is thrown from this method.
     *
     * @throws Exception
     * @param TransactionStatus $status
     * @return void
     */
    public function commit(TransactionStatus $status)
    {
        $conn = $status->getWrappedConnection();
        $conn->commit();
        if ($conn->getTransactionNestingLevel() == 0) {
            $status->markCompleted();
        }
    }

    /**
     * Rollback the transaction inside the status object.
     *
     * @param TransactionStatus $status
     * @return void
     */
    public function rollBack(TransactionStatus $status)
    {
        $conn = $status->getWrappedConnection();
        $conn->rollback();
        if ($conn->getTransactionNestingLevel() == 0) {
            $status->markCompleted();
        }
    }
}

