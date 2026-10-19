<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;

class EmployeeSAPController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $serviceLayerController;
    protected $serviceLayerModel;

    public function __construct() {
        $this->log = new LogModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->serviceLayerModel = new SapservicelayerModel();
        helper(['menu', 'utilerias']);
    }

    public function index() {
        helper('auth');

        if ($this->request->isAJAX()) {
            $request = service('request');
            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');
            $searchValue = trim($request->getGet('search')['value'] ?? '');
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir = strtolower($request->getGet('order')[0]['dir'] ?? 'asc');

            // Columnas visibles en DataTable
            $columns = ['empID', 'empID', 'ExtEmpNo', 'firstName', 'lastName', 'middleName', 'Dept', 'Active'];
            $orderField = $columns[$orderColumnIndex] ?? 'empID';

            $dataConect = $this->serviceLayerModel->first();
            $result = $this->getEmployeesODBC($dataConect, $searchValue, $start, $length, $orderField, $orderDir);

            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => $result['recordsTotal'],
                        'recordsFiltered' => $result['recordsFiltered'],
                        'data' => $result['data'],
            ]);
        }

        $titulos["title"] = lang('employee.title');
        $titulos["subtitle"] = lang('employee.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\employeeList', $titulos);
    }

    private function getEmployeesODBC($dataConect, $search, $start, $length, $orderField, $orderDir) {
        try {
            if (empty($dataConect['nameODBC']) || empty($dataConect['userODBC']) || empty($dataConect['passwordODBC']) || empty($dataConect['companyDB'])) {
                throw new \Exception('Datos de conexión incompletos');
            }

            $conn = odbc_connect(
                    $dataConect["nameODBC"],
                    $dataConect["userODBC"],
                    $dataConect["passwordODBC"]
            );
            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            $searchEsc = str_replace("'", "''", $search);

            // Conteo total
            $countSql = 'SELECT COUNT(*) AS total FROM OHEM WHERE 1=1';
            if (!empty($search)) {
                $countSql .= " AND (\"firstName\" LIKE '%$searchEsc%' OR \"lastName\" LIKE '%$searchEsc%' OR \"middleName\" LIKE '%$searchEsc%' OR \"ExtEmpNo\" LIKE '%$searchEsc%')";
            }
            $countStmt = odbc_exec($conn, $countSql);
            if (!$countStmt) {
                throw new \Exception('Error count: ' . odbc_errormsg($conn));
            }
            $total = 0;
            if (odbc_fetch_row($countStmt)) {
                $total = (int) odbc_result($countStmt, 1);
            }
            odbc_free_result($countStmt);

            // Consulta paginada
            $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            $allowed = ['empID', 'ExtEmpNo', 'firstName', 'lastName', 'middleName', 'Dept', 'Active'];
            if (!in_array($orderField, $allowed)) {
                $orderField = 'empID';
            }

            $baseSql = 'SELECT "empID", "ExtEmpNo", "firstName", "lastName", "middleName", "dept", "Active", "Code" FROM OHEM WHERE 1=1';
            if (!empty($search)) {
                $baseSql .= " AND (\"firstName\" LIKE '%$searchEsc%' OR \"lastName\" LIKE '%$searchEsc%' OR \"middleName\" LIKE '%$searchEsc%' OR \"ExtEmpNo\" LIKE '%$searchEsc%')";
            }
            $sql = $baseSql . " ORDER BY \"$orderField\" $orderDir LIMIT $length OFFSET $start";

            $stmt = odbc_exec($conn, $sql);
            if (!$stmt) {
                throw new \Exception('Error query: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($stmt)) {
                $row = $this->utf8ize($row);
                $data[] = [
                    'empID' => $row['empID'] ?? '',
                    'ExtEmpNo' => $row['ExtEmpNo'] ?? '',
                    'firstName' => $row['firstName'] ?? '',
                    'lastName' => $row['lastName'] ?? '',
                    'middleName' => $row['middleName'] ?? '',
                    'Dept' => $row['dept'] ?? '',
                    'Code' => $row['Code'] ?? '',
                    'Active' => $row['Active'] ?? '',
                ];
            }
            odbc_free_result($stmt);
            odbc_close($conn);

            return [
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'EmployeeSAPController::getEmployeesODBC - ' . $e->getMessage());
            return [
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getEmployee($empID) {
        try {
            $empID = (int) $empID;
            list($conn) = $this->getOdbcConnection();

            $sql = "SELECT \"empID\", \"ExtEmpNo\", \"Code\", \"firstName\", \"lastName\", \"middleName\", \"Active\", \"branch\" 
                    FROM OHEM WHERE \"empID\" = {$empID}";
            $rs = odbc_exec($conn, $sql);
            $row = odbc_fetch_array($rs);

            odbc_free_result($rs);
            odbc_close($conn);

            if ($row) {
                return $this->response->setJSON([
                            'empID' => (int) $row['empID'],
                            'ExtEmpNo' => $this->toUtf8($row['ExtEmpNo']),
                            'Code' => $this->toUtf8($row['Code']),
                            'firstName' => $this->toUtf8($row['firstName']),
                            'lastName' => $this->toUtf8($row['lastName']),
                            'middleName' => $this->toUtf8($row['middleName']),
                            'Active' => $this->toUtf8($row['Active']),
                            'BPLId' => $row['branch'] !== null ? (int) $row['branch'] : ''
                ]);
            }

            return $this->response->setJSON(['error' => true, 'message' => 'No encontrado']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage()]);
        }
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

    /**
     * Valida que no exista otro empleado con el mismo ExtEmpNo
     */
    private function extEmpNoExists($dataConect, string $extEmpNo, int $currentEmpID = 0): bool {
        if (empty($extEmpNo)) {
            return false;
        }

        $conn = @odbc_connect(
                        $dataConect["nameODBC"],
                        $dataConect["userODBC"],
                        $dataConect["passwordODBC"]
                );
        if (!$conn) {
            return false;
        }

        odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"');

        $extEmpNoEsc = str_replace("'", "''", trim($extEmpNo));
        $sql = 'SELECT COUNT(*) AS total FROM OHEM WHERE "ExtEmpNo" = \'' . $extEmpNoEsc . '\'';
        if ($currentEmpID > 0) {
            $sql .= ' AND "empID" <> ' . (int) $currentEmpID;
        }

        $stmt = odbc_exec($conn, $sql);
        $exists = false;
        if ($stmt && odbc_fetch_row($stmt)) {
            $exists = ((int) odbc_result($stmt, 1)) > 0;
        }

        if ($stmt) {
            odbc_free_result($stmt);
        }
        odbc_close($conn);

        return $exists;
    }

    public function save() {
        helper('auth');
        $userName = user()->username;
        $datos = $this->request->getPost();
        $empID = isset($datos['empID']) ? (int) $datos['empID'] : 0;
        $extEmpNo = trim($datos['ExtEmpNo'] ?? '');

        // Validaciones requeridas
        if (empty($datos['firstName']) || empty($datos['lastName'])) {
            return $this->respond([
                        'status' => 400,
                        'message' => 'Nombre y Apellido son obligatorios'
                            ], 400);
        }

        if (empty($extEmpNo)) {
            return $this->respond([
                        'status' => 400,
                        'message' => 'El Número de Empleado Externo es obligatorio'
                            ], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
        }

        // Validación de duplicidad en ExtEmpNo
        if ($this->extEmpNoExists($dataSL, $extEmpNo, $empID)) {
            return $this->respond([
                        'status' => 400,
                        'message' => "El número de empleado externo '{$extEmpNo}' ya se encuentra registrado."
                            ], 400);
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

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId'], 500);
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

        // Construcción del payload
        $payload = [
            'FirstName' => trim($datos['firstName']),
            'LastName' => trim($datos['lastName']),
            'ExternalEmployeeNumber' => $extEmpNo
        ];

        if (isset($datos['middleName'])) {
            $payload['MiddleName'] = trim($datos['middleName']);
        }
        if (isset($datos['Active'])) {
            $activeVal = $datos['Active'];
            $payload['Active'] = in_array($activeVal, [true, 1, '1', 'Y', 'tYES'], true) ? 'tYES' : 'tNO';
        }

        if ($empID <= 0) {
            $url = $slRoot . "/EmployeesInfo";
            $method = 'POST';
        } else {
            $url = $slRoot . "/EmployeesInfo({$empID})";
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
            $msgError = $body['error']['message']['value'] ?? 'Error en Service Layer';
            return $this->respond(['status' => $httpCode, 'message' => $msgError, 'body' => $body], $httpCode);
        }

        $nuevoEmpID = $empID;
        $data = null;
        if ($method === 'POST') {
            $data = json_decode($resp, true);
            $nuevoEmpID = $data['EmployeeID'] ?? $empID;
        }

        $this->log->save([
            "description" => ($empID <= 0 ? "Creación" : "Actualización") . " de empleado (ID $nuevoEmpID, No. Externo: $extEmpNo)",
            "user" => $userName
        ]);

        return $this->respond([
                    'status' => 200,
                    'message' => ($empID <= 0 ? 'Empleado creado correctamente' : 'Empleado actualizado correctamente'),
                    'data' => $data
                        ], 200);
    }

    public function delete($id) {
        helper('auth');
        $userName = user()->username;

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
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

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId'], 500);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $url = $slRoot . "/EmployeesInfo($id)";
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
                "User-Agent: PHP"
            ],
            CURLOPT_TIMEOUT => 30
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
            return $this->respond(['status' => $httpCode, 'message' => 'Error al eliminar', 'body' => $body], $httpCode);
        }

        $this->log->save([
            "description" => "Eliminación de empleado ID: $id",
            "user" => $userName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Empleado eliminado correctamente'], 200);
    }

    /**
     * Obtiene los roles de un empleado (vía ODBC)
     */
    public function getEmployeeRoles($empID) {
        $dataConect = $this->serviceLayerModel->first();
        if (empty($dataConect)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración ODBC'], 500);
        }

        $conn = odbc_connect(
                $dataConect["nameODBC"],
                $dataConect["userODBC"],
                $dataConect["passwordODBC"]
        );
        if (!$conn) {
            return $this->respond(['status' => 500, 'message' => 'Error conexión ODBC'], 500);
        }
        odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"');

        $sql = 'SELECT 
                HEM6."roleID",
                HEM6."line",
                HEM6."LogInstanc",
                HEM6."EncryptIV",
                OHTY."name" AS "RoleName",
                OHTY."descriptio"
            FROM HEM6
            LEFT JOIN OHTY ON HEM6."roleID" = OHTY."typeID"
            WHERE HEM6."empID" = ' . (int) $empID . '
            ORDER BY HEM6."line"';

        $stmt = odbc_exec($conn, $sql);
        if (!$stmt) {
            odbc_close($conn);
            return $this->respond(['status' => 500, 'message' => 'Error al obtener roles: ' . odbc_errormsg($conn)], 500);
        }

        $roles = [];
        while ($row = odbc_fetch_array($stmt)) {
            $row = $this->utf8ize($row);
            $roles[] = [
                'roleID' => $row['roleID'] ?? '',
                'line' => $row['line'] ?? '',
                'RoleName' => $row['RoleName'] ?? '',
                'Description' => $row['Description'] ?? '',
                'LogInstanc' => $row['LogInstanc'] ?? 0,
                'EncryptIV' => $row['EncryptIV'] ?? null
            ];
        }
        odbc_free_result($stmt);
        odbc_close($conn);

        return $this->respond($roles, 200);
    }

    /**
     * Lista todos los roles disponibles (para select2)
     */
    public function getRolesAjaxSelect2() {
        $request = service('request');
        $searchTerm = $request->getPost('searchTerm') ?? '';

        $dataConect = $this->serviceLayerModel->first();
        if (empty($dataConect)) {
            return $this->response->setJSON(['results' => []]);
        }

        $conn = odbc_connect(
                $dataConect["nameODBC"],
                $dataConect["userODBC"],
                $dataConect["passwordODBC"]
        );
        if (!$conn) {
            return $this->response->setJSON(['results' => []]);
        }
        odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"');

        $sql = 'SELECT "typeID", "name", "descriptio" FROM OHTY WHERE 1=1';
        if (!empty($searchTerm)) {
            $searchEsc = str_replace("'", "''", $searchTerm);
            $sql .= " AND (\"name\" LIKE '%$searchEsc%' OR \"descriptio\" LIKE '%$searchEsc%')";
        }
        $sql .= " ORDER BY \"name\" ASC LIMIT 20";
        $stmt = odbc_exec($conn, $sql);
        $results = [];
        while ($row = odbc_fetch_array($stmt)) {
            $row = $this->utf8ize($row);
            $results[] = [
                'id' => $row['typeID'],
                'text' => $row['typeID'] . ' - ' . $row['name'] . (trim($row['descriptio'] ?? '') ? ' (' . $row['descriptio'] . ')' : '')
            ];
        }
        odbc_free_result($stmt);
        odbc_close($conn);

        return $this->response->setJSON(['results' => $results]);
    }

    /**
     * Agrega un rol a un empleado usando Service Layer
     */
    public function addEmployeeRole() {
        helper('auth');
        $userName = user()->username;
        $post = $this->request->getPost();
        $empID = (int) ($post['empID'] ?? 0);
        $roleID = (int) ($post['RoleCode'] ?? 0);

        if ($empID <= 0 || $roleID == 0) {
            return $this->respond([
                        'status' => 400,
                        'message' => 'Faltan datos (empID o roleID)'
                            ], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
        }

        try {
            $conexionSap = $this->serviceLayerController->login(
                    $dataSL['url'], $dataSL['port'], $dataSL['password'],
                    $dataSL['username'], $dataSL['companyDB']
            );
        } catch (\Exception $e) {
            return $this->respond(['status' => 500, 'message' => 'Error login SL: ' . $e->getMessage()], 500);
        }

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId'], 500);
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

        // 1) GET roles actuales
        $getUrl = $slRoot . "/EmployeesInfo({$empID})?\$select=EmployeeID,EmployeeRolesInfoLines";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $getUrl,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $baseHeaders,
            CURLOPT_TIMEOUT => 60
        ]);
        $getResp = curl_exec($ch);
        $getErr = curl_error($ch);
        $getHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($getErr || $getHttp < 200 || $getHttp >= 300) {
            log_message('warning', 'SL falló GET EmployeesInfo: ' . ($getErr ?: "HTTP $getHttp: $getResp"));
        }

        $current = json_decode($getResp, true);
        $roles = $current['EmployeeRolesInfoLines'] ?? [];

        // Evitar duplicar
        $alreadyHasRole = false;
        foreach ($roles as $r) {
            if ((int) ($r['RoleID'] ?? 0) === $roleID) {
                $alreadyHasRole = true;
                break;
            }
        }
        if (!$alreadyHasRole) {
            $roles[] = ['RoleID' => $roleID];
        }

        // 2) PATCH de vuelta
        $patchUrl = $slRoot . "/EmployeesInfo({$empID})";
        $payload = ['EmployeeRolesInfoLines' => $roles];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $patchUrl,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
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

        if (!$err && $httpCode >= 200 && $httpCode < 300) {
            $this->log->save([
                "description" => "Asignación de rol ID '$roleID' al empleado $empID (vía SL)",
                "user" => $userName
            ]);

            return $this->respond([
                        'status' => 200,
                        'message' => 'Rol asignado correctamente'
                            ], 200);
        }

        log_message('warning', 'SL falló PATCH roles: ' . ($err ?: "HTTP $httpCode: $resp"));
        return $this->respond(['status' => 500, 'message' => 'Error al asignar rol en Service Layer'], 500);
    }

    /**
     * Elimina un rol de un empleado (vía Service Layer)
     */
    public function removeEmployeeRole($empID, $roleID) {
        helper('auth');
        $userName = user()->username;
        $empID = (int) $empID;
        $roleID = (int) $roleID;

        if ($empID <= 0 || $roleID == 0) {
            return $this->respond(['status' => 400, 'message' => 'Faltan datos'], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
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

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId'], 500);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $getHeaders = [
            "Accept: application/json",
            "Content-Type: application/json",
            "User-Agent: PHP",
            "B1S-CaseInsensitive: true"
        ];

        $patchHeaders = array_merge($getHeaders, [
            "B1S-ReplaceCollectionsOnPatch: true"
        ]);

        // 1) GET roles actuales
        $getUrl = $slRoot . "/EmployeesInfo({$empID})?" . http_build_query([
                    '$select' => 'EmployeeID,EmployeeRolesInfoLines'
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $getUrl,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $getHeaders,
            CURLOPT_TIMEOUT => 60
        ]);
        $getResp = curl_exec($ch);
        $getErr = curl_error($ch);
        $getHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($getErr || $getHttp < 200 || $getHttp >= 300) {
            return $this->respond([
                        'status' => 500,
                        'message' => 'Error al obtener roles actuales: ' . ($getErr ?: "HTTP $getHttp: $getResp")
                            ], 500);
        }

        $current = json_decode($getResp, true);
        $roles = $current['EmployeeRolesInfoLines'] ?? [];

        // 2) Filtrar quitando el rol
        $newRoles = array_values(array_filter($roles, function ($r) use ($roleID) {
                    return (int) ($r['RoleID'] ?? 0) !== $roleID;
                }));

        if (count($newRoles) === count($roles)) {
            return $this->respond([
                        'status' => 404,
                        'message' => 'El empleado no tiene asignado ese rol'
                            ], 404);
        }

        // 3) PATCH de reemplazo total
        $patchUrl = $slRoot . "/EmployeesInfo({$empID})";
        $payload = ['EmployeeRolesInfoLines' => $newRoles];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $patchUrl,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $patchHeaders,
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
            return $this->respond(['status' => $httpCode, 'message' => 'Error al eliminar rol', 'body' => $body], $httpCode);
        }

        $this->log->save([
            "description" => "Eliminación de rol ID '$roleID' del empleado $empID (vía SL)",
            "user" => $userName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Rol eliminado correctamente'], 200);
    }

    public function updateEmployeeRole() {
        helper('auth');
        $userName = user()->username;
        $post = $this->request->getPost();

        $empID = (int) ($post['empID'] ?? 0);
        $oldRoleID = (int) ($post['oldRoleID'] ?? 0);
        $newRoleID = (int) ($post['newRoleID'] ?? 0);

        if ($empID <= 0 || $oldRoleID <= 0 || $newRoleID <= 0) {
            return $this->respond(['status' => 400, 'message' => 'Faltan datos (empID, oldRoleID o newRoleID)'], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
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

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId'], 500);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $url = $slRoot . "/EmployeeRoles(EmployeeID=$empID,RoleCode='$oldRoleID')";
        $payload = ['RoleCode' => $newRoleID];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
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
            return $this->respond(['status' => 500, 'message' => 'cURL Error: ' . $err], 500);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode($resp, true);
            return $this->respond(['status' => $httpCode, 'message' => 'Error en SL', 'body' => $body], $httpCode);
        }

        $this->log->save([
            "description" => "Actualización de rol de $oldRoleID a $newRoleID para empleado $empID (vía SL)",
            "user" => $userName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Rol actualizado correctamente'], 200);
    }

    private function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        }
        return $mixed;
    }

    /**
     * Catálogo de sucursales (OBPL con fallback a OUBR)
     */
    public function getBranchesAjax() {
        try {
            list($conn) = $this->getOdbcConnection();

            // 1. Intentar con OBPL (Sucursales multi-branch de SAP)
            $sql = 'SELECT "BPLId", "BPLName" FROM OBPL WHERE "Disabled" = \'N\' ORDER BY "BPLId" ASC';
            $rs = @odbc_exec($conn, $sql);

            $data = [];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'BPLId' => (int) $row['BPLId'],
                        'BPLName' => $this->utf8ize($row['BPLName'])
                    ];
                }
                odbc_free_result($rs);
            }

            // 2. Si no hay registros en OBPL o no está activo, usar OUBR (Sucursales nativas de RRHH)
            if (empty($data)) {
                $sqlOubr = 'SELECT "Code", "Name" FROM OUBR ORDER BY "Code" ASC';
                $rsOubr = @odbc_exec($conn, $sqlOubr);
                if ($rsOubr) {
                    while ($row = odbc_fetch_array($rsOubr)) {
                        $data[] = [
                            'BPLId' => (int) $row['Code'],
                            'BPLName' => $this->utf8ize($row['Name'])
                        ];
                    }
                    odbc_free_result($rsOubr);
                }
            }

            odbc_close($conn);
            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }

    private function callServiceLayer($endpoint, $method = 'GET', $data = []) {
        $config = $this->serviceLayerModel->first();
        if (!$config) {
            throw new \Exception('Configuración de Service Layer no disponible.');
        }

        $baseUrl = rtrim($config['urlServiceLayer'] ?? '', '/');

        $loginUrl = $baseUrl . '/Login';
        $loginBody = json_encode([
            'CompanyDB' => $config['companyDB'],
            'UserName' => $config['userODBC'],
            'Password' => $config['passwordODBC']
        ]);

        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $loginBody);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $loginResp = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($loginResp, 0, $headerSize);
        curl_close($ch);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
        $cookies = implode('; ', $matches[1]);

        $targetUrl = $baseUrl . '/' . ltrim($endpoint, '/');
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);

        $httpHeaders = ['Content-Type: application/json'];
        if ($method === 'PATCH') {
            $httpHeaders[] = 'B1S-ReplaceCollectionsOnPatch: false';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

        if (!empty($data) && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => json_decode($res, true)];
    }

    public function getEmployeeBranches($empID) {
        try {
            $empID = (int) $empID;
            list($conn) = $this->getOdbcConnection();

            // Consulta unificada:
            // 1. HEM10: Sucursales asignadas directamente a la ficha del empleado (con o sin usuario).
            // 2. USR6: Sucursales del usuario SAP si tiene uno vinculado en OHEM.
            $sql = "
            SELECT DISTINCT T.\"BPLId\", T.\"BPLName\"
            FROM (
                SELECT T2.\"BPLId\", T2.\"BPLName\"
                FROM HEM10 T1
                INNER JOIN OBPL T2 ON T1.\"BPLId\" = T2.\"BPLId\"
                WHERE T1.\"empID\" = {$empID}

                UNION

                SELECT T2.\"BPLId\", T2.\"BPLName\"
                FROM OHEM T0
                INNER JOIN OUSR T3 ON T0.\"userId\" = T3.\"USERID\"
                INNER JOIN USR6 T1 ON T3.\"USER_CODE\" = T1.\"UserCode\"
                INNER JOIN OBPL T2 ON T1.\"BPLId\" = T2.\"BPLId\"
                WHERE T0.\"empID\" = {$empID}
            ) T
            ORDER BY T.\"BPLId\" ASC
        ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $branches = [];
            while ($row = odbc_fetch_array($rs)) {
                $branches[] = [
                    'BPLId' => (int) $row['BPLId'],
                    'BPLName' => $this->utf8ize($row['BPLName'])
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON($branches);
        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    /**
     * Establece la conexión ODBC con SAP HANA / SQL Server
     *
     * @return array [resource $conn, array $dataConect]
     * @throws \Exception
     */
    protected function getOdbcConnection() {
        $dataConect = $this->serviceLayerModel->first();
        if (!$dataConect) {
            throw new \Exception('No se encontró configuración de conexión SAP en la base de datos.');
        }

        $conn = @odbc_connect(
                        $dataConect['nameODBC'],
                        $dataConect['userODBC'],
                        $dataConect['passwordODBC']
                );

        if (!$conn) {
            throw new \Exception('Error al conectar por ODBC: ' . odbc_errormsg());
        }

        // Seleccionar esquema de base de datos de la empresa
        if (!empty($dataConect['companyDB'])) {
            $setSchema = @odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"');
            if (!$setSchema) {
                // Fallback para SQL Server si falla la sintaxis de HANA
                @odbc_exec($conn, 'USE [' . $dataConect['companyDB'] . ']');
            }
        }

        return [$conn, $dataConect];
    }

    /**
     * Asigna una sucursal a un empleado (en EmployeesInfo y, si tiene cuenta, en Users de SAP)
     */
    public function addEmployeeBranch() {
        helper('auth');
        $userName = user()->username;
        $empID = (int) $this->request->getPost('empID');
        $bplId = (int) $this->request->getPost('BPLId');

        if ($empID <= 0 || $bplId <= 0) {
            return $this->respond(['status' => 400, 'message' => 'Faltan datos requeridos'], 400);
        }

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

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId de SAP'], 500);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json",
            "User-Agent: PHP",
            "B1S-CaseInsensitive: true",
            "B1S-ReplaceCollectionsOnPatch: true"
        ];

        $wasModified = false;
        $userAlreadyHadIt = true;
        $empAlreadyHadIt = true;

        // =========================================================
        // 1. VERIFICAR SI TIENE USUARIO VINCULADO (OHEM)
        // =========================================================
        list($conn) = $this->getOdbcConnection();
        $sqlUser = "SELECT \"userId\" FROM OHEM WHERE \"empID\" = {$empID}";
        $rsUser = odbc_exec($conn, $sqlUser);
        $rowUser = odbc_fetch_array($rsUser);
        odbc_free_result($rsUser);
        odbc_close($conn);

        $userId = !empty($rowUser['userId']) ? (int) $rowUser['userId'] : 0;

        // =========================================================
        // 2. AGREGAR AL USUARIO SAP (SOLO SI TIENE USUARIO)
        // =========================================================
        if ($userId > 0) {
            $getUserUrl = $slRoot . "/Users({$userId})";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $getUserUrl,
                CURLOPT_PORT => $dataSL['port'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIE => $cookie,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30
            ]);
            $userResp = curl_exec($ch);
            $userHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($userHttp === 200) {
                $userData = json_decode($userResp, true);
                $userBranches = $userData['UserBranchAssignment'] ?? $userData['UserBranchAssignmentCollection'] ?? [];

                $newUserBranches = [];
                $foundInUser = false;
                foreach ($userBranches as $ub) {
                    $ubBpl = (int) ($ub['BPLID'] ?? $ub['BPLId'] ?? 0);
                    if ($ubBpl > 0) {
                        $newUserBranches[] = ['BPLID' => $ubBpl];
                        if ($ubBpl === $bplId) {
                            $foundInUser = true;
                        }
                    }
                }

                if (!$foundInUser) {
                    $userAlreadyHadIt = false;
                    $newUserBranches[] = ['BPLID' => $bplId];

                    $patchUserUrl = $slRoot . "/Users({$userId})";
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $patchUserUrl,
                        CURLOPT_PORT => $dataSL['port'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'PATCH',
                        CURLOPT_POSTFIELDS => json_encode(['UserBranchAssignment' => $newUserBranches]),
                        CURLOPT_COOKIE => $cookie,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_TIMEOUT => 30
                    ]);
                    $patchUserResp = curl_exec($ch);
                    $patchUserHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($patchUserHttp >= 200 && $patchUserHttp < 300) {
                        $wasModified = true;
                    }
                }
            }
        }

        // =========================================================
        // 3. AGREGAR A LA FICHA DEL EMPLEADO (EmployeesInfo / HEM10)
        // Se ejecuta SIEMPRE, tenga o no usuario de SAP
        // =========================================================
        $getEmpUrl = $slRoot . "/EmployeesInfo({$empID})";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $getEmpUrl,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        $empResp = curl_exec($ch);
        $empHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($empHttp === 200) {
            $empData = json_decode($empResp, true);
            $empBranches = $empData['EmployeeBranchAssignment'] ?? [];

            $newEmpBranches = [];
            $foundInEmp = false;
            foreach ($empBranches as $b) {
                $curBpl = (int) ($b['BPLID'] ?? $b['BPLId'] ?? 0);
                if ($curBpl > 0) {
                    $newEmpBranches[] = ['BPLID' => $curBpl];
                    if ($curBpl === $bplId) {
                        $foundInEmp = true;
                    }
                }
            }

            if (!$foundInEmp) {
                $empAlreadyHadIt = false;
                $newEmpBranches[] = ['BPLID' => $bplId];

                $patchEmpUrl = $slRoot . "/EmployeesInfo({$empID})";
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $patchEmpUrl,
                    CURLOPT_PORT => $dataSL['port'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_POSTFIELDS => json_encode(['EmployeeBranchAssignment' => $newEmpBranches]),
                    CURLOPT_COOKIE => $cookie,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 30
                ]);
                $patchEmpResp = curl_exec($ch);
                $patchEmpHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($patchEmpHttp >= 200 && $patchEmpHttp < 300) {
                    $wasModified = true;
                }
            }
        }

        // =========================================================
        // 4. RESPUESTA FINAL
        // =========================================================
        $alreadyAssigned = ($userId > 0) ? ($userAlreadyHadIt && $empAlreadyHadIt) : $empAlreadyHadIt;

        if ($alreadyAssigned) {
            return $this->respond([
                        'status' => 400,
                        'message' => 'La sucursal ya se encuentra asignada al empleado' . ($userId > 0 ? ' y a su usuario de SAP.' : '.')
                            ], 400);
        }

        if (!$wasModified) {
            return $this->respond([
                        'status' => 500,
                        'message' => 'Ocurrió un error al intentar comunicar la asignación a SAP.'
                            ], 500);
        }

        $detailUser = $userId > 0 ? " (Usuario SAP: {$userId})" : " (Empleado RH sin usuario)";
        $this->log->save([
            "description" => "Asignación de sucursal BPLID '{$bplId}' al empleado {$empID}{$detailUser}",
            "user" => $userName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Sucursal asignada correctamente'], 200);
    }

    /**
     * Elimina una sucursal asignada (sincronizando tanto en EmployeesInfo como en Users de SAP)
     */
    public function removeEmployeeBranch($empID, $bplId) {
        helper('auth');
        $userName = user()->username;
        $empID = (int) $empID;
        $bplId = (int) $bplId;

        if ($empID <= 0 || $bplId <= 0) {
            return $this->respond(['status' => 400, 'message' => 'Faltan datos requeridos'], 400);
        }

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

        if (empty($conexionSap->SessionId)) {
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId de SAP'], 500);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json",
            "User-Agent: PHP",
            "B1S-CaseInsensitive: true",
            "B1S-ReplaceCollectionsOnPatch: true"
        ];

        $wasModified = false;

        // =========================================================
        // 1. OBTENER Y REMOVER DEL USUARIO SAP ASOCIADO (USR6)
        // =========================================================
        list($conn) = $this->getOdbcConnection();
        $sqlUser = "SELECT \"userId\" FROM OHEM WHERE \"empID\" = {$empID}";
        $rsUser = odbc_exec($conn, $sqlUser);
        $rowUser = odbc_fetch_array($rsUser);
        odbc_free_result($rsUser);
        odbc_close($conn);

        $userId = !empty($rowUser['userId']) ? (int) $rowUser['userId'] : 0;

        if ($userId > 0) {
            // Se retira $select para evitar el bug de colecciones ocultas en Service Layer
            $getUserUrl = $slRoot . "/Users({$userId})";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $getUserUrl,
                CURLOPT_PORT => $dataSL['port'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIE => $cookie,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30
            ]);
            $userResp = curl_exec($ch);
            $userHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($userHttp === 200) {
                $userData = json_decode($userResp, true);

                // Validamos múltiples nombres posibles de la propiedad por si cambia la versión de SAP
                $userBranches = $userData['UserBranchAssignment'] ?? $userData['UserBranchAssignmentCollection'] ?? [];

                $newUserBranches = [];
                $foundInUser = false;
                foreach ($userBranches as $ub) {
                    $ubBpl = (int) ($ub['BPLID'] ?? $ub['BPLId'] ?? 0);
                    if ($ubBpl === $bplId) {
                        $foundInUser = true;
                    } elseif ($ubBpl > 0) {
                        $newUserBranches[] = ['BPLID' => $ubBpl];
                    }
                }

                // Si la sucursal estaba asignada al usuario, aplicamos PATCH
                if ($foundInUser) {
                    $patchUserUrl = $slRoot . "/Users({$userId})";
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $patchUserUrl,
                        CURLOPT_PORT => $dataSL['port'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'PATCH',
                        CURLOPT_POSTFIELDS => json_encode(['UserBranchAssignment' => $newUserBranches]),
                        CURLOPT_COOKIE => $cookie,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_TIMEOUT => 30
                    ]);
                    $patchUserResp = curl_exec($ch);
                    $patchUserHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($patchUserHttp >= 200 && $patchUserHttp < 300) {
                        $wasModified = true;
                    }
                }
            }
        }

        // =========================================================
        // 2. OBTENER Y REMOVER DE EMPLOYEESINFO (HEM10)
        // =========================================================
        // Se retira $select por precaución
        $getEmpUrl = $slRoot . "/EmployeesInfo({$empID})";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $getEmpUrl,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        $empResp = curl_exec($ch);
        $empHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($empHttp === 200) {
            $empData = json_decode($empResp, true);
            $empBranches = $empData['EmployeeBranchAssignment'] ?? [];

            $newEmpBranches = [];
            $foundInEmp = false;
            foreach ($empBranches as $b) {
                $curBpl = (int) ($b['BPLID'] ?? $b['BPLId'] ?? 0);
                if ($curBpl === $bplId) {
                    $foundInEmp = true;
                } elseif ($curBpl > 0) {
                    $newEmpBranches[] = ['BPLID' => $curBpl];
                }
            }

            // Si la sucursal estaba asignada al empleado directamente, aplicamos PATCH
            if ($foundInEmp) {
                $patchEmpUrl = $slRoot . "/EmployeesInfo({$empID})";
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $patchEmpUrl,
                    CURLOPT_PORT => $dataSL['port'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_POSTFIELDS => json_encode(['EmployeeBranchAssignment' => $newEmpBranches]),
                    CURLOPT_COOKIE => $cookie,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 30
                ]);
                $patchEmpResp = curl_exec($ch);
                $patchEmpHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($patchEmpHttp >= 200 && $patchEmpHttp < 300) {
                    $wasModified = true;
                }
            }
        }

        // =========================================================
        // 3. RESPUESTA FINAL
        // =========================================================
        if (!$wasModified) {
            return $this->respond([
                        'status' => 404,
                        'message' => "La sucursal ID $bplId no se encontraba asignada ni al empleado ni al usuario de SAP"
                            ], 404);
        }

        $this->log->save([
            "description" => "Eliminación de sucursal BPLID '$bplId' del empleado $empID (vía SL)",
            "user" => $userName
        ]);

        return $this->respond(['status' => 200, 'message' => 'Sucursal removida correctamente'], 200);
    }
}
