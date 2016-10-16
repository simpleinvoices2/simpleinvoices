<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Session\Container as SessionContainer;

class AuthorizationListener extends AbstractListenerAggregate
{
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
        $this->listeners[] = $events->attach(MvcEvent::EVENT_AUTHORIZATION, [$this, 'onAuthorization']);
    }
    
    /**
     * Listen to the "authorization" event and attempt to route the request
     *
     * @param  MvcEvent $event
     * @return bool
     */
    public function onAuthorization(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        
        if (!$routeMatch instanceof Router\RouteMatch) {
            return false;
        }
        
        $sessionContainer = new SessionContainer('SI_AUTH');
        $module = $routeMatch->getParam('module');
        $view   = $routeMatch->getParam('view');
        $action = $routeMatch->getParam('action');
            
        /*
         * API calls don't use the auth module
         */
        if ($module != 'api'){
            if (!isset($sessionContainer->id)){
                if  ($module !== "auth") {
                    header('Location: index.php?module=auth&view=login');
                    exit;
                }
            }
        }
            
        //
        // ACL
        //
        $acl = $event->getApplication()->getServiceManager()->get('SimpleInvoices\Permission\Acl');
            
        // defaults to allowed
        $isAllowed = true;
            
        if (empty($action)) {
            // no action is given
            if (!empty($view)) {
                // view is available with no action
                $isAllowed = $acl->isAllowed($sessionContainer->role_name, $module, $view);
            }
        } else {
            // action available
            $isAllowed = $acl->isAllowed($sessionContainer->role_name, $module, $action); // allowed
        }
            
        //basic customer page check
        // TODO: I think this is screwed up $sessionContainer->user_id return 0 lthough the user id is 1
        if( ($sessionContainer->role_name =='customer') && ($module == 'customers') ) {
            $id = $event->getRequest()->getQuery('id', null);
            if (!is_numeric($id)) {
                $isAllowed = false;
            }
            
            $id = (int) $id;
            if ($id !== $sessionContainer->user_id) {
                $isAllowed = false;
            }
        }
            
        //customer invoice page add/edit check since no acl for invoices
        if( (strcasecmp($sessionContainer->role_name, 'customer') === 0) && (strcasecmp($module, 'invoices') === 0) ) {
            if ( (strcasecmp($view, 'itemised') === 0 ) || (strcasecmp($view, 'total') === 0) || (strcasecmp($view, 'consulting') === 0) || (strcasecmp($action, 'view') === 0) ) {
                $isAllowed = false;
            } elseif (!empty($action)) {
                $id = $request->getQuery('id', null);
                if (is_numeric($id)) {
                    $id = (int) $id;
                    if ($id !== $sessionContainer->user_id) {
                        $isAllowed = false;
                    }
                }
            }
        }

        // Return boolen: allowed = true | disallowed = false
        return $isAllowed;
    }
}