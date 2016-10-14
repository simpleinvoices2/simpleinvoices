<?php
namespace SimpleInvoices\Mvc\Router;

use Zend\Stdlib\RequestInterface as Request;

class Router implements RouteInterface
{
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
        
        $module = $this->filenameEscape($request->getQuery('module', null));
        $view   = $this->filenameEscape($request->getQuery('view', null));
        $action = $this->filenameEscape($request->getQuery('case', null));

        $tempModule = trim($module);
        if (empty($tempModule)) {
            return null;
        }
        
        return new RouteMatch([
            'module' => $module,
            'view'   => $view,
            'action' => $action,
        ]);
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