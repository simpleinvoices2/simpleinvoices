<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\I18n\Translator\Translator;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Db\Adapter\Driver\ResultInterface;
use SimpleInvoices\SystemDefault\SystemDefaultManager;

class TranslatorServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return TranslatorInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Using hardcoded config
        $translator = new Translator();
        
        
        $systemDefaults = $container->get(SystemDefaultManager::class);
        $locale         = $systemDefaults->get('language', 'en_GB');
                
        $translator->addTranslationFilePattern('phparray', dirname(dirname(dirname(__DIR__))) . '/language', '%s.php');
        $translator->setLocale($locale);
        $translator->setFallbackLocale('en_GB');

        return $translator;
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return TranslatorInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, TranslatorInterface::class);
    }
}