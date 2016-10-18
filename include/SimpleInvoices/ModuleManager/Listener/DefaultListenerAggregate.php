<?php
namespace SimpleInvoices\ModuleManager\Listener;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use SimpleInvoices\ModuleManager\ModuleEvent;

class DefaultListenerAggregate extends AbstractListenerAggregate
{
    /**
     * Attach one or more listeners
     *
     * @param  EventManagerInterface $events
     * @param  int $priority
     * @return DefaultListenerAggregate
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $moduleLoaderListener = new ModuleLoaderListener();
        
        // High priority, we assume module autoloading (for FooNamespace\Module
        // classes) should be available before anything else
        $moduleLoaderListener->attach($events);
        $this->listeners[] = $moduleLoaderListener;
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULE_RESOLVE, new ModuleResolverListener);
        
        // High priority, because most other loadModule listeners will assume
        // the module's classes are available via autoloading
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULE, new AutoloaderListener(), 9000);
        
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULE, new InitTrigger());
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULE, new OnBootstrapListener());
        
        return $this;
    }
}