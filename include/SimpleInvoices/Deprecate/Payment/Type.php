<?php
namespace SimpleInvoices\Deprecate\Payment;

class Type
{

    public $type;
	public $domain_id;

	public function __construct()
	{
	    $auth_session = new \Zend\Session\Container('Zend_Auth');
		$this->domain_id = $auth_session->domain_id;
	}

    function select_or_insert_where() {

        $sql = "SELECT 
                    pt_id,
		            count(DISTINCT pt_id) as count
                FROM 
                    ".TB_PREFIX."payment_types 
                WHERE 
                    pt_description = :pt_description
		        AND 
		            domain_id = :domain_id
		            $where
                GROUP BY
                    pt_id;";
        
        $sth = dbQuery($sql, ':pt_description', $this->type, ':domain_id', $this->domain_id);
	    $pt = $sth->fetch();
	
	    if($pt['count'] =="1")
	    {
		    return $pt['pt_id'];
	    }
	    //add new payment type if no Paypal type
	    if($pt == '')
	    {
		    $new_pt = new Type();
		    $new_pt->pt_description = $this->type;
		    $new_pt->pt_enabled = "1";
		    $new_pt->insert();

		    $payment_type = new Type();
		    $payment_type->type = $this->type;
		    $payment_type->domain_id = $this->domain_id;
		    return $payment_type->select_or_insert_where();
	    }
    }

	public function insert()
	{
	        $sql = "INSERT INTO ".TB_PREFIX."payment_types (
				pt_description,
				pt_enabled,
				domain_id
			) VALUES (
				:pt_description,
				:pt_enabled,
				:domain_id
			)";
        	$sth = dbQuery($sql,
				':pt_description',$this->pt_description,
				':pt_enabled',$this->pt_enabled,
				':domain_id',$this->domain_id 
			);
        
 	       return $sth;
	}

}
