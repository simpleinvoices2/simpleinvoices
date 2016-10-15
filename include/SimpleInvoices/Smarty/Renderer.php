<?php
namespace SimpleInvoices\Smarty;

use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\View\Resolver\ResolverInterface;

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
    
    public function __construct(ServiceLocatorInterface $serviceManager)
    {
        global $menu; // TODO: Get rid of this!
        
        $this->serviceManager = $serviceManager;
        $this->smarty = $serviceManager->get('Smarty');
        $this->config = $serviceManager->get('SimpleInvoices\Config');
        
        $this->setMenu($menu);
        
        $this->moduleName = isset($_GET['module']) ? $this->filenameEscape($_GET['module'])  : null;
        $this->viewName   = isset($_GET['view'])   ? $this->filenameEscape($_GET['view'])    : null;
        $this->actionName = isset($_GET['case'])   ? $this->filenameEscape($_GET['case'])    : null;
        
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
    
    /**
     * Escapes a filename
     *
     * @param string $str The string to escape
     * @return string The escaped string
     */
    protected function filenameEscape($str)
    {
        // Returns an escaped value.
        $safe_str = preg_replace('/[^a-z0-9\-_\.]/i','_',$str);
        return $safe_str;
    }
    
    /*
     GetCustomPath: override template or module with custom one if it exists, else return default path if it exists
     ---------------------------------------------
     @name: name or dir/name of the module or template (without extension)
     @mode: template or module
     */
    protected function getCustomPath($name, $mode='template'){
        $my_custom_path="./custom/";
        $use_custom=1;
        if($mode=='template'){
            if($use_custom and file_exists("{$my_custom_path}default_template/{$name}.tpl")){
                $out=".{$my_custom_path}default_template/{$name}.tpl";
            }
            elseif(file_exists("./templates/default/{$name}.tpl")){
                $out="../templates/default/{$name}.tpl";
            }
        }
        if($mode=='module'){
            if($use_custom and file_exists("{$my_custom_path}modules/{$name}.php")){
                $out="{$my_custom_path}modules/{$name}.php";
            }
            elseif(file_exists("./modules/{$name}.php")){
                $out="./modules/{$name}.php";
            }
        }
        return $out;
    }
    
    public function render()
    {
        if (strcmp($this->moduleName, 'export') === 0) {
            $this->output = "fetch";
        }
        
        // TODO: We should load extension jQuery files in a nicer way
        $extension_jquery_files = "";
        foreach($this->config->extension as $extension) {
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
     * @return RendererInterface
     */
    public function setResolver(ResolverInterface $resolver)
    {
        $this->__templateResolver = $resolver;
        return $this;
    }
}