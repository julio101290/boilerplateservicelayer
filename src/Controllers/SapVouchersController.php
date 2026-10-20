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

class SapVouchersController extends BaseController {

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
     * Vista principal y endpoint AJAX para DataTables con filtros personalizados
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

                // Filtros específicos solicitados
                $filterVale     = trim($this->request->getGet('vale') ?? '');
                $filterItemCode = trim($this->request->getGet('itemCode') ?? '');
                $filterDocEntry = trim($this->request->getGet('docEntry') ?? '');
                $filterWhsCode  = trim($this->request->getGet('whsCode') ?? '');
                $filterImp      = trim($this->request->getGet('uImp') ?? '');

                // Ordenamiento dinámico
                $orderParam = $this->request->getGet('order');
                $orderColIndex = isset($orderParam[0]['column']) ? (int) $orderParam[0]['column'] : 1;
                $orderDirRaw = isset($orderParam[0]['dir']) ? strtolower($orderParam[0]['dir']) : 'desc';
                $orderDir = ($orderDirRaw === 'asc') ? 'ASC' : 'DESC';

                $columnsMap = [
                    1  => 'CAST(v."Code" AS INTEGER)',
                    2  => 'v."U_VALE"',
                    3  => 'v."U_DOCNUM"',
                    4  => 'v."U_DOCENTRY"',
                    5  => 'v."U_ITEMCODE"',
                    6  => 'v."U_CANT"',
                    7  => 'v."U_WHSCODE"',
                    8  => 'v."U_IMP"',
                    9  => 'v."U_CANCEL"',
                    10 => 'v."U_DOCDATE"',
                    11 => 'v."U_DOCDATEIMP"',
                ];

                $orderBy = $columnsMap[$orderColIndex] ?? 'CAST(v."Code" AS INTEGER)';

                // Construcción de filtros dinámicos
                $whereExtra = '';

                // Búsqueda global de DataTables
                if (!empty($search)) {
                    $searchClean = str_replace("'", "''", $search);
                    $whereExtra .= "
                        AND (
                            v.\"Code\" LIKE '%{$searchClean}%'
                            OR CAST(v.\"U_VALE\" AS NVARCHAR(50)) LIKE '%{$searchClean}%'
                            OR v.\"U_ITEMCODE\" LIKE '%{$searchClean}%'
                            OR CAST(v.\"U_DOCENTRY\" AS NVARCHAR(50)) LIKE '%{$searchClean}%'
                            OR CAST(v.\"U_DOCNUM\" AS NVARCHAR(50)) LIKE '%{$searchClean}%'
                            OR v.\"U_WHSCODE\" LIKE '%{$searchClean}%'
                        )
                    ";
                }

                // 1. Filtro por Número de Vale (U_VALE)
                if ($filterVale !== '') {
                    $valeClean = str_replace("'", "''", $filterVale);
                    $whereExtra .= " AND CAST(v.\"U_VALE\" AS NVARCHAR(50)) LIKE '%{$valeClean}%' ";
                }

                // 2. Filtro por ItemCode (U_ITEMCODE)
                if ($filterItemCode !== '') {
                    $itemClean = str_replace("'", "''", strtoupper($filterItemCode));
                    $whereExtra .= " AND UPPER(v.\"U_ITEMCODE\") LIKE '%{$itemClean}%' ";
                }

                // 3. Filtro por DocEntry (U_DOCENTRY)
                if ($filterDocEntry !== '') {
                    $docEntryClean = str_replace("'", "''", $filterDocEntry);
                    $whereExtra .= " AND CAST(v.\"U_DOCENTRY\" AS NVARCHAR(50)) LIKE '%{$docEntryClean}%' ";
                }

                // 4. Filtro por Almacén (U_WHSCODE)
                if ($filterWhsCode !== '') {
                    $whsClean = str_replace("'", "''", strtoupper($filterWhsCode));
                    $whereExtra .= " AND UPPER(v.\"U_WHSCODE\") LIKE '%{$whsClean}%' ";
                }

                // 5. Filtro por Estado Impreso (U_IMP)
                if ($filterImp === 'Y') {
                    $whereExtra .= " AND v.\"U_IMP\" = 'Y' ";
                } elseif ($filterImp === 'N') {
                    $whereExtra .= " AND (v.\"U_IMP\" <> 'Y' OR v.\"U_IMP\" IS NULL) ";
                }

                // Total sin filtrar
                $sqlTotal = 'SELECT COUNT(1) AS "total" FROM "@VALESINFO" v';
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $totalRecords = 0;
                if ($rsTotal && ($rowTotal = odbc_fetch_array($rsTotal))) {
                    $totalRecords = (int) ($rowTotal['total'] ?? $rowTotal['TOTAL'] ?? 0);
                    odbc_free_result($rsTotal);
                }

                // Total con filtros
                $sqlFiltered = "
                    SELECT COUNT(1) AS \"total\"
                    FROM \"@VALESINFO\" v
                    WHERE 1=1 {$whereExtra}
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
                        v.\"Code\",
                        v.\"Name\",
                        v.\"U_OBJTYPE\",
                        v.\"U_DOCNUM\",
                        v.\"U_ITEMCODE\",
                        v.\"U_VALE\",
                        v.\"U_CANT\",
                        v.\"U_IMP\",
                        v.\"U_DOCENTRY\",
                        v.\"U_CANCEL\",
                        v.\"U_WHSCODE\",
                        v.\"U_DOCENTRYUSO\",
                        v.\"U_OBJTYPEUSO\",
                        v.\"U_DOCDATE\",
                        v.\"U_DOCDATEIMP\"
                    FROM \"@VALESINFO\" v
                    WHERE 1=1
                      {$whereExtra}
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT {$length} OFFSET {$start}
                ";

                $rs = odbc_exec($conn, $sql);
                if (!$rs) {
                    throw new \Exception('Error al consultar vales: ' . odbc_errormsg($conn));
                }

                $data = [];
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'Code'          => $this->toUtf8($row['Code']),
                        'Name'          => $this->toUtf8($row['Name'] ?? ''),
                        'U_OBJTYPE'     => $this->toUtf8($row['U_OBJTYPE'] ?? ''),
                        'U_DOCNUM'      => $this->toUtf8($row['U_DOCNUM'] ?? ''),
                        'U_ITEMCODE'    => $this->toUtf8($row['U_ITEMCODE'] ?? ''),
                        'U_VALE'        => $this->toUtf8($row['U_VALE'] ?? ''),
                        'U_CANT'        => $this->toUtf8($row['U_CANT'] ?? '0'),
                        'U_IMP'         => $this->toUtf8($row['U_IMP'] ?? 'N'),
                        'U_DOCENTRY'    => $this->toUtf8($row['U_DOCENTRY'] ?? ''),
                        'U_CANCEL'      => $this->toUtf8($row['U_CANCEL'] ?? 'N'),
                        'U_WHSCODE'     => $this->toUtf8($row['U_WHSCODE'] ?? ''),
                        'U_DOCENTRYUSO' => $this->toUtf8($row['U_DOCENTRYUSO'] ?? ''),
                        'U_OBJTYPEUSO'  => $this->toUtf8($row['U_OBJTYPEUSO'] ?? ''),
                        'U_DOCDATE'     => $this->formatSapDate($row['U_DOCDATE'] ?? null),
                        'U_DOCDATEIMP'  => $this->formatSapDate($row['U_DOCDATEIMP'] ?? null, true),
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

        $data = [
            'title'     => 'Vales SAP',
            'subtitle'  => 'Control de Impresión de Vales (@VALESINFO)',
            'box_title' => 'Listado de Vales'
        ];

        return view('julio101290\boilerplateservicelayer\Views\vouchers', $data);
    }

    /**
     * Catálogo de Almacenes (OWHS) para el filtro Select2
     */
    public function getWarehousesAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn = $this->connectODBC();

            $where = '';
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where = "
                    WHERE \"WhsCode\" LIKE '%{$searchClean}%'
                       OR \"WhsName\" LIKE '%{$searchClean}%'
                ";
            }

            $sql = "
                SELECT \"WhsCode\", \"WhsName\"
                FROM OWHS
                {$where}
                ORDER BY \"WhsCode\" ASC
                LIMIT 50
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL en OWHS: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $code = $this->toUtf8($row['WhsCode']);
                $name = $this->toUtf8($row['WhsName']);
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
     * Obtener detalle de un vale por su Code
     */
    public function getVoucher($code = null) {
        try {
            if (empty($code)) {
                return $this->response->setJSON(['error' => true, 'message' => 'Código de vale inválido']);
            }

            $codeClean = str_replace("'", "''", trim(urldecode($code)));
            $conn = $this->connectODBC();

            $sql = "
                SELECT
                    v.\"Code\",
                    v.\"Name\",
                    v.\"U_OBJTYPE\",
                    v.\"U_DOCNUM\",
                    v.\"U_ITEMCODE\",
                    v.\"U_VALE\",
                    v.\"U_CANT\",
                    v.\"U_IMP\",
                    v.\"U_DOCENTRY\",
                    v.\"U_CANCEL\",
                    v.\"U_WHSCODE\",
                    v.\"U_DOCDATE\",
                    v.\"U_DOCDATEIMP\"
                FROM \"@VALESINFO\" v
                WHERE v.\"Code\" = '{$codeClean}'
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $row = odbc_fetch_array($rs);
            odbc_free_result($rs);
            odbc_close($conn);

            if (!$row) {
                return $this->response->setJSON(['error' => true, 'message' => 'No se encontró el vale solicitado']);
            }

            return $this->response->setJSON([
                'Code'         => $this->toUtf8($row['Code']),
                'U_VALE'       => $this->toUtf8($row['U_VALE'] ?? ''),
                'U_DOCNUM'     => $this->toUtf8($row['U_DOCNUM'] ?? ''),
                'U_DOCENTRY'   => $this->toUtf8($row['U_DOCENTRY'] ?? ''),
                'U_ITEMCODE'   => $this->toUtf8($row['U_ITEMCODE'] ?? ''),
                'U_CANT'       => $this->toUtf8($row['U_CANT'] ?? '0'),
                'U_WHSCODE'    => $this->toUtf8($row['U_WHSCODE'] ?? ''),
                'U_IMP'        => $this->toUtf8($row['U_IMP'] ?? 'N'),
                'U_CANCEL'     => $this->toUtf8($row['U_CANCEL'] ?? 'N'),
                'U_DOCDATE'    => $this->formatSapDate($row['U_DOCDATE'] ?? null),
                'U_DOCDATEIMP' => $this->formatSapDate($row['U_DOCDATEIMP'] ?? null, true)
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualiza directamente por ODBC el campo U_IMP ('Y' o 'N') en "@VALESINFO"
     */
    public function save() {
        helper('auth');
        $userName = user()->username ?? 'admin';

        $post = $this->request->getPost();
        $code = trim($post['Code'] ?? '');
        $uImp = strtoupper(trim($post['U_IMP'] ?? 'N'));

        if (empty($code)) {
            return $this->respond([
                'status'  => 400,
                'message' => 'El código (Code) del registro es obligatorio.'
            ], 400);
        }

        if (!in_array($uImp, ['Y', 'N'], true)) {
            return $this->respond([
                'status'  => 400,
                'message' => 'El estado de impresión debe ser Y (Impreso) o N (No Impreso).'
            ], 400);
        }

        try {
            $conn = $this->connectODBC();
            $codeClean = str_replace("'", "''", $code);
            $uImpClean = ($uImp === 'Y') ? 'Y' : 'N';

            $sqlUpdate = "
                UPDATE \"@VALESINFO\"
                SET \"U_IMP\" = '{$uImpClean}'
                WHERE \"Code\" = '{$codeClean}'
            ";

            $rsUpdate = odbc_exec($conn, $sqlUpdate);
            if (!$rsUpdate) {
                $odbcError = odbc_errormsg($conn);
                odbc_close($conn);
                return $this->respond([
                    'status'  => 500,
                    'message' => 'Error al actualizar vale en SAP: ' . $odbcError
                ], 500);
            }

            odbc_close($conn);
        } catch (\Throwable $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Error ODBC al actualizar estado de impresión: ' . $e->getMessage()
            ], 500);
        }

        // Registrar en bitácora
        $this->log->save([
            'description' => "Cambio de estado U_IMP a '{$uImp}' en @VALESINFO (Code: {$code})",
            'user'        => $userName
        ]);

        return $this->respond([
            'status'  => 200,
            'message' => "Estado de impresión actualizado a '{$uImp}' correctamente."
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

    /**
     * Formatea fechas de SAP HANA para lectura limpia
     */
    private function formatSapDate($dateStr, $includeTime = false) {
        if (empty($dateStr)) {
            return '';
        }
        $ts = strtotime($dateStr);
        if (!$ts) {
            return $this->toUtf8($dateStr);
        }
        return $includeTime ? date('Y-m-d H:i:s', $ts) : date('Y-m-d', $ts);
    }
}