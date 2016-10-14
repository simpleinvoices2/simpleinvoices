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
 * Before bootstrapping the application we need ZF1 autoloader
 * in order for non-ported code to work.
 */
set_include_path(get_include_path() . PATH_SEPARATOR . "./library/");
set_include_path(get_include_path() . PATH_SEPARATOR . "./library/pdf");
set_include_path(get_include_path() . PATH_SEPARATOR . "./include/");

require_once 'Zend/Loader/Autoloader.php';

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

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

$auth_session = new Zend_Session_Namespace('Zend_Auth');
$smarty       = $serviceManager->get('Smarty');

/**
 * Old stuff follows...
 */

//start use of zend_cache   
$frontendOptions = array(
    'lifetime' => 7200, // cache lifetime of 2 hours
    'automatic_serialization' => true
);
                   

/* 
 * Zend framework init - end
 */



/* 
 * Smarty inint - start
 */

#ini_set('display_errors',true);

require_once("library/paypal/paypal.class.php");

require_once('./library/HTMLPurifier/HTMLPurifier.standalone.php');
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
/*
 * Zend Framework cache section - start
 * -- must come after the tmp dir writeable check
 */
$backendOptions = array(
    'cache_dir' => './tmp/' // Directory where to put the cache files
);
                                   
// getting a Zend_Cache_Core object
$cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);

//required for some servers
Zend_Date::setOptions(array('cache' => $cache)); // Active aussi pour Zend_Locale
/*
 * Zend Framework cache section - end
 */

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

$zendDb = Zend_Db::factory($config->database->adapter, array(
    'host'     => $config->database->params->host,
    'username' => $config->database->params->username,
    'password' => $config->database->params->password,
    'dbname'   => $config->database->params->dbname,
    'port'     => $config->database->params->port)
);

//include_once("./include/sql_patches.php");

include_once("./include/class/db.php");
include_once("./include/class/index.php");
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

//
// include/include_auth.php - start
//

//if user logged into Simple Invoices with auth off then auth turned on - id via fake_auth and kill session
if ( ($config->authentication->enabled == 1 ) AND ($auth_session->fake_auth =="1" ) )
{
    Zend_Session::start();
    Zend_Session::destroy(true);
    header('Location: .');
}

// 1 = config->auth->enabled == "true"
if ($config->authentication->enabled == 1 ) {

    //TODO - this needs to be fixed !!
    if ($auth_session->domain_id == null)
    {
        $auth_session->domain_id = "1";
    }

    /*
     * API calls don't use the auth module
     */
    if ($module != 'api'){
        if (!isset($auth_session->id)){
            if(!isset($_GET['module'])) {
                $_GET['module'] = '';
            }

            if  ($_GET['module'] !== "auth") {
                header('Location: index.php?module=auth&view=login');
                exit;
            }
        }
    }
}

/*If auth not on - use default domain and user id of 1*/
if ($config->authentication->enabled != 1 )
{
    /*
     * chuck the user details sans password into the Zend_auth session
     */
    $auth_session->id = "1";
    $auth_session->domain_id = "1";
    $auth_session->email = "demo@simpleinvoices.org";
    //fake_auth is identifier to say that user logged in with auth off
    $auth_session->fake_auth = "1";
    //No Customer login as logins disabled
    $auth_session->user_id = "0";
}

//
// include/include_auth.php - end
//


include_once('./include/manageCustomFields.php');
include_once("./include/validation.php");

//if authentication enabled then do acl check etc..
if ($config->authentication->enabled == 1 )
{
    $acl = $serviceManager->get('SimpleInvoices\Permission\Acl');
	
    $acl_view   = (isset($_GET['view']) ? $_GET['view'] : null);
    $acl_action = (isset($_GET['action']) ? $_GET['action'] : null);
    
    if (empty($acl_action)) {
        // no action is given
        if (!empty($acl_view)) {
            // view is available with no action
            $checkPermission = $acl->isAllowed($auth_session->role_name, $module, $acl_view) ?  "allowed" : "denied"; // allowed
        }
    } else {
        // action available
        $checkPermission = $acl->isAllowed($auth_session->role_name, $module, $acl_action) ?  "allowed" : "denied"; // allowed
    }
    
    //basic customer page check
    if( ($auth_session->role_name =='customer') AND ($module == 'customers') AND ($_GET['id'] != $auth_session->user_id) )
    {
        $checkPermission = "denied";
    }
    
    //customer invoice page add/edit check since no acl for invoices
    if( ($auth_session->role_name =='customer') && ($module == 'invoices') ) {
        if (   $acl_view == 'itemised' || $acl_view == 'total' || $acl_view == 'consulting' || $acl_action == 'view' || ($acl_action != '' && isset($_GET['id']) && $_GET['id'] != $auth_session->user_id) ) {
            $checkPermission = "denied";
        }
    }
    
    $checkPermission == "denied" ? exit($LANG['denied_page']) :"" ;
}

//switch ($module)
//{
//	case "export" :	
//		$smarty_output = "fetch";
//		break;
//	default :
//		$smarty_output = "display";
//		break;
//}

//get the url - used for templates / pdf
$siUrl = getURL();
//zend db

// Get extensions from DB, and update config array


//If using the folowing line, the DB settings should be appended to the config array, instead of replacing it (NOT TESTED!)
//$config->extension() = $DB_extensions;


include_once("./include/backup.lib.php");

$defaults = getSystemDefaults();
$smarty -> assign("defaults",$defaults);
