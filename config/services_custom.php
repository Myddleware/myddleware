<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Solutions\woocommerce;
use App\Custom\Solutions\woocommercecustom; 

return function(ContainerConfigurator $configurator) {
        echo 'bonsoir';
    $services = $configurator->services();
    // $services->set(woocommerce::class);
    $services->set(woocommercecustom::class)
        ->decorate(woocommerce::class);
        // ->args([ref(woocommercecustom::class.'.woo')]);
    var_dump($services->get(woocommercecustom::class));

    
//   App\Custom\Solutions\woocommercecustom:
//   decorates: App\Solutions\woocommerce
//   # pass the old service as an argument
//   # arguments: ['@App\Custom\Solutions\woocommercecustom.inner']
//   decoration_priority: 5
};