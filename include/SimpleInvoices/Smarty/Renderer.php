<?php
namespace SimpleInvoices\Smarty;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Resolver\ResolverInterface;
use SimpleInvoices\Mvc\Router\RouteMatch;

class Renderer
{
    /**
     * @var ResolverInterface
     */
    protected $__templateResolver;
    
    protected $early_exit = [];
    
    protected $output = "display";
    
    protected $serviceManager;
    
    protected $moduleName = null;
    
    protected $viewName = null;
    
    protected $actionName = null;

    protected $smarty = null;
    
    protected $config = null;
    
    protected $menu = true;
    
    protected $routeMatch = null;
    
    public function __construct(ServiceLocatorInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->smarty         = $serviceManager->get('Smarty');
        $this->config         = $serviceManager->get('SimpleInvoices\Config');
        
        $application          = $serviceManager->get('SimpleInvoices');
        $event                = $application->getMvcEvent();
        $this->routeMatch     = $event->getRouteMatch();
        
        if ($this->routeMatch instanceof RouteMatch) {
            $this->moduleName = $this->routeMatch->getParam('module');
            $this->viewName   = $this->routeMatch->getParam('view');
            $this->actionName = $this->routeMatch->getParam('action');
        }
        
        $this->early_exit = [
            "auth_login",
            "api_cron",
            "auth_logout",
            "export_pdf",
            "export_invoice",
            "statement_export",
            "invoice_template",
            "payments_print",
            "documentation_view",
        ];
    }
    
    public function render()
    {
        if (!$this->routeMatch instanceof RouteMatch) {
            return;
        }
        
        if (strcmp($this->moduleName, 'export') === 0) {
            $this->output = "fetch";
        }
        
        $extensions = $this->serviceManager->get('ModuleManager')->getModules();
        
        // TODO: We should load extension jQuery files in a nicer way
        $extension_jquery_files = "";
        foreach($extensions as $extension) {
            /*
             * If extension is enabled then continue and include the requested file for that extension if it exists
             */
            if($extension->enabled == "1")
            {
                if(file_exists("./extensions/$extension->name/include/jquery/$extension->name.jquery.ext.js")) {
                    $extension_jquery_files .= "<script type=\"text/javascript\" src=\"./extensions/$extension->name/include/jquery/$extension->name.jquery.ext.js\"></script>";
                }
            }
        }
        $this->smarty->assign('extension_jquery_files', $extension_jquery_files);
        // End of extension jQuery files
        
        if( !in_array($this->moduleName . "_" . $this->viewName, $this->early_exit) ) {
            $template = $this->__templateResolver->resolve('header');
            if ($template) {
                $this->smarty->{$this->output}($template);
            }
        }
        
        // HERE was the dispatch code!!!
        
        if($this->moduleName == "export" || $this->viewName == "export" || $this->moduleName == "api") {
            exit(0);
        }
        
        $template = $this->__templateResolver->resolve('jquery/post_load_jquery');
        if ($template) {
            $this->smarty->{$this->output}($template);
        }
        
        if ($this->menu) {
            $template = $this->__templateResolver->resolve('menu');
            if ($template) {
                $this->smarty->{$this->output}($template);
            }
        }
        
        if( !in_array($this->moduleName . "_" . $this->viewName, $this->early_exit) ) {
            $template = $this->__templateResolver->resolve('main');
            if ($template) {
                $this->smarty->{$this->output}($template);
            }
        }
        
        //$this->renderPageSection();
        // --- page section: start
        $template = $this->__templateResolver->resolve($this->moduleName . '/' . $this->viewName);
        if ($template) {
            $path = dirname($template);
            $this->smarty->assign('path', $path);
            $this->smarty->{$this->output}($template);
        }
        
        // --- page section: end
        
        if( !in_array($this->moduleName . "_" . $this->viewName, $this->early_exit) ) {
            $template = $this->__templateResolver->resolve('footer');
            if ($template) {
                $this->smarty->{$this->output}($template);
            }
        }
    }
    
    /**
     * Sets the menu as visible or hidden.
     * 
     * @param bool $enabled
     */
    public function setMenu($enabled)
    {
        $this->menu = $enabled;
        return $this;
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
}