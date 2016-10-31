<?php
namespace SimpleInvoices\ModuleManager\Listener;

use SimpleInvoices\ModuleManager\ModuleEvent;
use Zend\Loader\AutoloaderFactory;
use SimpleInvoices\ModuleManager\Feature\AutoloaderProviderInterface;

class AutoloaderListener extends AbstractListener
{
    /**
     * @param  ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getModule();
        if (!$module instanceof AutoloaderProviderInterface && !method_exists($module, 'getAutoloaderConfig')) {
            return;
        }
        
        $autoloaderConfig = $module->getAutoloaderConfig();
        AutoloaderFactory::factory($autoloaderConfig);
    }
    
}