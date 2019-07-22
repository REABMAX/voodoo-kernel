<?php

namespace Voodoo\Kernel;

use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Router;
use League\Route\Strategy\StrategyInterface;
use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Voodoo\Configuration\ArrowPathResolver;
use Voodoo\Configuration\ConfigurationManager;
use Voodoo\Configuration\Contracts\ConfigurationManagerInterface;
use Voodoo\Di\Configurators\LeagueContainerConfigurator;
use Voodoo\Di\ContainerConfiguration;
use Voodoo\Di\Contracts\ContainerConfiguratorInterface;
use Voodoo\Event\Configurators\PhlyEventDispatcherConfigurator;
use Voodoo\Event\EventDispatcherConfiguratorInterface;
use Voodoo\Kernel\Contracts\KernelInterface;
use Voodoo\Kernel\Emitter\ConditionalEmitter;
use Voodoo\Module\ContainerModuleResolver;
use Voodoo\Module\Contracts\ModuleManagerInterface;
use Voodoo\Module\ModuleManager;
use Voodoo\Router\Configurators\LeagueRouterConfigurator;
use Voodoo\Router\Contracts\RouterConfiguratorInterface;
use Voodoo\Router\RouterConfiguration;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

/**
 * Class GenericKernel
 * @package Voodoo\Kernel
 */
abstract class Kernel implements KernelInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ContainerConfiguratorInterface
     */
    protected $containerConfigurator = null;

    /**
     * @var array
     */
    protected $containerConfiguration = [];

    /**
     * @var EventDispatcherConfiguratorInterface
     */
    protected $eventConfigurator = null;

    /**
     * @var RouterConfiguratorInterface
     */
    protected $routerConfigurator = null;

    /**
     * @var Router
     */
    protected $router = null;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var ModuleManagerInterface
     */
    protected $moduleManager;

    /**
     * @var array
     */
    protected $diConfig = [];

    /**
     * Kernel constructor.
     * @param ContainerConfiguratorInterface|null $containerConfigurator
     * @param ConfigurationManagerInterface|null $configurationManager
     * @param EventDispatcherConfiguratorInterface|null $eventDispatcherConfigurator
     * @param ModuleManagerInterface|null $moduleManager
     */
    public function __construct(
        ContainerConfiguratorInterface $containerConfigurator = null,
        ConfigurationManagerInterface $configurationManager = null,
        EventDispatcherConfiguratorInterface $eventDispatcherConfigurator = null,
        ModuleManagerInterface $moduleManager = null
    ) {
        $this->createContainerConfiguratorIfEmpty($containerConfigurator);
        $this->createEventDispatcherConfiguratorIfEmpty($eventDispatcherConfigurator);
        $this->createRouterConfigurator();
        $this->createConfigurationManagerIfEmpty($configurationManager);
        $this->createModuleManagerIfEmpty($moduleManager);

        $this->diConfig = [
            'aliases' => [
                ContainerConfiguratorInterface::class => $this->containerConfigurator,
                ContainerInterface::class => $this->getContainer(),
                RouterConfiguratorInterface::class => $this->routerConfigurator,
                Router::class => $this->getRouter(),
                ConfigurationManagerInterface::class => $this->configurationManager,
                ConfigurationManager::class => $this->configurationManager,
                EventDispatcherConfiguratorInterface::class => $this->eventConfigurator,
                EventDispatcherInterface::class => $this->eventConfigurator->getEventDispatcher(),
                ListenerProviderInterface::class => $this->eventConfigurator->getListenerProvider(),
                ModuleManagerInterface::class => $this->moduleManager,
            ],
        ];
    }

    /**
     * Dispatch the whole thing
     */
    public function dispatch()
    {
        $this->initializeContainer();
        $this->initializeEventDispatcher();
        $this->initializeRouter();
        $this->initializeConfigurationManager();
        $this->initializeMiddleware();

        $this->moduleManager->bootstrapModules($this->getContainer());

        $response = $this->getRouter()->dispatch(ServerRequestFactory::fromGlobals());
        $this->emit($response);
    }

    /**
     * @param ContainerConfiguratorInterface $configurator
     * @return mixed|void
     */
    public function setContainer(ContainerConfiguratorInterface $configurator)
    {
        $this->containerConfigurator = $configurator;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->routerConfigurator->getRouter();
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            $this->container = $this->containerConfigurator->getContainer();
        }

        return $this->container;
    }

    /**
     *
     */
    protected function initializeContainer()
    {
        $containerConfig = array_merge_recursive($this->diConfig, $this->di(), $this->moduleManager->getContainerConfiguration());
        $containerConfig = new ContainerConfiguration($containerConfig);
        $this->containerConfigurator->configureContainer($containerConfig);
    }

    /**
     *
     */
    protected function initializeEventDispatcher()
    {
        $eventConfig = array_merge_recursive($this->events(), $this->moduleManager->getEventConfiguration());
        ($this->eventConfigurator)($eventConfig);
    }

    /**
     *
     */
    protected function initializeRouter()
    {
        $routerConfig = array_merge_recursive($this->routes(), $this->moduleManager->getRouterConfiguration());
        $routerConfig = new RouterConfiguration($routerConfig);
        $this->routerConfigurator->configureRouter($routerConfig);
    }

    /**
     *
     */
    protected function initializeConfigurationManager()
    {
        $configuration = $this->moduleManager->getModuleConfiguration();
        $this->configurationManager = $this->configurationManager->setAndLock($configuration);
    }

    /**
     *
     */
    protected function initializeMiddleware()
    {
        $middleware = array_merge_recursive($this->middleware(), $this->moduleManager->getMiddlewareConfiguration());
        asort($middleware);

        foreach ($middleware as $middlewareClass => $sorting) {
            $middlewareObject = $this->getContainer()->get($middlewareClass);
            $this->getRouter()->middleware($middlewareObject);
        }
    }

    /**
     *
     */
    protected function createRouterConfigurator()
    {
        $router = new Router();
        $strategy = $this->createDispatcherStrategy();
        $router->setStrategy($strategy);
        $this->routerConfigurator = new LeagueRouterConfigurator($router);
    }

    /**
     * @param ContainerConfiguratorInterface|null $containerConfigurator
     */
    protected function createContainerConfiguratorIfEmpty(ContainerConfiguratorInterface $containerConfigurator = null)
    {
        if (!$containerConfigurator) {
            $container = new Container();
            $container->defaultToShared(true);
            $container->delegate(new ReflectionContainer());
            $containerConfigurator = new LeagueContainerConfigurator($container);
        }

        $this->containerConfigurator = $containerConfigurator;
    }

    /**
     * @param ConfigurationManagerInterface|null $configurationManager
     */
    protected function createConfigurationManagerIfEmpty(ConfigurationManagerInterface $configurationManager = null)
    {
        if (!$configurationManager) {
            $configurationManager = new ConfigurationManager(new ArrowPathResolver());
        }

        $this->configurationManager = $configurationManager;
    }

    /**
     * @param EventDispatcherConfiguratorInterface|null $eventDispatcherConfigurator
     */
    protected function createEventDispatcherConfiguratorIfEmpty(EventDispatcherConfiguratorInterface $eventDispatcherConfigurator = null)
    {
        if (!$eventDispatcherConfigurator) {
            $listenerProvider = new AttachableListenerProvider();
            $eventDispatcher = new EventDispatcher($listenerProvider);
            $eventDispatcherConfigurator = new PhlyEventDispatcherConfigurator($eventDispatcher, $listenerProvider, $this->getContainer());
        }

        $this->eventConfigurator = $eventDispatcherConfigurator;
    }

    /**
     * @param ModuleManagerInterface|null $moduleManager
     */
    protected function createModuleManagerIfEmpty(ModuleManagerInterface $moduleManager = null)
    {
        if (!$moduleManager) {
            $moduleResolver = new ContainerModuleResolver($this->getContainer());
            $moduleManager = new ModuleManager($this->modules(), $moduleResolver);
        }

        $this->moduleManager = $moduleManager;
    }

    /**
     * @return StrategyInterface
     */
    protected function createDispatcherStrategy(): StrategyInterface
    {
        $strategy = new Dispatcher();
        $strategy->setContainer($this->getContainer());
        return $strategy;
    }

    /**
     * @param ResponseInterface $response
     */
    protected function emit(ResponseInterface $response)
    {
        $emitterStack = new EmitterStack();
        $emitterStack->push(new SapiEmitter());
        $emitterStack->push(new ConditionalEmitter(new SapiStreamEmitter()));
        $emitterStack->emit($response);
    }

    /**
     * Return kernel container configuration
     * @return array
     */
    abstract protected function di(): array;

    /**
     * Return kernel event configuration
     * @return array
     */
    abstract protected function events(): array;

    /**
     * Return kernel default routes
     * @return array
     */
    abstract protected function routes(): array;

    /**
     * Return kernel default middleware
     * @return array
     */
    abstract protected function middleware(): array;

    /**
     * @return array
     */
    abstract protected function modules(): array;
}