<?php

namespace Voodoo\Kernel\Contracts;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Voodoo\Module\Contracts\ModuleManagerInterface;

/**
 * Interface KernelInterface
 * @package Voodoo\Kernel\Contracts
 */
interface KernelInterface
{
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
    );
}