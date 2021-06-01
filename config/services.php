<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\EventListener\RequestListener;
use App\EventListener\ResponseListener;
use App\EventListener\ExceptionListener;

return function(ContainerConfigurator $configurator) {


    $configurator->parameters()
    // the parameter name is an arbitrary string (the 'app.' prefix is recommended
    // to better differentiate your parameters from Symfony parameters).
        ->set('locale', 'en')
        ->set('env', '%env(APP_ENV)%')
        ->set('fallbacks', ['%locale%', 'fr'])
        ->set('secret', '%env(APP_SECRET)%')
        ->set('myd_version', '%env(MYDDLEWARE_VERSION)%');    


    // default configuration for services in *this* file
    $services = $configurator->services()
    ->defaults()
    ->autowire()      // Automatically injects dependencies in your services.
    ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
    ;

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('App\\', '../src/*')
        ->exclude('../src/{DependencyInjection,Entity,Migrations,Tests,Kernel.php,Solutions/lib}');

    // // Controller is loaded separately
    // $services->load('App\Controller', '../src/Controller');
    // $services->set(Controller::class)->tag('controller.service_arguments');
    $services
    ->load('App\\Controller\\', '../src/Controller')
    ->tag('controller.service_arguments');

    $services->set(RequestListener::class)
            ->autowire()
            ->tag('kernel.event_listener', [
                                            'event' => 'kernel.request',
                                            'method' => 'onKernelRequest'
                                            ]);

    $services->set(ResponseListener::class)
            ->tag('kernel.event_listener', [
                                            'event' => 'kernel.response',
                                            'method' => 'onKernelResponse'
                                            ]);

    $services->set(ExceptionListener::class)
            ->tag('kernel.event_listener', [
                                            'event' => 'kernel.exception'
                                            ]);

    $services->get('App\Manager\SolutionManager')
            ->public(false)
            ->autowire();       


    $services->load('App\\DataFixtures\\', '../src/DataFixtures')
            ->tag('doctrine.fixture.orm');

	$services->set(woocommerce::class);

    $services->set(woocommercecustom::class)
            ->decorate(woocommerce::class);
		
    // if(file_exists('../config/services_custom.php')){
        // $configurator->import('../config/services_custom.php', null, true);
    // }
};

