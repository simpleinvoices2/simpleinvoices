<?php
/*
 * Script: logout.php
 * 	Logout page
 *
 * License:
 *	 GPL v3 or above
 */

$menu = false;

// define browse
if (!defined("BROWSE")) define("BROWSE", "browse");

// we must never forget to start the session
// so config.php works ok without using index.php

$session = $services->get(\Zend\Session\SessionManager::class);
$session->start();
$session->destroy([
    'clear_storage'      => true,
    'send_expire_cookie' => true,
]);
header('Location: .');
