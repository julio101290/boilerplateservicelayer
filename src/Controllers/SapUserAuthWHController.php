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

class SapUserAuthWHController extends BaseController {

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
     * Vista principal y endpoint AJAX para DataTables de Almacenes Autorizados
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

                // Ordenamiento dinámico
                $orderParam = $this->request->getGet('order');
                $orderColIndex = isset($orderParam[0]['column']) ? (int) $orderParam[0]['column'] : 0;
                $orderDirRaw = isset($orderParam[0]['dir']) ? strtolower($orderParam[0]['dir']) : 'asc';
                $orderDir = ($orderDirRaw === 'desc') ? 'DESC' : 'ASC';

                $columnsMap = [
                    0 => 'T0."Code"',
                    1 => 'T0."Name"',
                    2 => 'T0."DocEntry"',
                    3 => 'T0."CreateDate"'
                ];

                $orderBy = $columnsMap[$orderColIndex] ?? 'T0."Code"';

                // Filtro de búsqueda
                $whereExtra = '';
                if (!empty($search)) {
                    $searchClean = str_replace("'", "''", trim($search));
                    $whereExtra .= "
                        WHERE (
                            T0.\"Code\" LIKE '%{$searchClean}%'
                            OR T0.\"Name\" LIKE '%{$searchClean}%'
                        )
                    ";
                }

                // Total sin filtrar
                $sqlTotal = 'SELECT COUNT(1) AS "total" FROM "@AUTORIZACOMPRA"';
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $totalRecords = 0;
                if ($rsTotal && ($rowTotal = odbc_fetch_array($rsTotal))) {
                    $totalRecords = (int) ($rowTotal['total'] ?? $rowTotal['TOTAL'] ?? 0);
                    odbc_free_result($rsTotal);
                }

                // Total con filtros
                $sqlFiltered = "SELECT COUNT(1) AS \"total\" FROM \"@AUTORIZACOMPRA\" T0 {$whereExtra}";
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $filteredRecords = $totalRecords;
                if ($rsFiltered && ($rowFiltered = odbc_fetch_array($rsFiltered))) {
                    $filteredRecords = (int) ($rowFiltered['total'] ?? $rowFiltered['TOTAL'] ?? 0);
                    odbc_free_result($rsFiltered);
                }

                // Consulta paginada con conteo de usuarios asignados
                $sql = "
                    SELECT
                        T0.\"Code\",
                        T0.\"Name\",
                        T0.\"DocEntry\",
                        T0.\"CreateDate\",
                        (
                            SELECT COUNT(1) 
                            FROM \"@AUTORIZACOMPRADET\" D 
                            WHERE D.\"Code\" = T0.\"Code\"
                        ) AS \"UsersCount\"
                    FROM \"@AUTORIZACOMPRA\" T0
                    {$whereExtra}
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT {$length} OFFSET {$start}
                ";

                $rs = odbc_exec($conn, $sql);
                if (!$rs) {
                    throw new \Exception('Error al consultar almacenes autorizados: ' . odbc_errormsg($conn));
                }

                $data = [];
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'Code' => $this->toUtf8($row['Code']),
                        'Name' => $this->toUtf8($row['Name']),
                        'DocEntry' => (int) ($row['DocEntry'] ?? 0),
                        'CreateDate' => $this->toUtf8($row['CreateDate'] ?? ''),
                        'UsersCount' => (int) ($row['UsersCount'] ?? $row['USERSCOUNT'] ?? 0)
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
            'title' => 'Autorización de Almacenes SAP',
            'subtitle' => 'Configuración de Solicitudes y Pedidos por Almacén',
            'box_title' => 'Listado de Autorizaciones por Almacén'
        ];

        return view('julio101290\boilerplateservicelayer\Views\sapUserAuthWH', $data);
    }

    /**
     * Obtiene la cabecera (@AUTORIZACOMPRA) y el detalle (@AUTORIZACOMPRADET) por Code
     */
    public function getAuthWH($code = null) {
        try {
            if (empty($code)) {
                return $this->response->setJSON(['error' => true, 'message' => 'Código de almacén no proporcionado']);
            }

            $codeClean = str_replace("'", "''", trim(urldecode($code)));
            $conn = $this->connectODBC();

            // 1. Obtener cabecera
            $sqlHeader = "
                SELECT \"Code\", \"Name\", \"DocEntry\"
                FROM \"@AUTORIZACOMPRA\"
                WHERE \"Code\" = '{$codeClean}'
            ";
            $rsHeader = odbc_exec($conn, $sqlHeader);
            if (!$rsHeader) {
                throw new \Exception('Error al consultar cabecera: ' . odbc_errormsg($conn));
            }
            $header = odbc_fetch_array($rsHeader);
            odbc_free_result($rsHeader);

            if (!$header) {
                odbc_close($conn);
                return $this->response->setJSON(['error' => true, 'message' => 'Almacén autorizado no encontrado']);
            }

            // 2. Obtener detalle de usuarios
            $sqlLines = "
                SELECT
                    \"LineId\",
                    \"U_USERID\",
                    \"U_SolComp\",
                    \"U_Pedido\",
                    \"U_UserName\",
                    \"U_FolioUser\"
                FROM \"@AUTORIZACOMPRADET\"
                WHERE \"Code\" = '{$codeClean}'
                ORDER BY \"LineId\" ASC
            ";
            $rsLines = odbc_exec($conn, $sqlLines);
            if (!$rsLines) {
                throw new \Exception('Error al consultar detalle: ' . odbc_errormsg($conn));
            }

            $details = [];
            while ($row = odbc_fetch_array($rsLines)) {
                $details[] = [
                    'LineId' => (int) $row['LineId'],
                    'U_USERID' => (int) $row['U_USERID'],
                    'U_SolComp' => $this->toUtf8($row['U_SolComp'] ?? 'N'),
                    'U_Pedido' => $this->toUtf8($row['U_Pedido'] ?? 'N'),
                    'U_UserName' => $this->toUtf8($row['U_UserName'] ?? ''),
                    'U_FolioUser' => $this->toUtf8($row['U_FolioUser'] ?? '')
                ];
            }
            odbc_free_result($rsLines);
            odbc_close($conn);

            return $this->response->setJSON([
                        'error' => false,
                        'header' => [
                            'Code' => $this->toUtf8($header['Code']),
                            'Name' => $this->toUtf8($header['Name']),
                            'DocEntry' => (int) ($header['DocEntry'] ?? 0)
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
     * Catálogo de almacenes de SAP (OWHS) para seleccionar en creación
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
                    'name' => $name
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
     * Catálogo de usuarios de SAP (OUSR) para el grid de detalle
     */
    public function getSapUsersAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn = $this->connectODBC();

            $where = " WHERE (\"Locked\" = 'N' OR \"Locked\" IS NULL) ";

            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    \"USER_CODE\" LIKE '%{$searchClean}%'
                    OR \"U_NAME\" LIKE '%{$searchClean}%'
                ) ";
            }

            $sql = "
                SELECT \"USERID\", \"USER_CODE\", \"U_NAME\"
                FROM OUSR
                {$where}
                ORDER BY \"U_NAME\" ASC
                LIMIT 40
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error al consultar usuarios SAP: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $userId = (int) $row['USERID'];
                $userCode = $this->toUtf8($row['USER_CODE']);
                $userName = $this->toUtf8($row['U_NAME']);

                $data[] = [
                    'id' => $userId,
                    'text' => $userCode . ' - ' . $userName,
                    'userCode' => $userCode,
                    'userName' => $userName,
                    'folioUser' => $userCode // Se envía el USER_CODE para U_FolioUser
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
     * Guarda (crea o actualiza) el UDO de Autorización de Compras vía Service Layer
     */
    public function save() {
        helper('auth');
        $currentUserName = user()->username;

        $post = $this->request->getPost();

        $isNew = (int) ($post['isNew'] ?? 1);
        $code = strtoupper(trim($post['Code'] ?? ''));
        $name = trim($post['Name'] ?? '');
        $rawLines = $post['details'] ?? [];

        // Si las líneas vienen como JSON string, decodificarlas
        if (is_string($rawLines)) {
            $rawLines = json_decode($rawLines, true) ?? [];
        }

        if (empty($code)) {
            return $this->respond(['status' => 400, 'message' => 'El código de almacén es obligatorio'], 400);
        }

        if (empty($name)) {
            return $this->respond(['status' => 400, 'message' => 'El nombre del almacén es obligatorio'], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración de Service Layer activa'], 500);
        }

        // Validación de unicidad si es nuevo
        if ($isNew === 1) {
            try {
                $conn = $this->connectODBC();
                $codeClean = str_replace("'", "''", $code);
                $rsCheck = odbc_exec($conn, "SELECT COUNT(1) AS \"cnt\" FROM \"@AUTORIZACOMPRA\" WHERE \"Code\" = '{$codeClean}'");
                $exists = 0;
                if ($rsCheck && ($row = odbc_fetch_array($rsCheck))) {
                    $exists = (int) ($row['cnt'] ?? $row['CNT'] ?? 0);
                    odbc_free_result($rsCheck);
                }
                odbc_close($conn);

                if ($exists > 0) {
                    return $this->respond(['status' => 400, 'message' => "El almacén {$code} ya tiene autorizaciones configuradas"], 400);
                }
            } catch (\Throwable $e) {
                return $this->respond(['status' => 500, 'message' => 'Error al verificar almacén: ' . $e->getMessage()], 500);
            }
        }

        // Estructuración de las líneas para la colección del UDO
        // En Service Layer, la colección del hijo se nombra con el nombre de la tabla sin arroba + Collection
        $linesPayload = [];
        $lineIdCounter = 1;

        foreach ($rawLines as $line) {
            $userId = (int) ($line['U_USERID'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $linesPayload[] = [
                'LineId' => $lineIdCounter++,
                'U_USERID' => $userId,
                'U_SolComp' => ($line['U_SolComp'] ?? 'N') === 'Y' ? 'Y' : 'N',
                'U_Pedido' => ($line['U_Pedido'] ?? 'N') === 'Y' ? 'Y' : 'N',
                'U_UserName' => trim($line['U_UserName'] ?? ''),
                'U_FolioUser' => trim($line['U_FolioUser'] ?? '')
            ];
        }

        // Conexión y login en Service Layer
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

        // Objeto UDO 'AutCompra' registrado en SAP Business One
        $payload = [
            'Name' => $name,
            'AUTORIZACOMPRADETCollection' => $linesPayload
        ];

        if ($isNew === 1) {
            $payload['Code'] = $code;
            $url = $slRoot . "/AutCompra";
            $method = 'POST';
        } else {
            $url = $slRoot . "/AutCompra('" . rawurlencode($code) . "')";
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

        // Registro en bitácora
        $this->log->save([
            "description" => ($isNew === 1 ? "Creación" : "Actualización") . " de autorizaciones de almacén '{$code}'",
            "user" => $currentUserName
        ]);

        return $this->respond([
                    'status' => 200,
                    'message' => ($isNew === 1 ? 'Autorizaciones de almacén creadas correctamente' : 'Autorizaciones actualizadas correctamente'),
                    'data' => $dataResp
                        ], 200);
    }

    /**
     * Eliminar registro de autorización por Code
     */
    public function delete($code = null) {
        helper('auth');
        $currentUserName = user()->username;

        if (empty($code)) {
            return $this->respond(['status' => 400, 'message' => 'Código de almacén requerido'], 400);
        }

        $codeClean = strtoupper(trim(urldecode($code)));
        $dataSL = $this->serviceLayerModel->first();

        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración de Service Layer'], 500);
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

        $url = $slRoot . "/AutCompra('" . rawurlencode($codeClean) . "')";

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
            "description" => "Eliminación de autorizaciones para almacén '{$codeClean}'",
            "user" => $currentUserName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Autorización eliminada correctamente'], 200);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

    /**
     * Conexión ODBC usando la configuración de BD en ServiceLayerModel
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
