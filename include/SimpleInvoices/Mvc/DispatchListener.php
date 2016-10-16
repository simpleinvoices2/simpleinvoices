<?php
namespace SimpleInvoices\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

class DispatchListener extends AbstractListenerAggregate
{
    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     * @param int                   $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch']);
    }
    
    /*
     GetCustomPath: override template or module with custom one if it exists, else return default path if it exists
     ---------------------------------------------
     @name: name or dir/name of the module or template (without extension)
     @mode: template or module
     */
    
    protected function getCustomPath($name,$mode='template')
    {
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
    
    /**
     * Listen to the "dispatch" event
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $config     = $e->getApplication()->getConfig();
        $routeMatch = $e->getRouteMatch();
        $module     = $routeMatch->getParam('module', null);
        $view       = $routeMatch->getParam('view', null);
        $action     = $routeMatch->getParam('action', null);
        
        /**
         * Backward compatibility
         */
        global $LANG;
        $services     = $e->getApplication()->getServiceManager();
        $smarty       = $services->get('Smarty');
        $auth_session = new \Zend\Session\Container('Zend_Auth');
        $logger       = $services->get('SimpleInvoices\Logger');
        $menu         = $e->getMenuVisibility();
        
        /**
         * Not really backward compatibility but new functionality
         * until it is fully refactored
         */
        $sqlQueries = $services->get('SimpleInvoices\SqlQueries');
        
        // =============================================================================================
        //                               S T A R T   D I S P A T C H I N G
        // =============================================================================================
        
        /**
         * ==============================================================================================
         * ---------------------------------------- START ---------------------------------------------
         * dont include the header if requested file is an invoice template - for print preview etc.. header is not needed
         */
        if (($module == "invoices" ) && (strstr($view, "template"))) {
            /*
             * If extension is enabled load the extension php file for the module
             * Note: this system is probably slow - if you got a better method for handling extensions let me know
             */
            $extensionInvoiceTemplateFile = 0;
            foreach($config->extension as $extension)
            {
                /*
                 * If extension is enabled then continue and include the requested file for that extension if it exists
                 */
                if($extension->enabled == "1") {
                    //echo "Enabled:".$value['name']."<br><br>";
                    if(file_exists("./extensions/$extension->name/modules/invoices/template.php")) {
                        include_once("./extensions/$extension->name/modules/invoices/template.php");
                        $extensionInvoiceTemplateFile++;
                    }
                }
            }
        
            /*
             * If no extension php file for requested file load the normal php file if it exists
             */
            if( ($extensionInvoiceTemplateFile == 0) AND ($my_path = $this->getCustomPath("invoices/template", 'module') ) )  {
                /* (soif) This /modules/invoices/template.php is empty : Should we really keep it? */
                include_once($my_path);
            }
        
            exit(0);
        }
        
        /**
         * ----------------------------------------- END ------------------------------------------------
         * ==============================================================================================
         */
        
        /**
         *  ==============================================================================================
         *  ---------------------------------------- START ---------------------------------------------
         * xml or ajax page requeset - start
         */
        
        if( strstr($module, "api") OR (strstr($view,"xml") OR (strstr($view, "ajax")) ) )
        {
            $extensionXml = 0;
            foreach($config->extension as $extension)
            {
                /*
                 * If extension is enabled then continue and include the requested file for that extension if it exists
                 */
                if($extension->enabled == "1")
                {
                    if(file_exists("./extensions/$extension->name/modules/$module/$view.php"))
                    {
                        include("./extensions/$extension->name/modules/$module/$view.php");
                        $extensionXml++;
                    }
                }
            }
        
            /*
             * If no extension php file for requested file load the normal php file if it exists
             */
            if ( ($extensionXml == 0) and ($my_path = $this->getCustomPath("$module/$view", 'module')) ) {
                include($my_path);
            }
        
            exit(0);
        }
        
        /**
         * xml or ajax page request - end
         * ----------------------------------------- END ------------------------------------------------
         * ==============================================================================================
         */
        
        /**
         *  ==============================================================================================
         *  ---------------------------------------- START ---------------------------------------------
         * If extension is enabled load its javascript files	- start
         * Note: this system is probably slow - if you got a better method for handling extensions let me know
         */
        $extension_jquery_files = "";
        
        foreach($config->extension as $extension)
        {
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
        
        $smarty->assign("extension_jquery_files", $extension_jquery_files);
        
        /**
         * If extension is enabled load its javascript files	- end
         * ----------------------------------------- END ------------------------------------------------
         * ==============================================================================================
         */
        
        /**
         * ==============================================================================================
         * * ---------------------------------------- START ---------------------------------------------
         * Include the php file for the requested page section - start
         */
        
        /*
         * If extension is enabled load the extension php file for the module
         * Note: this system is probably slow - if you got a better method for handling extensions let me know
         */
        $extensionPHPFile = 0;
        foreach($config->extension as $extension)
        {
            /*
             * If extension is enabled then continue and include the requested file for that extension if it exists
             */
            if($extension->enabled == "1")
            {
                if(file_exists("./extensions/$extension->name/modules/" . $module . '/' . $view . ".php")) {
        
                    include_once("./extensions/$extension->name/modules/" . $module . '/' . $view . ".php");
                    $extensionPHPFile++;
                }
            }
        }
        
        /*
         * If no extension php file for requested file load the normal php file if it exists
         */
        if ( ($extensionPHPFile == 0) &&  ($my_path = $this->getCustomPath($module . '/' . $view, 'module')) ) {
            include($my_path);
        }
        
        /**
         * Include the php file for the requested page section - end
         * ----------------------------------------- END ------------------------------------------------
         * ==============================================================================================
         */
        

        /**
         * Backward compatibility
         */
        $e->setMenuVisibility($menu);
    }
}