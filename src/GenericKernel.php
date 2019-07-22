<?php

namespace Voodoo\Kernel;

/**
 * Class GenericKernel
 * @package Voodoo\Kernel
 */
class GenericKernel extends Kernel
{
    /**
     * @return array
     */
    protected function middleware(): array
    {
        return [

        ];
    }

    /**
     * @return array
     */
    protected function events(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function routes(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function di(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function modules(): array
    {
        return [];
    }
}