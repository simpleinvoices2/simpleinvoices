<?php
namespace SimpleInvoices\ModuleManager;

use Traversable;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Session\Container;

class ModuleManager implements ModuleManagerInterface
{
    /**#@+
     * Reference to Zend\Mvc\MvcEvent::EVENT_BOOTSTRAP
     */
    const EVENT_BOOTSTRAP = 'bootstrap';
    /**#@-*/
    
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * @var ModuleEvent
     */
    protected $event;
    
    /**
     * @var EventManagerInterface
     */
    protected $events;
    
    /**
     * The user domain identifier.
     * 
     * @var int
     */
    protected $domainId;
    
    /**
     * Number of done SQL patches.
     * 
     * @var int
     */
    protected $doneSQLPatches = 0;
    
    /**
     * @var array An array of Module classes of loaded modules
     */
    protected $loadedModules = [];
    
    /**
     * @var bool
     */
    protected $loadFinished;
    
    /**
     * modules
     *
     * @var array|Traversable
     */
    protected $modules = [];
    
    /**
     * True if modules have already been loaded
     *
     * @var bool
     */
    protected $modulesAreLoaded = false;
    
    /**
     * The table for extensions/modules.
     * 
     * @var string|TableIdentifier
     */
    protected $table;
    
    /**
     * Constructor
     *
     * @param  AdapterInterface
     * @param  EventManagerInterface $eventManager
     */
    public function __construct(AdapterInterface $adapter, $table, $doneSQLPatches, $domainId = null, EventManagerInterface $eventManager = null)
    {
        $this->adapter = $adapter;
        
        if (is_numeric($domainId)) {
            $this->domainId = (int) $domainId;
        } else {
            $sessionContainer = new Container('SI_AUTH');
            if (isset($sessionContainer->domain_id)) {
                $this->domainId = $sessionContainer->domain_id;
            } else {
                $this->domainId = 1;
            }
        }
        
        if ($eventManager instanceof EventManagerInterface) {
            $this->setEventManager($eventManager);
        }
        
        $this->doneSQLPatches = $doneSQLPatches;
        $this->table          = $table;   
    }
    
    /**
     * Register the default event listeners
     *
     * @param EventManagerInterface $events
     * @return ModuleManager
     */
    protected function attachDefaultListeners($events)
    {
        $events->attach(ModuleEvent::EVENT_LOAD_MODULES, [$this, 'onLoadModules']);
    }
    
    /**
     * Get the module event
     *
     * @return ModuleEvent
     */
    public function getEvent()
    {
        if (!$this->event instanceof ModuleEvent) {
            $this->setEvent(new ModuleEvent());
        }
        return $this->event;
    }
    
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events instanceof EventManagerInterface) {
            $this->setEventManager( new EventManager() );
        }
        return $this->events;
    }
    
    /**
     * Get an array of the loaded modules.
     *
     * @param  bool  $loadModules If true, load modules if they're not already
     * @return array An array of Module objects, keyed by module name
     */
    public function getLoadedModules($loadModules = false)
    {
        if (true === $loadModules) {
            $this->loadModules();
        }
    
        return $this->loadedModules;
    }
    
    /**
     * Get the array of module names that this manager should load.
     *
     * @return array
     */
    public function getModules()
    {
        if (empty($this->modules)) {
            // Query for modules (AKA Extensions)
            if ($this->doneSQLPatches > 196) {
                $domainPredicate = new PredicateSet([
                    new Operator('domain_id', '=', 0),
                    new Operator('domain_id', '=', $this->domainId),
                ], PredicateSet::OP_OR);
                
                $select = new Select($this->table);
                $select->where($domainPredicate);
                $select->order('domain_id ASC');
                
                $sql = new Sql($this->adapter);
                $statement = $sql->prepareStatementForSqlObject($select);
                $result = $statement->execute();
              
                $resultSet = new ResultSet(null, new Model\Module());
                $resultSet->initialize($result);
                
                foreach ($resultSet as $result) {
                    // By setting the name as key we avoid duplicates
                    $this->modules[$result->name] = $result;
                }
            }
            
            // Check we have core as it is mandatory!
            if (!isset($this->modules['core'])) {
                $coreModule = new Model\Module();
                $coreModule->exchangeArray([
                    'id'          => 1,
                    'domain_id'   => 1,
                    'name'        => 'core',
                    'description' => 'Core part of Simple Invoices - always enabled',
                    'enabled'     => 1,
                ]);
                $this->modules['core'] = $coreModule;
            }
            
            // Make sure 'core' is enabled
            $this->modules['core']->setEnabled(true);
        }
        
        return $this->modules;
    }
    
    /**
     * Load a specific module by name.
     *
     * @param  string|array               $module
     * @return mixed Module's Module class
     */
    public function loadModule($module)
    {
        $moduleName = $module;
        if (is_array($module)) {
            $moduleName = key($module);
            $module     = current($module);
        }
        
        if (isset($this->loadedModules[$moduleName])) {
            return $this->loadedModules[$moduleName];
        }
        
        /*
         * Keep track of nested module loading using the $loadFinished
         * property.
         *
         * Increment the value for each loadModule() call and then decrement
         * once the loading process is complete.
         *
         * To load a module, we clone the event if we are inside a nested
         * loadModule() call, and use the original event otherwise.
         */
        if (!isset($this->loadFinished)) {
            $this->loadFinished = 0;
        }
        
        $event = ($this->loadFinished > 0) ? clone $this->getEvent() : $this->getEvent();
        $event->setModuleName($moduleName);
        
        $this->loadFinished++;
        
        $module = $this->loadModuleByName($event);
        
        $event->setModule($module);
        $event->setName(ModuleEvent::EVENT_LOAD_MODULE);
        
        $this->loadedModules[$moduleName] = $module;
        $this->getEventManager()->triggerEvent($event);
        
        $this->loadFinished--;
        
        return $module;
    }
    
    /**
     * Load a module with the name
     * @param  ModuleEvent $event
     * @return mixed                            module instance
     * @throws Exception\RuntimeException
     */
    protected function loadModuleByName(ModuleEvent $event)
    {
        // We are using a resolver to allow further customization.
        // for example, if the main module folder is NOT writable by
        // a users but he wants to upload a module he could define
        // a custom module path for his setup.
        $event->setName(ModuleEvent::EVENT_LOAD_MODULE_RESOLVE);
        $result = $this->getEventManager()->triggerEventUntil(function ($r) {
            return (is_object($r));
        }, $event);
        
        $module = $result->last();
        if (!is_object($module)) {
            throw new Exception\RuntimeException(sprintf(
                'Module (%s) could not be initialized.',
                $event->getModuleName()
            ));
        }
        
        return $module;
    }
    
    /**
     * Load the provided modules.
     *
     * @triggers loadModules
     * @triggers loadModules.post
     * @return   ModuleManager
     */
    public function loadModules()
    {
        if (true === $this->modulesAreLoaded) {
            return $this;
        }
        
        $events = $this->getEventManager();
        $event  = $this->getEvent();
        $event->setName(ModuleEvent::EVENT_LOAD_MODULES);
        $events->triggerEvent($event);
        
        /**
         * Having a dedicated .post event abstracts the complexity of priorities from the user.
         * Users can attach to the .post event and be sure that important
         * things like config merging are complete without having to worry if
         * they set a low enough priority.
         */
        $event->setName(ModuleEvent::EVENT_LOAD_MODULES_POST);
        $events->triggerEvent($event);
        
        return $this;
    }
    
    /**
     * Handle the loadModules event
     *
     * @return void
     */
    public function onLoadModules(ModuleEvent $event)
    {
        if (true === $this->modulesAreLoaded) {
            return $this;
        }
        
        foreach ($this->getModules() as $moduleName => $module) {
            // Only load enabled modules
            if ($module->isEnabled()) {
                $this->loadModule([$moduleName => $module]);
            }
        }
        
        $this->modulesAreLoaded = true;
    }
    
    /**
     * Set the database adapter.
     * 
     * @param AdapterInterface $adapter
     * @return \SimpleInvoices\ModuleManager\ModuleManager
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }
    
    /**
     * Set the module event
     *
     * @param  ModuleEvent $event
     * @return ModuleManager
     */
    public function setEvent(ModuleEvent $event)
    {
        $event->setTarget($this);
        $this->event = $event;
        return $this;
    }
    
    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return void
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers([
            __CLASS__,
            get_class($this),
            'si_module_manager',
        ]);
        $this->events = $events;
        $this->attachDefaultListeners($events);
        return $this;
    }
    
    /**
     * Set an array or Traversable of module names that this module manager should load.
     *
     * @param  mixed $modules array or Traversable of module names
     * @throws Exception\InvalidArgumentException
     * @return ModuleManager
     */
    public function setModules($modules)
    {
        if (is_array($modules) || $modules instanceof Traversable) {
            $this->modules = $modules;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Parameter to %s\'s %s method must be an array or implement the Traversable interface',
                    __CLASS__,
                    __METHOD__
                    )
                );
        }
        return $this;
    }
}