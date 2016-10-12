<?php


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
