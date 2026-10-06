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

class SapUserWHController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $serviceLayerController;
    protected $branchoffice;
    protected $userLinkSap;
    // Configuración exacta del UDO y sus tablas en SAP Business One
    protected $udoObject = 'UsuarioAlmacen';
    protected $tableHeader = '@USUARIOALMACEN';
    protected $tableLines = '@USUARIOALMACENDET';
    protected $childCollection = 'USUARIOALMACENDETCollection';

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
     * Vista principal y endpoint AJAX para DataTables
     */
    public function index() {
        helper('auth');

        // Valida por cabecera AJAX o por la presencia del parámetro draw de DataTables
        if ($this->request->isAJAX() || $this->request->getGet('draw') !== null) {
            try {
                $conn = $this->connectODBC();

                $draw = (int) ($this->request->getGet('draw') ?? 1);
                $start = (int) ($this->request->getGet('start') ?? 0);
                $length = (int) ($this->request->getGet('length') ?? 10);
                $search = $this->request->getGet('search')['value'] ?? '';

                // Mapeo de columnas para DataTables
                $orderParam = $this->request->getGet('order');
                $orderColIndex = isset($orderParam[0]['column']) ? (int) $orderParam[0]['column'] : 1;
                $orderDirRaw = isset($orderParam[0]['dir']) ? strtolower($orderParam[0]['dir']) : 'desc';
                $orderDir = ($orderDirRaw === 'asc') ? 'ASC' : 'DESC';

                $columnsMap = [
                    1 => 'T0."DocEntry"',
                    2 => 'T0."DocNum"',
                    3 => 'T0."U_empID"',
                    4 => 'T0."U_Empleado"',
                    5 => 'T0."CreateDate"'
                ];

                $orderBy = $columnsMap[$orderColIndex] ?? 'T0."DocEntry"';

                // Filtro de búsqueda
                $whereExtra = '';
                if (!empty($search)) {
                    $searchClean = str_replace("'", "''", trim($search));
                    $isNumeric = is_numeric($searchClean);
                    $numFilter = $isNumeric ? "OR T0.\"DocEntry\" = " . (int) $searchClean . " OR T0.\"U_empID\" = " . (int) $searchClean : "";

                    $whereExtra .= "
                        WHERE (
                            T0.\"U_Empleado\" LIKE '%{$searchClean}%'
                            {$numFilter}
                        )
                    ";
                }

                // Total sin filtrar
                $sqlTotal = "SELECT COUNT(1) AS \"total\" FROM \"{$this->tableHeader}\"";
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $totalRecords = 0;
                if ($rsTotal && ($rowTotal = odbc_fetch_array($rsTotal))) {
                    $totalRecords = (int) ($rowTotal['total'] ?? $rowTotal['TOTAL'] ?? 0);
                    odbc_free_result($rsTotal);
                }

                // Total con filtros
                $sqlFiltered = "SELECT COUNT(1) AS \"total\" FROM \"{$this->tableHeader}\" T0 {$whereExtra}";
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $filteredRecords = $totalRecords;
                if ($rsFiltered && ($rowFiltered = odbc_fetch_array($rsFiltered))) {
                    $filteredRecords = (int) ($rowFiltered['total'] ?? $rowFiltered['TOTAL'] ?? 0);
                    odbc_free_result($rsFiltered);
                }

                // Consulta paginada con conteo de la tabla hija @USUARIOALMACENDET
                $sql = "
                    SELECT
                        T0.\"DocEntry\",
                        T0.\"DocNum\",
                        T0.\"U_empID\",
                        T0.\"U_Empleado\",
                        T0.\"CreateDate\",
                        (
                            SELECT COUNT(1)
                            FROM \"{$this->tableLines}\" D
                            WHERE D.\"DocEntry\" = T0.\"DocEntry\"
                        ) AS \"WarehousesCount\"
                    FROM \"{$this->tableHeader}\" T0
                    {$whereExtra}
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT {$length} OFFSET {$start}
                ";

                $rs = odbc_exec($conn, $sql);
                if (!$rs) {
                    throw new \Exception('Error al consultar datos en SAP: ' . odbc_errormsg($conn));
                }

                $data = [];
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'DocEntry' => (int) ($row['DocEntry'] ?? 0),
                        'DocNum' => (int) ($row['DocNum'] ?? 0),
                        'U_empID' => (int) ($row['U_empID'] ?? 0),
                        'U_Empleado' => $this->toUtf8($row['U_Empleado']),
                        'CreateDate' => $this->toUtf8($row['CreateDate'] ?? ''),
                        'WarehousesCount' => (int) ($row['WarehousesCount'] ?? $row['WAREHOUSESCOUNT'] ?? 0)
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
            'title' => 'Asignación de Almacenes a Usuarios',
            'subtitle' => 'Configuración de Almacenes por Empleado',
            'box_title' => 'Listado de Usuarios y Almacenes Asignados'
        ];

        return view('julio101290\boilerplateservicelayer\Views\sapUserWH', $data);
    }

    /**
     * Obtiene la cabecera (@USUARIOALMACEN) y líneas (@USUARIOALMACENDET) por DocEntry
     */
    public function getUserWH($docEntry = null) {
        try {
            $docEntry = (int) $docEntry;
            if ($docEntry <= 0) {
                return $this->response->setJSON(['error' => true, 'message' => 'DocEntry inválido o no proporcionado']);
            }

            $conn = $this->connectODBC();

            // 1. Obtener Cabecera
            $sqlHeader = "
                SELECT \"DocEntry\", \"DocNum\", \"U_empID\", \"U_Empleado\"
                FROM \"{$this->tableHeader}\"
                WHERE \"DocEntry\" = {$docEntry}
            ";
            $rsHeader = odbc_exec($conn, $sqlHeader);
            if (!$rsHeader) {
                throw new \Exception('Error al consultar cabecera: ' . odbc_errormsg($conn));
            }
            $header = odbc_fetch_array($rsHeader);
            odbc_free_result($rsHeader);

            if (!$header) {
                odbc_close($conn);
                return $this->response->setJSON(['error' => true, 'message' => 'Registro no encontrado en SAP']);
            }

            // 2. Obtener Detalle de @USUARIOALMACENDET
            $sqlLines = "
                SELECT
                    \"LineId\",
                    \"U_WhsCode\",
                    \"U_WhsName\"
                FROM \"{$this->tableLines}\"
                WHERE \"DocEntry\" = {$docEntry}
                ORDER BY \"LineId\" ASC
            ";
            $rsLines = odbc_exec($conn, $sqlLines);
            if (!$rsLines) {
                throw new \Exception('Error al consultar detalle de almacenes: ' . odbc_errormsg($conn));
            }

            $details = [];
            while ($row = odbc_fetch_array($rsLines)) {
                $details[] = [
                    'LineId' => (int) $row['LineId'],
                    'U_WhsCode' => $this->toUtf8($row['U_WhsCode'] ?? ''),
                    'U_WhsName' => $this->toUtf8($row['U_WhsName'] ?? '')
                ];
            }
            odbc_free_result($rsLines);
            odbc_close($conn);

            return $this->response->setJSON([
                        'error' => false,
                        'header' => [
                            'DocEntry' => (int) $header['DocEntry'],
                            'DocNum' => (int) $header['DocNum'],
                            'U_empID' => (int) $header['U_empID'],
                            'U_Empleado' => $this->toUtf8($header['U_Empleado'])
                        ],
                        'details' => $details
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Catálogo de Empleados SAP (OHEM) para Select2 en la cabecera
     */
    public function getEmployeesAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn = $this->connectODBC();

            $where = " WHERE \"Active\" = 'Y' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $isNumeric = is_numeric($searchClean);
                $numFilter = $isNumeric ? "OR \"empID\" = " . (int) $searchClean : "";

                $where .= " AND (
                    \"lastName\" LIKE '%{$searchClean}%'
                    OR \"firstName\" LIKE '%{$searchClean}%'
                    {$numFilter}
                ) ";
            }

            $sql = "
                SELECT \"empID\", \"lastName\", \"firstName\", \"userId\"
                FROM OHEM
                {$where}
                ORDER BY \"lastName\" ASC
                LIMIT 40
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error al consultar empleados: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $empId = (int) $row['empID'];
                $lastName = $this->toUtf8($row['lastName'] ?? '');
                $firstName = $this->toUtf8($row['firstName'] ?? '');
                $fullName = trim($lastName . ' ' . $firstName);

                $data[] = [
                    'id' => $empId,
                    'text' => $empId . ' - ' . $fullName,
                    'empId' => $empId,
                    'fullName' => $fullName,
                    'userId' => (int) ($row['userId'] ?? 0)
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
     * Catálogo de Almacenes SAP (OWHS) para Select2 en las líneas
     */
    public function getWarehousesAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn = $this->connectODBC();

            $where = " WHERE \"Locked\" = 'N' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    \"WhsCode\" LIKE '%{$searchClean}%'
                    OR \"WhsName\" LIKE '%{$searchClean}%'
                ) ";
            }

            $sql = "
                SELECT \"WhsCode\", \"WhsName\"
                FROM OWHS
                {$where}
                ORDER BY \"WhsCode\" ASC
                LIMIT 40
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error al consultar almacenes: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $code = $this->toUtf8($row['WhsCode']);
                $name = $this->toUtf8($row['WhsName']);
                $data[] = [
                    'id' => $code,
                    'text' => $code . ' - ' . $name,
                    'whsCode' => $code,
                    'whsName' => $name
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
     * Guarda (crea o actualiza) el UDO en Service Layer
     */
    public function save() {
        helper('auth');
        $currentUserName = user()->username;

        $post = $this->request->getPost();

        $isNew = (int) ($post['isNew'] ?? 1);
        $docEntry = (int) ($post['DocEntry'] ?? 0);
        $empId = (int) ($post['U_empID'] ?? 0);
        $empleado = trim($post['U_Empleado'] ?? '');
        $rawLines = $post['details'] ?? [];

        if (is_string($rawLines)) {
            $rawLines = json_decode($rawLines, true) ?? [];
        }

        if ($empId <= 0) {
            return $this->respond(['status' => 400, 'message' => 'Debe seleccionar un empleado válido'], 400);
        }

        if (empty($empleado)) {
            return $this->respond(['status' => 400, 'message' => 'El nombre del empleado es obligatorio'], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración de Service Layer activa'], 500);
        }

        // Validación de unicidad si es registro nuevo
        if ($isNew === 1) {
            try {
                $conn = $this->connectODBC();
                $rsCheck = odbc_exec($conn, "SELECT COUNT(1) AS \"cnt\" FROM \"{$this->tableHeader}\" WHERE \"U_empID\" = {$empId}");
                $exists = 0;
                if ($rsCheck && ($row = odbc_fetch_array($rsCheck))) {
                    $exists = (int) ($row['cnt'] ?? $row['CNT'] ?? 0);
                    odbc_free_result($rsCheck);
                }
                odbc_close($conn);

                if ($exists > 0) {
                    return $this->respond(['status' => 400, 'message' => "El empleado ya tiene almacenes configurados en el sistema"], 400);
                }
            } catch (\Throwable $e) {
                return $this->respond(['status' => 500, 'message' => 'Error al verificar empleado: ' . $e->getMessage()], 500);
            }
        }

        // Estructuración de líneas para USUARIOALMACENDETCollection
        $linesPayload = [];
        $lineIdCounter = 1;

        foreach ($rawLines as $line) {
            $whsCode = trim($line['U_WhsCode'] ?? $line['whsCode'] ?? '');
            if (empty($whsCode)) {
                continue;
            }

            $linesPayload[] = [
                'LineId' => $lineIdCounter++,
                'U_WhsCode' => $whsCode,
                'U_WhsName' => trim($line['U_WhsName'] ?? $line['whsName'] ?? '')
            ];
        }

        // Login en Service Layer
        try {
            $conexionSap = $this->serviceLayerController->login(
                    $dataSL['url'],
                    $dataSL['port'],
                    $dataSL['password'],
                    $dataSL['username'],
                    $dataSL['companyDB']
            );
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error de autenticación con Service Layer: ' . $e->getMessage()], 500);
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

        // Payload hacia el UDO 'UsuarioAlmacen'
        $payload = [
            'U_empID' => $empId,
            'U_Empleado' => $empleado,
            $this->childCollection => $linesPayload
        ];

        if ($isNew === 1) {
            $url = $slRoot . "/{$this->udoObject}";
            $method = 'POST';
        } else {
            if ($docEntry <= 0) {
                return $this->respond(['status' => 400, 'message' => 'DocEntry es requerido para actualizar'], 400);
            }
            $url = $slRoot . "/{$this->udoObject}({$docEntry})";
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
            return $this->respond(['status' => 500, 'message' => 'Error cURL: ' . $err], 500);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode($resp, true);
            $msgError = $body['error']['message']['value'] ?? $body['error']['message'] ?? 'Error devuelto por Service Layer';
            return $this->respond(['status' => $httpCode, 'message' => $msgError, 'body' => $body], $httpCode);
        }

        $dataResp = null;
        if ($method === 'POST') {
            $dataResp = json_decode($resp, true);
        }

        $this->log->save([
            "description" => ($isNew === 1 ? "Creación" : "Actualización") . " de almacenes para empleado '{$empleado}' (empID: {$empId})",
            "user" => $currentUserName
        ]);

        return $this->respond([
                    'status' => 200,
                    'message' => ($isNew === 1 ? 'Almacenes asignados correctamente' : 'Almacenes actualizados correctamente'),
                    'data' => $dataResp
                        ], 200);
    }

    /**
     * Elimina el registro del UDO por DocEntry
     */
    public function delete($docEntry = null) {
        helper('auth');
        $currentUserName = user()->username;

        $docEntry = (int) $docEntry;
        if ($docEntry <= 0) {
            return $this->respond(['status' => 400, 'message' => 'DocEntry inválido o no proporcionado'], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración de Service Layer activa'], 500);
        }

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

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $url = $slRoot . "/{$this->udoObject}({$docEntry})";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: PHP",
                "B1S-CaseInsensitive: true"
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return $this->respond(['status' => 500, 'message' => 'Error cURL: ' . $err], 500);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode($resp, true);
            $msgError = $body['error']['message']['value'] ?? $body['error']['message'] ?? 'Error al eliminar en Service Layer';
            return $this->respond(['status' => $httpCode, 'message' => $msgError], $httpCode);
        }

        $this->log->save([
            "description" => "Eliminación de almacenes asignados DocEntry '{$docEntry}'",
            "user" => $currentUserName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Registro eliminado correctamente'], 200);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

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
