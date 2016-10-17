<?php
namespace SimpleInvoices\Deprecate;

use SimpleInvoices\I18n\SiLocal;
use SimpleInvoices\Deprecate\Product;
use Zend\Mail\Message;

class Inventory {
	
 	public $start_date;
 	public $domain_id;

	public function __construct()
	{
	    $auth_session    = new \Zend\Session\Container('SI_AUTH');
		$this->domain_id = $auth_session->domain_id;
	}

	public function insert()
	{
	        $sql = "INSERT INTO ".TB_PREFIX."inventory (
				domain_id,
				product_id,
				quantity,
				cost,
				date,
				note
			) VALUES (
				:domain_id,
				:product_id,
				:quantity,
				:cost,
				:date,
				:note
			)";
        	$sth = dbQuery($sql,
				':domain_id',$this->domain_id, 
				':product_id',$this->product_id,
				':quantity',$this->quantity,
				':cost',$this->cost,
				':date',$this->date,
				':note',$this->note
			);
        
 	       return $sth;

	}

	public function update()
	{
	        $sql = "UPDATE 
				".TB_PREFIX."inventory
			SET 
				product_id = :product_id,
				quantity = :quantity,
				cost = :cost,
				date = :date,
				note = :note
			WHERE 
				id = :id 
			AND domain_id = :domain_id
			";
        	$sth = dbQuery($sql,
				':id',$this->id, 
				':domain_id',$this->domain_id, 
				':product_id',$this->product_id,
				':quantity',$this->quantity,
				':cost',$this->cost,
				':date',$this->date,
				':note',$this->note
			);
        
 	       return $sth;
	}

	public function delete()
	{

	}

    public function select_all($type='', $dir='DESC', $rp='25', $page='1')
	{
		global $LANG;
		$valid_search_fields = array('p.description', 'iv.date', 'iv.quantity', 'iv.cost', 'iv.quantity * iv.cost');

		/*SQL Limit - start*/
		$start = (($page-1) * $rp);
		$limit = " LIMIT $start, $rp";
		/*SQL Limit - end*/

		/*SQL where - start*/
		$where = "";
		$query = isset($_POST['query']) ? $_POST['query'] : null;
		$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : null;
		if ( ! (empty($qtype) || empty($query)) ) {
			if ( in_array($qtype, $valid_search_fields) ) {
				$where = " AND $qtype LIKE :query ";
			} else {
				$qtype = null;
				$query = null;
			}
		}
		/*SQL where - end*/
		

		/*Check that the sort field is OK*/
		if (!empty($this->sort)) {
		    $sort = $this->sort;
		} else {
		    $sort = "id";
		}

		if($type =="count")
		{
		    $limit="";
		}


		$sql = "SELECT
				inv.id as id,
				inv.product_id ,
				inv.date ,
				inv.quantity ,
                p.description,
                (select coalesce(p.reorder_level,0) as reorder_level),
				inv.cost,
				inv.quantity * inv.cost as total_cost
			FROM 
				".TB_PREFIX."products p
				LEFT JOIN ".TB_PREFIX."inventory inv
					ON (p.id = inv.product_id AND p.domain_id = inv.domain_id)
			 WHERE 
				inv.domain_id = :domain_id
				$where
			GROUP BY
			    inv.id
			ORDER BY
			$sort $dir
			$limit";

		if (empty($query)) {
			$sth = dbQuery($sql, ':domain_id', $this->domain_id);
		} else {
			$sth = dbQuery($sql, ':domain_id', $this->domain_id, ':query', "%$query%");
		}

		if($type =="count")
		{
			return $sth->rowCount();
		} else {
			return $sth->fetchAll();
		}
	}

	public function select()
	{
		global $LANG;

		$sql = "SELECT
				iv.*,
                p.description
			FROM 
				".TB_PREFIX."products p
				LEFT JOIN ".TB_PREFIX."inventory iv 
					ON (p.id = iv.product_id AND p.domain_id = iv.domain_id)
			WHERE 
				iv.domain_id = :domain_id
			AND iv.id = :id;";
		$sth = dbQuery($sql, ':domain_id', $this->domain_id, ':id', $this->id);

		return $sth->fetch();
	}



	public function check_reorder_level()
	{
	    global $serviceManager;
	    
        //select qty and reorder level

        $inventory = new Product();
        $sth = $inventory->select_all('count');

        $inventory_all = $sth->fetchAll(\PDO::FETCH_ASSOC);
        
        $email="";
        foreach ($inventory_all as $row) 
        {
             if($row['quantity'] <= $row['reorder_level'])
             {
                $message = "The quantity of Product: ".$row['description']." is ".SiLocal::number($row['quantity']).", which is equal to or below its reorder level of ".$row['reorder_level'];
                $return['row_'.$row['id']]['message'] = $message;
                $email_message .= $message . "<br />\n";
             }
        }

        //print_r($return);
        #$attachment = file_get_contents('./tmp/cache/' . $pdf_file_name);
        
        $mailMessage = new Message();
        $mailMessage->setFrom('simpleinvoices@localhost.localdomain');
        $mailMessage->addTo( getAdminEmail() );
        $mailMessage->setSubject('Simple Invoices reorder level email');
        $mailMessage->setBody($email_message);
        $mailMessage->setEncoding('utf-8');
        
        $serviceManager->get('SimpleInvoices\Mail\TransportInterface')->send($mailMessage);
        
        return $return;
    }
}
