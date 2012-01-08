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

namespace SimpleThings\TransactionalBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Scope changes the scope of ALL container scoped services from
 * 'container' to 'transactional' if they depend on any transactional
 * connection resource.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class TransactionalScopePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $connectionServices = $container->getParameter('simple_things_transactional.connection_serices');
        $graph = $container->getCompiler()->getServiceReferenceGraph();

        $visited = array();
        foreach ($connectionServices as $connectionServiceId) {
            $this->changeScopeTransactional($connectionServiceId, $visited);
        }
    }

    private function changeScopeTransactional($serviceId, $visited)
    {
        if (isset($visited[$serviceId])) {
            return;
        }
        $visited[$serviceId] = true;

        $node = $graph->getNode($serviceId);
        $def = $container->getDefinition($serviceId);

        if ($def->getScope() == ContainerInterface::SCOPE_CONTAINER) {
            $def->setScope('transactional');
        }

        foreach ($node->getOutNodes() as $outNode) {
            $this->changeScopeTransactional($outNode->getId(), $visited);
        }
    }
}

