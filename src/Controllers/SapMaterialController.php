<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;

class SapMaterialController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $branchoffice;
    protected $userLinkSap;

    public function __construct() {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log                   = new LogModel();
        $this->empresa               = new EmpresasModel();
        $this->serviceLayerModel     = new SapservicelayerModel();
        $this->branchoffice          = new BranchofficesModel();
        $this->userLinkSap           = new User_sap_linkModel();
        helper(['menu', 'utilerias']);
    }

    /**
     * Vista principal y endpoint AJAX para DataTables
     */
    public function index() {
       
        
        helper('auth');
        // Si la petición proviene de DataTables (AJAX)
        if ($this->request->isAJAX()) {
            try {
                $conn = $this->connectODBC();

                // Parámetros enviados por DataTables
                $draw   = (int) ($this->request->getGet('draw') ?? 1);
                $start  = (int) ($this->request->getGet('start') ?? 0);
                $length = (int) ($this->request->getGet('length') ?? 10);
                $search = $this->request->getGet('search')['value'] ?? '';

                // Filtro de búsqueda
                $whereSearch = '';
                if (!empty($search)) {
                    $searchClean = str_replace("'", "''", trim($search));
                    $whereSearch = "
                        AND (
                            \"ItemCode\" LIKE '%{$searchClean}%'
                            OR \"ItemName\" LIKE '%{$searchClean}%'
                        )
                    ";
                }

                // 1. Total de registros sin filtrar (solo de compra)
                $sqlTotal = "SELECT COUNT(1) AS \"total\" FROM OITM WHERE \"PrchseItem\" = 'Y'";
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $totalRecords = 0;
                if ($rsTotal && ($rowTotal = odbc_fetch_array($rsTotal))) {
                    $totalRecords = (int) ($rowTotal['total'] ?? $rowTotal['TOTAL'] ?? 0);
                    odbc_free_result($rsTotal);
                }

                // 2. Total con filtros aplicados
                $sqlFiltered = "SELECT COUNT(1) AS \"total\" FROM OITM WHERE \"PrchseItem\" = 'Y' {$whereSearch}";
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $filteredRecords = $totalRecords;
                if ($rsFiltered && ($rowFiltered = odbc_fetch_array($rsFiltered))) {
                    $filteredRecords = (int) ($rowFiltered['total'] ?? $rowFiltered['TOTAL'] ?? 0);
                    odbc_free_result($rsFiltered);
                }

                // 3. Consulta paginada (Sintaxis SAP HANA / MySQL compatible con LIMIT OFFSET)
                $sql = "
                    SELECT
                        \"ItemCode\",
                        \"ItemName\",
                        \"BuyUnitMsr\",
                        \"PrchseItem\",
                        \"validFor\"
                    FROM OITM
                    WHERE \"PrchseItem\" = 'Y'
                      {$whereSearch}
                    ORDER BY \"ItemCode\"
                    LIMIT {$length} OFFSET {$start}
                ";

                $rs = odbc_exec($conn, $sql);
                if (!$rs) {
                    throw new \Exception('Error al consultar artículos: ' . odbc_errormsg($conn));
                }

                $data = [];
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'ItemCode'   => $this->toUtf8($row['ItemCode']),
                        'ItemName'   => $this->toUtf8($row['ItemName']),
                        'BuyUnitMsr' => $this->toUtf8($row['BuyUnitMsr'] ?? ''),
                        'PrchseItem' => $this->toUtf8($row['PrchseItem'] ?? 'Y'),
                        'validFor'   => $this->toUtf8($row['validFor'] ?? 'Y')
                    ];
                }

                odbc_free_result($rs);
                odbc_close($conn);

                return $this->response->setJSON([
                    'draw'            => $draw,
                    'recordsTotal'    => $totalRecords,
                    'recordsFiltered' => $filteredRecords,
                    'data'            => $data
                ]);

            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'draw'            => (int) ($this->request->getGet('draw') ?? 1),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => true,
                    'message'         => $e->getMessage()
                ]);
            }
        }

        // Carga de la vista HTML
        $data = [
            'title'     => 'Artículos SAP',
            'subtitle'  => 'Catálogo de Artículos de Compra',
            'box_title' => 'Listado de Artículos'
        ];

        return view('julio101290\boilerplateservicelayer\Views\materials', $data);
    }

    /**
     * Obtener el detalle de un artículo por ItemCode para el modal de edición
     */
    public function getMaterial($itemCode = null) {
        try {
            if (empty($itemCode)) {
                return $this->response->setJSON(['error' => true, 'message' => 'Código no proporcionado']);
            }

            $itemCodeClean = str_replace("'", "''", trim(urldecode($itemCode)));
            $conn = $this->connectODBC();

            $sql = "
                SELECT
                    \"ItemCode\",
                    \"ItemName\",
                    \"BuyUnitMsr\",
                    \"PrchseItem\",
                    \"InvntItem\",
                    \"SellItem\",
                    \"validFor\"
                FROM OITM
                WHERE \"ItemCode\" = '{$itemCodeClean}'
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $row = odbc_fetch_array($rs);
            odbc_free_result($rs);
            odbc_close($conn);

            if (!$row) {
                return $this->response->setJSON(['error' => true, 'message' => 'Artículo no encontrado']);
            }

            return $this->response->setJSON([
                'ItemCode'   => $this->toUtf8($row['ItemCode']),
                'ItemName'   => $this->toUtf8($row['ItemName']),
                'BuyUnitMsr' => $this->toUtf8($row['BuyUnitMsr'] ?? ''),
                'PrchseItem' => $this->toUtf8($row['PrchseItem'] ?? 'Y'),
                'InvntItem'  => $this->toUtf8($row['InvntItem'] ?? 'Y'),
                'SellItem'   => $this->toUtf8($row['SellItem'] ?? 'N'),
                'validFor'   => $this->toUtf8($row['validFor'] ?? 'Y')
            ]);

        } catch (\Throwable $e) {
            return $this->response->setJSON([
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
                    'id'   => $itemCode,
                    'text' => $itemCode . ' - ' . $itemName
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            $response['data'] = $data;
            return $this->response->setJSON($response);

        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'data'    => [],
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

    /**
     * Abre conexión ODBC y establece el esquema de la BD de SAP
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
     * Normalizador a UTF-8 para cadenas procedentes de ODBC
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