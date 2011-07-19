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

class TransactionalMatcher
{
    private $patterns;

    private $cache = array();

    public function __construct(array $patterns)
    {
        $this->patterns = $patterns;
    }

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
            $connections = array();

            foreach ($this->patterns AS $pattern) {
                if (isset($pattern['methods'][$method]) && preg_match('('.$pattern.')', $subject)) {
                    foreach ($pattern['conn'] AS $connectionName) {
                        if (isset($connections[$connectionName])) {
                            throw TransactionException::duplicateConnectionMatch($connectionName, $pattern);
                        }

                        $connections[$connectionName] = array(
                            'isolation' => $pattern['isolation'],
                            'propagation' => $pattern['propagation'],
                            'noRollbackFor' => $pattern['noRollbackFor'],
                        );
                    }
                }
            }

            if (!$connections) {
                $this->cache[$subject][$method] = false;
            }

            $this->cache[$subject][$method] = new TransactionDefinition($connections);
        }
        return $this->cache[$subject][$method];
    }
}