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
use Zend\Db\Metadata\Metadata;

/**
 * Provides a class to store the application wide
 * variables and objects.
 */
class Application implements ApplicationInterface, EventManagerAwareInterface
{
    const ERROR_CONTROLLER_NOT_FOUND       = 'error-controller-not-found';
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
        
        $this->defaultListeners[] = \SimpleInvoices\Mvc\DispatchListener::class;
        
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
        
        // Verify some folders
        if (!is_writable('./tmp')) {
            simpleInvoicesError('notWriteable','directory','./tmp');
        }
        
        if (!is_writable('./tmp/cache')) {
            simpleInvoicesError('notWriteable','file','./tmp/cache');
        }
        
        // Setup default listeners
        $listeners = array_unique(array_merge($this->defaultListeners, $listeners));
        
        foreach ($listeners as $listener) {
            $serviceManager->get($listener)->attach($events);
        }
        
        //set up app with relevant php setting
        date_default_timezone_set($this->getConfig()->phpSettings->date->timezone);
        error_reporting($this->getConfig()->debug->error_reporting);
        ini_set('display_startup_errors', $this->getConfig()->phpSettings->display_startup_errors);
        ini_set('display_errors', $this->getConfig()->phpSettings->display_errors);
        ini_set('log_errors', $this->getConfig()->phpSettings->log_errors);
        ini_set('error_log', $this->getConfig()->phpSettings->error_log);
        
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
             * chuck the user details sans password into the SI_AUTH session
             */
            $sessionContainer->id = "1";
            $sessionContainer->domain_id = "1";
            $sessionContainer->email = "demo@simpleinvoices.org";
            //fake_auth is identifier to say that user logged in with auth off
            $sessionContainer->fake_auth = true;
            //No Customer login as logins disabled
            $sessionContainer->user_id = "0";
        }
        
        if (!isset($sessionContainer->fake_auth)) {
            // TODO: Check the user is enabled.
            //       Don't use session as I want to close his connections as soon as it is disabled
        }
        
        // Initialize modules
        $moduleManager = $serviceManager->get('SimpleInvoices\ModuleManager');
        $moduleManager->loadModules();
        
        // TODO: This should go in the module manager but while we get it finished
        //       we will load the translations for extension here
        $translator = $this->serviceManager->get(\Zend\I18n\Translator\TranslatorInterface::class);
        foreach($moduleManager->getModules() as $extension) {
            /*
             * If extension is enabled then continue and include the requested file for that extension if it exists
             */
            if($extension->enabled == "1") {
                // TODO: this applies to expense and sub_customer. Make the appropiate files.
                if(file_exists('./extensions/' . $extension->name . '/language'))
                {
                    $translator->addTranslationFilePattern('phparray', './extensions/' . $extension->name . '/language', '%s.php');
                }
            }
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
        $events = $this->events;
        $event->setTarget($this);
        $event->setName(MvcEvent::EVENT_RENDER);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);
        $event->setName(MvcEvent::EVENT_FINISH);
        $event->stopPropagation(false); // Clear before triggering
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
        // TODO: Remove this!
        global $LANG;
        
        $events = $this->events;
        $event  = $this->getMvcEvent();
        
        // ===================================================
        // --------------- RUN FIRST CODE START --------------
        
        // --------------- INSTALLER START ---------------
        $metadata = new Metadata($this->serviceManager->get('SimpleInvoices\Database\Adapter'));
        $tables   = $metadata->getTableNames();
        if (!in_array(TB_PREFIX . 'customers', $tables)) {
            // Redirect
            if ($this->request->getQuery('module', null) !== 'install') {
                header("Location: " . $this->request->getBaseUrl() . '/index.php?module=install&view=index');
                exit(0);
            }
        }
        
        if ($this->request->getQuery('module', null) !== 'install') {
            $patchManager = $this->serviceManager->get('SimpleInvoices\PatchManager');
            if ($patchManager->isActive() && ($patchManager->hasNewPatches())) {
                if (($this->request->getQuery('module', null) !== 'options') || ($this->request->getQuery('view', null) !== 'database_sqlpatches')) {
                    if ($this->request->getQuery('action', null) === 'run') {
                        // TODO: Maybe I was debbuging since I don't understand why this die is here
                        die("Run");
                    } else {
                        header("Location: " . $this->request->getBaseUrl() . '/index.php?module=options&view=database_sqlpatches');
                        exit(0);
                    }
                }
            }
        }
        
        // ---------------- INSTALLER END ----------------
        
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
        
            // Define callback used to determine whether or not to short-circuit
            $shortCircuitAuthorization = function ($r) use ($event) {
                if (!$r) {
                    // If returned false exit right away as access is forbiden
                    return true;
                }
                return false;
            };
        
            // Trigger authorization event
            $event->setName(MvcEvent::EVENT_AUTHORIZATION);
            $event->stopPropagation(false); // Clear before triggering
            $result = $events->triggerEventUntil($shortCircuitAuthorization, $event);
        
            if (!$result->last()) {
                header('HTTP/1.0 403 Forbidden');
                // $checkPermission == "denied" ? exit($LANG['denied_page']) :"" ;
                echo "You are not allowed to view this page";
                exit(1);
            }
        }
        
        // ---------------- RUN FIRST CODE END ---------------
        // ===================================================
        
        $smarty = $this->serviceManager->get('Smarty');
        $smarty->assign("config", $this->getConfig()); // to toggle the login / logout button visibility in the menu
        $smarty->assign("module", $event->getRouteMatch()->getParam('module', null));
        $smarty->assign("view", $event->getRouteMatch()->getParam('view', null));
        $smarty->assign("siUrl", getUrl());//used for template css
        $smarty->assign("LANG", $LANG);
        //For Making easy enabled pop-menus (see biller)
        $smarty->assign("enabled", array($LANG['disabled'], $LANG['enabled']));
        $smarty->assign("defaults", getSystemDefaults());
        
        // Trigger dispatch event
        $event->setName(MvcEvent::EVENT_DISPATCH);
        $event->stopPropagation(false); // Clear before triggering
        $result = $events->triggerEventUntil($shortCircuit, $event);
        
        // Complete response
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
        
        $response = $this->response;
        $event->setResponse($response);
        return $this->completeRequest($event);
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