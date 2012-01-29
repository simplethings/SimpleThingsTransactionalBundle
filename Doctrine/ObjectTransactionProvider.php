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
 * Doctrine ObjectManager TransactionProvider
 */
class ObjectTransactionProvider implements TransactionProviderInterface
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function createTransaction(TransactionDefinition $def)
    {
        $conn = $this->container->get('doctrine.' . $def->getConnectionName() . '_manager');
        return new ObjectTransactionStatus($conn, $def);
    }
}

