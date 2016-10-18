<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\PatchManager\PatchManager;

class PatchManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return PatchManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter = $container->get('SimpleInvoices\Database\Adapter');
        return new PatchManager($adapter, TB_PREFIX . 'sql_patchmanager');
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return PatchManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\PatchManager');
    }
}