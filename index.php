<?php
/*
 * Script: index.php
 * 	Main controller file for Simple Invoices
 *
 * License:
 *	 GPL v3 or above
 */


//minor change to test github emails - test

//stop browsing to files directly - all viewing to be handled by index.php
//if browse not defined then the page will exit
define("BROWSE","browse");

/**
 * Composer autoloader
 */
require_once './vendor/autoload.php';

/**
 * Requirements
 */
if (!extension_loaded('intl')) { 
    throw new \Exception("Simple Invoices requires PHP extension 'intl' (see http://www.php.net/intl).");
}

/**
 * Init file
 */
require_once("./include/init.php");
	
/**
 * Run the application
 */
$application->run();

