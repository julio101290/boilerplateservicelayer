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

    public function getEmployee($id) {
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

        $sql = 'SELECT "empID", "ExtEmpNo", "firstName", "lastName", "middleName", "dept", "Active", "Code" FROM OHEM WHERE "empID" = ' . (int) $id;
        $stmt = odbc_exec($conn, $sql);
        if (!$stmt) {
            odbc_close($conn);
            return $this->respond(['status' => 500, 'message' => 'Error query'], 500);
        }
        $row = odbc_fetch_array($stmt);
        odbc_free_result($stmt);
        odbc_close($conn);

        if (!$row) {
            return $this->respond(['status' => 404, 'message' => 'Empleado no encontrado'], 404);
        }
        $row = $this->utf8ize($row);
        return $this->respond($row, 200);
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

        if ($empID <= 0 || $roleID <= 0) {
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

        if ($empID <= 0 || $roleID <= 0) {
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
}