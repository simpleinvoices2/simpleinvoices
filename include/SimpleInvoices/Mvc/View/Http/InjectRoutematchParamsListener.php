<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SimpleInvoices\Mvc\View\Http;

use SimpleInvoices\Mvc\MvcEvent;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Request as HttpRequest;

class InjectRoutematchParamsListener extends AbstractListenerAggregate
{
    /**
     * Should request params overwrite existing request params?
     *
     * @var bool
     */
    protected $overwrite = true;

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'injectParams'], 90);
    }

    /**
     * Take parameters from RouteMatch and inject them into the request.
     *
     * @param  MvcEvent $e
     * @return void
     */
    public function injectParams(MvcEvent $e)
    {
        $routeMatchParams = $e->getRouteMatch()->getParams();
        $request = $e->getRequest();

        if (! $request instanceof HttpRequest) {
            // unsupported request type
            return;
        }

        $params = $request->get();

        if ($this->overwrite) {
            // Overwrite existing parameters, or create new ones if not present.
            foreach ($routeMatchParams as $key => $val) {
                $params->$key = $val;
            }
            return;
        }

        // Only create new parameters.
        foreach ($routeMatchParams as $key => $val) {
            if (! $params->offsetExists($key)) {
                $params->$key = $val;
            }
        }
    }

    /**
     * Should RouteMatch parameters replace existing Request params?
     *
     * @param  bool $overwrite
     */
    public function setOverwrite($overwrite)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * @return bool
     */
    public function getOverwrite()
    {
        return $this->overwrite;
    }
}
