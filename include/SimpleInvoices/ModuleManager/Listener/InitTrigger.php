<?php
namespace SimpleInvoices\ModuleManager\Listener;

use SimpleInvoices\ModuleManager\ModuleEvent;

/**
 * Init trigger
 */
class InitTrigger extends AbstractListener
{
    /**
     * @param ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getModule();
        if (!$module instanceof InitProviderInterface && !method_exists($module, 'init')) {
            return;
        }
        
        $module->init($e->getTarget());
    } 
}