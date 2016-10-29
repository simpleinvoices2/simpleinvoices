<?php
use SimpleInvoices\SystemDefault\SystemDefaultManager;
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
 * Non-refactored code
 */
include_once('./include/functions.php');
include_once("./include/sql_queries.php");
include_once('./include/manageCustomFields.php');
include_once("./include/validation.php");

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
        \Zend\I18n\Translator\TranslatorInterface::class => \SimpleInvoices\Service\TranslatorServiceFactory::class,
        \Zend\I18n\Translator\LoaderPluginManager::class => \Zend\I18n\Translator\LoaderPluginManagerFactory::class,
        \SimpleInvoices\SystemDefault\SystemDefaultManager::class => \SimpleInvoices\SystemDefault\Service\SystemDefaultManagerServiceFactory::class,
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
 * We need to remove globals and use the service manager and events to replace 
 * them.
 * 
 * These are things that have changed but still not fully 
 * refactored.
 */
$routeMatch        = $application->getMvcEvent()->getRouteMatch();
$module            = $routeMatch->getParam('module', null);
$view              = $routeMatch->getParam('view', null);
$action            = $routeMatch->getParam('action', null);
//$config->extension = $serviceManager->get('SimpleInvoices\ModuleManager')->getModules();

$smarty            = $serviceManager->get('Smarty');
$logger            = $serviceManager->get('SimpleInvoices\Logger');

$systemDefaults    = $serviceManager->get(SystemDefaultManager::class);
$LANG              = $serviceManager->get(\Zend\I18n\Translator\TranslatorInterface::class)->getAllMessages('default', $systemDefaults->get('language', 'en_GB'))->getArrayCopy();

$sqlQueries        = $serviceManager->get('SimpleInvoices\SqlQueries');
$dbh               = $sqlQueries->getDbHandle();

// TODO: This supports old code, should find a better way
$menu              = true;

/**
 * Run the application
 */
$application->run();