<?php
namespace SimpleInvoices\Smarty\View;

use Zend\View\Renderer\RendererInterface;
use Zend\View\Resolver\ResolverInterface;

class PhpRenderer implements RendererInterface
{
    /**
     * @var \Smarty
     */
    protected $__engine;
    
    /**
     * Template resolver
     *
     * @var ResolverInterface
     */
    protected $__templateResolver;
    
    public function __construct(\Smarty $engine)
    {
        $this->__engine = $engine;
    }
    
    /**
     * Return the template engine object, if any
     *
     * If using a third-party template engine, such as Smarty, patTemplate,
     * phplib, etc, return the template engine object. Useful for calling
     * methods on these objects, such as for setting filters, modifiers, etc.
     *
     * @return mixed
     */
    public function getEngine()
    {
        return $this->__engine;
    }
    
    /**
     * Set the resolver used to map a template name to a resource the renderer may consume.
     *
     * @param  ResolverInterface $resolver
     * @return Renderer
     */
    public function setResolver(ResolverInterface $resolver)
    {
        $this->__templateResolver = $resolver;
        return $this;
    }
    
    /**
     * Processes a view script and returns the output.
     *
     * @param  string|ModelInterface   $nameOrModel The script/resource process, or a view model
     * @param  null|array|\ArrayAccess $values      Values to use during rendering
     * @return string The script output.
     */
    public function render($nameOrModel, $values = null)
    {
        
    }
}