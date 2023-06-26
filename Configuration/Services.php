<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('Netlogix\\Nxsolrajax\\', '../Classes/')
        ->exclude('../Classes/Event/*');
};
