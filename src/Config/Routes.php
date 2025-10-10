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

    $routes->resource('servicelayer/getauthreq', [
        'filter' => 'permission:reqauth-permission',
        'controller' => 'RequisitionAuthController',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
    ]);

    $routes->post('servicelayer/authorizeReq'
            , 'RequisitionAuthController::authorizeReq'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('servicelayer/authorizeReq',
            'ServiceLayerController::authorizeReq',
            [
                'filter' => 'permission:authorize-permission',
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    $routes->post('servicelayer/showlistProductsReq',
            'RequisitionAuthController::showReqItems',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    // Purchase Orders (mismo esquema que RequisitionAuth)
    $routes->resource('servicelayer/getauthpo', [
        'filter' => 'permission:poauth-permission', // cambia el permiso si quieres otro
        'controller' => 'PurchaseAuthController',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
    ]);

// Autorizar Purchase Order (PATCH via controlador)
    $routes->post('servicelayer/authorizePO',
            'PurchaseAuthController::authorizeOrder',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

// Obtener/mostrar líneas del PO (DataTables / modal)
    $routes->post('servicelayer/showlistProductsPO',
            'PurchaseAuthController::showPOItems',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

// Select2 users (reutiliza el método getUsersAjaxSelect2 si lo tienes)
    $routes->post('servicelayer/getUsersAjaxSelect2',
            'PurchaseOrderAuthController::getUsersAjaxSelect2',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    // Purchase Orders (mismo esquema que RequisitionAuth)
    $routes->resource('servicelayer/pricelistsap', [
        'filter' => 'permission:listprice-permission', // cambia el permiso si quieres otro
        'controller' => 'PricelistController',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
    ]);

    // Select2 users (reutiliza el método getUsersAjaxSelect2 si lo tienes)
    $routes->post('servicelayer/loaddatatable',
            'PricelistController::loadDatatable',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );
});
