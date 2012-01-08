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

namespace SimpleThings\TransactionalBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Scope;
use SimpleThings\TransactionalBundle\DependencyInjection\CompilerPass\TransactionalScopePass;
use SimpleThings\TransactionalBundle\DependencyInjection\CompilerPass\DetectConnectionsPass;

class SimpleThingsTransactionalBundle extends Bundle
{
    public function boot()
    {
        $this->getContainer()->enterScope('transactional');
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addScope(new Scope('transactional'));
        $container->addCompilerPass(new TransactionalScopePass());
        $container->addCompilerPass(new DetectConnectionsPass());
    }

    public function shutdown()
    {
        $this->getContainer()->leaveScope('transactional');
    }
}
