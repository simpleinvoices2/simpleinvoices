<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\Event;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

class MvcEvent extends Event
{
    const EVENT_BOOTSTRAP      = 'bootstrap';
    const EVENT_DISPATCH       = 'dispatch';
    
    /**
     * @var Application
     */
    protected $application;
    
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var ResponseInterface
     */
    protected $response;
    
    /**
     * Get application instance
     *
     * @return ApplicationInterface
     */
    public function getApplication()
    {
        return $this->application;
    }
    
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
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
    
    /**
     * Set application instance
     *
     * @param  ApplicationInterface $application
     * @return MvcEvent
     */
    public function setApplication(ApplicationInterface $application)
    {
        $this->setParam('application', $application);
        $this->application = $application;
        return $this;
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
    
    /**
     * Set response
     *
     * @param ResponseInterface $response
     * @return MvcEvent
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->setParam('response', $response);
        $this->response = $response;
        return $this;
    }
}