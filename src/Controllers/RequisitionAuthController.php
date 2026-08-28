<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplateservicelayer\Models\{
    User_sap_linkModel
};
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplate\Models\UserModel;
use julio101290\boilerplatecompanies\Models\UsuariosempresaModel;

class RequisitionAuthController extends BaseController {

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

    public function index() {
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');
            $searchValue = $request->getGet('search')['value'] ?? '';
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 1);
            $orderDir = $request->getGet('order')[0]['dir'] ?? 'asc';

            // Capturar si es No autorizadas (0) o Ya autorizadas (1)
            $authorized = (int) ($request->getGet('authorized') ?? 0);

            // Mapeo exacto según el orden de columnas en tu vista:
            // 0: Acciones | 1: Almacén | 2: Folio (DocNum) | 3: Fecha (DocDate)
            $fields = [
                0 => 'DocEntry',
                1 => 'Almacen',
                2 => 'DocNum',
                3 => 'DocDate'
            ];
            $orderField = $fields[$orderColumnIndex] ?? 'DocNum';

            $dataSL = $this->serviceLayerModel->select("*")->first();

            $conexionSap = $this->serviceLayerController->login(
                    $dataSL["url"],
                    $dataSL["port"],
                    $dataSL["password"],
                    $dataSL["username"],
                    $dataSL["companyDB"]
            );

            $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";

            $userLinkSap = $this->user_sap_link->select("*")->where("iduser", $idUser)->first();

            $result = $this->showReqWithOoutAuth(
                    $cookie,
                    $userLinkSap["sapuser"],
                    $searchValue,
                    $dataSL["url"],
                    $dataSL["port"],
                    $start,
                    $length,
                    $orderField,
                    $orderDir,
                    $fields,
                    $authorized
            );

            if (isset($result['error']) && $result['error'] === true) {
                return $this->response->setStatusCode(500)->setJSON($result);
            }

            $recordsTotal = $result['recordsTotal'] ?? 0;
            $recordsFiltered = $result['recordsFiltered'] ?? $recordsTotal;
            $data = $result['data'] ?? [];

            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => (int) $recordsTotal,
                        'recordsFiltered' => (int) $recordsFiltered,
                        'data' => $data,
            ]);
        }

        $titulos["title"] = lang('authreq.title');
        $titulos["subtitle"] = lang('authreq.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\requisitionAuth', $titulos);
    }

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

    public function showReqWithOoutAuth(
            $cookie,
            $userAuth,
            $search,
            $baseUrlRoot,
            $port,
            $start = 0,
            $length = 10,
            $orderField = 'DocNum',
            $orderDir = 'asc',
            array $fields = [],
            int $authorized = 0
    ) {
        try {
            $autorizador = trim((string) $userAuth);
            $search = trim((string) $search);
            $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

            // Validar que orderField sea seguro y permitido
            $allowedFields = ['DocEntry', 'DocNum', 'DocDate', 'CardCode', 'CardName', 'Almacen', 'NombreAlmacen'];
            $orderField = in_array($orderField, $allowedFields) ? $orderField : 'DocNum';

            $dataConect = $this->serviceLayerModel->first();

            // Conexión ODBC
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

            // Filtro según botón
            if ($authorized === 1) {
                $authCondition = "OPRQ.\"U_Authorized\" NOT LIKE 'U%'";
            } else {
                $authCondition = "OPRQ.\"U_Authorized\" LIKE 'U%'";
            }

            // Consulta SQL: se corrigió ORDER BY "{$orderField}" para permitir ordenar por alias (Almacen)
            $sql = "
        SELECT
            OPRQ.\"DocEntry\",
            OPRQ.\"DocNum\",
            OPRQ.\"DocDate\",
            OPRQ.\"CardCode\",
            OPRQ.\"CardName\",
            MAX(PRQ1.\"WhsCode\") AS \"Almacen\",
            MAX(OWHS.\"WhsName\") AS \"NombreAlmacen\",
            OPRQ.\"DocTotal\" - OPRQ.\"VatSum\" AS \"TotalSinImpuestos\",
            OPRQ.\"VatSum\" AS \"Impuestos\",
            OPRQ.\"DocTotal\" AS \"TotalConImpuestos\",
            OPRQ.\"U_Autorizador\",
            OPRQ.\"UserSign\",
            UC.\"U_NAME\" AS \"NombreUsuario\"
        FROM OPRQ
        INNER JOIN PRQ1 ON PRQ1.\"DocEntry\" = OPRQ.\"DocEntry\"
        LEFT JOIN OWHS ON OWHS.\"WhsCode\" = PRQ1.\"WhsCode\"
        LEFT JOIN OUSR UC ON UC.\"USERID\" = OPRQ.\"UserSign\"
        WHERE
            OPRQ.\"CANCELED\" = 'N'
            AND {$authCondition}
            AND OPRQ.\"U_Autorizador\" = '{$autorizador}'
        ";

            if ($search !== '') {
                $sql .= " AND (OPRQ.\"DocNum\" LIKE '%{$search}%' OR OPRQ.\"CardName\" LIKE '%{$search}%')";
            }

            $sql .= "
        GROUP BY
            OPRQ.\"DocEntry\",
            OPRQ.\"DocNum\",
            OPRQ.\"DocDate\",
            OPRQ.\"CardCode\",
            OPRQ.\"CardName\",
            OPRQ.\"DocTotal\",
            OPRQ.\"VatSum\",
            OPRQ.\"U_Autorizador\",
            OPRQ.\"UserSign\",
            UC.\"U_NAME\"
        ORDER BY \"{$orderField}\" {$orderDir}
        LIMIT {$length} OFFSET {$start}
        ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $data[] = $this->utf8ize([
                    'DocEntry' => $row['DocEntry'],
                    'DocNum' => $row['DocNum'],
                    'DocDate' => $row['DocDate'],
                    'CardCode' => $row['CardCode'],
                    'CardName' => $row['CardName'],
                    'Almacen' => $row['Almacen'],
                    'NombreAlmacen' => $row['NombreAlmacen'],
                    'TotalSinImpuestos' => round((float) $row['TotalSinImpuestos'], 2),
                    'Impuestos' => round((float) $row['Impuestos'], 2),
                    'TotalConImpuestos' => round((float) $row['TotalConImpuestos'], 2),
                    'AutorizadorKey' => $row['U_Autorizador'],
                    'UsuarioKey' => $row['UserSign'],
                    'NombreDeUsuario' => $row['NombreUsuario'],
                    '_raw' => $row
                ]);
            }

            odbc_free_result($rs);
            odbc_close($conn);

            $records = count($data);

            return [
                'recordsTotal' => $records,
                'recordsFiltered' => $records,
                'data' => $data
            ];
        } catch (\Throwable $e) {
            return [
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get users via Ajax for select2
     */
    public function getUsersAjaxSelect2() {

        $request = service('request');
        $postData = $request->getPost();

        $response = array();

        // Read new token and assign in $response['token']
        $response['token'] = csrf_hash();
        $idEmpresa = $postData['idEmpresa'];

        $listUsers = $this->user_sap_link->mdlGetUsers($postData['searchTerm'], $idEmpresa)->getResultArray();

        $data = array();
        $data[] = array(
            "id" => 0,
            "text" => "0 Todos Los Productos",
        );

        $jsonVariable = ' { "results": [';

        foreach ($listUsers as $user) {

            $jsonVariable .= ' {
                    "id": "' . $user["id"] . '",
                    "text": "' . utf8_encode($user["id"] . " - " . $user["username"] . " " . $user["firstname"] . " " . $user["lastname"]) . '"
                  },';
        }


        $jsonVariable = substr($jsonVariable, 0, -1);

        $jsonVariable .= ' ]
                            }';

        echo ($jsonVariable);
    }

    public function authorizeReq() {
        helper('auth');

        $request = service('request');

        // usuario autenticado (ajusta si tu helper devuelve otra cosa)
        $idUser = user() ? user()->id : null;

        $userName = user()->username;

        // --- 1) leer input (JSON o form)
        $inputJson = $request->getJSON(true); // array asociativo
        if (!empty($inputJson) && is_array($inputJson)) {
            $docEntry = isset($inputJson['docEntry']) ? (int) $inputJson['docEntry'] : 0;
            $docNum = $inputJson['docNum'] ?? null;
            $almacen = $inputJson['almacen'] ?? null;
        } else {
            $docEntry = (int) $request->getPost('docEntry');
            $docNum = $request->getPost('docNum') ?? null;
            $almacen = $request->getPost('almacen') ?? null;
        }

        if (!$docEntry) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Falta docEntry']);
        }

        // --- 2) obtener configuración Service Layer y login
        $dataSL = $this->serviceLayerModel->select('*')->first();
        if (empty($dataSL)) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'No hay configuración Service Layer']);
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
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Error login SL: ' . $e->getMessage()]);
        }

        if (empty($conexionSap->SessionId)) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'No se obtuvo SessionId de Service Layer']);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";

        // --- 3) normalizar root SL (asegurar exactamente /b1s/v1 una vez)
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            // si ya contiene, dejar solo una ocurrencia (en caso de doble '/b1s/v1' accidental)
            // asegurar que termina con /b1s/v1
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        // --- 4) obtener user mapping local -> SAP (si existe)
        $sapAutorizer = null;
        if ($idUser !== null) {
            $userLinkSap = $this->user_sap_link->select('*')->where('iduser', $idUser)->first();
            $sapAutorizer = $userLinkSap['sapuser'] ?? null; // puede ser UserCode o InternalKey según tu mapping
        }

        // --- 5) GET requisición actual para validar estado
        $urlGet = $slRoot . "/PurchaseRequests({$docEntry})?\$select=U_Authorized,U_Autorizador,DocNum";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlGet,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: PHP",
                "B1S-CaseInsensitive: true"
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        $respGet = curl_exec($ch);
        $errGet = curl_error($ch);
        $httpGet = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errGet) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'cURL Error (GET): ' . $errGet]);
        }
        if ($httpGet < 200 || $httpGet >= 300) {
            $body = json_decode($respGet, true);
            return $this->response->setStatusCode($httpGet)->setJSON(['success' => false, 'error' => 'Error al obtener requisición', 'body' => $body ?? $respGet]);
        }

        $row = json_decode($respGet, true);
        // En SL la respuesta puede venir como objeto o con "value": [...]
        if (isset($row['value']) && is_array($row['value'])) {
            $sample = $row['value'][0] ?? [];
        } else {
            $sample = is_array($row) ? $row : [];
        }

        $currentUA = $sample['U_Authorized'] ?? '';
        $currentAutorizador = $sample['U_Autorizador'] ?? null;
        $docNumFromSL = $sample['DocNum'] ?? $docNum;

        // validar estado (según tu regla: startswith 'U')
        if (!str_starts_with((string) $currentUA, 'U')) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'La requisición no está en estado pendiente de autorización']);
        }

        // validar autorizador (opcional)
        if ($sapAutorizer !== null) {
            // comparar según como esté guardado en U_Autorizador (UserCode o InternalKey)
            if ((string) $currentAutorizador !== (string) $sapAutorizer) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No estás autorizado para aprobar esta requisición']);
            }
        }

        // --- 6) preparar PATCH para autorizar
        $urlPatch = $slRoot . "/PurchaseRequests({$docEntry})";
        $payload = [
            'U_Authorized' => 'Y'
        ];
        // opcional: setear quién autoriza si tienes el código
        if (!empty($sapAutorizer)) {
            $payload['U_Autorizador'] = $sapAutorizer;
        }

        $jsonPayload = json_encode($payload);

        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL => $urlPatch,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => $jsonPayload,
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
        $respPatch = curl_exec($ch2);
        $errPatch = curl_error($ch2);
        $httpPatch = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        if ($errPatch) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'cURL Error (PATCH): ' . $errPatch]);
        }
        if ($httpPatch < 200 || $httpPatch >= 300) {
            $body = json_decode($respPatch, true);
            return $this->response->setStatusCode($httpPatch)->setJSON(['success' => false, 'error' => 'Error al actualizar requisición', 'body' => $body ?? $respPatch]);
        }

        // --- 7) GET fila actualizada (para devolver updatedRow)
        $urlGet2 = $slRoot . "/PurchaseRequests({$docEntry})?\$select=DocEntry,DocNum,U_Authorized,U_Autorizador";
        $ch3 = curl_init();
        curl_setopt_array($ch3, [
            CURLOPT_URL => $urlGet2,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: PHP",
                "B1S-CaseInsensitive: true"
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        $resp2 = curl_exec($ch3);
        $err2 = curl_error($ch3);
        $http2 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
        curl_close($ch3);

        if ($err2 || $http2 < 200 || $http2 >= 300) {
            // devolvemos éxito aunque no pudimos recuperar la fila actualizada
            return $this->response->setJSON([
                        'success' => true,
                        'message' => "Requisición {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
                        'docEntry' => $docEntry,
                        'docNum' => $docNumFromSL
            ]);
        }

        $decodedRow = json_decode($resp2, true);
        $updatedRow = $decodedRow['value'][0] ?? $decodedRow;

        $datosBitacora["description"] = "Se autorizo la Rquisicion con los siguientes datos" . json_encode([
                    'success' => true,
                    'message' => "Requisición {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
                    'docEntry' => $docEntry,
                    'docNum' => $docNumFromSL,
                    'updatedRow' => $updatedRow
        ]);

        $datosBitacora["user"] = $userName;

        $this->log->save($datosBitacora);

        return $this->response->setJSON([
                    'success' => true,
                    'message' => "Requisición {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
                    'docEntry' => $docEntry,
                    'docNum' => $docNumFromSL,
                    'updatedRow' => $updatedRow
        ]);
    }

    public function showReqItems() {
        $request = service('request');

        // --- input (JSON body o post) ---
        $input = $request->getJSON(true);
        if (empty($input)) {
            $input = $request->getPost();
        }

        $draw = (int) ($input['draw'] ?? 0);
        $start = (int) ($input['start'] ?? 0);
        $length = (int) ($input['length'] ?? 10);
        $searchValue = (string) ($input['search']['value'] ?? ($input['search'] ?? ''));
        $orderColumnIndex = (int) ($input['order'][0]['column'] ?? 1);
        $orderDir = (strtolower($input['order'][0]['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

        $docEntry = isset($input['docEntry']) ? (int) $input['docEntry'] : 0;
        if ($docEntry <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'docEntry requerido'
            ]);
        }

        // Mapeo de columnas de DataTables a campos reales
        $columnsMap = [
            0 => 'ItemCode',
            1 => 'ItemCode',
            2 => 'ItemDescription', // en realidad es Dscription en la tabla
            3 => 'Quantity'
        ];
        $orderField = $columnsMap[$orderColumnIndex] ?? 'ItemCode';
        // Nota: si ordenas por ItemDescription, el campo en la tabla es Dscription,
        // pero como estamos usando alias, podemos usar directamente Dscription en ORDER BY
        if ($orderField === 'ItemDescription') {
            $orderField = 'Dscription';
        }

        try {
            // -----------------------------
            // 1) Conexión ODBC HANA
            // -----------------------------
            $dataConect = $this->serviceLayerModel->first();
            $conn = odbc_connect(
                    $dataConect["nameODBC"],
                    $dataConect["userODBC"],
                    $dataConect["passwordODBC"]
            );
            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // Fijar schema HANA
            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 2) Construir SQL para líneas de SOLICITUDES (PRQ1)
            // -----------------------------
            $sql = "
                SELECT
                    \"DocEntry\",
                    \"LineNum\",
                    \"ItemCode\",
                    \"Dscription\" as \"ItemDescription\",
                    \"Quantity\"
                FROM \"PRQ1\"               -- ✅ Cambio: antes era POR1
                WHERE \"DocEntry\" = {$docEntry}
            ";

            if ($searchValue !== '') {
                $searchEsc = str_replace("'", "''", $searchValue);
                $sql .= " AND (\"ItemCode\" LIKE '%{$searchEsc}%' OR \"Dscription\" LIKE '%{$searchEsc}%')";
            }

            // El ORDER BY debe usar el nombre real del campo (Dscription) no el alias
            $orderFieldReal = ($orderField === 'Dscription') ? 'Dscription' : $orderField;
            $sql .= " ORDER BY \"{$orderFieldReal}\" {$orderDir}";

            if ($length > 0) {
                $sql .= " LIMIT {$length} OFFSET {$start}";
            }

            // -----------------------------
            // 3) Ejecutar consulta
            // -----------------------------
            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 4) Obtener resultados
            // -----------------------------
            $data = [];
            $idx = $start;
            while ($row = odbc_fetch_array($rs)) {
                $idx++;
                $data[] = [
                    'No' => $idx,
                    'Articulo' => $row['ItemCode'] ?? '',
                    'Descripcion' => $row['ItemDescription'] ?? '',
                    'Cantidad' => $row['Quantity'] ?? 0,
                    '_raw' => $row
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            $recordsTotal = count($data);
            $recordsFiltered = $recordsTotal;

            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => $recordsTotal,
                        'recordsFiltered' => $recordsFiltered,
                        'data' => $data
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }

    private function utf8ize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->utf8ize($value);
            }
            return $data;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = $this->utf8ize($value);
            }
            return $data;
        }

        if (is_string($data)) {
            return mb_convert_encoding(
                    $data,
                    'UTF-8',
                    'UTF-8, ISO-8859-1, Windows-1252'
            );
        }

        return $data;
    }

    public function deauthorizeReq() {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error' => 'Petición no permitida.'
            ]);
        }

        $json = $this->request->getJSON(true);
        $docEntry = (int) ($json['docEntry'] ?? 0);
        $docNum = $json['docNum'] ?? '';

        if ($docEntry <= 0) {
            return $this->response->setJSON([
                        'success' => false,
                        'error' => 'El DocEntry no es válido o es requerido.'
            ]);
        }

        try {
            $dataSL = $this->serviceLayerModel->select("*")->first();

            // -------------------------------------------------------------
            // 1) VALIDACIÓN: Verificar si tiene documentos posteriores en HANA
            // -------------------------------------------------------------
            $conn = odbc_connect(
                    $dataSL["nameODBC"],
                    $dataSL["userODBC"],
                    $dataSL["passwordODBC"]
            );

            if (!$conn) {
                throw new \Exception('Error de conexión ODBC: ' . odbc_errormsg());
            }

            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataSL["companyDB"] . '"')) {
                odbc_close($conn);
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // ObjType 1470000113 = Solicitud de Compra (OPRQ)
            // Buscamos si existe en Órdenes de Compra (OPOR) u Ofertas (OPQT) no canceladas
            $sqlCheck = "
            SELECT 
                (SELECT COUNT(DISTINCT OPOR.\"DocNum\") 
                 FROM POR1 
                 INNER JOIN OPOR ON OPOR.\"DocEntry\" = POR1.\"DocEntry\" 
                 WHERE POR1.\"BaseType\" = 1470000113 
                   AND POR1.\"BaseEntry\" = {$docEntry} 
                   AND OPOR.\"CANCELED\" = 'N') AS \"CountPedidos\",

                (SELECT COUNT(DISTINCT OPQT.\"DocNum\") 
                 FROM PQT1 
                 INNER JOIN OPQT ON OPQT.\"DocEntry\" = PQT1.\"DocEntry\" 
                 WHERE PQT1.\"BaseType\" = 1470000113 
                   AND PQT1.\"BaseEntry\" = {$docEntry} 
                   AND OPQT.\"CANCELED\" = 'N') AS \"CountOfertas\"
            FROM DUMMY
        ";

            $rsCheck = odbc_exec($conn, $sqlCheck);
            if (!$rsCheck) {
                odbc_close($conn);
                throw new \Exception('Error al validar documentos posteriores: ' . odbc_errormsg($conn));
            }

            $rowCheck = odbc_fetch_array($rsCheck);
            odbc_free_result($rsCheck);
            odbc_close($conn);

            $countPedidos = (int) ($rowCheck['CountPedidos'] ?? 0);
            $countOfertas = (int) ($rowCheck['CountOfertas'] ?? 0);

            // Si existen documentos posteriores activos, frenamos la operación
            if ($countPedidos > 0 || $countOfertas > 0) {
                $motivo = [];
                if ($countPedidos > 0) {
                    $motivo[] = "{$countPedidos} Orden(es) de Compra / Pedido(s)";
                }
                if ($countOfertas > 0) {
                    $motivo[] = "{$countOfertas} Oferta(s) de Compra";
                }

                return $this->response->setJSON([
                            'success' => false,
                            'error' => 'No se puede desautorizar: el folio ' . $docNum . ' ya tiene documentos posteriores asociados (' . implode(', ', $motivo) . ').'
                ]);
            }

            // -------------------------------------------------------------
            // 2) Conexión a Service Layer para actualizar U_Authorized
            // -------------------------------------------------------------
            $conexionSap = $this->serviceLayerController->login(
                    $dataSL["url"],
                    $dataSL["port"],
                    $dataSL["password"],
                    $dataSL["username"],
                    $dataSL["companyDB"]
            );

            $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";
            $urlSL = rtrim($dataSL["url"], '/') . ':' . $dataSL["port"] . '/b1s/v1/PurchaseRequests(' . $docEntry . ')';

            $payloadUpdate = [
                'U_Authorized' => 'U' // Regresa a estado No Autorizado / Pendiente
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlSL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadUpdate));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Cookie: ' . $cookie
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 204 || $httpCode === 200) {
                return $this->response->setJSON([
                            'success' => true,
                            'message' => 'Requisición desautorizada con éxito.'
                ]);
            } else {
                $resDecoded = json_decode($response, true);
                $errMsg = $resDecoded['error']['message']['value'] ?? $curlError ?? 'Error al actualizar en Service Layer.';

                return $this->response->setJSON([
                            'success' => false,
                            'error' => $errMsg,
                            'httpCode' => $httpCode
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'success' => false,
                        'error' => $e->getMessage()
            ]);
        }
    }
}
