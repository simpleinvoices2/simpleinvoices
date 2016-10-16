<?php
/*
 * Script: login.php
 * 	Login page
 *
 * License:
 *	 GPL v3 or above
 */

global $zendDb;

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

        /*
         * grab user data  from the database
         */

        // patch 147 adds user_role table - need to accomodate pre and post patch 147
		if ( $PatchesDone < "147" ) {
            $result = $zendDb->fetchRow("
				SELECT 
					u.user_id AS id, u.user_email, u.user_name
				FROM 
					".TB_PREFIX."users u
				WHERE 
					user_email = ?", $userEmail
            );
            $result['role_name']="administrator";

        } elseif ( $PatchesDone < "184" ) {
            $result = $zendDb->fetchRow("
				SELECT 
					u.user_id AS id, u.user_email, u.user_name, r.name AS role_name, u.user_domain_id
				FROM 
					".TB_PREFIX."user u 
					LEFT JOIN ".TB_PREFIX."user_role r ON (u.user_role_id = r.id)
				WHERE 
					u.user_email = ?", $userEmail
            );
        } elseif ( $PatchesDone < "292" ) {
            $result = $zendDb->fetchRow("
				SELECT 
					u.id, u.email, r.name AS role_name, u.domain_id, 0 AS user_id
				FROM 
					".TB_PREFIX."user u 
					LEFT JOIN ".TB_PREFIX."user_role r ON (u.role_id = r.id)
				WHERE 
					u.email = ? AND u.enabled = '".ENABLED."'", $userEmail
            );

		// Customer / Biller User ID available on and after Patch 292
        } else {
            $result = $zendDb->fetchRow("
				SELECT 
					u.id, u.email, r.name AS role_name, u.domain_id, u.user_id
				FROM 
					".TB_PREFIX."user u 
					LEFT JOIN ".TB_PREFIX."user_role r ON (u.role_id = r.id)
				WHERE 
					u.email = ? AND u.enabled = '".ENABLED."'", $userEmail
            );
        }
        
        /*
         * chuck the user details sans password into the Zend_auth session
         */
        $authNamespace = new \Zend\Session\Container('SI_AUTH');
        $authNamespace->setExpirationSeconds(60 * 60);
		
        foreach ($result as $key => $value)
        {
            $authNamespace->$key = $value;
        }

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

