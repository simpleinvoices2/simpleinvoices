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
    //Zend_Config_Ini('./config/custom.config.php', $environment,true);
} else {
    $config = $reader->fromFile('./config/config.php');
    $config = new \Zend\Config\Config($config['production'], true);
    //$config = new Zend_Config_Ini('./config/config.php', $environment,true);	//added 'true' to allow modifications from db
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
$routeMatch = $application->getMvcEvent()->getRouteMatch();
$module = $routeMatch->getParam('module', null);
$view   = $routeMatch->getParam('view', null);
$action = $routeMatch->getParam('action', null);

$auth_session = new \Zend\Session\Container('SI_AUTH');
$smarty       = $serviceManager->get('Smarty');

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
 
$install_tables_exists = checkTableExists(TB_PREFIX."biller");
if ($install_tables_exists == true)
{
	$install_data_exists = checkDataExists();
}

//TODO - add this as a function in sql_queries.php or a class file
//if ( ($install_tables_exists != false) AND ($install_data_exists != false) )
if ( $install_tables_exists != false )
{
	if (getNumberOfDoneSQLPatches() > "196")
	{
	    $sql="SELECT * from ".TB_PREFIX."extensions WHERE (domain_id = :domain_id OR domain_id =  0 ) ORDER BY domain_id ASC";
	    $sth = dbQuery($sql,':domain_id', $auth_session->domain_id ) or die(htmlsafe(end($dbh->errorInfo())));

	    while ( $this_extension = $sth->fetch() ) 
	    { 
	    	$DB_extensions[$this_extension['name']] = $this_extension; 
	    }
	    $config->extension = $DB_extensions;
	}
}

// If no extension loaded, load Core
if (! $config->extension)
{
	$extension_core = new \Zend\Config\Config(array('core'=>array(
		'id'=>1,
		'domain_id'=>1,
		'name'=>'core',
		'description'=>'Core part of Simple Invoices - always enabled',
		'enabled'=>1
	)));
	$config->extension = $extension_core;
}

include_once('./include/language.php');

//add class files for extensions

checkConnection();

include_once('./include/manageCustomFields.php');
include_once("./include/validation.php");

//get the url - used for templates / pdf
$siUrl = getURL();

//If using the folowing line, the DB settings should be appended to the config array, instead of replacing it (NOT TESTED!)
//$config->extension() = $DB_extensions;


include_once("./include/backup.lib.php");

$defaults = getSystemDefaults();
$smarty -> assign("defaults",$defaults);
