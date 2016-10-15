<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Adapter\Adapter;

class DatabaseFactory implements FactoryInterface
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
        $config = $container->get('SimpleInvoices\Config');
        
        $pdoAdapter = substr($config->database->adapter, 4);
        if (!$pdoAdapter) {
            die("No PDO adapter has been defined!");
        }
        
        // Build DSN
        $dsn = $pdoAdapter . ":dbname=" . $config->database->params->dbname;
        
        if (isset($config->database->params->host)) {
            $dsn = $dsn . ";host=" . $config->database->params->host;
        } else {
            $dsn = $dns . ";host=localhost";
        }
        
        if (isset($config->database->params->port) && !empty($config->database->params->port)) {
            if (is_numeric($config->database->params->port)) {
                $dsn = $dsn . ";port=" . $config->database->params->port;
            }
        }
        
        // Build the configuration array
        $adapterConfig = [
            'driver'   => 'pdo',
            'dsn'      => $dsn,
            'username' => $config->database->params->username,
            'password' => $config->database->params->password,        
        ];
        
        if (isset($config->database->utf8) && (strcasecmp($pdoAdapter, 'mysql') === 0)) {
            if ($config->database->utf8 === 1) {
                $adapterConfig['driver_options'] = [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
                ];
            } 
        }
        
        return new Adapter($adapterConfig);
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return HttpRequest
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\Database\Adapter');
    }
}