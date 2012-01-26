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
interface TransactionProviderInterface
{
    /**
     * Get a transaction status object.
     *
     * Re-use an existing transaction status if there is already one for the
     * currently active transaction. Throw an exception if the read-only status
     * is not equal for the previously defined transaction.
     *
     * @param TransactionDefinition $def
     * @return TransactionStatus
     */
    function createTransaction(TransactionDefinition $def);
}
