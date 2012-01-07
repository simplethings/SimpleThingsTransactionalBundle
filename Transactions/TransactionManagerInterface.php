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

/**
 * Wraps a transactional service into a common interface
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
interface TransactionManagerInterface
{
    /**
     * Get a transaction status object.
     *
     * 1. Returns a new transaction if none was opened with this manager yet.
     * 2. Returns a previous transaction if the propagation is REQUIRED.
     * 3. Returns a new transaction if the propagation is REQUIRES_NEW.
     *
     * @return TransactionDefinition
     */
    function getTransaction(TransactionDefinition $def);

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
    function commit(TransactionStatus $status);

    /**
     * Rollback the transaction inside the status object.
     *
     * @param TransactionStatus $status
     * @return void
     */
    function rollBack(TransactionStatus $status);
}
