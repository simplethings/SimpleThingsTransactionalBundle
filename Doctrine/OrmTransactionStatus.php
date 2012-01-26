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

namespace SimpleThingsTransactionalBundle\Doctrine;

use Doctrine\ORM\EntityManager;

class OrmTransactionStatus implements TransactionStatus
{
    private $def;
    private $manager;
    private $completed = false;

    public function __construct(EntityManager $conn, TransactionDefinition $def)
    {
        $this->manager = $manager;
        $this->def = $def;
    }

    /**
     * Checks if the transaction is read-only.
     *
     * A read-only transaction does not commit changes to the database when
     * commit is called. It allows the underlying transaction manager to
     * perform optimizations to this regard if possible.
     *
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->def->isReadOnly();
    }

    /**
     * Check if transaction is broken and has to be rolled back at this point.
     *
     * @return bool
     */
    public function isRollBackOnly()
    {
        return $this->manager->getConnection()->isRollBackOnly();
    }

    /**
     * Mark the transaction as rollback-only
     *
     * @return void
     */
    public function setRollBackOnly()
    {
        return $this->manager->getConnection()->setRollBackOnly(True);
    }

    /**
     * Check if this transaction was committed already.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->isCompleted;
    }

    /**
     * Return the connection object that is wrapped in this status.
     *
     * @return object
     */
    public function getWrappedConnection()
    {
        return $this->manager;
    }

    /**
     * Begin the transaction
     */
    public function beginTransaction()
    {
        $this->manager->beginTransaction();
    }

    /**
     * Commit the transaction
     *
     * Depending on the Transaction#isRollBackOnly status this method commits
     * or rollbacks the transaction wrapped inside the status. If an error
     * happens during commit the original exception of the underlying
     * connection is thrown from this method.
     *
     * @throws Exception
     * @return void
     */
    public function commit()
    {
        if ($this->isReadOnly()) {
            return $this->rollBack();
        }

        if ( ! $this->isRollBackOnly() && $this->manager->getConnection()->getTransactionNestingLevel() == 1) {
            $this->manager->flush();
        }
        $this->manager->commit();

        if ($this->manager->getConnection()->getTransactionNestingLevel() == 0) {
            $this->completed = true;
        }
    }

    /**
     * Rollback the transaction inside the status object.
     *
     * @return void
     */
    public function rollBack()
    {
        $this->manager->rollBack();
        if ($this->manager->getConnection()->getTransactionNestingLevel() == 0) {
            $this->completed = true;
        }
    }
}

