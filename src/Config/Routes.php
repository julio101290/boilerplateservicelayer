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

    $routes->post('servicelayer/deauthorizeReq',
            'ServiceLayerController::deauthorizeReq',
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

    $routes->post('servicelayer/deauthorizeOrder',
            'PurchaseAuthController::deauthorizeOrder',
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

    $routes->resource('link_sap_branchoffice', [
        'filter' => 'permission:link_sap_branchoffice-permission',
        'controller' => 'link_sap_branchofficeController',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
        'except' => 'show'
    ]);

    $routes->post('link_sap_branchoffice/save'
            , 'Link_sap_branchofficeController::save',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    $routes->post('link_sap_branchoffice/getLink_sap_branchoffice'
            , 'Link_sap_branchofficeController::getLink_sap_branchoffice',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    // Select2 users (reutiliza el método getUsersAjaxSelect2 si lo tienes)
    $routes->post('branchoffice/getBranchOfficeSAPAjax',
            'Link_sap_branchofficeController::getSucursalesAjax',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    $routes->get('servicelayer/analizadorSAPXML',
            'CFDISAPController::analizadorCFDI',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->get('servicelayer/analizadorSAPXML',
            'CFDISAPController::analizadorCFDI',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
                'filter' => 'permission:analizadorCFDI-permission'
            ]
    );

    // Para procesar el archivo Excel
    $routes->post('servicelayer/procesarAnalisisCFDI',
            'CFDISAPController::procesarAnalisisCFDI',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->get('refundsauth',
            'RefundsAuthController::index',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
                'filter' => 'permission:refundsauth-permission',
            ]
    );

    $routes->get('servicelayer/refundsauth',
            'RefundsAuthController::index',
            [
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
            ]
    );

    //$routes->get('refundsauth', 'RefundsAuthController::index');
    $routes->post('servicelayer/refundsauth/authorizeVoucher'
            , 'RefundsAuthController::authorizeVoucher'
            , [
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
            ]
    );

    $routes->post('servicelayer/refundsauth/showVoucherDetails'
            , 'RefundsAuthController::showVoucherDetails'
            , [
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers',
            ]
    );

    /**
     * Refunds
     */
    $routes->get('servicelayer/listRefunds'
            , 'RefundsController::index'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->get('servicelayer/newRefund'
            , 'RefundsController::newRefund'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('servicelayer/refunds/authorizeVoucher'
            , 'RefundsController::authorizeVoucher'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('servicelayer/refunds/showVoucherDetails'
            , 'RefundsController::showVoucherDetails'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('servicelayer/refunds/getUser_sap_link'
            , 'RefundsController::getUser_sap_link'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('servicelayer/refunds/save'
            , 'RefundsController::save'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->get('servicelayer/refunds/delete/(:num)'
            , 'RefundsController::delete/$1'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('servicelayer/refunds/getUsersAjaxSelect2'
            , 'RefundsController::getUsersAjaxSelect2'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('brachofficeSAP/getBranchofficeAjax'
            , 'BranchofficesController::getBranchofficeAjax'
            , ['namespace' => 'julio101290\boilerplatebranchoffice\Controllers']
    );

    $routes->post('sapBranchOffice/getBranchOfficesAjax'
            , 'SapBranchofficeController::getBranchofficeAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->post('SAPEmployess/getSAPEmployeAjax'
            , 'SapEmployeesController::getEmployeesAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // EmployeeSAPController
    $routes->get('servicelayer/employees'
            , 'EmployeeSAPController::index'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    $routes->get('servicelayer/employees/getEmployee/(:num)'
            , 'EmployeeSAPController::getEmployee/$1'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->post('servicelayer/employees/save'
            , 'EmployeeSAPController::save'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->delete('servicelayer/employees/delete/(:num)'
            , 'EmployeeSAPController::delete/$1', ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->post('servicelayer/employees/getEmployeesAjaxSelect2'
            , 'EmployeeSAPController::getEmployeesAjaxSelect2'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    // Roles de empleados
    $routes->get('servicelayer/employees/getEmployeeRoles/(:num)'
            , 'EmployeeSAPController::getEmployeeRoles/$1'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->post('servicelayer/employees/getRolesAjaxSelect2'
            , 'EmployeeSAPController::getRolesAjaxSelect2'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->post('servicelayer/employees/addEmployeeRole'
            , 'EmployeeSAPController::addEmployeeRole'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->delete('servicelayer/employees/removeEmployeeRole/(:num)/(:any)'
            , 'EmployeeSAPController::removeEmployeeRole/$1/$2'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

    $routes->post('servicelayer/employees/updateEmployeeRole'
            , 'EmployeeSAPController::updateEmployeeRole'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']);

// ==========================================
    // RUTAS PARA ARTÍCULOS SAP (OITM)
    // ==========================================
    // Consulta rápida vía ODBC para Select2 general
    $routes->post('SAPMaterials/getSAPMaterialAjax'
            , 'SapMaterialController::getItemsAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Listado y AJAX para Datatables (con filtro de grupo)
    $routes->get('servicelayer/materials'
            , 'SapMaterialController::index'
            , [
        'filter' => 'permission:SAPMaterials-permission',
        'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    // Consecutivo automático con 5 ceros (ej. RMM -> RMM00003)
    $routes->get('servicelayer/materials/getNextItemCode/(:segment)'
            , 'SapMaterialController::getNextItemCode/$1'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Grupos de Artículos (OITB) para los Select2 (Filtro y Modal)
    $routes->get('servicelayer/materials/getItemGroupsAjax'
            , 'SapMaterialController::getItemGroupsAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Obtener datos del artículo para editar
    $routes->get('servicelayer/materials/getMaterial/(:segment)'
            , 'SapMaterialController::getMaterial/$1'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Guardar / Actualizar en Service Layer
    $routes->post('servicelayer/materials/save'
            , 'SapMaterialController::save'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Búsqueda AJAX para selects dentro de otros formularios
    $routes->post('servicelayer/materials/getMaterialsAjaxSelect2'
            , 'SapMaterialController::getItemsAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Unidades de Medida (OUOM) para Select2
    $routes->get('servicelayer/materials/getUnitsAjax'
            , 'SapMaterialController::getUnitsAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Unidades de Medida (OUOM) para Select2
    $routes->get('servicelayer/materials/getUnitsAjax'
            , 'SapMaterialController::getUnitsAjax'
            , ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // =========================================================================
    // RUTAS PARA AUTORIZACIÓN DE ALMACENES SAP (@AUTORIZACOMPRA / AutCompra)
    // =========================================================================
    // Listado y AJAX para DataTables
    $routes->get('servicelayer/sapuserauthwh',
            'SapUserAuthWHController::index',
            [
                'filter' => 'permission:SAPUserAuthWH-permission',
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    // Obtener cabecera y detalle por código de almacén para edición
    $routes->get('servicelayer/sapuserauthwh/getAuthWH/(:segment)',
            'SapUserAuthWHController::getAuthWH/$1',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Almacenes SAP (OWHS) para Select2
    $routes->get('servicelayer/sapuserauthwh/getWarehousesAjax',
            'SapUserAuthWHController::getWarehousesAjax',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Usuarios SAP (OUSR) para Select2 en el detalle
    $routes->get('servicelayer/sapuserauthwh/getSapUsersAjax',
            'SapUserAuthWHController::getSapUsersAjax',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Guardar / Actualizar UDO en Service Layer (Cabecera y líneas)
    $routes->post('servicelayer/sapuserauthwh/save',
            'SapUserAuthWHController::save',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Eliminar registro UDO por código de almacén
    $routes->post('servicelayer/sapuserauthwh/delete/(:segment)',
            'SapUserAuthWHController::delete/$1',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // =========================================================================
    // RUTAS PARA USUARIOS Y ALMACENES SAP (@USUARIOALMACEN / UsuarioAlmacen)
    // =========================================================================
    // Listado y AJAX para DataTables
    $routes->get('servicelayer/sapuserwh',
            'SapUserWHController::index',
            [
                'filter' => 'permission:SAPUserWH-permission',
                'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
            ]
    );

    // Obtener cabecera y detalle por DocEntry para edición
    $routes->get('servicelayer/sapuserwh/getUserWH/(:segment)',
            'SapUserWHController::getUserWH/$1',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Empleados SAP (OHEM) para Select2 en la cabecera
    $routes->get('servicelayer/sapuserwh/getEmployeesAjax',
            'SapUserWHController::getEmployeesAjax',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Catálogo de Almacenes SAP (OWHS) para Select2 en el detalle
    $routes->get('servicelayer/sapuserwh/getWarehousesAjax',
            'SapUserWHController::getWarehousesAjax',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Guardar / Actualizar UDO en Service Layer (Cabecera y líneas)
    $routes->post('servicelayer/sapuserwh/save',
            'SapUserWHController::save',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Eliminar registro UDO por DocEntry
    $routes->post('servicelayer/sapuserwh/delete/(:segment)',
            'SapUserWHController::delete/$1',
            ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );
    
    // =========================================================================
    // RUTAS PARA CORRECCIÓN DE ODÓMETRO / HORÓMETRO (OIGE / IGE1 / InventoryGenExits)
    // =========================================================================
    // Vista principal del módulo
    $routes->get('servicelayer/sapodometro',
        'SapOdometroController::index',
        [
            'filter' => 'permission:SAPOdometro-permission',
            'namespace' => 'julio101290\boilerplateservicelayer\Controllers'
        ]
    );

    // Búsqueda de líneas de salida por DocNum y OcrCode (ODBC)
    $routes->get('servicelayer/sapodometro/searchLines',
        'SapOdometroController::searchLines',
        ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );

    // Actualización de U_Odometro en SAP vía Service Layer (PATCH)
    $routes->post('servicelayer/sapodometro/update',
        'SapOdometroController::update',
        ['namespace' => 'julio101290\boilerplateservicelayer\Controllers']
    );
});
