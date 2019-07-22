<?php

namespace Voodoo\Kernel;

use League\Route\Route;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class VoodooAdrStrategy
 * @package Voodoo\Adr\Dispatcher
 */
class Dispatcher extends ApplicationStrategy
{
    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface
    {
        foreach ($route->getVars() as $name => $argument) {
            $request = $request->withAttribute($name, $argument);
        }

        $response = call_user_func_array($route->getCallable($this->getContainer()), [$request]);

        return $response;
    }
}