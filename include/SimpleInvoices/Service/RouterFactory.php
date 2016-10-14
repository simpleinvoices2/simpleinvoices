<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\Mvc\Router\Router;

class RouterFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return Router
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Router();
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Router
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\Router');
    }
}