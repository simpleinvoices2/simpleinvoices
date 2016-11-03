<?php
namespace SimpleInvoices\ModuleManager\Listener;

use SimpleInvoices\ModuleManager\ModuleEvent;
use SimpleInvoices\ModuleManager\DumbModule;

class ModuleResolverListener extends AbstractListener
{
    /**
     * @param  ModuleEvent $e
     * @return object|false False if module class does not exist
     */
    public function __invoke(ModuleEvent $e)
    {
        $moduleName = $e->getModuleName();
        $class      = $moduleName . '\Module';
        
        if (!class_exists($class)) {
            return false;
        }
        
        return new $class;
    }
}