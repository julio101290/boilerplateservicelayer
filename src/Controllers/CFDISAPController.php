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
use PhpOffice\PhpSpreadsheet\IOFactory;

class CFDISAPController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $user_sap_link;
    protected $empresa;
    protected $serviceLayerController;
    protected $serviceLayerModel;
    protected $users;
    protected $usersPerCompanie;

    public function __construct()
    {
        $this->user_sap_link = new User_sap_linkModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->users = new UserModel();
        $this->usersPerCompanie = new UsuariosempresaModel();

        helper(['menu', 'utilerias']);
    }

    // ==================== MÉTODOS EXISTENTES ====================

    public function index()
    {
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

            $columns = ['DocNum', 'CardName', 'DocDate'];
            $orderField = $columns[$orderColumnIndex] ?? 'DocEntry';

            $dataConect = $this->serviceLayerModel->first();
            $userLinkSap = $this->user_sap_link->select('*')->where('iduser', $idUser)->first();
            $autorizador = $userLinkSap['sapuser'] ?? null;

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
        return view('julio101290\boilerplateservicelayer\Views\purchaseAuth', $titulos);
    }

    public function getUser_sap_link()
    {
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

    public function save()
    {
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

    public function delete($id)
    {
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

    public function showReqWithOoutAuth($userAuth, $search, $start = 0, $length = 10, $orderField = 'DocEntry', $orderDir = 'asc', $dataConect = "")
    {
        try {
            $autorizador = (string) $userAuth;
            $search = trim((string) $search);
            $start = (int) $start;
            $length = (int) $length;
            $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

            $allowedOrderFields = ['DocEntry', 'DocNum', 'DocDate', 'CardName'];
            if (!in_array($orderField, $allowedOrderFields, true)) {
                $orderField = 'DocEntry';
            }

            $conn = odbc_connect($dataConect["nameODBC"], $dataConect["userODBC"], $dataConect["passwordODBC"]);
            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect["companyDB"] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

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
                OPOR."VatSum" AS "Impuestos",
                OPOR."DocTotal" AS "TotalConImpuestos",
                MAX(OPOR."DiscSum") AS "Descuento",
                OPOR."UserSign",
                UC."U_NAME" AS "NombreUsuario",
                OPOR."U_Autorizador",
                MAX(OHEM."firstName" || \' \' || OHEM."lastName") AS "NombreOwner"
            FROM OPOR
            INNER JOIN ' . $dataConect["companyDB"] . '.POR1 ON ' . $dataConect["companyDB"] . '.POR1."DocEntry" = OPOR."DocEntry"
            LEFT JOIN ' . $dataConect["companyDB"] . '.OWHS ON ' . $dataConect["companyDB"] . '.OWHS."WhsCode" = POR1."WhsCode"
            LEFT JOIN ' . $dataConect["companyDB"] . '.OUSR UC ON UC."USERID" = ' . $dataConect["companyDB"] . '.OPOR."UserSign"
            LEFT JOIN ' . $dataConect["companyDB"] . '.OHEM ON OHEM."empID" = OPOR."OwnerCode"
            WHERE
                OPOR."CANCELED" = \'N\'
                AND OPOR."U_Authorized" = \'U\'
                AND OPOR."U_Autorizador" = \'' . $userAuth . '\'
            GROUP BY
                OPOR."DocEntry", OPOR."DocNum", OPOR."DocDate", OPOR."CardCode", OPOR."CardName",
                OPOR."DocTotal", OPOR."VatSum", OPOR."UserSign", OPOR."U_Autorizador", UC."U_NAME"
            ';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $row = $this->utf8ize($row);
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
                    'Descuento' => round((float) $row['Descuento'], 2),
                    'NombreOwner' => $row['NombreOwner'],
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

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        }
        return $mixed;
    }

    public function getUsersAjaxSelect2()
    {
        $request = service('request');
        $postData = $request->getPost();
        $response['token'] = csrf_hash();
        $idEmpresa = $postData['idEmpresa'];

        $listUsers = $this->user_sap_link->mdlGetUsers($postData['searchTerm'], $idEmpresa)->getResultArray();

        $jsonVariable = ' { "results": [';
        foreach ($listUsers as $user) {
            $jsonVariable .= ' {
                "id": "' . $user["id"] . '",
                "text": "' . utf8_encode($user["id"] . " - " . $user["username"] . " " . $user["firstname"] . " " . $user["lastname"]) . '"
            },';
        }
        $jsonVariable = substr($jsonVariable, 0, -1);
        $jsonVariable .= ' ] }';
        echo $jsonVariable;
    }

    public function authorizeOrder()
    {
        helper('auth');
        $request = service('request');
        $idUser = user() ? user()->id : null;
        $userName = user()->username;

        $inputJson = $request->getJSON(true);
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
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        $sapAutorizer = null;
        if ($idUser !== null) {
            $userLinkSap = $this->user_sap_link->select('*')->where('iduser', $idUser)->first();
            $sapAutorizer = $userLinkSap['sapuser'] ?? null;
        }

        $urlGet = $slRoot . "/PurchaseOrders({$docEntry})?\$select=U_Authorized,U_Autorizador,DocNum";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlGet,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ["Accept: application/json", "User-Agent: PHP", "B1S-CaseInsensitive: true"],
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
        $sample = (isset($row['value']) && is_array($row['value'])) ? ($row['value'][0] ?? []) : (is_array($row) ? $row : []);
        $currentUA = $sample['U_Authorized'] ?? '';
        $currentAutorizador = $sample['U_Autorizador'] ?? null;
        $docNumFromSL = $sample['DocNum'] ?? $docNum;

        if (!str_starts_with((string) $currentUA, 'U')) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'La orden de compra no está en estado pendiente de autorización']);
        }
        if ($sapAutorizer !== null && (string) $currentAutorizador !== (string) $sapAutorizer) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'No estás autorizado para aprobar esta orden de compra']);
        }

        $urlPatch = $slRoot . "/PurchaseOrders({$docEntry})";
        $payload = ['U_Authorized' => 'Y'];
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
            CURLOPT_HTTPHEADER => ["Accept: application/json", "Content-Type: application/json", "User-Agent: PHP", "B1S-CaseInsensitive: true"],
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

        $urlGet2 = $slRoot . "/PurchaseOrders({$docEntry})?\$select=DocEntry,DocNum,U_Authorized,U_Autorizador";
        $ch3 = curl_init();
        curl_setopt_array($ch3, [
            CURLOPT_URL => $urlGet2,
            CURLOPT_PORT => $dataSL['port'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ["Accept: application/json", "User-Agent: PHP", "B1S-CaseInsensitive: true"],
            CURLOPT_TIMEOUT => 30
        ]);
        $resp2 = curl_exec($ch3);
        $err2 = curl_error($ch3);
        $http2 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
        curl_close($ch3);

        if ($err2 || $http2 < 200 || $http2 >= 300) {
            return $this->response->setJSON([
                'success' => true,
                'message' => "Orden de compra {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
                'docEntry' => $docEntry,
                'docNum' => $docNumFromSL
            ]);
        }

        $decodedRow = json_decode($resp2, true);
        $updatedRow = $decodedRow['value'][0] ?? $decodedRow;
        $this->log->save([
            "description" => "Se autorizó la requisición con los siguientes datos" . json_encode([
                'success' => true,
                'message' => "Requisición {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
                'docEntry' => $docEntry,
                'docNum' => $docNumFromSL,
                'updatedRow' => $updatedRow
            ]),
            "user" => $userName
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => "Orden de compra {$docNumFromSL} (DocEntry {$docEntry}) autorizada",
            'docEntry' => $docEntry,
            'docNum' => $docNumFromSL,
            'updatedRow' => $updatedRow
        ]);
    }

    public function showPOItems()
    {
        $request = service('request');
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

        $columnsMap = [0 => 'LineNum', 1 => 'ItemCode', 2 => 'ItemDescription', 3 => 'U_Etapa', 4 => 'Quantity', 5 => 'Price', 6 => 'Total'];
        $orderField = $columnsMap[$orderColumnIndex] ?? 'LineNum';

        $dataConect = $this->serviceLayerModel->first();
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

        $sql = 'SELECT "LineNum", "ItemCode", "Dscription" AS "ItemDescription", "U_Etapa", "Quantity", "Price", ("Quantity" * "Price") AS "Total"
                FROM ' . $dataConect["companyDB"] . '."POR1"
                WHERE "DocEntry" = ' . $docEntry . '
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

        $lines = [];
        while ($row = odbc_fetch_array($stmt)) {
            $lines[] = $this->utf8ize($row);
        }
        odbc_close($conn);

        if (empty($lines)) {
            return $this->response->setJSON(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }

        $filtered = $lines;
        if ($searchValue !== '') {
            $sEsc = mb_strtolower($searchValue);
            $filtered = array_values(array_filter($lines, function ($ln) use ($sEsc) {
                $code = mb_strtolower((string) ($ln['ItemCode'] ?? ''));
                $desc = mb_strtolower((string) ($ln['ItemDescription'] ?? ''));
                return (strpos($code, $sEsc) !== false) || (strpos($desc, $sEsc) !== false);
            }));
        }

        $recordsTotal = count($lines);
        $recordsFiltered = count($filtered);

        usort($filtered, function ($a, $b) use ($orderField, $orderDir) {
            $va = $a[$orderField] ?? '';
            $vb = $b[$orderField] ?? '';
            $cmp = (is_numeric($va) && is_numeric($vb)) ? ($va <=> $vb) : strcasecmp((string) $va, (string) $vb);
            return ($orderDir === 'desc') ? -$cmp : $cmp;
        });

        $paged = ($length > 0) ? array_slice($filtered, $start, $length) : $filtered;
        $out = [];
        foreach ($paged as $idx => $line) {
            $cantidad = isset($line['Quantity']) ? (float) $line['Quantity'] : 0;
            $precio = isset($line['Price']) ? (float) $line['Price'] : null;
            $total = isset($line['Total']) ? (float) $line['Total'] : ($precio !== null ? $precio * $cantidad : null);
            $out[] = [
                'No' => $start + $idx + 1,
                'Articulo' => $line['ItemCode'] ?? '',
                'Descripcion' => $line['ItemDescription'] ?? '',
                'U_Etapa' => $line['U_Etapa'] ?? '',
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

    // ==================== NUEVOS MÉTODOS PARA ANÁLISIS CFDI (vía ODBC) ====================

    /**
     * Muestra el formulario para subir el Excel descargado del SAT
     */
    public function analizadorCFDI()
    {
        helper('auth');
        $conexiones = $this->serviceLayerModel->findAll();
        $data = [
            'conexiones' => $conexiones,
            'titulo' => 'Analizador CFDI vs SAP'
        ];
        return view('julio101290\boilerplateservicelayer\Views\CFDISAP', $data);
    }

    /**
     * Procesa el archivo Excel, consulta SAP vía ODBC y devuelve JSON
     */
    public function procesarAnalisisCFDI()
    {
        $file = $this->request->getFile('excelFile');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['error' => 'No se ha subido un archivo válido.']);
        }

        $sapConnectionId = $this->request->getPost('sapConnection');
        if (!$sapConnectionId) {
            return $this->response->setJSON(['error' => 'Debe seleccionar una conexión SAP.']);
        }

        $sapConfig = $this->serviceLayerModel->find($sapConnectionId);
        if (!$sapConfig) {
            return $this->response->setJSON(['error' => 'Conexión SAP no encontrada.']);
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => 'No se pudo leer el archivo Excel: ' . $e->getMessage()]);
        }
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (count($rows) < 4) {
            return $this->response->setJSON(['error' => 'El archivo Excel no contiene suficientes filas (mínimo 4).']);
        }

        $headers = array_map('trim', $rows[2]);
        $uuidColIndex = array_search('UUID', $headers);
        if ($uuidColIndex === false) {
            return $this->response->setJSON(['error' => 'No se encontró la columna "UUID" en el archivo.']);
        }

        $rfcCol = array_search('Rfc Emisor', $headers);
        $nombreCol = array_search('Nombre Emisor', $headers);
        $folioCol = array_search('Folio', $headers);
        $fechaCol = array_search('Fecha', $headers);
        $subtotalCol = array_search('Sub Total', $headers);
        $totalCol = array_search('Total', $headers);
        $impuestoCol = array_search('Total impuesto Trasladado', $headers);
        $impuestoRetCol = array_search('Total impuesto Retenido', $headers);
        $metodoPagoCol = array_search('Método de Pago', $headers);
        $formaPagoCol = array_search('Forma de Pago', $headers);
        $monedaCol = array_search('Moneda', $headers);

        // Conexión ODBC
        $conn = odbc_connect($sapConfig['nameODBC'], $sapConfig['userODBC'], $sapConfig['passwordODBC']);
        if (!$conn) {
            return $this->response->setJSON(['error' => 'Error de conexión ODBC: ' . odbc_errormsg()]);
        }
        if (!odbc_exec($conn, 'SET SCHEMA "' . $sapConfig['companyDB'] . '"')) {
            return $this->response->setJSON(['error' => 'Error SET SCHEMA: ' . odbc_errormsg($conn)]);
        }

        $resultados = [];
        for ($i = 3; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) continue;

            $uuid = trim($row[$uuidColIndex] ?? '');
            if (empty($uuid)) {
                $xmlFile = trim($row[0] ?? '');
                if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $xmlFile, $matches)) {
                    $uuid = $matches[0];
                } else {
                    $resultados[] = $this->crearResultadoVacio($row, $rfcCol, $nombreCol, $folioCol, $fechaCol, $subtotalCol, $totalCol, $impuestoCol, $impuestoRetCol, $metodoPagoCol, $formaPagoCol, $monedaCol, 'UUID vacío');
                    continue;
                }
            }

            $infoSAP = $this->buscarUUIDenSAP_ODBC($uuid, $conn, $sapConfig['companyDB']);
            $resultado = $this->crearResultadoCompleto($row, $rfcCol, $nombreCol, $folioCol, $fechaCol, $subtotalCol, $totalCol, $impuestoCol, $impuestoRetCol, $metodoPagoCol, $formaPagoCol, $monedaCol, $uuid, $infoSAP);
            $resultados[] = $resultado;
        }

        odbc_close($conn);
        return $this->response->setJSON(['success' => true, 'data' => $resultados, 'total' => count($resultados)]);
    }

    /**
     * Busca un UUID en las tablas de SAP usando ODBC
     * IMPORTANTE: Usa el campo U_FolioFiscal (según tu personalización)
     */
    private function buscarUUIDenSAP_ODBC($uuid, $conn, $companyDB)
    {
        // Campo personalizado donde se guarda el UUID/folio fiscal
        $uuidField = "U_FolioFiscal";

        $tablas = [
            'OPCH' => 'Factura Proveedor',
            'ORPC' => 'Nota Crédito Proveedor',
            'ODPI' => 'Factura Anticipo'
        ];

        $info = ['encontrado' => 'NO', 'registro' => '', 'tipo' => '', 'importe' => ''];
        foreach ($tablas as $tabla => $tipoDesc) {
            $sql = "SELECT \"DocEntry\", \"DocNum\", \"DocTotal\" 
                    FROM \"{$companyDB}\".\"{$tabla}\" 
                    WHERE \"{$uuidField}\" = ?";
            $stmt = odbc_prepare($conn, $sql);
            if (!$stmt) continue;
            if (!odbc_execute($stmt, [$uuid])) continue;
            $row = odbc_fetch_array($stmt);
            if ($row) {
                $info['encontrado'] = 'SI';
                $info['registro'] = $row['DocNum'] ?? $row['DocEntry'];
                $info['tipo'] = $tipoDesc;
                $info['importe'] = number_format((float) $row['DocTotal'], 2);
                break;
            }
            odbc_free_result($stmt);
        }
        return $info;
    }

    private function crearResultadoCompleto($row, $rfcCol, $nombreCol, $folioCol, $fechaCol, $subtotalCol, $totalCol, $impuestoCol, $impuestoRetCol, $metodoPagoCol, $formaPagoCol, $monedaCol, $uuid, $infoSAP)
    {
        return [
            'rfc'               => ($rfcCol !== false) ? $row[$rfcCol] : '',
            'nombre_emisor'     => ($nombreCol !== false) ? $row[$nombreCol] : '',
            'folio'             => ($folioCol !== false) ? $row[$folioCol] : '',
            'fecha'             => ($fechaCol !== false) ? $row[$fechaCol] : '',
            'subtotal'          => ($subtotalCol !== false) ? $row[$subtotalCol] : '',
            'impuesto'          => ($impuestoCol !== false) ? $row[$impuestoCol] : '',
            'impuesto_retenido' => ($impuestoRetCol !== false) ? $row[$impuestoRetCol] : '',
            'total'             => ($totalCol !== false) ? $row[$totalCol] : '',
            'uuid'              => $uuid,
            'metodo_pago'       => ($metodoPagoCol !== false) ? $row[$metodoPagoCol] : '',
            'forma_pago'        => ($formaPagoCol !== false) ? $row[$formaPagoCol] : '',
            'moneda'            => ($monedaCol !== false) ? $row[$monedaCol] : '',
            'encontrado_sap'    => $infoSAP['encontrado'],
            'registro_sap'      => $infoSAP['registro'],
            'tipo_movimiento'   => $infoSAP['tipo'],
            'importe_sap'       => $infoSAP['importe']
        ];
    }

    private function crearResultadoVacio($row, $rfcCol, $nombreCol, $folioCol, $fechaCol, $subtotalCol, $totalCol, $impuestoCol, $impuestoRetCol, $metodoPagoCol, $formaPagoCol, $monedaCol, $motivo)
    {
        return [
            'rfc'               => ($rfcCol !== false) ? $row[$rfcCol] : '',
            'nombre_emisor'     => ($nombreCol !== false) ? $row[$nombreCol] : '',
            'folio'             => ($folioCol !== false) ? $row[$folioCol] : '',
            'fecha'             => ($fechaCol !== false) ? $row[$fechaCol] : '',
            'subtotal'          => ($subtotalCol !== false) ? $row[$subtotalCol] : '',
            'impuesto'          => ($impuestoCol !== false) ? $row[$impuestoCol] : '',
            'impuesto_retenido' => ($impuestoRetCol !== false) ? $row[$impuestoRetCol] : '',
            'total'             => ($totalCol !== false) ? $row[$totalCol] : '',
            'uuid'              => '',
            'metodo_pago'       => ($metodoPagoCol !== false) ? $row[$metodoPagoCol] : '',
            'forma_pago'        => ($formaPagoCol !== false) ? $row[$formaPagoCol] : '',
            'moneda'            => ($monedaCol !== false) ? $row[$monedaCol] : '',
            'encontrado_sap'    => 'ERROR',
            'registro_sap'      => '',
            'tipo_movimiento'   => $motivo,
            'importe_sap'       => ''
        ];
    }
}