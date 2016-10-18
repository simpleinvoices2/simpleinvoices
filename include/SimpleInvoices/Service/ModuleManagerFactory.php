<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\ModuleManager\ModuleManagerInterface;
use SimpleInvoices\ModuleManager\ModuleManager;

class ModuleManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return ModuleManagerInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter        = $container->get('SimpleInvoices\Database\Adapter');
        $patchManager   = $container->get('SimpleInvoices\PatchManager');
        $doneSQLPatches = $patchManager->getNumberOfDoneSQLPatches();
        
        return new ModuleManager($adapter, TB_PREFIX . 'extensions', $doneSQLPatches);
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ModuleManagerInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\ModuleManager');
    }
}