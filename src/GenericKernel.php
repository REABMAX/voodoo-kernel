<?php

namespace Voodoo\Kernel;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Voodoo\Kernel\Contracts\KernelInterface;
use Voodoo\Kernel\Emitter\ConditionalEmitter;
use Voodoo\Kernel\Event\MiddlewareRegisteredEvent;
use Voodoo\Module\Contracts\ModuleManagerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use Zend\Stratigility\Middleware\ErrorResponseGenerator;
use Zend\Stratigility\MiddlewarePipe;

/**
 * Class GenericKernel
 * @package Voodoo\Kernel
 */
class GenericKernel implements KernelInterface
{
    /**
     * @var MiddlewarePipe
     */
    protected $middlewareStack = null;

    /**
     * Dispatch the whole thing
     *
     * @param ContainerInterface $container
     * @param ModuleManagerInterface $moduleManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function dispatch(
        ContainerInterface $container,
        ModuleManagerInterface $moduleManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $container->set(EventDispatcherInterface::class, $eventDispatcher);
        $container->set(ModuleManagerInterface::class, $moduleManager);

        $moduleManager->configureDependencyInjection($container);
        $moduleManager->bootstrapModules($eventDispatcher, $container);

        $eventDispatcher->dispatch('core.middleware_register', new MiddlewareRegisteredEvent($this->middlewareStack));

        $emitter = $this->createEmitter();
        $runner = new RequestHandlerRunner(
            $this->middlewareStack,
            $emitter,
            [ServerRequestFactory::class, 'fromGlobals'],
            function(\Throwable $e) {
                $generator = new ErrorResponseGenerator();
                return $generator($e, new ServerRequest(), new Response());
            }
        );

        $runner->run();
    }

    /**
     * @return mixed
     */
    protected function createEmitter(): EmitterInterface
    {
        $sapiStreamEmitter = new SapiStreamEmitter();
        $sapiEmitter = new SapiEmitter();
        $conditionalEmitter = new ConditionalEmitter($sapiStreamEmitter);
        $stack = new EmitterStack();
        $stack->push($sapiEmitter);
        $stack->push($conditionalEmitter);
        return $stack;
    }
}