<?php
namespace SimpleInvoices\ModuleManager;

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
    
    protected $domainId;
    
    protected $doneSQLPatches;
    
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
}