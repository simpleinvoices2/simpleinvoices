<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

class MailTransportFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return TransportInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('SimpleInvoices\Config');
        
        
        if($config->email->smtp_auth == true) {
            $authentication = array(
                'auth' => 'login',
                'username' => $config->email->username,
                'password' => $config->email->password,
                'ssl' => $config->email->secure,
                'port' => $config->email->smtpport
            );
        }
        
        $transport = new SmtpTransport();
        
        if($config->email->use_local_sendmail == false) {    
            if ($config->email->smtp_auth) {
                if (!empty($config->email->secure)) {
                    
                    $options   = new SmtpOptions([
                        'name'              => $config->email->host,
                        'host'              => $config->email->host,
                        'port'              => $config->email->smtpport,
                        'connection_class'  => 'login',
                        'connection_config' => [
                            'ssl'      => $config->email->secure,
                            'username' => $config->email->username,
                            'password' => $config->email->password,
                        ],
                    ]);
                } else {
                    $options   = new SmtpOptions([
                        'name'              => $config->email->host,
                        'host'              => $config->email->host,
                        'port'              => $config->email->smtpport,
                        'connection_class'  => 'login',
                        'connection_config' => [
                            'username' => $config->email->username,
                            'password' => $config->email->password,
                        ],
                    ]);
                }
            } else {
                $options   = new SmtpOptions([
                    'name'              => $config->email->host,
                    'host'              => $config->email->host,
                    'port'              => $config->email->smtpport
                ]);
            }
            
            $transport->setOptions($options);
        }
        
        return $transport;
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return TransportInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\Mail\TransportInterface');
    }
}