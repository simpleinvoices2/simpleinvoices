<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SmartyFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $smarty = new \Smarty();
        
        $smarty->debugging = false;
        
        $smarty->compile_dir = "tmp/cache";
        
        //if(!is_writable($smarty->compile_dir)) {
        //    simpleInvoicesError("notWriteable", 'folder', $smarty -> compile_dir);
        //    //exit("Simple Invoices Error : The folder <i>".$smarty -> compile_dir."</i> has to be writeable");
        //}
        
        //adds own smarty plugins
        $smarty->plugins_dir = array("plugins","include/smarty_plugins");
        
        //add stripslash smarty function
        $smarty->register_modifier("unescape", "stripslashes");
        
        $smarty->register_modifier("siLocal_number", array("siLocal", "number"));
        $smarty->register_modifier("siLocal_number_clean", array("siLocal", "number_clean"));
        $smarty->register_modifier("siLocal_number_trim", array("siLocal", "number_trim"));
        $smarty->register_modifier("siLocal_number_formatted", array("siLocal", "number_formatted"));
        $smarty->register_modifier("siLocal_date", array("siLocal", "date"));
        $smarty->register_modifier('htmlsafe', 'htmlsafe');
        $smarty->register_modifier('urlsafe', 'urlsafe');
        $smarty->register_modifier('urlencode', 'urlencode');
        $smarty->register_modifier('outhtml', 'outhtml');
        $smarty->register_modifier('htmlout', 'outhtml'); //common typo
        $smarty->register_modifier('urlescape', 'urlencode'); //common typo
        
        return $smarty;
    }
    
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'Smarty');
    }
}