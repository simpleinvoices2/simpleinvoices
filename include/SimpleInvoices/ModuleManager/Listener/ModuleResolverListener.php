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
            // Old modules will fail, so we must do
            // something different ;)
            $fakeClass = $this->backwardCompatibilityLoader($moduleName);
            if ($fakeClass instanceof DumbModule) {
                return $fakeClass;
            }
            
            return false;
        }
        
        return new $class;
    }
    
    /**
     * Creates a DumbModule object for backward compatility.
     * 
     * TODO: Remove this when done refactoring.
     * 
     * @param unknown $moduleName
     */
    protected function backwardCompatibilityLoader($moduleName)
    {
        if (strcasecmp($moduleName, 'core') === 0) {
            return new DumbModule();
        }
        
        if (is_dir('./extensions/' . $moduleName)) {
            $module = new DumbModule();
            $module->extensionPath = './extensions/' . $moduleName;
            return $module;
        }
        
        return false;
    }
}