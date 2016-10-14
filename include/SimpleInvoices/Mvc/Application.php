<?php
namespace SimpleInvoices\Mvc;

use Zend\ServiceManager\ServiceManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

/**
 * Provides a class to store the application wide
 * variables and objects.
 */
class Application implements ApplicationInterface, EventManagerAwareInterface
{
    /**
     * Default application event listeners
     *
     * @var array
     */
    protected $defaultListeners = [];
    
    /**
     * MVC event token
     * @var MvcEvent
     */
    protected $event;
    
    /**
     * @var EventManagerInterface
     */
    protected $events;
    
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var ResponseInterface
     */
    protected $response;
    
    /**
     * @var ServiceManager
     */
    protected $serviceManager;
    
    public function __construct(ServiceManager $serviceManager, EventManagerInterface $events = null, RequestInterface $request = null, ResponseInterface $response = null)
    {
        $this->serviceManager = $serviceManager;
        $this->setEventManager($events ?: $serviceManager->get('EventManager'));
        $this->request        = $request ?: $serviceManager->get('Request');
        $this->response       = $response ?: $serviceManager->get('Response');
        
        /**
         * Default listeners
         */
        if (!$serviceManager->has('DispatchListener')) {
            $this->serviceManager->setService('DispatchListener', new DispatchListener());
        }
        $this->defaultListeners[] = 'DispatchListener';
    }
    
    /**
     * Bootstrap the application
     *
     * Defines and binds the MvcEvent, and passes it the request, response, and
     * router. Attaches the ViewManager as a listener. Triggers the bootstrap
     * event.
     *
     * @param array $listeners List of listeners to attach.
     * @return Application
     */
    public function bootstrap(array $listeners = [])
    {
        $events         = $this->events;
        $serviceManager = $this->serviceManager;
        
        // Setup default listeners
        $listeners = array_unique(array_merge($this->defaultListeners, $listeners));
        
        foreach ($listeners as $listener) {
            $serviceManager->get($listener)->attach($events);
        }
        
        // Bootstrap session
        \Zend_Session::start();
        $sessionContainer = new \Zend_Session_Namespace('Zend_Auth');
        if (empty($sessionContainer->domain_id)) {
            // set the default domain
            $sessionContainer->domain_id = 1;
        }
        
        // Setup MVC Event
        $this->event = $event  = new MvcEvent();
        $event->setName(MvcEvent::EVENT_BOOTSTRAP);
        $event->setTarget($this);
        $event->setApplication($this);
        $event->setRequest($this->request);
        $event->setResponse($this->response);
        //$event->setRouter($serviceManager->get('Router'));
        
        // Trigger bootstrap events
        $events->triggerEvent($event);
        
        return $this;
    }
    
    /**
     * Retrieve the application configuration
     *
     * @return array|object
     */
    public function getConfig()
    {
        return $this->serviceManager->get('SimpleInvoices\Config');
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
        return $this->events;
    }
    
    /**
     * Get the MVC event instance
     *
     * @return MvcEvent
     */
    public function getMvcEvent()
    {
        if (!$this->event instanceof MvcEvent) {
            $event = new MvcEvent();
            $event->setTarget($this);
            $event->setApplication( $this );
            $event->setRequest($this->request);
            $event->setResponse($this->response);
            
            $this->event = $event;
        }
        return $this->event;
    }
    
    /**
     * Get the request object
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Get the response object
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
    
    /**
     * Retrieve the service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }
    
    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return void
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