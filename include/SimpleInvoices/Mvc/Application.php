<?php
namespace SimpleInvoices\Mvc;

use Zend\ServiceManager\ServiceManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;
use SimpleInvoices\View\Resolver\TemplatePathStack;
use Zend\Session\SessionManager;
use Zend\Session\Container as SessionContainer;

/**
 * Provides a class to store the application wide
 * variables and objects.
 */
class Application implements ApplicationInterface, EventManagerAwareInterface
{
    const ERROR_ROUTER_NO_MATCH            = 'error-router-no-match';
    const ERROR_NOT_AUTHORIZED             = 'error-not-authorized';
    
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
        if (!$serviceManager->has('RouteListener')) {
            $this->serviceManager->setService('RouteListener', new RouteListener());
        }
        $this->defaultListeners[] = 'RouteListener';
        
        if (!$serviceManager->has('DispatchListener')) {
            $this->serviceManager->setService('DispatchListener', new DispatchListener());
        }
        $this->defaultListeners[] = 'DispatchListener';
        
        if (!$serviceManager->has('RenderListener')) {
            $this->serviceManager->setService('RenderListener', new RenderListener());
        }
        $this->defaultListeners[] = 'RenderListener';
        
        if (!$serviceManager->has('AuthorizationListener')) {
            $this->serviceManager->setService('AuthorizationListener', new AuthorizationListener());
        }
        $this->defaultListeners[] = 'AuthorizationListener';
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
        $session = $this->serviceManager->get(SessionManager::class);
        $session->start();
        $sessionContainer = new SessionContainer('SI_AUTH');
        if (empty($sessionContainer->domain_id)) {
            // set the default domain
            $sessionContainer->domain_id = 1;
        }
        
        //if user logged into Simple Invoices with auth off then auth turned on - id via fake_auth and kill session
        if ($this->getConfig()->authentication->enabled) {
            if ($sessionContainer->fake_auth) {
                $session->destroy([
                    'clear_storage'      => true,
                    'send_expire_cookie' => true,
                ]);
                header('Location: .');
            }
        } else {
            /*
             * If auth not on - use default domain and user id of 1
             * 
             * chuck the user details sans password into the Zend_auth session
             */
            $sessionContainer->id = "1";
            $sessionContainer->domain_id = "1";
            $sessionContainer->email = "demo@simpleinvoices.org";
            //fake_auth is identifier to say that user logged in with auth off
            $sessionContainer->fake_auth = "1";
            //No Customer login as logins disabled
            $sessionContainer->user_id = "0";
        }
        
        // Setup MVC Event
        $this->event = $event  = new MvcEvent();
        $event->setName(MvcEvent::EVENT_BOOTSTRAP);
        $event->setTarget($this);
        $event->setApplication($this);
        $event->setRequest($this->request);
        $event->setResponse($this->response);
        $event->setRouter($serviceManager->get('SimpleInvoices\Router'));
        
        // Trigger bootstrap events
        $events->triggerEvent($event);
        
        return $this;
    }
    
    /**
     * Complete the request
     *
     * Triggers "render" and "finish" events, and returns response from
     * event object.
     *
     * @param  MvcEvent $event
     * @return Application
     */
    protected function completeRequest(MvcEvent $event)
    {
        echo "You should not be here!<br />";
        echo "Something must of gone really bad if you arrived to this page.";
        die();
        // TODO: This is what it should do!
        // ================================
        //$events = $this->events;
        //$event->setTarget($this);
        //$event->setName(MvcEvent::EVENT_RENDER);
        //$event->stopPropagation(false); // Clear before triggering
        //$events->triggerEvent($event);
        //$event->setName(MvcEvent::EVENT_FINISH);
        //$event->stopPropagation(false); // Clear before triggering
        //$events->triggerEvent($event);
        //return $this;
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
            $event->setRouter( $this->serviceManager->get('SimpleInvoices\Router') );
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
     * Run the application
     *
     * @return self
     */
    public function run()
    {
        $events = $this->events;
        $event  = $this->getMvcEvent();
        
        /**
         * trigger the 'dispatch' event.
         */
        $event->setName(MvcEvent::EVENT_DISPATCH);
        $events->triggerEvent($event);
     
        /**
         * Render the output
         */
        $event->setName(MvcEvent::EVENT_RENDER);
        $events->triggerEvent($event);
        //$renderer = new \SimpleInvoices\Smarty\Renderer($this->serviceManager);
        //$renderer->setResolver( $this->serviceManager->get(TemplatePathStack::class) );
        //$renderer->render();
    }
    
    /**
     * While refactoring code we need another method but all this
     * code should be inside the Application::run() method.
     * 
     * TODO: Move this code to the start or Application::run() when possible.
     */
    public function runFirst()
    {
        $events = $this->events;
        $event  = $this->event;
        
        // Define callback used to determine whether or not to short-circuit
        $shortCircuit = function ($r) use ($event) {
            if ($r instanceof ResponseInterface) {
                return true;
            }
            if ($event->getError()) {
                return true;
            }
            return false;
        };
        
        // Trigger route event
        $event->setName(MvcEvent::EVENT_ROUTE);
        $event->stopPropagation(false); // Clear before triggering
        $result = $events->triggerEventUntil($shortCircuit, $event);
        if ($result->stopped()) {
            $response = $result->last();
            if ($response instanceof ResponseInterface) {
                $event->setName(MvcEvent::EVENT_FINISH);
                $event->setTarget($this);
                $event->setResponse($response);
                $event->stopPropagation(false); // Clear before triggering
                $events->triggerEvent($event);
                $this->response = $response;
                return $this;
            }
        }
        
        if ($event->getError()) {
            return $this->completeRequest($event);
        }
        
        // If authentication is enabled
        if ($this->getConfig()->authentication->enabled) {
            // =========================================
            // -------------   S T A R T   -------------
            // API calls don't use the auth module
            $sessionContainer = new SessionContainer('SI_AUTH');
            $module = $event->getRouteMatch()->getParam('module');
            if ($module != 'api'){
                if (!isset($sessionContainer->id)){
                    if  ($module !== "auth") {
                        header('Location: index.php?module=auth&view=login');
                        exit;
                    }
                }
            }
            // ---------------   E N D   ---------------
            //==========================================
            
            // Trigger authorization event
            $event->setName(MvcEvent::EVENT_AUTHORIZATION);
            $event->stopPropagation(false); // Clear before triggering
            $result = $events->triggerEvent($event);
            
            if (!$result->last()) {
                header('HTTP/1.0 403 Forbidden');
                // $checkPermission == "denied" ? exit($LANG['denied_page']) :"" ;
                echo "You are not allowed to view this page";
                exit(1);
            }
        }
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