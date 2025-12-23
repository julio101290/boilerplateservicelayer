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
            // --- Controlador (parte que llama al Service Layer) ---
            $request = service('request');

            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');
            $searchValue = $request->getGet('search')['value'] ?? '';
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir = $request->getGet('order')[0]['dir'] ?? 'asc';

            // $fields debe venir definido en tu controlador (array con nombres de campos en SAP Service Layer)
            $orderField = $fields[$orderColumnIndex] ?? 'DocEntry';

            $dataSL = $this->serviceLayerModel->select("*")->first();

            $conexionSap = $this->serviceLayerController->login(
                    $dataSL["url"],
                    $dataSL["port"],
                    $dataSL["password"],
                    $dataSL["username"],
                    $dataSL["companyDB"]
            );

            $cookie = "B1SESSION=" . $conexionSap->SessionId . ";  ROUTEID=.node1";

            $userLinkSap = $this->user_sap_link->select("*")->where("iduser", $idUser)->first();

            $fields = [
                'DocNum',
                'CardName',
                'DocDate',
                'DocStatus'
            ];

            // Llamada que soporta pagination, orden y búsqueda
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
                    $fields // pasamos los campos para búsqueda/contains
            );

            // Manejo de errores sencillos si $result no es el esperado
            if (isset($result['error'])) {
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
            $orderField = 'DocEntry',
            $orderDir = 'asc',
            array $fields = []
    ) {
        try {
            // -----------------------------
            // 1) Normalizar entradas
            // -----------------------------
            $autorizador = trim((string) $userAuth);
            $search = trim((string) $search);
            $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';
            $orderField = $orderField ?: 'DocEntry';

            $dataConect = $this->serviceLayerModel->first();
            // -----------------------------
            // 2) Conexión ODBC a HANA
            // -----------------------------
            $conn = odbc_connect(
                    $dataConect["nameODBC"],
                    $dataConect["userODBC"],
                    $dataConect["passwordODBC"]
            );
            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // 🔥 Fijar schema
            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 3) Construir SQL directo
            // -----------------------------
            $sql = "
            SELECT
                OPOR.\"DocEntry\",
                OPOR.\"DocNum\",
                OPOR.\"DocDate\",
                OPOR.\"CardCode\",
                OPOR.\"CardName\",
                MAX(POR1.\"WhsCode\") AS \"Almacen\",
                MAX(OWHS.\"WhsName\") AS \"NombreAlmacen\",
                OPOR.\"DocTotal\" - OPOR.\"VatSum\" AS \"TotalSinImpuestos\",
                OPOR.\"VatSum\" AS \"Impuestos\",
                OPOR.\"DocTotal\" AS \"TotalConImpuestos\",
                OPOR.\"U_Autorizador\",
                OPOR.\"UserSign\",
                UC.\"U_NAME\" AS \"NombreUsuario\"
            FROM OPOR
            INNER JOIN POR1 ON POR1.\"DocEntry\" = OPOR.\"DocEntry\"
            LEFT JOIN OWHS ON OWHS.\"WhsCode\" = POR1.\"WhsCode\"
            LEFT JOIN OUSR UC ON UC.\"USERID\" = OPOR.\"UserSign\"
            WHERE
                OPOR.\"CANCELED\" = 'N'
                AND OPOR.\"U_Authorized\" LIKE 'U%'
                AND OPOR.\"U_Autorizador\" = '{$autorizador}'
        ";

            if ($search !== '') {
                $sql .= " AND (OPOR.\"DocNum\" LIKE '%{$search}%' OR OPOR.\"CardName\" LIKE '%{$search}%')";
            }

            $sql .= "
            GROUP BY
                OPOR.\"DocEntry\",
                OPOR.\"DocNum\",
                OPOR.\"DocDate\",
                OPOR.\"CardCode\",
                OPOR.\"CardName\",
                OPOR.\"DocTotal\",
                OPOR.\"VatSum\",
                OPOR.\"U_Autorizador\",
                OPOR.\"UserSign\",
                UC.\"U_NAME\"
            ORDER BY OPOR.\"{$orderField}\" {$orderDir}
            LIMIT {$length} OFFSET {$start}
        ";

            // -----------------------------
            // 4) Ejecutar consulta
            // -----------------------------
            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 5) Obtener resultados
            // -----------------------------
            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $data[] = [
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
                ];
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

        // mapear columna de DataTables a campo real
        $columnsMap = [
            0 => 'ItemCode',
            1 => 'ItemCode',
            2 => 'ItemDescription',
            3 => 'Quantity'
        ];
        $orderField = $columnsMap[$orderColumnIndex] ?? 'ItemCode';

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
            

            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // 🔥 Fijar schema HANA
            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 2) Construir SQL para líneas
            // -----------------------------
            $sql = "
            SELECT
                \"DocEntry\",
                \"LineNum\",
                \"ItemCode\",
                \"Dscription\" as \"ItemDescription\",
                \"Quantity\"
            FROM \"POR1\"
            WHERE \"DocEntry\" = {$docEntry}
        ";

            if ($searchValue !== '') {
                $searchEsc = str_replace("'", "''", $searchValue);
                $sql .= " AND (\"ItemCode\" LIKE '%{$searchEsc}%' OR \"ItemDescription\" LIKE '%{$searchEsc}%')";
            }

            $sql .= " ORDER BY \"{$orderField}\" {$orderDir}";

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
    
}
