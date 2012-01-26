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

class OrmTransactionManager extends ObjectTransactionManager
{
    protected function doBeginTransaction(TransactionDefinition $def)
    {
        $manager = $this->container->get('simple_things_transactional.connections.' . $def->getConnectionName());
        $manager->beginTransaction();
        return $this->createTxStatus($manager, $def);
    }

    protected function doCommit(TransactionStatus $status)
    {
        $manager = $status->getWrappedConnection();
        $manager->flush();
        $manager->commit();

        $status->markCompleted();
    }

    protected function doRollBack(TransactionStatus $status)
    {
        $manager = $status->getWrappedConnection();
        $manager->rollBack();

        $status->markCompleted();
    }
}

