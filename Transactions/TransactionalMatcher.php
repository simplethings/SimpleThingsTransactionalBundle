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

namespace SimpleThings\TransactionalBundle\Transactions;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Annotations\Reader;

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
     * @param Request $request
     * @param mixed $controllerCallback
     * @return TransactionDefinition|false
     */
    public function match(Request $request, $controllerCallback)
    {
        if (!is_array($controllerCallback)) {
            return false;
        }
        $method = $request->getMethod();

        list($controller, $action) = $controllerCallback;
        $class = get_class($controller);

        $subject = $class . "::" . $action;

        if (!isset($this->cache[$subject][$method])) {            
            $this->matchPatterns($subject, $method);
            $this->matchAnnotations($subject, $method, $controller, $action);

            if (!isset($this->cache[$subject][$method])) {   
                $this->cache[$subject][$method] = false;
            } else {
                $this->cache[$subject][$method] = new TransactionDefinition($this->cache[$subject][$method]);
            }
        }
        return $this->cache[$subject][$method];
    }

    /**
     * Match transactional patterns.
     * 
     * @param string $subject
     * @param string $method
     */
    private function matchPatterns($subject, $method)
    {
        foreach ($this->patterns AS $pattern) {
            if (in_array($method, $pattern['methods']) && preg_match('(' . $pattern['pattern'] . ')', $subject)) {
                $this->storeMatch($subject, $method, $pattern);
            }
        }
    }

    /**
     * Match annotations on controllers for transactional behavior.
     * 
     * @param string $subject
     * @param string $method
     * @param object $controller
     * @param string $action
     * @return void
     */
    private function matchAnnotations($subject, $method, $controller, $action)
    {
        if ($this->reader === null) {
            return;
        }

        $reflClass = new \ReflectionObject($controller);
        if ($txAnnot = $this->reader->getClassAnnotation($reflClass, 'SimpleThings\TransactionalBundle\Annotations\Transactional')) {
            $annotData = array_merge($this->defaults, array_filter((array)$txAnnot, function($v) { return $v !== null; }));

            if (in_array($method, $annotData['methods'])) {
                $this->storeMatch($connections, $annotData);
            }
        }

        if ($txAnnot = $this->reader->getMethodAnnotation($reflClass->getMethod($action), 'SimpleThings\TransactionalBundle\Annotations\Transactional')) {
            $annotData = array_merge($this->defaults, array_filter((array)$txAnnot, function($v) { return $v !== null; }));

            if (in_array($method, $annotData['methods'])) {
                $this->storeMatch($subject, $method, $annotData);
            }
        }
    }

    private function storeMatch($subject, $method, $pattern)
    {
        foreach ($pattern['conn'] AS $connectionName) {
            if (isset($this->cache[$subject][$method][$connectionName])) {
                throw TransactionException::duplicateConnectionMatch($connectionName, $pattern);
            }

            $this->cache[$subject][$method][$connectionName] = array(
                'isolation' => $pattern['isolation'],
                'propagation' => $pattern['propagation'],
                'noRollbackFor' => $pattern['noRollbackFor'],
                'subrequest' => $pattern['subrequest'],
            );
        }
    }
}