<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Http\Response as HttpResponse;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class LoggerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return Logger
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logFile = "./tmp/log/si.log";
        if (!is_file($logFile))
        {
            $createLogFile = fopen($logFile, 'w') or die(simpleInvoicesError('notWriteable', 'folder', 'tmp/log'));
            fclose($createLogFile);
        }
        
        if (!is_writable($logFile)) {
            simpleInvoicesError('notWriteable', 'file', $logFile);
        }
        
        $writer = new Stream($logFile);
        $logger = new Logger();
        $logger->addWriter($writer);
        
        return $logger;
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Logger
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\Logger');
    }
}