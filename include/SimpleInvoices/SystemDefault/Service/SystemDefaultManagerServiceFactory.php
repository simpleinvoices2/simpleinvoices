<?php
namespace SimpleInvoices\SystemDefault\Service;

use Zend\ServiceManager\FactoryInterface;
use SimpleInvoices\SystemDefault\SystemDefaultManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

class SystemDefaultManagerServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return SystemDefaultManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter  = $container->get('SimpleInvoices\Database\Adapter');
        return new SystemDefaultManager($adapter, TB_PREFIX . 'system_defaults');
    }
    
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return SystemDefaultManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, SystemDefaultManager::class);
    }
}