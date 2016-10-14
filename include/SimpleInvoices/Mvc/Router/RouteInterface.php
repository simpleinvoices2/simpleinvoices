<?php
namespace SimpleInvoices\Mvc\Router;

use Zend\Stdlib\RequestInterface as Request;

/**
 * RouteInterface interface.
 */
interface RouteInterface
{
    /**
     * Match a given request.
     *
     * @param  Request $request
     * @return RouteMatch|null
     */
    public function match(Request $request);

    /**
     * Assemble the route.
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = [], array $options = []);
}
