<?php
namespace SimpleInvoices\ModuleManager\Listener;

use SimpleInvoices\ModuleManager\ModuleEvent;
use SimpleInvoices\ModuleManager\Feature\BootstrapListenerInterface;
use SimpleInvoices\ModuleManager\ModuleManager;

/**
 * Bootstrap listener
 */
class OnBootstrapListener extends AbstractListener
{
    /**
     * @param  ModuleEvent $e
     * @return void
     */
    public function __invoke(ModuleEvent $e)
    {
        $module = $e->getModule();
        
        if (!$module instanceof BootstrapListenerInterface && !method_exists($module, 'onBootstrap')) {
            return;
        }
        
        $moduleManager = $e->getTarget();
        $events        = $moduleManager->getEventManager();
        $sharedEvents  = $events->getSharedManager();
        $sharedEvents->attach('Simpleinvoices\Mvc\Application', ModuleManager::EVENT_BOOTSTRAP, [$module, 'onBootstrap']);
    }
}