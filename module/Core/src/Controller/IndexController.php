<?php
namespace SimpleInvoices\Core\Controller;

use SimpleInvoices\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $debtor = getTopDebtor();
        $customer = getTopCustomer();
        $biller = getTopBiller();
        
        $billers = getBillers();
        $customers = getCustomers();
        $taxes = getTaxes();
        $products = getProducts();
        $preferences = getPreferences();
        $defaults = getSystemDefaults();
        
        if ($billers == null OR $customers == null OR $taxes == null OR $products == null OR $preferences == null)
        {
            $first_run_wizard = true;
        } else {
            $first_run_wizard = false;
        }
        
        // TODO: Re-enable this?
        //$smarty->assign("mysql",$mysql);
        //$smarty->assign("db_server",$db_server);
        /*
        $smarty -> assign("patch",count($patch));
        $smarty -> assign("max_patches_applied", $max_patches_applied);
        */
        
        return [
            'first_run_wizard' => $first_run_wizard,
            'biller'           => $biller,
            'billers'          => $billers,
            'customer'         => $customer,
            'customers'        => $customers,
            'taxes'            => $taxes,
            'products'         => $products,
            'preferences'      => $preferences,
            'debtor'           => $debtor,
            'pageActive'       => 'dashboard',
            'active_tab'       => '#home',
        ];
        /*
        $smarty -> assign("biller", $biller);
        $smarty -> assign("billers", $billers);
        $smarty -> assign("customer", $customer);
        $smarty -> assign("customers", $customers);
        $smarty -> assign("taxes", $taxes);
        $smarty -> assign("products", $products);
        $smarty -> assign("preferences", $preferences);
        $smarty -> assign("debtor", $debtor);
        //$smarty -> assign("title", $title);
        
        $smarty -> assign('pageActive', 'dashboard');
        $smarty -> assign('active_tab', '#home');
        */
    }
}