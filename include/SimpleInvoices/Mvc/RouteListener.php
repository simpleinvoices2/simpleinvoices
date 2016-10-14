<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

class RouteListener extends AbstractListenerAggregate
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
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute']);
    }
    
    /**
     * Listen to the "route" event and attempt to route the request
     *
     * @param  MvcEvent $event
     * @return null|RouteMatch
     */
    public function onRoute(MvcEvent $event)
    {
        $request    = $event->getRequest();
        $router     = $event->getRouter();
        $routeMatch = $router->match($request);
        
        if ($routeMatch instanceof Router\RouteMatch) {
            $event->setRouteMatch($routeMatch);
            return $routeMatch;
        }
        
        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError(Application::ERROR_ROUTER_NO_MATCH);
        
        $target  = $event->getTarget();
        $results = $target->getEventManager()->triggerEvent($event);
        if (!empty($results)) {
            return $results->last();
        }
        
        return $event->getParams();
    }
}