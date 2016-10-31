<?php
namespace SimpleInvoices\ModuleManager\Listener;

use Zend\Loader\ModuleAutoloader;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use SimpleInvoices\ModuleManager\ModuleEvent;

class ModuleLoaderListener extends AbstractListener implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $callbacks = [];
    
    /**
     * @var bool
     */
    protected $generateCache;
    
    /**
     * @var array
     */
    protected $moduleLoader;
    
    /**
     * Constructor.
     *
     * Creates an instance of the ModuleAutoloader and injects the module paths
     * into it.
     *
     * @param  ListenerOptions $options
     */
    public function __construct(ListenerOptions $options = null)
    {
        parent::__construct($options);
        
        $this->generateCache = $this->options->getModuleMapCacheEnabled();
        $this->moduleLoader  = new ModuleAutoloader($this->options->getModulePaths());
        
        if ($this->hasCachedClassMap()) {
            $this->generateCache = false;
            $this->moduleLoader->setModuleClassMap($this->getCachedConfig());
        }
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
        
        if ($this->generateCache) {
            $this->callbacks[] = $events->attach(
                ModuleEvent::EVENT_LOAD_MODULES_POST,
                [$this, 'onLoadModulesPost']
            );
        }
    }
    
    /**
     * Detach all previously attached listeners
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->callbacks as $index => $callback) {
            if ($events->detach($callback)) {
                unset($this->callbacks[$index]);
            }
        }
    }
    
    /**
     * @return array
     */
    protected function getCachedConfig()
    {
        return include $this->options->getModuleMapCacheFile();
    }
    
    /**
     * @return bool
     */
    protected function hasCachedClassMap()
    {
        if ($this->options->getModuleMapCacheEnabled() && file_exists($this->options->getModuleMapCacheFile())) 
        {
            return true;
        }
        return false;
    }
    
    /**
     * loadModulesPost
     *
     * Unregisters the ModuleLoader and generates the module class map cache.
     *
     * @param  ModuleEvent $event
     */
    public function onLoadModulesPost(ModuleEvent $event)
    {
        $this->moduleLoader->unregister();
        $this->writeArrayToFile(
            $this->options->getModuleMapCacheFile(),
            $this->moduleLoader->getModuleClassMap()
            );
    }
}