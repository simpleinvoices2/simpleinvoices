<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SmartyView\Strategy;

use SmartyView\Renderer\PhpRenderer;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\View\ViewEvent;
use Zend\View\Renderer\RendererInterface;

class PhpRendererStrategy extends AbstractListenerAggregate
{
    /**
     * @var PhpRenderer
     */
    protected $renderer;

    /**
     * Constructor
     *
     * @param  PhpRenderer $renderer
     */
    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Retrieve the composed renderer
     *
     * @return PhpRenderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
    
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, [$this, 'selectRenderer'], $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, [$this, 'injectResponse'], $priority);
    }

    /**
     * Select the PhpRenderer; typically, this will be registered last or at
     * low priority.
     *
     * @param  ViewEvent $e
     * @return PhpRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        return $this->renderer;
    }

    /**
     * Populate the response object from the View
     *
     * Populates the content of the response object from the view rendering
     * results.
     *
     * @param ViewEvent $e
     * @return void
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            return;
        }

        $result   = $e->getResult();
        $response = $e->getResponse();

        $response->setContent($result);
    }
}
