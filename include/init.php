<?php
/**
 * Composer autoloader
 */
require_once './vendor/autoload.php';

/**
 * Load configuration
 * 
 * Differences from ZF1
 *
 * The Zend\Config\Reader component no longer supports the following features:
 * 
 *     + Inheritance of sections.
 *     + Reading of specific sections.
 *     
 */
include_once('./config/define.php');
$reader = new \Zend\Config\Reader\Ini();
if( is_file('./config/custom.config.php') ){
    $config = $reader->fromFile('./config/custom.config.php');
    $config = new \Zend\Config\Config($config['production'], true);
} else {
    $config = $reader->fromFile('./config/config.php');
    $config = new \Zend\Config\Config($config['production'], true);
}

/**
 * Service manager
 */
$serviceManager = new \Zend\ServiceManager\ServiceManager([
    'factories' => [
        'Smarty' => \SimpleInvoices\Service\SmartyFactory::class,
        'SimpleInvoices\Permission\Acl' => \SimpleInvoices\Service\AclFactory::class,
        'Request' => \SimpleInvoices\Service\RequestFactory::class,
        'Response' => \SimpleInvoices\Service\ResponseFactory::class,
        'SimpleInvoices\EventManager' => \SimpleInvoices\Service\EventManagerFactory::class,
        'SimpleInvoices\Router' => \SimpleInvoices\Service\RouterFactory::class,
        'SimpleInvoices\Logger' => \SimpleInvoices\Service\LoggerFactory::class,
        'SimpleInvoices\SqlQueries' => \SimpleInvoices\Service\SqlQueriesFactory::class,
        'SimpleInvoices\Database\Adapter' => \SimpleInvoices\Service\DatabaseFactory::class,
        \SimpleInvoices\SystemDefault\SystemDefaultManager::class => \SimpleInvoices\Service\SystemDefaultManagerFactory::class,
        \SimpleInvoices\View\Resolver\TemplatePathStack::class => \SimpleInvoices\Service\ViewTemplatePathStackFactory::class,
        \Zend\Session\SessionManager::class => \SimpleInvoices\Service\SessionManagerFactory::class,
        \SimpleInvoices\Authentication\AuthenticationService::class => \SimpleInvoices\Service\AuthenticationServiceFactory::class,
        'SimpleInvoices\Mail\TransportInterface' => \SimpleInvoices\Service\MailTransportFactory::class,
        'SimpleInvoices\ModuleManager' => \SimpleInvoices\Service\ModuleManagerFactory::class,
        'SimpleInvoices\PatchManager' => \SimpleInvoices\Service\PatchManagerFactory::class,
    ],
]);

// ... add the configuration to the service manager
$serviceManager->setService('SimpleInvoices\Config', $config);

/**
 * Initialize the application and store it in the service manager.
 */
$application = new \SimpleInvoices\Mvc\Application($serviceManager, $serviceManager->get('SimpleInvoices\EventManager'));
$serviceManager->setService('SimpleInvoices', $application);

/**
 * Bootstrap the application
 */
$application->bootstrap();

/**
 * TODO: Really it should be $application->run() but since code has not been
 *       completelly refactored we need to split the run method in half :( 
 */
$application->runFirst();

/**
 * Backward compatibility
 * 
 * These are things that have changed but still not fully 
 * refactored.
 */
$routeMatch        = $application->getMvcEvent()->getRouteMatch();
$module            = $routeMatch->getParam('module', null);
$view              = $routeMatch->getParam('view', null);
$action            = $routeMatch->getParam('action', null);
$config->extension = $serviceManager->get('SimpleInvoices\ModuleManager')->getModules();

$auth_session = new \Zend\Session\Container('SI_AUTH');
$smarty       = $serviceManager->get('Smarty');

// TODO: This supports old code, should find a better way
$menu         = true;

/**
 * Old stuff follows...
 */

/* 
 * Smarty inint - start
 */

#ini_set('display_errors',true);

include_once('./include/functions.php');

//ob_start('addCSRFProtection');

if (!is_writable('./tmp')) {
    
   simpleInvoicesError('notWriteable','directory','./tmp');
}

/**
 * Logger
 */
$logger = $serviceManager->get('SimpleInvoices\Logger');

/*
 * log file - end
 */

if (!is_writable('./tmp/cache')) {
    
   simpleInvoicesError('notWriteable','file','./tmp/cache');
}


//cache directory. Have to be writeable (chmod 777)
//$smarty->compile_dir = "tmp/cache";
if(!is_writable($smarty -> compile_dir)) {
	simpleInvoicesError("notWriteable", 'folder', $smarty -> compile_dir);
	//exit("Simple Invoices Error : The folder <i>".$smarty -> compile_dir."</i> has to be writeable");
}

$path = pathinfo($_SERVER['REQUEST_URI']);
//SC: Install path handling will need changes if used in non-HTML contexts
$install_path = htmlsafe($path['dirname']);

//set up app with relevant php setting
date_default_timezone_set($config->phpSettings->date->timezone);
error_reporting($config->debug->error_reporting);
ini_set('display_startup_errors', $config->phpSettings->display_startup_errors);  
ini_set('display_errors', $config->phpSettings->display_errors); 
ini_set('log_errors', $config->phpSettings->log_errors); 
ini_set('error_log', $config->phpSettings->error_log); 

//include_once("./include/sql_patches.php");

$db = \SimpleInvoices\Deprecate\Db::getInstance();

include_once("./include/sql_queries.php");
 
include_once('./include/language.php');


include_once('./include/manageCustomFields.php');
include_once("./include/validation.php");
