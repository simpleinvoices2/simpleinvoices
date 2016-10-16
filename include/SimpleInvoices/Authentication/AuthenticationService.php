<?php
namespace SimpleInvoices\Authentication;

use Zend\Authentication\AuthenticationService as BaseAuthenticationService;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Authentication\Adapter\AdapterInterface;
//use Zend\EventManager\EventManagerInterface;
//use Zend\Authentication\Adapter\AdapterInterface;
//use Zend\Authentication\AuthenticationService;
//use Zend\Authentication\Storage\StorageInterface;

class AuthenticationService extends BaseAuthenticationService
{
    /**
     * @var EventManagerInterface
     */
    protected $events;
    
    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param AdapterInterface $adapter
     * @param EventManagerInterface $events
     */
    public function __construct(StorageInterface $storage = null, AdapterInterface $adapter = null, EventManagerInterface $events = null)
    {
        parent::__construct($storage, $adapter);
        
        if (null !== $events) {
            $this->setEventManager($events);
        }
    }
    
    /**
     * Authenticates against the supplied adapter
     *
     * @param  Adapter\AdapterInterface $adapter
     * @return Result
     * @throws Exception\RuntimeException
     */
    public function authenticate(Adapter\AdapterInterface $adapter = null)
    {
        if (!$adapter) {
            if (!$adapter = $this->getAdapter()) {
                throw new Exception\RuntimeException('An adapter must be set or passed prior to calling authenticate()');
            }
        }
        
        $events = $this->getEventManager();
        
        // Trigger authentication event
        $event = new AuthenticationEvent();
        $event->setTarget($this);
        $event->setName(AuthenticationEvent::EVENT_AUTHENTICATE);
        $event->setAdapter($adapter);
        $events->triggerEvent($event);
        
        $result = parent::authenticate($adapter);
        
        // Trigger authentication post event
        $event = new AuthenticationEvent();
        $event->setTarget($this);
        $event->setName(AuthenticationEvent::EVENT_AUTHENTICATE_POST);
        $event->setAdapter($adapter);
        $event->setParam('authentication_result', $result);
        $events->triggerEvent($event);
    
        return $result;
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
            $this->events = new EventManager();
        }
        return $this->events;
    }
    
    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return AuthenticationService
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $eventManager->setIdentifiers([
            __CLASS__,
            get_class($this),
        ]);
        $this->events = $eventManager;
        return $this;
    }
}