<?php

include ('extensions/sub_customer/include/class/sub_customer.php');

function getSubCustomers($customer_id)
{
    $auth_session = new \Zend\Session\Container('Zend_Auth');
    
    $domain_id = $auth_session->domain_id;

    $sql = "SELECT * FROM ".TB_PREFIX."customers WHERE parent_customer_id = :customer_id AND domain_id = :domain_id";
	$sth = dbQuery($sql, ':customer_id', $customer_id, ':domain_id',$domain_id);
	return $sth->fetchAll();
}
