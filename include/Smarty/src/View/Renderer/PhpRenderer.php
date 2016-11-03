<?php
namespace SimpleInvoices\Smarty\View\Renderer;

use ArrayAccess;
use ArrayObject;
use SimpleInvoices\Smarty\View\Exception;
use Zend\View\Renderer\RendererInterface;
use Zend\View\Renderer\TreeRendererInterface;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Model\ModelInterface;
use SimpleInvoices\Smarty\View\Resolver\TemplatePathStack;

class PhpRenderer implements RendererInterface, TreeRendererInterface
{
    /**
     * @var string Rendered content
     */
    private $__content = '';
    
    /**
     * @var \Smarty
     */
    private $__engine;
    
    /**
     * Template being rendered
     *
     * @var null|string
     */
    private $__template = null;
    
    /**
     * Template resolver
     *
     * @var ResolverInterface
     */
    private $__templateResolver;
    
    /**
     * Queue of templates to render
     * @var array
     */
    private $__templates = [];

    /**
     * Constructor.
     * 
     * @param \Smarty $engine Smarty object.
     */
    public function __construct(\Smarty $engine)
    {
        $this->__engine = $engine;
    }
    
    /**
     * Add a template to the stack
     *
     * @param  string $template
     * @return PhpRenderer
     */
    public function addTemplate($template)
    {
        $this->__templates[] = $template;
        return $this;
    }
    
    /**
     * Indicate whether the renderer is capable of rendering trees of view models
     *
     * @see \Zend\View\Renderer\TreeRendererInterface::canRenderTrees()
     * @return bool
     */
    public function canRenderTrees()
    {
        
    }
    
    /**
     * Return the template engine object, if any
     *
     * If using a third-party template engine, such as Smarty, patTemplate,
     * phplib, etc, return the template engine object. Useful for calling
     * methods on these objects, such as for setting filters, modifiers, etc.
     *
     * @see \Zend\View\Renderer\RendererInterface::getEngine()
     * @return mixed
     */
    public function getEngine()
    {
        return $this->__engine;
    }
    
    /**
     * Set the resolver used to map a template name to a resource the renderer may consume.
     *
     * @see \Zend\View\Renderer\RendererInterface::setResolver()
     * @param  ResolverInterface $resolver
     * @return PhpRenderer
     */
    public function setResolver(ResolverInterface $resolver)
    {
        $this->__templateResolver = $resolver;
        return $this;
    }
    
    /**
     * Processes a view script and returns the output.
     *
     * @see \Zend\View\Renderer\RendererInterface::render()
     * @param  string|ModelInterface   $nameOrModel The script/resource process, or a view model
     * @param  null|array|\ArrayAccess $values      Values to use during rendering
     * @return string The script output.
     */
    public function render($nameOrModel, $values = null)
    {
        if ($nameOrModel instanceof ModelInterface) {
            $model       = $nameOrModel;
            $nameOrModel = $model->getTemplate();
            if (empty($nameOrModel)) {
                throw new Exception\DomainException(sprintf(
                    '%s: received View Model argument, but template is empty',
                    __METHOD__
                    ));
            }
            $options = $model->getOptions();
            foreach ($options as $setting => $value) {
                $method = 'set' . $setting;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
                unset($method, $setting, $value);
            }
            unset($options);
        
            // Give view model awareness via ViewModel helper
            //$helper = $this->plugin('view_model');
            //$helper->setCurrent($model);
        
            $values = $model->getVariables();
            if ($values instanceof ArrayObject) {
                $values = $values->getArrayCopy();
            }
            unset($model);
        }
        
        // find the script file name using the parent private method
        $this->addTemplate($nameOrModel);
        unset($nameOrModel); // remove $name from local scope
        
        if (null !== $values) {
            $this->setVars($values);
        }
        unset($values);
        
        while ($this->__template = array_pop($this->__templates)) {
            $this->__file = $this->resolver($this->__template);
            if (!$this->__file) {
                throw new Exception\RuntimeException(sprintf(
                    '%s: Unable to render template "%s"; resolver could not resolve to a file',
                    __METHOD__,
                    $this->__template
                ));
            }
            try {
                ob_start();
                $this->__engine->fetch($this->__file);
                $this->__content = ob_get_clean();
            } catch (\Exception $ex) {
                ob_end_clean();
                throw $ex;
            }
            //if ($includeReturn === false && empty($this->__content)) {
            //    throw new Exception\UnexpectedValueException(sprintf(
            //        '%s: Unable to render template "%s"; Smarty::fetch() failed',
            //        __METHOD__,
            //        $this->__file
            //    ));
            //}
        }
        
        return $this->__content;
    }
    
    /**
     * Retrieve template name or template resolver
     *
     * @param  null|string $name
     * @return string|Resolver
     */
    public function resolver($name = null)
    {
        if (null === $this->__templateResolver) {
            $this->setResolver(new TemplatePathStack());
        }
    
        if (null !== $name) {
            return $this->__templateResolver->resolve($name, $this);
        }
    
        return $this->__templateResolver;
    }
    
    /**
     * Set variable storage
     *
     * Expects either an array, or an object implementing ArrayAccess.
     *
     * @param  array|ArrayAccess $variables
     * @return PhpRenderer
     * @throws Exception\InvalidArgumentException
     */
    public function setVars($variables)
    {
        if (!is_array($variables)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expected array; received "%s"',
                (is_object($variables) ? get_class($variables) : gettype($variables))
            ));
        }
    
        $this->__engine->assign($variables);
        
        return $this;
    }
    
    /**
     * Get a single variable, or all variables
     *
     * @param  mixed $key
     * @return mixed
     */
    public function vars($key = null)
    {
        if (null === $key) {
            if (method_exists($this->__engine, 'getTemplateVars')) {
                return $this->__engine->getTemplateVars();
            } elseif (method_exists($this->__engine, 'get_template_vars')) {
                return $this->__engine->get_template_vars();
            } else {
                throw new Exception\RuntimeException('Smarty does not support getting template variables.');
            }
        }
        
        if (method_exists($this->__engine, 'getTemplateVars')) {
            return $this->__engine->getTemplateVars($key);
        } elseif (method_exists($this->__engine, 'get_template_vars')) {
            return $this->__engine->get_template_vars($key);
        } else {
            throw new Exception\RuntimeException('Smarty does not support getting template variables.');
        }
    }
}