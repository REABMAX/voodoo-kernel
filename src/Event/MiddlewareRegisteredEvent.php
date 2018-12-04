<?php

namespace Voodoo\Kernel\Event;

use Symfony\Component\EventDispatcher\Event;
use Zend\Stratigility\MiddlewarePipe;

/**
 * Class MiddlewareRegisteredEvent
 * @package Voodoo\Core\Kernel\Event
 */
class MiddlewareRegisteredEvent extends Event
{
    /**
     * @var MiddlewarePipe
     */
    protected $middlewarePipe;

    /**
     * MiddlewareRegisteredEvent constructor.
     * @param MiddlewarePipe $middlewarePipe
     */
    public function __construct(MiddlewarePipe $middlewarePipe)
    {
        $this->middlewarePipe = $middlewarePipe;
    }

    /**
     * @return MiddlewarePipe
     */
    public function getMiddlewarePipe()
    {
        return $this->middlewarePipe;
    }
}