<?php
use SimpleInvoices\I18n\SiLocal;

if($_GET['id'])
{
    $sql = sprintf("SELECT cost FROM ".TB_PREFIX."products WHERE id = %d AND domain_id = %d LIMIT 1", $_GET['id'], $auth_session->domain_id);
    $states = dbQuery($sql);
//  $output = '';
    if($states->rowCount() > 0)
    {	
        $row = $states->fetch();

        // $output .= '<input id="state" class="field select two-third addr" value="'.$row['unit_price'].'"/>';
        /*Format with decimal places with precision as defined in config.php*/
        $output['cost'] = SiLocal::number_formatted($row['cost']);
        // $output .= $_POST['id'];
    }
    
    // TODO: What if $output is not declared?
    echo json_encode($output);
    
    exit();
} else {
    echo "";
}

// Perform teh Queries!
//$sql = 'SELECT * FROM si_products';
//$country = mysqlQuery($sql) or die('Query Failed:' . mysql_error());
