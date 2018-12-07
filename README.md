# voodoo-kernel
Kernel package for my personal project skeleton

Example index.php

```php
<?php

require_once __DIR__.'/../vendor/autoload.php';

$moduleManager = new \Voodoo\Module\ModuleManager(
    __DIR__."/../config/modules.php",
    new \Voodoo\Module\NewModuleLoader()
);

$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

$containerBuilder = new Aura\Di\ContainerBuilder();
$container = $containerBuilder->newConfiguredInstance(
    [new \Voodoo\Di\ConfigurationBundle($eventDispatcher)],
    true
);

$application = new \Voodoo\Kernel\GenericKernel();

$application->dispatch(
    $container,
    $moduleManager,
    $eventDispatcher
);
```