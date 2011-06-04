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

namespace SimpleThings\TransactionalBundle;

class TransactionalController
{
    private $controller;
    private $txManagers = array();
    
    /**
     * @param array $controller
     * @param array $txManagers
     */
    public function __construct($controller, array $txManagers)
    {
        $this->controller = $controller;
        $this->txManagers = $txManagers;
    }
    
    public function getController()
    {
        return $this->controller;
    }
    
    public function __call($method, $args)
    {
        foreach ($this->txManagers AS $txManager) {
            $txManager->beginTransaction();
        }
        
        try {
            $response = call_user_func_array(array($this->controller, $method), $args);
            
            foreach ($this->txManagers AS $txManager) {
                $txManager->commit();
            }
            return $response;
            
        } catch(\Exception $e) {
            foreach ($this->txManagers AS $txManager) {
                $txManager->rollBack();
            }
            throw $e;
        }
    }
}