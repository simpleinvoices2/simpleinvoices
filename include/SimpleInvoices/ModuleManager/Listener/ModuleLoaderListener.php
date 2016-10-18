<?php
namespace SimpleInvoices\ModuleManager\Listener;

use Zend\Loader\ModuleAutoloader;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use SimpleInvoices\ModuleManager\ModuleEvent;

class ModuleLoaderListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $callbacks = [];
    
    /**
     * @var array
     */
    protected $moduleLoader;
    
    public function __construct()
    {
        $this->moduleLoader = new ModuleAutoloader([
            './extensions',
            './modules',
        ]);
    }
    
    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     * @param int                   $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->callbacks[] = $events->attach(
            ModuleEvent::EVENT_LOAD_MODULES,
            [$this->moduleLoader, 'register'],
            9000
        );
        
        //$this->callbacks[] = $events->attach(
        //    ModuleEvent::EVENT_LOAD_MODULES_POST,
        //    [$this->moduleLoader, 'unregister'],
        //    9000
        //);
    }
    
    /**
     * Detach all previously attached listeners
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        
    }
}