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
use SimpleThings\TransactionalBundle\Transactions\TransactionProviderInterface;

/**
 * Doctrine Object TransactionManager for any Doctrine ObjectManager.
 *
 * The commit operation of this manager directly translates to the flush
 * operation of the Object Manager, synchronizing any pending changes to the
 * database. Creating a new transaction when one already exists translates to
 * resetting the service and reconstituting the "previous" manager when the
 * transaction is committed or rolled back.
 */
class DBALTransactionProvider implements TransactionProviderInterface
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
     * @param TransactionDefinition $def
     * @return TransactionStatus
     */
    public function createTransaction(TransactionDefinition $def)
    {
        $conn = $this->container->get('doctrine.' . $def->getConnectionName().'_connection');
        return new DBALTransactionStatus($conn, $def);
    }
}

