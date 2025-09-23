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

    $routes->resource('user_sap_link', [
        'filter' => 'permission:user_sap_link-permission',
        'controller' => 'user_sap_linkController',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
        'except' => 'show'
    ]);

    $routes->post('user_sap_link/save'
            , 'User_sap_linkController::save'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('sapservicelayer/getUsersSAPAjax'
            , 'User_sap_linkController::usersSAPSelect2'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('sapservicelayer/getUsersAjax'
            , 'User_sap_linkController::getUsersAjaxSelect2'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('user_sap_link/getUser_sap_link'
            , 'User_sap_linkController::getUser_sap_link'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
            );
});
