<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;

class SapMaterialController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $serviceLayerController;
    protected $branchoffice;
    protected $userLinkSap;

    public function __construct() {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->branchoffice = new BranchofficesModel();
        $this->userLinkSap = new User_sap_linkModel();
        helper(['menu', 'utilerias']);
    }

    /**
     * Vista principal y endpoint AJAX para DataTables con filtro por Grupo y orden dinámico
     */
    public function index() {
        helper('auth');

        if ($this->request->isAJAX()) {
            try {
                $conn = $this->connectODBC();

                $draw = (int) ($this->request->getGet('draw') ?? 1);
                $start = (int) ($this->request->getGet('start') ?? 0);
                $length = (int) ($this->request->getGet('length') ?? 10);
                $search = $this->request->getGet('search')['value'] ?? '';
                $groupCode = $this->request->getGet('groupCode') ?? '';

                // Ordenamiento dinámico
                $orderParam = $this->request->getGet('order');
                $orderColIndex = isset($orderParam[0]['column']) ? (int) $orderParam[0]['column'] : 1;
                $orderDirRaw = isset($orderParam[0]['dir']) ? strtolower($orderParam[0]['dir']) : 'asc';
                $orderDir = ($orderDirRaw === 'desc') ? 'DESC' : 'ASC';

                $columnsMap = [
                    1 => 'T0."ItemCode"',
                    2 => 'T0."ItemName"',
                    3 => 'T1."ItmsGrpNam"',
                    4 => 'T0."BuyUnitMsr"',
                    5 => 'T0."PriceUnit"',
                    6 => 'T0."VATLiable"',
                    7 => 'T0."validFor"',
                ];

                $orderBy = $columnsMap[$orderColIndex] ?? 'T0."ItemCode"';

                // Filtro de búsqueda
                $whereExtra = '';
                if (!empty($search)) {
                    $searchClean = str_replace("'", "''", trim($search));
                    $whereExtra .= "
                        AND (
                            T0.\"ItemCode\" LIKE '%{$searchClean}%'
                            OR T0.\"ItemName\" LIKE '%{$searchClean}%'
                            OR T1.\"ItmsGrpNam\" LIKE '%{$searchClean}%'
                        )
                    ";
                }

                // Filtro por Grupo
                if (!empty($groupCode)) {
                    $groupClean = (int) $groupCode;
                    $whereExtra .= " AND T0.\"ItmsGrpCod\" = {$groupClean} ";
                }

                // Total sin filtrar
                $sqlTotal = "SELECT COUNT(1) AS \"total\" FROM OITM WHERE \"PrchseItem\" = 'Y'";
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $totalRecords = 0;
                if ($rsTotal && ($rowTotal = odbc_fetch_array($rsTotal))) {
                    $totalRecords = (int) ($rowTotal['total'] ?? $rowTotal['TOTAL'] ?? 0);
                    odbc_free_result($rsTotal);
                }

                // Total con filtros
                $sqlFiltered = "
                    SELECT COUNT(1) AS \"total\" 
                    FROM OITM T0 
                    LEFT JOIN OITB T1 ON T0.\"ItmsGrpCod\" = T1.\"ItmsGrpCod\"
                    WHERE T0.\"PrchseItem\" = 'Y' {$whereExtra}
                ";
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $filteredRecords = $totalRecords;
                if ($rsFiltered && ($rowFiltered = odbc_fetch_array($rsFiltered))) {
                    $filteredRecords = (int) ($rowFiltered['total'] ?? $rowFiltered['TOTAL'] ?? 0);
                    odbc_free_result($rsFiltered);
                }

                // Consulta paginada
                $sql = "
                    SELECT
                        T0.\"ItemCode\",
                        T0.\"ItemName\",
                        T0.\"BuyUnitMsr\",
                        T0.\"PriceUnit\",
                        T0.\"ItmsGrpCod\",
                        T1.\"ItmsGrpNam\",
                        T0.\"VATLiable\",
                        T0.\"validFor\"
                    FROM OITM T0
                    LEFT JOIN OITB T1 ON T0.\"ItmsGrpCod\" = T1.\"ItmsGrpCod\"
                    WHERE T0.\"PrchseItem\" = 'Y'
                      {$whereExtra}
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT {$length} OFFSET {$start}
                ";

                $rs = odbc_exec($conn, $sql);
                if (!$rs) {
                    throw new \Exception('Error al consultar artículos: ' . odbc_errormsg($conn));
                }

                $data = [];
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'ItemCode' => $this->toUtf8($row['ItemCode']),
                        'ItemName' => $this->toUtf8($row['ItemName']),
                        'BuyUnitMsr' => $this->toUtf8($row['BuyUnitMsr'] ?? ''),
                        'PriceUnit' => $this->toUtf8($row['PriceUnit'] ?? '1'),
                        'ItmsGrpCod' => $this->toUtf8($row['ItmsGrpCod'] ?? ''),
                        'ItmsGrpNam' => $this->toUtf8($row['ItmsGrpNam'] ?? ''),
                        'VATLiable' => $this->toUtf8($row['VATLiable'] ?? 'Y'),
                        'validFor' => $this->toUtf8($row['validFor'] ?? 'Y')
                    ];
                }

                odbc_free_result($rs);
                odbc_close($conn);

                return $this->response->setJSON([
                            'draw' => $draw,
                            'recordsTotal' => $totalRecords,
                            'recordsFiltered' => $filteredRecords,
                            'data' => $data
                ]);
            } catch (\Throwable $e) {
                return $this->response->setJSON([
                            'draw' => (int) ($this->request->getGet('draw') ?? 1),
                            'recordsTotal' => 0,
                            'recordsFiltered' => 0,
                            'data' => [],
                            'error' => true,
                            'message' => $e->getMessage()
                ]);
            }
        }

        $data = [
            'title' => 'Artículos SAP',
            'subtitle' => 'Catálogo de Artículos de Compra',
            'box_title' => 'Listado de Artículos'
        ];

        return view('julio101290\boilerplateservicelayer\Views\materials', $data);
    }

    /**
     * Calcula el siguiente ItemCode con prefijo y 5 ceros (ej. RMM -> RMM00003)
     */
    public function getNextItemCode($prefix = '') {
        try {
            $prefix = strtoupper(trim(urldecode($prefix)));
            $cleanPrefix = preg_replace('/[^A-Z0-9_\-]/', '', $prefix);

            if (empty($cleanPrefix)) {
                return $this->response->setJSON([
                            'status' => 400,
                            'nextCode' => ''
                ]);
            }

            $conn = $this->connectODBC();

            $sql = "
                SELECT \"ItemCode\"
                FROM OITM
                WHERE \"ItemCode\" LIKE '{$cleanPrefix}%'
                ORDER BY \"ItemCode\" DESC
            ";

            $rs = odbc_exec($conn, $sql);
            $maxNumber = 0;
            $prefixLen = strlen($cleanPrefix);

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code = trim($this->toUtf8($row['ItemCode']));
                    $numericSuffix = substr($code, $prefixLen);

                    if (is_numeric($numericSuffix)) {
                        $num = (int) $numericSuffix;
                        if ($num > $maxNumber) {
                            $maxNumber = $num;
                        }
                    }
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            $nextNumber = $maxNumber + 1;
            $nextCode = $cleanPrefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            return $this->response->setJSON([
                        'status' => 200,
                        'prefix' => $cleanPrefix,
                        'nextCode' => $nextCode
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'status' => 500,
                        'nextCode' => '',
                        'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Catálogo de Grupos de Artículos (OITB) para Select2
     */
    public function getItemGroupsAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn = $this->connectODBC();

            $where = '';
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where = " WHERE \"ItmsGrpNam\" LIKE '%{$searchClean}%' ";
            }

            $sql = "
                SELECT \"ItmsGrpCod\", \"ItmsGrpNam\"
                FROM OITB
                {$where}
                ORDER BY \"ItmsGrpNam\" ASC
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $data[] = [
                    'id' => (int) $row['ItmsGrpCod'],
                    'text' => $this->toUtf8($row['ItmsGrpCod']) . ' - ' . $this->toUtf8($row['ItmsGrpNam'])
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'data' => [],
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener detalle de un artículo por ItemCode
     */
    public function getMaterial($itemCode = null) {
        try {
            if (empty($itemCode)) {
                return $this->response->setJSON(['error' => true, 'message' => lang('material.messages.invalid_code')]);
            }

            $itemCodeClean = str_replace("'", "''", trim(urldecode($itemCode)));
            $conn = $this->connectODBC();

            $sql = "
                SELECT
                    T0.\"ItemCode\",
                    T0.\"ItemName\",
                    T0.\"ItemType\",
                    T0.\"ItmsGrpCod\",
                    T1.\"ItmsGrpNam\",
                    T0.\"BuyUnitMsr\",
                    T0.\"PriceUnit\",
                    T0.\"VATLiable\",
                    T0.\"PrchseItem\",
                    T0.\"InvntItem\",
                    T0.\"SellItem\",
                    T0.\"validFor\"
                FROM OITM T0
                LEFT JOIN OITB T1 ON T0.\"ItmsGrpCod\" = T1.\"ItmsGrpCod\"
                WHERE T0.\"ItemCode\" = '{$itemCodeClean}'
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $row = odbc_fetch_array($rs);
            odbc_free_result($rs);
            odbc_close($conn);

            if (!$row) {
                return $this->response->setJSON(['error' => true, 'message' => lang('material.messages.not_found')]);
            }

            $itemType = $this->toUtf8($row['ItemType'] ?? 'itItems');
            if ($itemType === 'I')
                $itemType = 'itItems';
            if ($itemType === 'L')
                $itemType = 'itLabor';
            if ($itemType === 'T')
                $itemType = 'itTravel';

            return $this->response->setJSON([
                        'ItemCode' => $this->toUtf8($row['ItemCode']),
                        'ItemName' => $this->toUtf8($row['ItemName']),
                        'ItemType' => $itemType,
                        'ItmsGrpCod' => (int) $row['ItmsGrpCod'],
                        'ItmsGrpNam' => $this->toUtf8($row['ItmsGrpNam'] ?? ''),
                        'BuyUnitMsr' => $this->toUtf8($row['BuyUnitMsr'] ?? ''),
                        'PriceUnit' => $row['PriceUnit'] ?? 1,
                        'VATLiable' => $this->toUtf8($row['VATLiable'] ?? 'Y'),
                        'PrchseItem' => $this->toUtf8($row['PrchseItem'] ?? 'Y'),
                        'InvntItem' => $this->toUtf8($row['InvntItem'] ?? 'Y'),
                        'SellItem' => $this->toUtf8($row['SellItem'] ?? 'N'),
                        'validFor' => $this->toUtf8($row['validFor'] ?? 'Y')
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Catálogo de Unidades de Medida (OUOM) para Select2
     */
    public function getUnitsAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = " WHERE \"Locked\" = 'N' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    \"UomCode\" LIKE '%{$searchClean}%' 
                    OR \"UomName\" LIKE '%{$searchClean}%'
                ) ";
            }

            $sql = "
                SELECT \"UomCode\", \"UomName\"
                FROM OUOM
                {$where}
                ORDER BY \"UomCode\" ASC
                LIMIT 30
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL en OUOM: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $code = $this->toUtf8($row['UomCode']);
                $name = $this->toUtf8($row['UomName']);
                $data[] = [
                    'id'   => $code,
                    'text' => $code . ($name ? ' - ' . $name : '')
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON(['data' => $data]);

        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'data'    => [],
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Búsqueda AJAX para Select2 general
     */
    public function getItemsAjax() {
        try {
            $postData = $this->request->getPost();
            $response = ['token' => csrf_hash()];

            $conn = $this->connectODBC();

            $whereSearch = '';
            if (!empty($postData['searchTerm'])) {
                $search = str_replace("'", "''", trim($postData['searchTerm']));
                $whereSearch = "
                    AND (
                        \"ItemCode\" LIKE '%{$search}%'
                        OR \"ItemName\" LIKE '%{$search}%'
                    )
                ";
            }

            $sql = "
                SELECT
                    \"ItemCode\",
                    \"ItemName\"
                FROM OITM
                WHERE \"PrchseItem\" = 'Y'
                  AND \"validFor\" = 'Y'
                  AND \"frozenFor\" = 'N'
                  {$whereSearch}
                ORDER BY \"ItemCode\"
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $itemCode = $this->toUtf8($row['ItemCode']);
                $itemName = $this->toUtf8($row['ItemName']);

                $data[] = [
                    'id' => $itemCode,
                    'text' => $itemCode . ' - ' . $itemName
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            $response['data'] = $data;
            return $this->response->setJSON($response);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'token' => csrf_hash(),
                        'data' => [],
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Guarda (crea o actualiza) el artículo estrictamente vía Service Layer
     */
    public function save() {
        helper('auth');
        $userName = user()->username;

        $post = $this->request->getPost();

        $isNew = (int) ($post['isNew'] ?? 1);
        $itemCode = strtoupper(trim($post['ItemCode'] ?? ''));
        $itemName = trim($post['ItemName'] ?? '');
        $itemType = $post['ItemType'] ?? 'itItems';
        $itmsGrpCod = (int) ($post['ItmsGrpCod'] ?? 0);
        $buyUnitMsr = strtoupper(trim($post['BuyUnitMsr'] ?? ''));
        $vatLiable = ($post['VATLiable'] ?? 'Y') === 'Y' ? 'tYES' : 'tNO';
        $validFor = ($post['validFor'] ?? 'Y') === 'Y' ? 'tYES' : 'tNO';

        // Validaciones obligatorias de campos
        if (empty($itemCode) || empty($itemName)) {
            return $this->respond([
                        'status' => 400,
                        'message' => lang('material.messages.code_required')
                            ], 400);
        }

        if ($itmsGrpCod <= 0) {
            return $this->respond([
                        'status' => 400,
                        'message' => lang('material.messages.group_required')
                            ], 400);
        }

        if (empty($buyUnitMsr)) {
            return $this->respond([
                        'status' => 400,
                        'message' => lang('material.messages.unit_required')
                            ], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
        }

        // Validación de unicidad: ODBC en modo SOLO LECTURA (SELECT)
        if ($isNew === 1) {
            try {
                $conn = $this->connectODBC();
                $itemCodeClean = str_replace("'", "''", $itemCode);
                $rsCheck = odbc_exec($conn, "SELECT COUNT(1) AS \"cnt\" FROM OITM WHERE \"ItemCode\" = '{$itemCodeClean}'");
                $exists = 0;
                if ($rsCheck && ($row = odbc_fetch_array($rsCheck))) {
                    $exists = (int) ($row['cnt'] ?? $row['CNT'] ?? 0);
                    odbc_free_result($rsCheck);
                }
                odbc_close($conn);

                if ($exists > 0) {
                    return $this->respond([
                                'status' => 400,
                                'message' => lang('material.messages.code_exists')
                                    ], 400);
                }
            } catch (\Throwable $e) {
                return $this->respond(['status' => 500, 'message' => 'Error al validar código: ' . $e->getMessage()], 500);
            }
        }

        // Login a Service Layer
        try {
            $conexionSap = $this->serviceLayerController->login(
                    $dataSL['url'],
                    $dataSL['port'],
                    $dataSL['password'],
                    $dataSL['username'],
                    $dataSL['companyDB']
            );
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error login SL: ' . $e->getMessage()], 500);
        }

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId de Service Layer'], 500);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $baseHeaders = [
            "Accept: application/json",
            "Content-Type: application/json",
            "User-Agent: PHP",
            "B1S-CaseInsensitive: true"
        ];

        // Payload de Service Layer: propiedades válidas y nativas de 'Item'
        $payload = [
            'ItemName' => $itemName,
            'ItemType' => $itemType,
            'ItemsGroupCode' => $itmsGrpCod,
            'PurchaseUnit' => $buyUnitMsr,
            'VatLiable' => $vatLiable,
            'Valid' => $validFor,
            'PurchaseItem' => 'tYES',
            'InventoryItem' => 'tYES',
            'SalesItem' => 'tNO'
        ];

        if ($isNew === 1) {
            $payload['ItemCode'] = $itemCode;
            $url = $slRoot . "/Items";
            $method = 'POST';
        } else {
            $url = $slRoot . "/Items('" . rawurlencode($itemCode) . "')";
            $method = 'PATCH';
        }

        $jsonPayload = json_encode($payload);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $baseHeaders,
            CURLOPT_TIMEOUT => 60
        ]);

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return $this->respond(['status' => 500, 'message' => 'cURL Error: ' . $err], 500);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode($resp, true);
            $msgError = $body['error']['message']['value'] ?? $body['error']['message'] ?? 'Error en Service Layer';
            return $this->respond(['status' => $httpCode, 'message' => $msgError, 'body' => $body], $httpCode);
        }

        $dataResp = null;
        if ($method === 'POST') {
            $dataResp = json_decode($resp, true);
        }

        // Bitácora de operaciones
        $this->log->save([
            "description" => ($isNew === 1 ? "Creación" : "Actualización") . " de artículo de compra '{$itemCode}'",
            "user" => $userName
        ]);

        return $this->respond([
                    'status' => 200,
                    'message' => ($isNew === 1 ? lang('material.messages.saved') : 'Artículo actualizado correctamente'),
                    'data' => $dataResp
                        ], 200);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

    /**
     * Conexión ODBC usando la configuración de la BD
     */
    private function connectODBC() {
        $dataConect = $this->serviceLayerModel->first();
        if (!$dataConect) {
            throw new \Exception('No se encontró configuración de conexión SAP.');
        }

        $conn = odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
        );

        if (!$conn) {
            throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
        }

        if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"')) {
            throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
        }

        return $conn;
    }

    /**
     * Normalizador a UTF-8
     */
    private function toUtf8($value) {
        if (is_null($value)) {
            return '';
        }
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
}
