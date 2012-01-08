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

class OrmTransactionManager extends AbstractTransactionManager
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function doBeginTransaction(TransactionDefinition $def)
    {
        $manager = $container->get('simple_things_transactional.connections.' . $def->getManagerName());
        $manager->beginTransaction();
        return $this->createTxStatus($manager, $def);
    }

    protected function doCommit(TransactionStatus $def)
    {
        $def->commit();
    }

    protected function doRollBack(TransactionStatus $def)
    {
        $def->rollBack();
    }

    protected function createTxStatus($manager, $def)
    {
        return new OrmTransactionStatus($manager, $def);
    }
}

