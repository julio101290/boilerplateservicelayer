<?php

$routes->group('admin', function ($routes) {





    $routes->resource('sapservicelayer', [
        'filter' => 'permission:servicelayer-permission',
        'controller' => 'SapservicelayerController',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
    ]);

    $routes->post('sapservicelayer/save'
            , 'SapservicelayerController::save'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('sapservicelayer/getSapservicelayer'
            , 'SapservicelayerController::getSapservicelayer'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    /*
      $routes->resource('products', [
      'filter' => 'permission:products-permission'
      ,'controller' => 'productsController'
      ,'except' => 'show'
      ,'namespace' => 'julio101290\boilerplateproducts\Controllers'

      ]);
     * 
     * 
     */
});
