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

namespace SimpleThings\TransactionalBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

class SimpleThingsTransactionalExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $builder
     */
    public function load(array $configs, ContainerBuilder $builder)
    {
        $loader = new XmlFileLoader($builder, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $config = array();
        foreach ($configs AS $c) {
            $config = array_merge($config, $c);
        }

        if (isset($config['auto_transactional']) && $config['auto_transactional'] && isset($config['patterns']) && count($config['patterns'])) {
            throw new \InvalidArgumentException("Cannot activate auto_transactional and set patterns at the same time.");
        }

        if (!isset($config['defaults'])) {
            $config['defaults'] = array();
        }
        $config['defaults'] = array_merge(array(
                'conn' => array(),
                'pattern' => '.*',
                'noRollbackFor' => array(),
                'methods' => array('POST', 'PUT', 'DELETE', 'PATCH'),
            ), $config['defaults']);

        if (isset($config['auto_transactional']) && $config['auto_transactional']) {
            $patterns = array( $config['defaults'] );
        } else {
            $patterns = array();
            if (isset($config['patterns'])) {
                foreach ($config['patterns'] AS $pattern) {
                    $patterns[] = array_merge($config['defaults'], $pattern);
                }
            }
        }

        if (isset($config['annotations']) && $config['annotations'] == true) {
            $args = array($patterns, $config['defaults'], new Reference('annotation_reader'));
        } else {
            $args = array($patterns);
        }

        $def = $builder->getDefinition('simple_things_transactional.transactional_matcher');
        $def->setArguments($args);

    }
}
