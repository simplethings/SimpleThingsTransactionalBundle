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

class OrmTransactionStatus implements TransactionStatus
{
    private $def;
    private $manager;
    private $completed = false;

    public function __construct(EntityManager $manager, TransactionDefinition $def)
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
        return $this->def->getReadOnly();
    }

    /**
     * Check if transaction is broken and has to be rolled back at this point.
     *
     * @return bool
     */
    public function isRollBackOnly()
    {
        return $this->manager->getConnection()->isRollbackOnly();
    }

    /**
     * Mark the transaction as rollback-only
     *
     * @return void
     */
    public function setRollBackOnly()
    {
        $this->manager->getConnection()->setRollbackOnly(true);
    }

    /**
     * Check if this transaction was committed already.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->completed;
    }

    /**
     * Check if this transaction has savepoints.
     *
     * @return bool
     */
    public function hasSavepoint()
    {
        return false;
    }

    /**
     * Commit the transaction at this point.
     *
     * @return void
     */
    public function commit()
    {
        $this>completed = true;
        $this->manager->flush();
        $this->manager->commit();
    }

    /**
     * Rollback the transaction at this point marking it as complete.
     *
     * @return void
     */
    public function rollBack()
    {
        $this->completed = true;
        $this->manager->rollBack();
        $this->manager->clear();
    }
}

