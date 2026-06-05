<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplate\Models\UserModel;
use julio101290\boilerplatecompanies\Models\UsuariosempresaModel;

class RefundsAuthController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $user_sap_link;
    protected $empresa;
    protected $serviceLayerController;
    protected $serviceLayerModel;
    protected $users;
    protected $usersPerCompanie;

    public function __construct() {
        $this->user_sap_link = new User_sap_linkModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->users = new UserModel();
        $this->usersPerCompanie = new UsuariosempresaModel();

        helper(['menu', 'utilerias']);
    }

    // -----------------------------------------------------------------
    // Vista principal (DataTable)
    // -----------------------------------------------------------------
    public function index() {
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');
            $searchValue = trim($request->getGet('search')['value'] ?? '');
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir = strtolower($request->getGet('order')[0]['dir'] ?? 'asc');

            // Columnas permitidas para ordenar
            $columns = [
                'Code',
                'U_Folio',
                'U_Area',
                'U_Employee',
                'U_Date',
                'U_Total',
                'U_Status',
                'U_UserCode',
                'U_CodeMov',
                'U_TypeVoucher',
                'U_Branch'
            ];
            $orderField = $columns[$orderColumnIndex] ?? 'Code';

            $dataConect = $this->serviceLayerModel->first(); // array
            // Obtener usuario SAP vinculado (opcional)
            $userLinkSap = $this->user_sap_link
                    ->select('*')
                    ->where('iduser', $idUser)
                    ->first();
            $autorizador = $userLinkSap['sapuser'] ?? null;

            $result = $this->showVouchersWithoutAuth(
                    $searchValue,
                    $start,
                    $length,
                    $orderField,
                    $orderDir,
                    $dataConect
            );

            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => $result['recordsTotal'],
                        'recordsFiltered' => $result['recordsFiltered'],
                        'data' => $result['data'],
            ]);
        }

        $titulos["title"] = 'Autorización de Comprobantes';
        $titulos["subtitle"] = 'Comprobantes pendientes de autorización';

        return view('julio101290\boilerplateservicelayer\Views\refundsAuth', $titulos);
    }

    // -----------------------------------------------------------------
    // Consulta ODBC para obtener comprobantes pendientes (U_Status = 2)
    // -----------------------------------------------------------------
    public function showVouchersWithoutAuth(
            string $search,
            int $start,
            int $length,
            string $orderField,
            string $orderDir,
            array $dataConect   // ← ahora es array
    ): array {
        try {
            $conn = odbc_connect(
                    $dataConect['nameODBC'],
                    $dataConect['userODBC'],
                    $dataConect['passwordODBC']
            );
            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // Establecer esquema
            $schemaSet = odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"');
            if (!$schemaSet) {
                log_message('error', 'Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            $tableName = '"' . $dataConect['companyDB'] . '"."@QSYS_GLO_VOUC"';
            $where = '"U_Status" = 2';

            // Búsqueda
            if (!empty($search)) {
                $escaped = str_replace("'", "''", $search);
                $where .= ' AND (
                    "U_Folio" LIKE \'%' . $escaped . '%\'
                    OR "U_Area" LIKE \'%' . $escaped . '%\'
                    OR CAST("U_Employee" AS VARCHAR) LIKE \'%' . $escaped . '%\'
                    OR "U_Coments" LIKE \'%' . $escaped . '%\'
                    OR "U_UserCode" LIKE \'%' . $escaped . '%\'
                    OR "U_CodeMov" LIKE \'%' . $escaped . '%\'
                )';
            }

            // Ordenamiento
            $allowedOrder = ['Code', 'U_Folio', 'U_Area', 'U_Employee', 'U_Date', 'U_Total', 'U_Status', 'U_UserCode', 'U_CodeMov', 'U_TypeVoucher', 'U_Branch'];
            if (!in_array($orderField, $allowedOrder, true)) {
                $orderField = 'Code';
            }
            $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

            // Total de registros
            $sqlCount = "SELECT COUNT(*) AS total FROM $tableName WHERE $where";
            $rsCount = odbc_exec($conn, $sqlCount);
            if (!$rsCount) {
                throw new \Exception('Error COUNT: ' . odbc_errormsg($conn));
            }
            $totalRow = odbc_fetch_array($rsCount);
            $recordsTotal = (int) ($totalRow['total'] ?? 0);
            odbc_free_result($rsCount);

            // Consulta paginada con ROW_NUMBER()
            $sqlPaged = "
                SELECT * FROM (
                    SELECT
                        \"Code\",
                        \"U_Folio\",
                        \"U_Area\",
                        \"U_Employee\",
                        \"U_Coments\",
                        \"U_Date\",
                        \"U_Total\",
                        \"U_Status\",
                        \"U_UserCode\",
                        \"U_CodeMov\",
                        \"U_TypeVoucher\",
                        \"U_Branch\",
                        ROW_NUMBER() OVER (ORDER BY \"$orderField\" $orderDir) AS rn
                    FROM $tableName
                    WHERE $where
                ) AS t
                WHERE rn > $start AND rn <= " . ($start + $length) . "
                ORDER BY rn
            ";

            $rsData = odbc_exec($conn, $sqlPaged);
            if (!$rsData) {
                throw new \Exception('Error DATA: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rsData)) {
                unset($row['rn']);
                $row = $this->utf8ize($row);
                $data[] = [
                    'Code' => $row['Code'],
                    'U_Folio' => $row['U_Folio'],
                    'U_Area' => $row['U_Area'],
                    'U_Employee' => $row['U_Employee'],
                    'U_Coments' => $row['U_Coments'],
                    'U_Date' => $row['U_Date'],
                    'U_Total' => round((float) $row['U_Total'], 2),
                    'U_Status' => $row['U_Status'],
                    'U_UserCode' => $row['U_UserCode'],
                    'U_CodeMov' => $row['U_CodeMov'],
                    'U_TypeVoucher' => $row['U_TypeVoucher'],
                    'U_Branch' => $row['U_Branch'],
                ];
            }

            odbc_free_result($rsData);
            odbc_close($conn);

            return [
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Error en showVouchersWithoutAuth: ' . $e->getMessage());
            return [
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    // -----------------------------------------------------------------
    // Autorizar un comprobante (cambiar U_Status de 2 a 4)
    // -----------------------------------------------------------------
    public function authorizeVoucher() {
        helper('auth');
        $request = service('request');
        $userName = user()->username;

        $inputJson = $request->getJSON(true);
        if (!empty($inputJson)) {
            $code = (int) ($inputJson['code'] ?? 0);
            $folio = $inputJson['folio'] ?? null;
        } else {
            $code = (int) $request->getPost('code');
            $folio = $request->getPost('folio');
        }

        if (!$code) {
            return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error' => 'Falta el parámetro "code"'
            ]);
        }

        $dataConect = $this->serviceLayerModel->first(); // array
        if (!$dataConect) {
            return $this->response->setStatusCode(500)->setJSON([
                        'success' => false,
                        'error' => 'No hay configuración de conexión SAP'
            ]);
        }

        $conn = odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
        );
        if (!$conn) {
            return $this->response->setStatusCode(500)->setJSON([
                        'success' => false,
                        'error' => 'Error conexión ODBC: ' . odbc_errormsg()
            ]);
        }

        $tableName = '"' . $dataConect['companyDB'] . '"."@QSYS_GLO_VOUC"';

        // Verificar estado actual
        $sqlCheck = "SELECT \"U_Status\", \"U_Folio\" FROM $tableName WHERE \"Code\" = $code";
        $rsCheck = odbc_exec($conn, $sqlCheck);
        if (!$rsCheck || !($row = odbc_fetch_array($rsCheck))) {
            odbc_close($conn);
            return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'Comprobante no encontrado'
            ]);
        }

        $currentStatus = (int) ($row['U_Status'] ?? 0);
        $folioActual = $row['U_Folio'] ?? $folio;

        if ($currentStatus !== 2) {
            odbc_close($conn);
            return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error' => "El comprobante no está pendiente de autorización (status actual: $currentStatus). Se requiere status = 2."
            ]);
        }

        // Actualizar status a 4
        $sqlUpdate = "UPDATE $tableName SET \"U_Status\" = 4 WHERE \"Code\" = $code";
        $rsUpdate = odbc_exec($conn, $sqlUpdate);
        if (!$rsUpdate) {
            $errMsg = odbc_errormsg($conn);
            odbc_close($conn);
            return $this->response->setStatusCode(500)->setJSON([
                        'success' => false,
                        'error' => "Error al actualizar: $errMsg"
            ]);
        }

        odbc_close($conn);

        // Registrar bitácora
        $this->log->save([
            'description' => "Usuario {$userName} autorizó el comprobante {$folioActual} (Code: {$code})",
            'user' => $userName
        ]);

        return $this->response->setJSON([
                    'success' => true,
                    'message' => "Comprobante {$folioActual} autorizado correctamente",
                    'code' => $code,
                    'folio' => $folioActual
        ]);
    }

    // -----------------------------------------------------------------
    // Mostrar detalle (líneas) de un comprobante (DataTable)
    // -----------------------------------------------------------------
    public function showVoucherDetails() {
        $request = service('request');

        $input = $request->getJSON(true);
        if (empty($input)) {
            $input = $request->getPost();
        }

        $draw = (int) ($input['draw'] ?? 0);
        $start = (int) ($input['start'] ?? 0);
        $length = (int) ($input['length'] ?? 10);
        $searchValue = (string) ($input['search']['value'] ?? ($input['search'] ?? ''));
        $orderColumnIndex = (int) ($input['order'][0]['column'] ?? 1);
        $orderDir = strtolower($input['order'][0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        // Código del comprobante (Code de VOUC)
        $codeVoucher = (int) ($input['code'] ?? 0);
        if ($codeVoucher <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'Se requiere el parámetro "code"'
            ]);
        }

        $dataConect = $this->serviceLayerModel->first();
        if (!$dataConect) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'No hay configuración ODBC'
            ]);
        }

        $conn = odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
        );
        if (!$conn) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'No se pudo conectar a HANA vía ODBC'
            ]);
        }

        // Consulta directa a VODE usando U_CodeVoucher = Code del voucher
        $tableVode = '"' . $dataConect['companyDB'] . '"."@QSYS_GLO_VODE"';
        $sqlDetails = "SELECT
                        \"U_Line\",
                        \"U_NA\",
                        \"U_Type\",
                        \"U_Provider\",
                        \"U_Subtotal\",
                        \"U_IVA\",
                        \"U_Total\",
                        \"U_Coments\"
                    FROM $tableVode
                    WHERE \"U_CodeVoucher\" = $codeVoucher
                    ORDER BY \"U_Line\" ASC";

        $stmt = odbc_exec($conn, $sqlDetails);
        if (!$stmt) {
            odbc_close($conn);
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'Error ejecutando query ODBC: ' . odbc_errormsg($conn)
            ]);
        }

        $lines = [];
        while ($row = odbc_fetch_array($stmt)) {
            $lines[] = $this->utf8ize($row);
        }
        odbc_free_result($stmt);
        odbc_close($conn);

        // Si no hay líneas, retornar vacío
        if (empty($lines)) {
            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
            ]);
        }

        // Filtrado local (por búsqueda en proveedor, tipo o comentarios)
        $filtered = $lines;
        if ($searchValue !== '') {
            $s = mb_strtolower($searchValue);
            $filtered = array_filter($lines, function ($ln) use ($s) {
                $provider = mb_strtolower((string) ($ln['U_Provider'] ?? ''));
                $type = mb_strtolower((string) ($ln['U_Type'] ?? ''));
                $comments = mb_strtolower((string) ($ln['U_Coments'] ?? ''));
                return strpos($provider, $s) !== false ||
                        strpos($type, $s) !== false ||
                        strpos($comments, $s) !== false;
            });
            $filtered = array_values($filtered);
        }

        $recordsTotal = count($lines);
        $recordsFiltered = count($filtered);

        // Columnas para ordenar (mapeo según las columnas mostradas en DataTable)
        $columnsMap = [
            0 => 'U_Line',
            1 => 'U_NA',
            2 => 'U_Type',
            3 => 'U_Provider',
            4 => 'U_Subtotal',
            5 => 'U_IVA',
            6 => 'U_Total'
        ];
        $orderField = $columnsMap[$orderColumnIndex] ?? 'U_Line';

        usort($filtered, function ($a, $b) use ($orderField, $orderDir) {
            $va = $a[$orderField] ?? '';
            $vb = $b[$orderField] ?? '';
            if (is_numeric($va) && is_numeric($vb)) {
                $cmp = $va <=> $vb;
            } else {
                $cmp = strcasecmp((string) $va, (string) $vb);
            }
            return ($orderDir === 'desc') ? -$cmp : $cmp;
        });

        // Paginación
        $paged = ($length > 0) ? array_slice($filtered, $start, $length) : $filtered;

        $out = [];
        $idx = $start + 1;
        foreach ($paged as $line) {
            $out[] = [
                'No' => $idx++,
                'Linea' => $line['U_Line'] ?? '',
                'NA' => $line['U_NA'] ?? '',
                'Tipo' => $line['U_Type'] ?? '',
                'Proveedor' => $line['U_Provider'] ?? '',
                'Subtotal' => isset($line['U_Subtotal']) ? round((float) $line['U_Subtotal'], 2) : 0,
                'IVA' => isset($line['U_IVA']) ? round((float) $line['U_IVA'], 2) : 0,
                'Total' => isset($line['U_Total']) ? round((float) $line['U_Total'], 2) : 0,
                'Comentarios' => $line['U_Coments'] ?? '',
            ];
        }

        return $this->response->setJSON([
                    'draw' => $draw,
                    'recordsTotal' => $recordsTotal,
                    'recordsFiltered' => $recordsFiltered,
                    'data' => $out
        ]);
    }

    // -----------------------------------------------------------------
    // Métodos auxiliares (heredados del controlador original)
    // -----------------------------------------------------------------
    public function getUser_sap_link() {
        helper('auth');
        $idUser = user()->id;
        $userName = user()->username;
        $firstname = user()->firstname;
        $lastname = user()->lastname;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $idUser_sap_link = $this->request->getPost("idUser_sap_link");
        $dato = $this->user_sap_link->whereIn('idEmpresa', $empresasID)
                ->where('id', $idUser_sap_link)
                ->first();

        $companie = $this->empresa->where("id", $dato["idEmpresa"])->first();

        $dato["username"] = $userName . " " . $firstname . " " . $lastname;
        $dato["nameCompanie"] = $companie["nombre"];

        return $this->response->setJSON($dato);
    }

    public function save() {
        helper('auth');
        $userName = user()->username;
        $datos = $this->request->getPost();
        $idKey = $datos["idUser_sap_link"] ?? 0;

        if ($idKey == 0) {
            try {
                if (!$this->user_sap_link->save($datos)) {
                    $errores = implode(" ", $this->user_sap_link->errors());
                    return $this->respond(['status' => 400, 'message' => $errores], 400);
                }
                $this->log->save([
                    "description" => lang("user_sap_link.logDescription") . json_encode($datos),
                    "user" => $userName
                ]);
                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } catch (\Throwable $ex) {
                return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
            }
        } else {
            if (!$this->user_sap_link->update($idKey, $datos)) {
                $errores = implode(" ", $this->user_sap_link->errors());
                return $this->respond(['status' => 400, 'message' => $errores], 400);
            }
            $this->log->save([
                "description" => lang("user_sap_link.logUpdated") . json_encode($datos),
                "user" => $userName
            ]);
            return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
        }
    }

    public function delete($id) {
        helper('auth');
        $userName = user()->username;
        $registro = $this->user_sap_link->find($id);

        if (!$this->user_sap_link->delete($id)) {
            return $this->respond(['status' => 404, 'message' => lang("user_sap_link.msg.msg_get_fail")], 404);
        }

        $this->user_sap_link->purgeDeleted();
        $this->log->save([
            "description" => lang("user_sap_link.logDeleted") . json_encode($registro),
            "user" => $userName
        ]);

        return $this->respondDeleted($registro, lang("user_sap_link.msg_delete"));
    }

    public function getUsersAjaxSelect2() {
        $request = service('request');
        $postData = $request->getPost();

        $response = array();
        $response['token'] = csrf_hash();
        $idEmpresa = $postData['idEmpresa'];

        $listUsers = $this->user_sap_link->mdlGetUsers($postData['searchTerm'], $idEmpresa)->getResultArray();

        $jsonVariable = '{ "results": [';

        foreach ($listUsers as $user) {
            $jsonVariable .= ' {
                    "id": "' . $user["id"] . '",
                    "text": "' . utf8_encode($user["id"] . " - " . $user["username"] . " " . $user["firstname"] . " " . $user["lastname"]) . '"
                  },';
        }

        $jsonVariable = rtrim($jsonVariable, ',');
        $jsonVariable .= ' ] }';

        echo $jsonVariable;
    }

    // -----------------------------------------------------------------
    // Utilidad UTF-8
    // -----------------------------------------------------------------
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
