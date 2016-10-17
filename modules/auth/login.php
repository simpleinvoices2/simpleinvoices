<?php
/*
 * Script: login.php
 * 	Login page
 *
 * License:
 *	 GPL v3 or above
 */

$menu = false;

// we must never forget to start the session
// so config.php works ok without using index.php define browse
define("BROWSE","browse");

// TODO: Really not needed as it should be initialized in bootstrap
$session = $services->get(\Zend\Session\SessionManager::class);
$session->start();

$errorMessage = '';

$authenticationService = $services->get(\SimpleInvoices\Authentication\AuthenticationService::class);

if (!empty($_POST['user']) && !empty($_POST['pass'])) 
{
    $PatchesDone = getNumberOfDoneSQLPatches();

    $userEmail = $_POST['user'];
    $password  = $_POST['pass'];

    // Set the input credential values (e.g., from a login form)
    $authenticationService->getAdapter()->setIdentity($userEmail)
                                        ->setCredential($password);
    
    // Perform the authentication query, saving the result
    $result = $authenticationService->authenticate();
    
    if ($result->isValid()) {
        $session->start();

        if ($authNamespace->role_name == 'customer' && $authNamespace->user_id > 0) {
            header('Location: index.php?module=customers&view=details&action=view&id='.$authNamespace->user_id);
        } else {
            header('Location: .');
        }
    } else {
        $errorMessage = 'Sorry, wrong user / password';
    }
}

if($_POST['action'] == 'login' && (empty($_POST['user']) OR empty($_POST['pass'])))
{
    $errorMessage = 'Username and password required';
}

// No translations for login since user's lang not known as yet
$smarty->assign("errorMessage",$errorMessage);

