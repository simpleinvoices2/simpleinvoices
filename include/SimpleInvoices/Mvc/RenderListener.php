<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use SimpleInvoices\View\Resolver\TemplatePathStack;

class RenderListener extends AbstractListenerAggregate
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
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER, [$this, 'render']);
    }
    
    public function render(MvcEvent $event)
    {
        $serviceManager = $event->getApplication()->getServiceManager();

        $renderer = new \SimpleInvoices\Smarty\Renderer($serviceManager);
        $renderer->setResolver( $serviceManager->get(TemplatePathStack::class) );
        $renderer->setMenu( $event->getMenuVisibility() );
        $renderer->render();
    }
}