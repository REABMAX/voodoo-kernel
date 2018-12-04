<?php

namespace Voodoo\Kernel\Emitter;

use Psr\Http\Message\ResponseInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

/**
 * Class ConditionalEmitter
 * @package Voodoo\Kernel\Emitter
 */
class ConditionalEmitter implements EmitterInterface
{
    /**
     * @var EmitterInterface
     */
    protected $emitter;

    /**
     * ConditionalEmitter constructor.
     * @param EmitterInterface $emitter
     */
    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    public function emit(ResponseInterface $response): bool
    {
        if(!$response->hasHeader('Content-Disposition') && !$response->hasHeader('Content-Range')) {
            return false;
        }
        return $this->emitter->emit($response);
    }
}