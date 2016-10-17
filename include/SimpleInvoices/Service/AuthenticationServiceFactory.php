<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter;
use Zend\Authentication\Storage\Session;
use Zend\Db\Sql\Select;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return AuthenticationService
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $eventManager = $container->get('SimpleInvoices\EventManager');
        $zendDb       = $container->get('SimpleInvoices\Database\Adapter');
        $storage      = new Session('SI_AUTH', 'id');
        $adapter      = new CredentialTreatmentAdapter($zendDb);
        
        // =============================================
        // ----------------   S T A R T   --------------
        // This code was originally in the auth module
        $PatchesDone = getNumberOfDoneSQLPatches();
        
        //sql patch 161 changes user table name - need to accomodate
        $user_table    = ($PatchesDone < "161") ? "users" : "user";
        $user_email    = ($PatchesDone < "184") ? "user_email" : "email";
        $user_password = ($PatchesDone < "184") ? "user_password" : "password";
        
        $adapter->setTableName(TB_PREFIX.$user_table)
                ->setIdentityColumn($user_email)
                ->setCredentialColumn($user_password)
                ->setCredentialTreatment('MD5(?)');
        
        // Modify the select here
        $select = $adapter->getDbSelect();
        
        if ($PatchesDone >= 147) {
            if($PatchesDone < 184) {
                $select->join(
                    TB_PREFIX . 'user_role', 
                    TB_PREFIX . $user_table . '.USER_role_id = ' . TB_PREFIX . 'user_role.id', 
                    ['role_name' => 'name'], 
                    Select::JOIN_LEFT
                );
            } else {
                $select->join(
                    TB_PREFIX . 'user_role', 
                    TB_PREFIX . $user_table . '.role_id = ' . TB_PREFIX . 'user_role.id', 
                    ['role_name' => 'name'], 
                    Select::JOIN_LEFT
                );
                $select->where(['enabled' => 1]);
            }
        }
        
        // ------------------   E N D   ----------------
        // =============================================
        
        return new AuthenticationService($storage, $adapter, $eventManager);
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return AuthenticationService
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, AuthenticationService::class);
    }
}