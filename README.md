# voodoo-kernel

This is a psr-ready php kernel package.

## Quick start

First, create your kernel class

```php
<?php

namespace My\Project;

/**
* Class Kernel
 * @package My\Project
 */
class Kernel extends \Voodoo\Kernel\Kernel
{
   protected function middleware() : array
   {
     return [
        My\Psr\Middleware::class => 99,    
     ];
   }
   
   protected function routes() : array
   {
     return [
        "welcome" => [
            "path" => "/",
            "method" => "GET",
            "action" => My\Action::class,    
        ],
        "welcome_name" => [
            "path" => "/{name}",
            "method" => "GET",
            "action" => My\Action::class,   
        ],
     ];
   }
   
   protected function di() : array
   {
     return [
        "definitions" => [
            My\Class::class => [
                "arguments" => [
                    My\Dependency::class,
                    "string argument"  
                ],
                "setters" => [
                    "injectConfiguration"  => [
                        "argument1",
                        "argument2"  
                    ],
                ],   
            ],  
        ],    
        "factories" => [
            My\Class::class => function() {
                return new My\Class("Argument"),
            },
        ],
        "aliases" => [
            My\ClassInterface::class => My\ClassImplementation::class,  
        ],
     ];
   }
   
   protected function events() : array
   {
       // Hand over invokables
     return [
        My\Event::class => [
            new My\EventListenerObject(),
            function (My\Event $event) {
                // Do something
            }.
            My\OtherEventListener::class,
        ],     
    ];
   }
   
   protected function modules() : array
   {
       // Instances of voodoo/module ModuleInterface
     return [
        My\FirstModule::class,
        My\SecondModule::class,    
     ];
   }
}
```

Then, create your front controller (index.php)

```php
<?php

include __DIR__.'/../vendor/autoload.php';

use My\Project\Kernel;

$kernel = new Kernel();
$kernel->dispatch();

```

## Extended

You can define your IoC container, event listener, configuration manager, module manager etc.
yourself:

```php
<?php

// Must implement Voodoo\Di\Contracts\ContainerConfiguratorInterface
$containerConfigurator = new MyContainerConfigurator();

// Must implement Voodoo\Di\Configuration\Contracts\ConfigurationManagerInterface
$configurationManager = new MyConfigurationManager();

// Must implement Voodoo\Event\Contracts\EventDispatcherConfiguratorInterface
$eventDispatcherConfigurator = new MyEventDispatcherConfigurator();

// Must implement Voodoo\Module\Contracts\ModuleManagerInterface;
$moduleManager = new MyModuleManager();

$kernel = new Kernel($containerConfigurator, $configurationManager, $eventDispatcherConfigurator, $moduleManager):

```

Default ContainerConfigurator: `Voodoo\Di\Configurators\LeagueContainerConfigurator`

Default ConfigurationManager: `Voodoo\Configuration\ConfigurationManager`

Default EventDispatcherConfigurator: `Voodoo\Event\Configurators\PhlyEventDispatcherConfigurator`

Default ModuleManager: `Voodoo\Module\ModuleManager`