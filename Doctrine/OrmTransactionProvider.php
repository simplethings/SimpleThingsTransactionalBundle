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

use SimpleThings\TransactionalBundle\Transactions\TransactionProviderInterface;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

class OrmTransactionProvider implements TransactionProviderInterface
{
    public function createTransaction(TransactionDefinition $def)
    {
        $manager = $this->container->get('doctrine.' . $def->getConnectionName().'_entity_manager');
        return new OrmTransactionStatus($manager, $def);
    }
}
