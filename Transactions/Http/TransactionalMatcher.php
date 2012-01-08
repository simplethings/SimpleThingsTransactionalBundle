<?php

/**
 * SimpleThings Transactional
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace SimpleThings\TransactionalBundle\Transactions\Http;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Annotations\Reader;
use SimpleThings\TransactionalBundle\TransactionException;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

/**
 * TransactionalMatcher finds the transaction definitions for matched
 * controllers.
 *
 * @todo Extract configuration into its own loader classes
 */
class TransactionalMatcher
{
    /**
     * @var array
     */
    private $patterns;

    /**
     * @var array
     */
    private $defaults;

    /**
     * @var array
     */
    private $cache = array();

    /**
     * @var Reader
     */
    private $reader;

    public function __construct(array $patterns, array $defaults = array(), Reader $reader = null)
    {
        $this->patterns = $patterns;
        $this->defaults = $defaults;
        $this->reader = $reader;
    }

    /**
     * Match if he current controller/action should be transactional or not.
     *
     * Important: Only Controller as services or Class#Action method
     * controllers can be transactional.
     *
     * @param string $method HTTP Method
     * @param mixed $controllerCallback
     * @return TransactionDefinition|false
     */
    public function match($method, $controllerCallback)
    {
        if (!is_array($controllerCallback)) {
            return false;
        }

        list($controller, $action) = $controllerCallback;
        $class = get_class($controller);

        $subject = $class . "::" . $action;

        if (!isset($this->cache[$subject][$method])) {
            $this->cache[$subject] = array();
            $this->matchPatterns($subject);
            $this->matchAnnotations($subject, $controller, $action);
        }

        $definitions = array();
        $requireNew = false;
        foreach ($this->cache[$subject] as $connectionName => $definition) {
            if ($definition['propagation'] == TransactionDefinition::PROPAGATION_REQUIRES_NEW) {

            }

            $definitions[] = new TransactionDefinition(
                $definition['managerName'],
                $definition['propagation'],
                $definition['isolation'],
                ! in_array($method, $definition['methods'])
            );
        }

        return $definitions;
    }

    /**
     * Match transactional patterns.
     *
     * @param string $subject
     */
    private function matchPatterns($subject)
    {
        foreach ($this->patterns AS $pattern) {
            if (preg_match('(' . $pattern['pattern'] . ')', $subject)) {
                $this->storeMatch($subject, $pattern);
            }
        }
    }

    /**
     * Match annotations on controllers for transactional behavior.
     *
     * @param string $subject
     * @param object $controller
     * @param string $action
     * @return void
     */
    private function matchAnnotations($subject, $controller, $action)
    {
        if ($this->reader === null) {
            return;
        }

        $reflClass = new \ReflectionObject($controller);
        if ($txAnnot = $this->reader->getClassAnnotation($reflClass, 'SimpleThings\TransactionalBundle\Annotations\Transactional')) {
            $annotData = array_merge($this->defaults, array_filter((array)$txAnnot, function($v) { return $v !== null; }));
            $this->storeMatch($subject, $annotData);
        }

        if ($txAnnot = $this->reader->getMethodAnnotation($reflClass->getMethod($action), 'SimpleThings\TransactionalBundle\Annotations\Transactional')) {
            $annotData = array_merge($this->defaults, array_filter((array)$txAnnot, function($v) { return $v !== null; }));
            $this->storeMatch($subject, $annotData);
        }
    }

    private function storeMatch($subject, $pattern)
    {
        $managerName = $pattern['conn'];
        if (isset($this->cache[$subject][$managerName])) {
            throw TransactionException::duplicateConnectionMatch($managerName, $pattern);
        }

        $this->cache[$subject][$managerName] = array(
                'managerName' => $managerName,
                'isolation' => $pattern['isolation'],
                'propagation' => $pattern['propagation'],
                'noRollbackFor' => $pattern['noRollbackFor'],
                'methods' => $pattern['methods'],
                );
    }
}
