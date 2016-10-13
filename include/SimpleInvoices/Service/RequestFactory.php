<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Http\PhpEnvironment\Request as HttpRequest;

class RequestFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return HttpRequest
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new HttpRequest();
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return HttpRequest
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'Request');
    }
}