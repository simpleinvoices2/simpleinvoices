<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SimpleInvoices\Mvc\Service;

use Interop\Container\ContainerInterface;
use SimpleInvoices\ModuleManager\Listener\ServiceListener;
use SimpleInvoices\ModuleManager\Listener\ServiceListenerInterface;
use SimpleInvoices\Mvc\Application;
use SimpleInvoices\Mvc\Controller\ControllerManager;
use SimpleInvoices\Mvc\SendResponseListener;
use SimpleInvoices\Mvc\View;
use ReflectionClass;
use Zend\Config\Config;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceListenerFactory implements FactoryInterface
{
    /**
     * @var string
     */
    const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';

    /**
     * @var string
     */
    const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';

    /**
     * Default mvc-related service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $defaultServiceConfig = [
        'aliases' => [
            //'configuration'                              => 'config',
            //'Configuration'                              => 'config',
            //'HttpDefaultRenderingStrategy'               => View\Http\DefaultRenderingStrategy::class,
            //'MiddlewareListener'                         => 'Zend\Mvc\MiddlewareListener',
            //'RouteListener'                              => 'Zend\Mvc\RouteListener',
            'SendResponseListener'                       => SendResponseListener::class,
            //'View'                                       => 'Zend\View\View',
            //'ViewFeedRenderer'                           => 'Zend\View\Renderer\FeedRenderer',
            //'ViewJsonRenderer'                           => 'Zend\View\Renderer\JsonRenderer',
            //'ViewPhpRendererStrategy'                    => 'Zend\View\Strategy\PhpRendererStrategy',
            //'ViewPhpRenderer'                            => 'Zend\View\Renderer\PhpRenderer',
            //'ViewRenderer'                               => 'Zend\View\Renderer\PhpRenderer',
            //'Zend\Mvc\Controller\PluginManager'          => 'ControllerPluginManager',
            //'Zend\Mvc\View\Http\InjectTemplateListener'  => 'InjectTemplateListener',
            //'Zend\View\Renderer\RendererInterface'       => 'Zend\View\Renderer\PhpRenderer',
            //'Zend\View\Resolver\TemplateMapResolver'     => 'ViewTemplateMapResolver',
            //'Zend\View\Resolver\TemplatePathStack'       => 'ViewTemplatePathStack',
            //'Zend\View\Resolver\AggregateResolver'       => 'ViewResolver',
            //'Zend\View\Resolver\ResolverInterface'       => 'ViewResolver',
            'ControllerManager'                          => ControllerManager::class,
        ],
        'invokables' => [],
        'factories'  => [
            'Application'                    => ApplicationFactory::class,
            //'config'                         => 'Zend\Mvc\Service\ConfigFactory',
            ControllerManager::class         => ControllerManagerFactory::class,
            'ControllerPluginManager'        => ControllerPluginManagerFactory::class,
            'DispatchListener'               => DispatchListenerFactory::class,
            //'HttpExceptionStrategy'          => HttpExceptionStrategyFactory::class,
            //'HttpMethodListener'             => 'Zend\Mvc\Service\HttpMethodListenerFactory',
            //'HttpRouteNotFoundStrategy'      => HttpRouteNotFoundStrategyFactory::class,
            'HttpViewManager'                => HttpViewManagerFactory::class,
            //'InjectTemplateListener'         => 'Zend\Mvc\Service\InjectTemplateListenerFactory',
            //'PaginatorPluginManager'         => 'Zend\Mvc\Service\PaginatorPluginManagerFactory',
            'Request'                        => RequestFactory::class,
            'Response'                       => ResponseFactory::class,
            'ViewHelperManager'              => ViewHelperManagerFactory::class,
            //View\Http\DefaultRenderingStrategy::class => HttpDefaultRenderingStrategyFactory::class,
            //'ViewFeedStrategy'               => 'Zend\Mvc\Service\ViewFeedStrategyFactory',
            //'ViewJsonStrategy'               => 'Zend\Mvc\Service\ViewJsonStrategyFactory',
            'ViewManager'                    => ViewManagerFactory::class,
            //'ViewResolver'                   => 'Zend\Mvc\Service\ViewResolverFactory',
            //'ViewTemplateMapResolver'        => 'Zend\Mvc\Service\ViewTemplateMapResolverFactory',
            //'ViewTemplatePathStack'          => 'Zend\Mvc\Service\ViewTemplatePathStackFactory',
            //'ViewPrefixPathStackResolver'    => 'Zend\Mvc\Service\ViewPrefixPathStackResolverFactory',
            //'Zend\Mvc\MiddlewareListener'    => InvokableFactory::class,
            //'Zend\Mvc\RouteListener'         => InvokableFactory::class,
            SendResponseListener::class      => SendResponseListenerFactory::class,
            //'Zend\View\Renderer\FeedRenderer' => InvokableFactory::class,
            //'Zend\View\Renderer\JsonRenderer' => InvokableFactory::class,
            //'Zend\View\Renderer\PhpRenderer' => ViewPhpRendererFactory::class,
            //'Zend\View\Strategy\PhpRendererStrategy' => ViewPhpRendererStrategyFactory::class,
            //'Zend\View\View'                 => ViewFactory::class,
        ],
    ];

    /**
     * Constructor
     *
     * When executed under zend-servicemanager v3, injects additional aliases
     * to ensure backwards compatibility.
     */
    public function __construct()
    {
        $r = new ReflectionClass(ServiceLocatorInterface::class);
        if ($r->hasMethod('build')) {
            $this->injectV3Aliases();
        }
    }

    /**
     * Create the service listener service
     *
     * Tries to get a service named ServiceListenerInterface from the service
     * locator, otherwise creates a ServiceListener instance, passing it the
     * container instance and the default service configuration, which can be
     * overridden by modules.
     *
     * It looks for the 'service_listener_options' key in the application
     * config and tries to add service/plugin managers as configured. The value
     * of 'service_listener_options' must be a list (array) which contains the
     * following keys:
     *
     * - service_manager: the name of the service manage to create as string
     * - config_key: the name of the configuration key to search for as string
     * - interface: the name of the interface that modules can implement as string
     * - method: the name of the method that modules have to implement as string
     *
     * @param  ServiceLocatorInterface  $serviceLocator
     * @return ServiceListenerInterface
     * @throws ServiceNotCreatedException for invalid ServiceListener service
     * @throws ServiceNotCreatedException For invalid configurations.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        //$configuration   = $container->get('ApplicationConfig');
        $configuration   = [];

        $serviceListener = $container->has('ServiceListenerInterface')
            ? $container->get('ServiceListenerInterface')
            : new ServiceListener($container);

        if (! $serviceListener instanceof ServiceListenerInterface) {
            throw new ServiceNotCreatedException(
                'The service named ServiceListenerInterface must implement '
                .  ServiceListenerInterface::class
            );
        }

        $serviceListener->setDefaultServiceConfig($this->defaultServiceConfig);

        if (isset($configuration['service_listener_options'])) {
            $this->injectServiceListenerOptions($configuration['service_listener_options'], $serviceListener);
        }

        return $serviceListener;
    }

    /**
     * Create and return the ServiceListener (v2)
     *
     * @param ServiceLocatorInterface $container
     * @return ServiceListenerInterface
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ServiceListener::class);
    }

    /**
     * Validate and inject plugin manager options into the service listener.
     *
     * @param array $options
     * @param ServiceListenerInterface $serviceListener
     * @throws ServiceListenerInterface for invalid $options types
     */
    private function injectServiceListenerOptions($options, ServiceListenerInterface $serviceListener)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'The value of service_listener_options must be an array, %s given.',
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        foreach ($options as $key => $newServiceManager) {
            $this->validatePluginManagerOptions($newServiceManager, $key);

            $serviceListener->addServiceManager(
                $newServiceManager['service_manager'],
                $newServiceManager['config_key'],
                $newServiceManager['interface'],
                $newServiceManager['method']
            );
        }
    }

    /**
     * Validate the structure and types for plugin manager configuration options.
     *
     * Ensures all required keys are present in the expected types.
     *
     * @param array $options
     * @param string $name Plugin manager service name; used for exception messages
     * @throws ServiceNotCreatedException for any missing configuration options.
     * @throws ServiceNotCreatedException for configuration options of invalid types.
     */
    private function validatePluginManagerOptions($options, $name)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'Plugin manager configuration for "%s" is invalid; must be an array, received "%s"',
                $name,
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        if (! isset($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'service_manager'));
        }

        if (! is_string($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'service_manager',
                gettype($options['service_manager'])
            ));
        }

        if (! isset($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'config_key'));
        }

        if (! is_string($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'config_key',
                gettype($options['config_key'])
            ));
        }

        if (! isset($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'interface'));
        }

        if (! is_string($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'interface',
                gettype($options['interface'])
            ));
        }

        if (! isset($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'method'));
        }

        if (! is_string($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'method',
                gettype($options['method'])
            ));
        }
    }

    /**
     * Inject additional aliases for zend-servicemanager v3 usage
     *
     * If the constructor detects that we're operating under zend-servicemanager v3,
     * this method injects additional aliases to ensure that common services
     * can be retrieved using both Titlecase and lowercase, and will get the
     * same instances.
     *
     * @return void
     */
    private function injectV3Aliases()
    {
        $this->defaultServiceConfig['aliases']['application'] = 'Application';
        //$this->defaultServiceConfig['aliases']['Config']      = 'config';
        $this->defaultServiceConfig['aliases']['request']     = 'Request';
        $this->defaultServiceConfig['aliases']['response']    = 'Response';
    }
}
