<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\Event;
use Zend\Stdlib\RequestInterface;

class MvcEvent extends Event
{
    const EVENT_DISPATCH = 'dispatch';
    
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * Get request
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * Set request
     *
     * @param RequestInterface $request
     * @return MvcEvent
     */
    public function setRequest(RequestInterface $request)
    {
        $this->setParam('request', $request);
        $this->request = $request;
        return $this;
    }
}