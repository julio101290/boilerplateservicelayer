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

class PurchaseAuthController extends BaseController {

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

        if ($this->request->isAJAX()) {

            $request = service('request');

            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');

            $searchValue = trim($request->getGet('search')['value'] ?? '');

            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir = strtolower($request->getGet('order')[0]['dir'] ?? 'asc');

            $columns = [
                'DocNum',
                'CardName',
                'DocDate'
            ];

            $orderField = $columns[$orderColumnIndex] ?? 'DocEntry';

            //GET SAP CONECTION DATA

            $dataConect = $this->serviceLayerModel->first();

            // Usuario SAP ligado al usuario del sistema
            $userLinkSap = $this->user_sap_link
                    ->select('*')
                    ->where('iduser', $idUser)
                    ->first();

            $autorizador = $userLinkSap['sapuser'] ?? null;

            // 🔥 LLAMADA ODBC + STORED PROCEDURE
            $result = $this->showReqWithOoutAuth(
                    $autorizador,
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

        $titulos["title"] = lang('authrorder.title');
        $titulos["subtitle"] = lang('authrorder.subtitle');

        return view(
                'julio101290\boilerplateservicelayer\Views\purchaseAuth',
                $titulos
        );
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
            $userAuth,
            $search,
            $start = 0,
            $length = 10,
            $orderField = 'DocEntry',
            $orderDir = 'asc',
            $dataConect = ""
    ) {
        try {

            // -----------------------------
            // 1) Normalizar entradas
            // -----------------------------
            $autorizador = (string) $userAuth;
            $search = trim((string) $search);
            $start = (int) $start;
            $length = (int) $length;

            $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

            $allowedOrderFields = ['DocEntry', 'DocNum', 'DocDate', 'CardName'];
            if (!in_array($orderField, $allowedOrderFields, true)) {
                $orderField = 'DocEntry';
            }

            // -----------------------------
            // 2) Conexión ODBC
            // -----------------------------
            $conn = odbc_connect(
                    $dataConect["nameODBC"],
                    $dataConect["userODBC"],
                    $dataConect["passwordODBC"]
            );

            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // 🔥 FIJAR SCHEMA (OBLIGATORIO EN HANA)
            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 3) SQL DIRECTO
            // -----------------------------
            $sql = '
                    SELECT
                        OPOR."DocEntry",
                        OPOR."DocNum",
                        OPOR."DocDate",
                        OPOR."CardCode",
                        OPOR."CardName",



                        MAX(POR1."WhsCode") AS "Almacen",
                        MAX(OWHS."WhsName") AS "NombreAlmacen",
                            OPOR."DocTotal" - OPOR."VatSum" AS "TotalSinImpuestos",

                            OPOR."DocTotal" - OPOR."VatSum" AS "TotalSinImpuestos",
                        OPOR."VatSum" AS "Impuestos",
                        OPOR."DocTotal" AS "TotalConImpuestos",

                        OPOR."UserSign",
                        UC."U_NAME" AS "NombreUsuario",

                        OPOR."U_Autorizador"



                    FROM OPOR
                    INNER JOIN TEST_GUSA3_5.POR1 
                        ON TEST_GUSA3_5.POR1."DocEntry" = OPOR."DocEntry"
                    LEFT JOIN TEST_GUSA3_5.OWHS 
                        ON TEST_GUSA3_5.OWHS."WhsCode" = POR1."WhsCode"
                    LEFT JOIN TEST_GUSA3_5.OUSR UC 
                        ON UC."USERID" = TEST_GUSA3_5.OPOR."UserSign"    


                    WHERE
                        OPOR."CANCELED" = \'N\'
                        AND OPOR."U_Authorized" = \'U\'
                        AND OPOR."U_Autorizador" = \'' . $userAuth . '\'

                    GROUP BY
                        OPOR."DocEntry",
                        OPOR."DocNum",
                        OPOR."DocDate",
                        OPOR."CardCode",
                        OPOR."CardName",
                        OPOR."DocTotal",
                        OPOR."VatSum",
                        OPOR."VatSum",
                        OPOR."UserSign",
                        OPOR."U_Autorizador",
                        UC."U_NAME"

';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            // -----------------------------
            // 4) Fetch resultados
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
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            $records = count($data);

            return [
                'recordsTotal' => $records,
                'recordsFiltered' => $records,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => true,
                'message' => $e->getMessage(),
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

    public function authorizeOrder() {
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
        $urlGet = $slRoot . "/PurchaseOrders({$docEntry})?\$select=U_Authorized,U_Autorizador,DocNum";
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
            return $this->response->setStatusCode($httpGet)->setJSON(['success' => false, 'error' => 'Error al obtener orden de compra', 'body' => $body ?? $respGet]);
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
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'La orden de compra no está en estado pendiente de autorización']);
        }

        // validar autorizador (opcional)
        if ($sapAutorizer !== null) {
            // comparar según como esté guardado en U_Autorizador (UserCode o InternalKey)
            if ((string) $currentAutorizador !== (string) $sapAutorizer) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No estás autorizado para aprobar esta orden de compra']);
            }
        }

        // --- 6) preparar PATCH para autorizar
        $urlPatch = $slRoot . "/PurchaseOrders({$docEntry})";
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
            return $this->response->setStatusCode($httpPatch)->setJSON(['success' => false, 'error' => 'Error al actualizar orden de compra', 'body' => $body ?? $respPatch]);
        }

        // --- 7) GET fila actualizada (para devolver updatedRow)
        $urlGet2 = $slRoot . "/PurchaseOrders({$docEntry})?\$select=DocEntry,DocNum,U_Authorized,U_Autorizador";
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
                        'message' => "Orden de compra {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
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
                    'message' => "Orden de compra {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
                    'docEntry' => $docEntry,
                    'docNum' => $docNumFromSL,
                    'updatedRow' => $updatedRow
        ]);
    }

    public function showPOItems() {
        $request = service('request');

        // --- Input JSON o POST ---
        $input = $request->getJSON(true);
        if (empty($input)) {
            $input = $request->getPost();
        }

        $draw = (int) ($input['draw'] ?? 0);
        $start = (int) ($input['start'] ?? 0);
        $length = (int) ($input['length'] ?? 10);
        $searchValue = (string) ($input['search']['value'] ?? ($input['search'] ?? ''));
        $orderColumnIndex = (int) (($input['order'][0]['column'] ?? 1));
        $orderDir = (strtolower($input['order'][0]['dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

        $docEntry = isset($input['docEntry']) ? (int) $input['docEntry'] : 0;
        if ($docEntry <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'docEntry requerido'
            ]);
        }

        // --- Columnas para DataTables ---
        $columnsMap = [
            0 => 'LineNum',
            1 => 'ItemCode',
            2 => 'ItemDescription',
            3 => 'Quantity',
            4 => 'Price',
            5 => 'Total'
        ];
        $orderField = $columnsMap[$orderColumnIndex] ?? 'LineNum';

        $dataConect = $this->serviceLayerModel->first();
        // --- Conexión ODBC ---

        $conn = odbc_connect($dataConect["nameODBC"], $dataConect["userODBC"], $dataConect["passwordODBC"]);

        if (!$conn) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'No se pudo conectar a HANA vía ODBC'
            ]);
        }

        // 🔥 FIJAR SCHEMA (OBLIGATORIO EN HANA)
        if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
            throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
        }

        // --- Query líneas de orden ---
        $sql = 'SELECT
                "LineNum",
                "ItemCode",
                "Dscription" AS "ItemDescription",
                "Quantity",
                "Price",
                ("Quantity" * "Price") AS "Total"
            FROM "TEST_GUSA3_5"."POR1"
            WHERE "DocEntry" = ? 
            ORDER BY "LineNum" ASC';

        $stmt = odbc_prepare($conn, $sql);
        if (!$stmt) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'Error preparando query ODBC'
            ]);
        }

        $res = odbc_execute($stmt, [$docEntry]);
        if (!$res) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                        'error' => 'Error ejecutando query ODBC'
            ]);
        }

        // --- Traer todas las filas ---
        $lines = [];
        while ($row = odbc_fetch_array($stmt)) {
            $lines[] = $row;
        }

        odbc_close($conn);

        if (empty($lines)) {
            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
            ]);
        }

        // --- Filtrado por búsqueda ---
        $filtered = $lines;
        if ($searchValue !== '') {
            $sEsc = mb_strtolower($searchValue);
            $filtered = array_filter($lines, function ($ln) use ($sEsc) {
                $code = mb_strtolower((string) ($ln['ItemCode'] ?? ''));
                $desc = mb_strtolower((string) ($ln['ItemDescription'] ?? ''));
                return (strpos($code, $sEsc) !== false) || (strpos($desc, $sEsc) !== false);
            });
            $filtered = array_values($filtered);
        }

        $recordsTotal = count($lines);
        $recordsFiltered = count($filtered);

        // --- Ordenamiento ---
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

        // --- Paginación ---
        $paged = ($length > 0) ? array_slice($filtered, $start, $length) : $filtered;

        // --- Formatear salida DataTables ---
        $out = [];
        foreach ($paged as $idx => $line) {
            $cantidad = isset($line['Quantity']) ? (float) $line['Quantity'] : 0;
            $precio = isset($line['Price']) ? (float) $line['Price'] : null;
            $total = isset($line['Total']) ? (float) $line['Total'] : ($precio !== null ? $precio * $cantidad : null);

            $out[] = [
                'No' => $start + $idx + 1,
                'Articulo' => $line['ItemCode'] ?? '',
                'Descripcion' => $line['ItemDescription'] ?? '',
                'Cantidad' => $cantidad,
                'Precio' => $precio !== null ? round($precio, 2) : null,
                'Total' => $total !== null ? round($total, 2) : null,
                '_raw' => $line
            ];
        }

        return $this->response->setJSON([
                    'draw' => $draw,
                    'recordsTotal' => $recordsTotal,
                    'recordsFiltered' => $recordsFiltered,
                    'data' => $out
        ]);
    }
}
