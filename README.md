# voodoo-kernel
Kernel package for my personal project skeleton

Example index.php

```
<?php

require_once __DIR__.'/../vendor/autoload.php';

$moduleManager = new \Voodoo\Core\Module\ModuleManager(
    \Voodoo\Core\Path::CONFIGURATION."modules.php",
    new \Voodoo\Core\Module\ModuleLoader()
);

$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

$containerBuilder = new Aura\Di\ContainerBuilder();
$container = $containerBuilder->newConfiguredInstance(
    new \Voodoo\Core\DependencyInjection\ModuleConfigurationBundle($eventDispatcher),
    true
);

$application = new \Voodoo\Core\Kernel\Cms(
    $container,
    $moduleManager,
    $eventDispatcher
);

$application->dispatch();
```