<?php
namespace SimpleInvoices\Smarty;

use Zend\ServiceManager\ServiceLocatorInterface;

class Renderer
{
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
        
        $this->renderHeader();
        
        // HERE was the dispatch code!!!
        
        if($this->moduleName == "export" || $this->viewName == "export" || $this->moduleName == "api") {
            exit(0);
        }
        
        $this->renderJavascript();
        $this->renderMenu();
        $this->renderMain();
        $this->renderPageSection();
        $this->renderFooter();
    }
    
    public function renderHeader()
    {
        if( !in_array($this->moduleName . "_" . $this->viewName, $this->early_exit) )
        {
            $extensionHeader = 0;
            foreach($this->config->extension as $extension)
            {
                /*
                 * If extension is enabled then continue and include the requested file for that extension if it exists
                 */
                if($extension->enabled == "1")
                {
                    if(file_exists("./extensions/$extension->name/templates/default/header.tpl"))
                    {
                        $this->smarty->{$this->output}("../extensions/$extension->name/templates/default/header.tpl");       
                        $extensionHeader++;
                    }
                }
            }
        
            /*
             * If no extension php file for requested file load the normal template file if it exists
             */
            if($extensionHeader == 0)
            {
                $this->smarty->{$this->output}($this->getCustomPath('header'));
            }
        }
    }
    
    /**
     * If extension is enabled load its post load javascript files	- start
     * By Post load - i mean post of the .php so that it can used info from the .php in the javascript
     * Note: this system is probably slow - if you got a better method for handling extensions let me know
     */
    public function renderJavascript()
    {
        $extensionPostLoadJquery = 0;
        foreach($this->config->extension as $extension)
        {
            /*
             * If extension is enabled then continue and include the requested file for that extension if it exists
             */
            if($extension->enabled == "1")
            {
                if(file_exists("./extensions/$extension->name/include/jquery/$extension->name.post_load.jquery.ext.js.tpl")) {
                    $this->smarty->{$this->output}("../extensions/$extension->name/include/jquery/$extension->name.post_load.jquery.ext.js.tpl");
                }
            }
        }
        
        /*
         * If no extension php file for requested file load the normal php file if it exists
         * Don't load it in the authentication module. It's not needed! Generates wrong HTML code.
         */
        if (($extensionPostLoadJquery == 0) && ($this->moduleName !='auth'))
        {
            $this->smarty->{$this->output}("../public/assets/jquery/post_load.jquery.ext.js.tpl");
        }
    }
    
    public function renderMenu()
    {
        if($this->menu)
        {
            $extensionMenu = 0;
            foreach($this->config->extension as $extension)
            {
                /*
                 * If extension is enabled then continue and include the requested file for that extension if it exists
                 */
                if($extension->enabled == "1")
                {
                    if(file_exists("./extensions/$extension->name/templates/default/menu.tpl"))
                    {
                        $this->smarty->{$this->output}("../extensions/$extension->name/templates/default/menu.tpl");
                        $extensionMenu++;
                    }
                }
            }
            
            /*
             * If no extension php file for requested file load the normal php file if it exists
             */
            if ($extensionMenu == "0")
            {
                $this->smarty->{$this->output}($this->getCustomPath('menu'));
            }
        }    
    }
    
    public function renderMain()
    {
        if( !in_array($this->moduleName . "_" . $this->viewName, $this->early_exit) )
        {
            $extensionMain = 0;
            foreach($this->config->extension as $extension)
            {
                /*
                 * If extension is enabled then continue and include the requested file for that extension if it exists
                 */
                if($extension->enabled == "1")
                {
                    if(file_exists("./extensions/$extension->name/templates/default/main.tpl"))
                    {
                        $this->smarty->{$this->output}("../extensions/$extension->name/templates/default/main.tpl");
                        $extensionMain++;
                    }
                }
            }
        
            /*
             * If no extension php file for requested file load the normal php file if it exists
             */
            if($extensionMain == "0")
            {
                $this->smarty->{$this->output}($this->getCustomPath('main'));
            }
        }
    }
    
    public function renderPageSection()
    {
        /*
         * If no extensions template is applicable then show the default one
         * use the $extensionTemplates variable to count the number of applicable extensions template
         * --if = 0 after checking all extensions then show default
         */
        $extensionTemplates = 0;
        $my_tpl_path = '';
        foreach($this->config->extension as $extension)
        {
            /*
             * If extension is enabled then continue and include the requested file for that extension if it exists
             */
            if($extension->enabled == "1")
            {
                if(file_exists("./extensions/$extension->name/templates/default/" . $this->moduleName . "/" . $this->viewName . ".tpl"))
                {
                    $path 		= "../extensions/$extension->name/templates/default/" . $this->moduleName . "/";
                    $my_tpl_path="../extensions/{$extension->name}/templates/default/" . $this->moduleName . "/" . $this->viewName . ".tpl";
                    $extensionTemplates++;
                }
            }
        }
        
        /*
         * If no application templates found then show default template
         * TODO Note: if more than one extension has got a template for the requested file than thats trouble :(
         * - we really need a better extensions system
         */  
        if( $extensionTemplates == 0 )
        {
            if ($my_tpl_path = $this->getCustomPath($this->moduleName . '/' . $this->viewName)) {
                $path = dirname($my_tpl_path) . '/';
                $extensionTemplates++;
            }
        }
        
        $this->smarty->assign("path", $path);
        $this->smarty->{$this->output}($my_tpl_path);
        
        // If no smarty template - add message - onyl uncomment for dev - commented out for release
        if ($extensionTemplates == 0 )
        {
            error_log("NOTEMPLATE!!!");
        }
    }
    
    public function renderFooter()
    {
        if( !in_array($this->moduleName . "_" . $this->viewName, $this->early_exit) )
        {
            $extensionFooter = 0;
            foreach($this->config->extension as $extension)
            {
                /*
                 * If extension is enabled then continue and include the requested file for that extension if it exists
                 */
                if($extension->enabled == "1")
                {
                    if(file_exists("./extensions/$extension->name/templates/default/footer.tpl"))
                    {
                        $this->smarty->{$this->output}("../extensions/$extension->name/templates/default/footer.tpl");
                        $extensionFooter++;
                    }
                }
            }
            
            /*
             * If no extension php file for requested file load the normal php file if it exists
             */
            if($extensionFooter == 0) {
                $this->smarty->{$this->output}($this->getCustomPath('footer'));
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
}