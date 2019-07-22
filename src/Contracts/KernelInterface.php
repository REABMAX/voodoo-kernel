<?php

namespace Voodoo\Kernel\Contracts;

use Psr\EventDispatcher\ListenerProviderInterface;
use Voodoo\Di\Contracts\ContainerConfiguratorInterface;
use Voodoo\Module\Contracts\ModuleManagerInterface;
use Voodoo\Router\Contracts\RouterConfiguratorInterface;

/**
 * Interface KernelInterface
 * @package Voodoo\Kernel\Contracts
 */
interface KernelInterface
{
    /**
     * Dispatch the whole thing
     */
    public function dispatch();
}