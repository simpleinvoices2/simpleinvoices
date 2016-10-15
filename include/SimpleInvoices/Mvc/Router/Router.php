<?php
namespace SimpleInvoices\Mvc\Router;

use Zend\Stdlib\RequestInterface as Request;

class Router implements RouteInterface
{
    protected $defaults = [];
    
    public function __construct($defaults = [])
    {
        if (empty($defaults)) {
            $defaults = [
                'module' => 'index',
                'view'   => 'index',
                'action' => null,
            ];
        }
        
        $this->defaults = $defaults;
    }
    /**
     * Escapes a filename
     *
     * @param string $str The string to escape
     * @return string     The escaped string
     */
    protected function filenameEscape($str)
    {
        if ( empty($str)) {
            return null;
        }
        
        // Returns an escaped value.
        $safe_str = preg_replace('/[^a-z0-9\-_\.]/i','_',$str);
        return $safe_str;
    }
    
    /**
     * Match a given request.
     *
     * @param  Request $request
     * @return RouteMatch|null
     */
    public function match(Request $request)
    {
        if (!$request instanceof \Zend\Http\Request) {
            return null;
        }
        
        $params = [];
        
        $params['module'] = $this->filenameEscape($request->getQuery('module', null));
        $params['view']   = $this->filenameEscape($request->getQuery('view', null));
        $params['action'] = $this->filenameEscape($request->getQuery('case', null));

        if (null === $params['module']) {
            $params['module'] = $this->defaults['module'];
        }
        
        if (null === $params['view']) {
            $params['view'] = $this->defaults['view'];
        }
        
        /** 
         * Backward compatibility
         * 
         * Some parts of the code get direct access to the $_GET variable
         * this shouldn't happen as it could be leading to cross-directory 
         * vulnerabilities. But, since as this happens I simple change
         * the $_GET variable to keep things cleaner.
         */
        $_GET['module'] = $params['module'];
        $_GET['view']   = $params['view'];
        if (!empty($params['action'])) {
            $_GET['case'] = $params['action'];
        } else {
            unset($_GET['case']);
        }

        return new RouteMatch($params);
    }
    
    /**
     * Assemble the route.
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = [], array $options = [])
    {
        
    }
}