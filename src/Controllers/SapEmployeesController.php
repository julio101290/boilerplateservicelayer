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

class EmployeeSAPController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $branchoffice;
    protected $userLinkSap;

    public function __construct() {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->branchoffice = new BranchofficesModel();
        $this->userLinkSap = new User_sap_linkModel();
        helper(['menu', 'utilerias']);
    }

    private function getOdbcConnection() {
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

        return [$conn, $dataConect];
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

    /**
     * Vista principal o respuesta Ajax al DataTable
     */
    public function index() {
        if ($this->request->isAJAX()) {
            return $this->indexDatatable();
        }
        return view('julio101290\boilerplateservicelayer\Views\employees');
    }

    /**
     * Obtiene el catálogo de sucursales (Intenta OBPL y hace fallback a OUBR si OBPL no está activo)
     */
    public function getBranchesAjax() {
        try {
            list($conn) = $this->getOdbcConnection();

            $sql = 'SELECT "BPLId", "BPLName" FROM OBPL WHERE "Disabled" = \'N\' ORDER BY "BPLId" ASC';
            $rs = @odbc_exec($conn, $sql);

            $data = [];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'BPLId' => (int) $row['BPLId'],
                        'BPLName' => $this->toUtf8($row['BPLName'])
                    ];
                }
                odbc_free_result($rs);
            }

            if (empty($data)) {
                $sqlOubr = 'SELECT "Code", "Name" FROM OUBR ORDER BY "Code" ASC';
                $rsOubr = @odbc_exec($conn, $sqlOubr);
                if ($rsOubr) {
                    while ($row = odbc_fetch_array($rsOubr)) {
                        $data[] = [
                            'BPLId' => (int) $row['Code'],
                            'BPLName' => $this->toUtf8($row['Name'])
                        ];
                    }
                    odbc_free_result($rsOubr);
                }
            }

            odbc_close($conn);
            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    /**
     * DataTable Server-side: Consulta segura uniendo OUBR y OBPL
     */
    public function indexDatatable() {
        try {
            list($conn) = $this->getOdbcConnection();

            // OHEM almacena la sucursal de RRHH en el campo "branch"
            $sql = '
                SELECT 
                    T0."empID", 
                    T0."ExtEmpNo", 
                    T0."Code", 
                    T0."firstName", 
                    T0."lastName", 
                    T0."middleName", 
                    T0."dept", 
                    T0."Active",
                    T0."branch" AS "BranchId",
                    COALESCE(T2."BPLName", T1."Name", \'\') AS "BPLName"
                FROM OHEM T0
                LEFT JOIN OUBR T1 ON T0."branch" = T1."Code"
                LEFT JOIN OBPL T2 ON T0."branch" = T2."BPLId"
                ORDER BY T0."empID" ASC
            ';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error al consultar empleados: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $data[] = [
                    'empID' => (int) $row['empID'],
                    'ExtEmpNo' => $this->toUtf8($row['ExtEmpNo']),
                    'Code' => $this->toUtf8($row['Code']),
                    'firstName' => $this->toUtf8($row['firstName']),
                    'lastName' => $this->toUtf8($row['lastName']),
                    'middleName' => $this->toUtf8($row['middleName']),
                    'Dept' => $this->toUtf8($row['dept']),
                    'Active' => $this->toUtf8($row['Active']),
                    'BPLId' => $row['BranchId'] !== null ? (int) $row['BranchId'] : '',
                    'BPLName' => $this->toUtf8($row['BPLName'] ?? '')
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON(['data' => $data]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener datos de un empleado individual
     */
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

    /**
     * Guardar o actualizar empleado
     */
    public function save() {
        try {
            $post = $this->request->getPost();
            $empID = isset($post['empID']) ? (int) $post['empID'] : 0;
            $bplId = isset($post['BPLId']) && $post['BPLId'] !== '' ? (int) $post['BPLId'] : null;

            if (empty($bplId)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'La sucursal es obligatoria.']);
            }

            // En Service Layer de SAP el campo es "Branch" (apuntando a OUBR)
            $payload = [
                'FirstName' => trim($post['firstName']),
                'LastName' => trim($post['lastName']),
                'MiddleName' => trim($post['middleName'] ?? ''),
                'ExternalEmployeeNumber' => trim($post['ExtEmpNo']),
                'Active' => $post['Active'] === 'Y' ? 'tYES' : 'tNO'
            ];

            if ($empID > 0) {
                $res = $this->callServiceLayer("EmployeesInfo({$empID})", 'PATCH', $payload);
                if ($res['code'] === 204 || $res['code'] === 200) {
                    return $this->response->setJSON(['status' => 200, 'message' => 'Empleado actualizado correctamente']);
                }
                $errMsg = $res['body']['error']['message']['value'] ?? 'Error al actualizar empleado en SAP';
                return $this->response->setJSON(['status' => 400, 'message' => $errMsg]);
            } else {
                $res = $this->callServiceLayer('EmployeesInfo', 'POST', $payload);
                if ($res['code'] === 201 || $res['code'] === 200) {
                    return $this->response->setJSON(['status' => 201, 'message' => 'Empleado registrado correctamente']);
                }
                $errMsg = $res['body']['error']['message']['value'] ?? 'Error al crear empleado en SAP';
                return $this->response->setJSON(['status' => 400, 'message' => $errMsg]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Roles Select2 (incluye roles negativos de sistema como -1 y -2)
     */
    public function getRolesAjaxSelect2() {
        try {
            $postData = $this->request->getPost();
            list($conn) = $this->getOdbcConnection();

            $whereSearch = '';
            if (!empty($postData['searchTerm'])) {
                $term = addslashes($postData['searchTerm']);
                $whereSearch = " WHERE \"name\" LIKE '%{$term}%' OR \"descriptio\" LIKE '%{$term}%' ";
            }

            $sql = "SELECT \"roleID\", \"name\", \"descriptio\" FROM OHRM {$whereSearch} ORDER BY \"roleID\" ASC";
            $rs = odbc_exec($conn, $sql);

            $results = [];
            while ($row = odbc_fetch_array($rs)) {
                $roleID = (int) $row['roleID'];
                $name = $this->toUtf8($row['name']);
                $desc = $this->toUtf8($row['descriptio'] ?? '');

                $results[] = [
                    'id' => $roleID,
                    'text' => "{$roleID} - {$name}" . ($desc ? " ({$desc})" : "")
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON(['results' => $results]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['results' => []]);
        }
    }

    public function getEmployeeRoles($empID) {
        try {
            $empID = (int) $empID;
            list($conn) = $this->getOdbcConnection();

            $sql = "
                SELECT 
                    T0.\"roleID\", 
                    T1.\"name\" AS \"RoleName\", 
                    T1.\"descriptio\" AS \"Description\"
                FROM HEM6 T0
                INNER JOIN OHRM T1 ON T0.\"roleID\" = T1.\"roleID\"
                WHERE T0.\"empID\" = {$empID}
                ORDER BY T0.\"line\" ASC
            ";

            $rs = odbc_exec($conn, $sql);
            $roles = [];
            while ($row = odbc_fetch_array($rs)) {
                $roles[] = [
                    'roleID' => (int) $row['roleID'],
                    'RoleName' => $this->toUtf8($row['RoleName']),
                    'Description' => $this->toUtf8($row['Description'])
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON($roles);
        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    public function addEmployeeRole() {
        try {
            $empID = (int) $this->request->getPost('empID');
            $roleCode = $this->request->getPost('RoleCode');

            if ($empID <= 0 || !is_numeric($roleCode)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Datos de rol inválidos']);
            }
            $roleCode = (int) $roleCode;

            $empData = $this->callServiceLayer("EmployeesInfo({$empID})?$select=EmployeeRolesSetup");
            $currentRoles = $empData['body']['EmployeeRolesSetup'] ?? [];

            foreach ($currentRoles as $r) {
                if ((int) $r['RoleID'] === $roleCode) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'El empleado ya tiene asignado este rol.']);
                }
            }

            $currentRoles[] = ['RoleID' => $roleCode];
            $patchPayload = ['EmployeeRolesSetup' => $currentRoles];

            $res = $this->callServiceLayer("EmployeesInfo({$empID})", 'PATCH', $patchPayload);

            if ($res['code'] === 204 || $res['code'] === 200) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Rol asignado correctamente']);
            }

            $errMsg = $res['body']['error']['message']['value'] ?? 'No se pudo asignar el rol en SAP';
            return $this->response->setJSON(['status' => 400, 'message' => $errMsg]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function removeEmployeeRole($empID, $roleCode) {
        try {
            $empID = (int) $empID;
            if (!is_numeric($roleCode)) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Código de rol inválido']);
            }
            $roleCode = (int) $roleCode;

            $empData = $this->callServiceLayer("EmployeesInfo({$empID})?$select=EmployeeRolesSetup");
            $currentRoles = $empData['body']['EmployeeRolesSetup'] ?? [];

            $newRoles = [];
            foreach ($currentRoles as $r) {
                if ((int) $r['RoleID'] !== $roleCode) {
                    $newRoles[] = ['RoleID' => (int) $r['RoleID']];
                }
            }

            $patchPayload = ['EmployeeRolesSetup' => $newRoles];
            $res = $this->callServiceLayer("EmployeesInfo({$empID})", 'PATCH', $patchPayload);

            if ($res['code'] === 204 || $res['code'] === 200) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Rol eliminado correctamente']);
            }

            $errMsg = $res['body']['error']['message']['value'] ?? 'No se pudo remover el rol en SAP';
            return $this->response->setJSON(['status' => 400, 'message' => $errMsg]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // ===== GESTIÓN DE MÚLTIPLES SUCURSALES (Sincronización con USR6 del usuario SAP vinculado) =====

    public function getEmployeeBranches($empID) {
        try {
            $empID = (int) $empID;
            list($conn) = $this->getOdbcConnection();

            // Buscar las sucursales asignadas al usuario de SAP vinculado al empleado (USR6)
            $sql = "
                SELECT T2.\"BPLId\", T2.\"BPLName\"
                FROM OHEM T0
                INNER JOIN USR6 T1 ON T0.\"userId\" = T1.\"UserCode\"
                INNER JOIN OBPL T2 ON T1.\"BPLId\" = T2.\"BPLId\"
                WHERE T0.\"empID\" = {$empID}
                ORDER BY T2.\"BPLId\" ASC
            ";

            $rs = @odbc_exec($conn, $sql);
            $branches = [];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $branches[] = [
                        'BPLId' => (int) $row['BPLId'],
                        'BPLName' => $this->toUtf8($row['BPLName'])
                    ];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);
            return $this->response->setJSON($branches);
        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    public function addEmployeeBranch() {
        try {
            $empID = (int) $this->request->getPost('empID');
            $bplId = (int) $this->request->getPost('BPLId');

            if ($empID <= 0 || $bplId <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Datos de sucursal inválidos']);
            }

            // Obtener el usuario SAP vinculado al empleado
            list($conn) = $this->getOdbcConnection();
            $sql = "SELECT \"userId\" FROM OHEM WHERE \"empID\" = {$empID}";
            $rs = odbc_exec($conn, $sql);
            $row = odbc_fetch_array($rs);
            odbc_free_result($rs);
            odbc_close($conn);

            $userId = !empty($row['userId']) ? (int) $row['userId'] : 0;
            if ($userId <= 0) {
                return $this->response->setJSON([
                            'status' => 400,
                            'message' => 'Para asignar múltiples sucursales SAP, el empleado debe tener un Usuario de SAP vinculado (campo userId).'
                ]);
            }

            // Actualizar UserBranchAssignment vía Service Layer
            $userData = $this->callServiceLayer("Users({$userId})?$select=UserBranchAssignment");
            $assignments = $userData['body']['UserBranchAssignment'] ?? [];

            foreach ($assignments as $a) {
                if ((int) $a['BPLID'] === $bplId) {
                    return $this->response->setJSON(['status' => 400, 'message' => 'La sucursal ya está asignada al usuario.']);
                }
            }

            $assignments[] = ['BPLID' => $bplId];
            $res = $this->callServiceLayer("Users({$userId})", 'PATCH', ['UserBranchAssignment' => $assignments]);

            if ($res['code'] === 204 || $res['code'] === 200) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Sucursal asignada']);
            }

            return $this->response->setJSON(['status' => 400, 'message' => 'No se pudo asignar la sucursal en SAP']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    public function removeEmployeeBranch($empID, $bplId) {
        try {
            $empID = (int) $empID;
            $bplId = (int) $bplId;

            list($conn) = $this->getOdbcConnection();
            $sql = "SELECT \"userId\" FROM OHEM WHERE \"empID\" = {$empID}";
            $rs = odbc_exec($conn, $sql);
            $row = odbc_fetch_array($rs);
            odbc_free_result($rs);
            odbc_close($conn);

            $userId = !empty($row['userId']) ? (int) $row['userId'] : 0;
            if ($userId <= 0) {
                return $this->response->setJSON(['status' => 400, 'message' => 'Empleado sin usuario SAP vinculado']);
            }

            $userData = $this->callServiceLayer("Users({$userId})?$select=UserBranchAssignment");
            $assignments = $userData['body']['UserBranchAssignment'] ?? [];

            $newAssignments = [];
            foreach ($assignments as $a) {
                if ((int) $a['BPLID'] !== $bplId) {
                    $newAssignments[] = ['BPLID' => (int) $a['BPLID']];
                }
            }

            $res = $this->callServiceLayer("Users({$userId})", 'PATCH', ['UserBranchAssignment' => $newAssignments]);

            if ($res['code'] === 204 || $res['code'] === 200) {
                return $this->response->setJSON(['status' => 200, 'message' => 'Sucursal removida']);
            }

            return $this->response->setJSON(['status' => 400, 'message' => 'Error al remover sucursal en SAP']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }
}
