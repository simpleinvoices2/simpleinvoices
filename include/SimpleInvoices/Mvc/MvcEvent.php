<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\Event;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

class MvcEvent extends Event
{
    const EVENT_BOOTSTRAP      = 'bootstrap';
    const EVENT_DISPATCH       = 'dispatch';
    const EVENT_DISPATCH_ERROR = 'dispatch.error';
    const EVENT_ROUTE          = 'route';
    const EVENT_RENDER         = 'render';
    const EVENT_AUTHORIZATION  = 'authorization';
    
    /**
     * @var Application
     */
    protected $application;
    
    protected $menuVisibility = true;
    
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var ResponseInterface
     */
    protected $response;
    
    /**
     * @var mixed
     */
    protected $result;
    
    /**
     * @var null|Router\RouteMatch
     */
    protected $routeMatch;
    
    /**
     * @var Router\Router
     */
    protected $router;
    
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
     * Retrieve the error message, if any
     *
     * @return string
     */
    public function getError()
    {
        return $this->getParam('error', '');
    }
    
    public function getMenuVisibility()
    {
        return $this->menuVisibility;
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
     * Get route match
     *
     * @return null|Router\RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->routeMatch;
    }
    
    /**
     * Get router
     *
     * @return Router\Router
     */
    public function getRouter()
    {
        return $this->router;
    }
    
    /**
     * Does the event represent an error response?
     *
     * @return bool
     */
    public function isError()
    {
        return (bool) $this->getParam('error', false);
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
     * Set the error message (indicating error in handling request)
     *
     * @param  string $message
     * @return MvcEvent
     */
    public function setError($message)
    {
        $this->setParam('error', $message);
        return $this;
    }
    
    public function setMenuVisibility($visible)
    {
        $this->menuVisibility = $visible;
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
    
    /**
     * Set route match
     *
     * @param Router\RouteMatch $matches
     * @return MvcEvent
     */
    public function setRouteMatch(Router\RouteMatch $matches)
    {
        $this->setParam('route-match', $matches);
        $this->routeMatch = $matches;
        return $this;
    }
    
    /**
     * Set router
     *
     * @param Router\Router $router
     * @return MvcEvent
     */
    public function setRouter(Router\Router $router)
    {
        $this->setParam('router', $router);
        $this->router = $router;
        return $this;
    }
    
    /**
     * Get the currently registered controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->getParam('controller');
    }
    
    /**
     * Get controller class
     *
     * @return string
     */
    public function getControllerClass()
    {
        return $this->getParam('controller-class');
    }
    
    /**
     * Get result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * Set controller name
     *
     * @param  string $name
     * @return MvcEvent
     */
    public function setController($name)
    {
        $this->setParam('controller', $name);
        return $this;
    }
    
    /**
     * Set controller class
     *
     * @param string $class
     * @return MvcEvent
     */
    public function setControllerClass($class)
    {
        $this->setParam('controller-class', $class);
        return $this;
    }
    
    /**
     * Set result
     *
     * @param mixed $result
     * @return MvcEvent
     */
    public function setResult($result)
    {
        $this->setParam('__RESULT__', $result);
        $this->result = $result;
        return $this;
    }
}