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
        // normalizar entradas
        $autorizador = trim((string) $userAuth);
        $search = trim((string) $search);
        $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';
        $orderField = $orderField ?: 'DocEntry';

        // helper curl (usa $port y $cookie del scope)
        $doCurl = function ($url) use ($port, $cookie) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_PORT => $port,
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_COOKIE => $cookie,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "User-Agent: PHP cURL",
                    "B1S-CaseInsensitive: true"
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return ['err' => $err, 'httpCode' => $httpCode, 'body' => $response];
        };

        // helper curl con timeout mayor (para consultas potencialmente más largas)
        $doCurlTimeout = function ($url) use ($port, $cookie) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_PORT => $port,
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_COOKIE => $cookie,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 60, // 60s
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "User-Agent: PHP cURL",
                    "B1S-CaseInsensitive: true"
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return ['err' => $err, 'httpCode' => $httpCode, 'body' => $response];
        };

        // 1) filtro base (autorizador + startswith)
        $filterParts = ["startswith(U_Authorized,'U')"];
        if ($autorizador !== '') {
            $escaped = str_replace("'", "''", $autorizador);
            $filterParts[] = ctype_digit($escaped) ? "U_Autorizador eq {$escaped}" : "U_Autorizador eq '{$escaped}'";
        }
        $baseFilter = implode(' and ', $filterParts);

        // 2) búsqueda global (si aplica)
        $fullFilter = $baseFilter;
        if ($search !== '') {
            $searchEsc = str_replace("'", "''", $search);
            $searchParts = [];
            $candidateFields = empty($fields) ? ['DocNum', 'DocEntry', 'U_WhsCode', 'U_Almacen', 'U_Autorizador', 'UserSign', 'U_Creator'] : $fields;
            foreach ($candidateFields as $f)
                $searchParts[] = "contains({$f},'{$searchEsc}')";
            $searchFilter = '(' . implode(' or ', $searchParts) . ')';
            $fullFilter = $baseFilter !== '' ? ($baseFilter . ' and ' . $searchFilter) : $searchFilter;
        }

        // 3) recordsTotal y recordsFiltered (usando $count)
        $recordsTotal = 0;
        $recordsFiltered = 0;
        $countTotalUrl = rtrim($baseUrlRoot, '/') . "/PurchaseRequests/\$count";
        if ($baseFilter !== '')
            $countTotalUrl .= "?%24filter=" . rawurlencode($baseFilter);
        $resCountTotal = $doCurl($countTotalUrl);
        if ($resCountTotal['err'])
            return ['error' => "cURL Error #: " . $resCountTotal['err']];
        if ($resCountTotal['httpCode'] < 200 || $resCountTotal['httpCode'] >= 300) {
            return ['error' => 'Service Layer HTTP error (count total)', 'httpCode' => $resCountTotal['httpCode'], 'body' => $resCountTotal['body']];
        }
        $recordsTotal = (int) trim($resCountTotal['body']);

        if ($fullFilter !== '') {
            $countFilteredUrl = rtrim($baseUrlRoot, '/') . "/PurchaseRequests/\$count?%24filter=" . rawurlencode($fullFilter);
            $resCountFiltered = $doCurl($countFilteredUrl);
            if ($resCountFiltered['err'])
                return ['error' => "cURL Error #: " . $resCountFiltered['err']];
            if ($resCountFiltered['httpCode'] < 200 || $resCountFiltered['httpCode'] >= 300) {
                return ['error' => 'Service Layer HTTP error (count filtered)', 'httpCode' => $resCountFiltered['httpCode'], 'body' => $resCountFiltered['body']];
            }
            $recordsFiltered = (int) trim($resCountFiltered['body']);
        } else {
            $recordsFiltered = $recordsTotal;
        }

        // 4) Probe para detectar campos válidos
        $probeUrl = rtrim($baseUrlRoot, '/') . "/PurchaseRequests?\$top=1";
        if ($fullFilter !== '')
            $probeUrl .= '&%24filter=' . rawurlencode($fullFilter);
        $resProbe = $doCurl($probeUrl);
        $availableKeys = [];
        if (!$resProbe['err'] && $resProbe['httpCode'] >= 200 && $resProbe['httpCode'] < 300) {
            $decProbe = json_decode($resProbe['body'], true);
            $sample = $decProbe['value'][0] ?? null;
            if (is_array($sample))
                $availableKeys = array_keys($sample);
        } else {
            // fallback a campos seguros si probe falla o no hay filas
            $availableKeys = ['DocEntry', 'DocNum', 'DocDate', 'UserSign', 'U_Autorizador', 'U_WhsCode', 'U_Almacen', 'U_Creator'];
        }

        // 5) detectar campos disponibles para whs/creator/auth
        $possibleWhsFields = ['U_WhsCode', 'U_Almacen', 'U_Whs', 'WhsCode'];
        $possibleCreatorFields = ['U_Creator', 'UserSign', 'Creator', 'UserCode'];
        $possibleAuthFields = ['U_Autorizador', 'Autorizador', 'U_Authorizer'];

        $whsField = null;
        foreach ($possibleWhsFields as $f)
            if (in_array($f, $availableKeys)) {
                $whsField = $f;
                break;
            }

        $creatorField = null;
        foreach ($possibleCreatorFields as $f)
            if (in_array($f, $availableKeys)) {
                $creatorField = $f;
                break;
            }

        $authField = null;
        foreach ($possibleAuthFields as $f)
            if (in_array($f, $availableKeys)) {
                $authField = $f;
                break;
            }

        // 6) construir select con campos válidos
        $safeBase = ['DocEntry', 'DocNum', 'DocDate'];
        $selectFields = [];
        foreach ($safeBase as $s)
            if (in_array($s, $availableKeys))
                $selectFields[] = $s;
        if ($whsField !== null)
            $selectFields[] = $whsField;
        if ($creatorField !== null)
            $selectFields[] = $creatorField;
        if ($authField !== null)
            $selectFields[] = $authField;
        if (empty($selectFields))
            $selectFields = $safeBase;

        $selectParam = '%24select=' . rawurlencode(implode(',', $selectFields));
        $orderbyParam = '%24orderby=' . rawurlencode($orderField . ' ' . $orderDir);
        $queryParts = [$selectParam, $orderbyParam];
        if ($length > 0) {
            $queryParts[] = '%24skip=' . (int) $start;
            $queryParts[] = '%24top=' . (int) $length;
        }
        if ($fullFilter !== '')
            $queryParts[] = '%24filter=' . rawurlencode($fullFilter);

        $urlData = rtrim($baseUrlRoot, '/') . '/PurchaseRequests?' . implode('&', $queryParts);
        $resData = $doCurl($urlData);
        if ($resData['err'])
            return ['error' => "cURL Error #: " . $resData['err']];
        if ($resData['httpCode'] < 200 || $resData['httpCode'] >= 300) {
            return ['error' => 'Service Layer HTTP error (data fetch)', 'httpCode' => $resData['httpCode'], 'body' => $resData['body']];
        }
        $decData = json_decode($resData['body'], true);
        $rows = $decData['value'] ?? [];

        // --------- 7) Traer Users solo para las claves presentes en $rows (filtrado) ----------
        $usersMap = [];
        $userKeys = [];
        if (!empty($rows)) {
            foreach ($rows as $r) {
                if (!empty($creatorField) && !empty($r[$creatorField]))
                    $userKeys[(string) $r[$creatorField]] = true;
                if (!empty($authField) && !empty($r[$authField]))
                    $userKeys[(string) $r[$authField]] = true;
            }
        }

        if (!empty($userKeys)) {
            $filters = [];
            foreach (array_keys($userKeys) as $k) {
                $k = (string) $k;
                if (ctype_digit($k)) {
                    $filters[] = "InternalKey eq {$k}";
                    $filters[] = "UserID eq {$k}";
                    $filters[] = "USERID eq {$k}";
                } else {
                    $escaped = str_replace("'", "''", $k);
                    $filters[] = "UserCode eq '{$escaped}'";
                }
            }
            $filters = array_unique($filters);
            $filterUsers = implode(' or ', array_slice($filters, 0, 200)); // limit conditions

            $urlUsers = rtrim($baseUrlRoot, '/') . "/Users?\$select=UserCode,Name&\$filter=" . rawurlencode($filterUsers);

            $resUsers = $doCurlTimeout($urlUsers);

            if ($resUsers['err'] || $resUsers['httpCode'] < 200 || $resUsers['httpCode'] >= 300) {
                // fallback probe ligero
                $probeUsers = $doCurlTimeout(rtrim($baseUrlRoot, '/') . "/Users?\$top=1");
                if (!$probeUsers['err'] && $probeUsers['httpCode'] >= 200 && $probeUsers['httpCode'] < 300) {
                    $decU = json_decode($probeUsers['body'], true);
                    foreach ($decU['value'] ?? [] as $u) {
                        if (isset($u['UserCode']))
                            $usersMap[(string) $u['UserCode']] = $u['Name'] ?? $u['UserCode'];
                        if (isset($u['InternalKey']))
                            $usersMap[(string) $u['InternalKey']] = $u['Name'] ?? ($u['UserCode'] ?? '');
                        if (isset($u['UserID']))
                            $usersMap[(string) $u['UserID']] = $u['Name'] ?? ($u['UserCode'] ?? '');
                    }
                } else {
                    // no fue posible obtener users, seguimos con usersMap vacío
                    $usersMap = [];
                }
            } else {
                $decU = json_decode($resUsers['body'], true);
                foreach ($decU['value'] ?? [] as $u) {
                    if (isset($u['UserCode']))
                        $usersMap[(string) $u['UserCode']] = $u['Name'] ?? $u['UserCode'];
                    if (isset($u['InternalKey']))
                        $usersMap[(string) $u['InternalKey']] = $u['Name'] ?? ($u['UserCode'] ?? '');
                    if (isset($u['UserID']))
                        $usersMap[(string) $u['UserID']] = $u['Name'] ?? ($u['UserCode'] ?? '');
                }
            }
        }

        // 8) Traer Warehouses (una sola vez)
        $whsMap = [];
        $resWhs = $doCurl(rtrim($baseUrlRoot, '/') . "/Warehouses?\$select=WhsCode,WhsName");
        if (!$resWhs['err'] && $resWhs['httpCode'] >= 200 && $resWhs['httpCode'] < 300) {
            $decW = json_decode($resWhs['body'], true);
            foreach ($decW['value'] ?? [] as $w) {
                if (isset($w['WhsCode']))
                    $whsMap[(string) $w['WhsCode']] = $w['WhsName'] ?? $w['WhsCode'];
            }
        }

        // 9) Mapear resultados y devolver
        $out = [];
        foreach ($rows as $r) {
            $almCode = '';
            if ($whsField !== null && !empty($r[$whsField]))
                $almCode = (string) $r[$whsField];

            $creatorKey = $creatorField !== null && !empty($r[$creatorField]) ? (string) $r[$creatorField] : '';
            $authKey = $authField !== null && !empty($r[$authField]) ? (string) $r[$authField] : '';

            $creatorName = $usersMap[$creatorKey] ?? $creatorKey;
            $authName = $usersMap[$authKey] ?? $authKey;
            $whsName = $whsMap[$almCode] ?? $almCode;

            $out[] = [
                'DocNum' => $r['DocNum'] ?? '',
                'DocEntry' => $r['DocEntry'] ?? '',
                'DocDate' => $r['DocDate'] ?? '',
                'Almacen' => $almCode,
                'AlmacenName' => $whsName,
                'UsuarioKey' => $creatorKey,
                'NombreDeUsuario' => $creatorName,
                'AutorizadorKey' => $authKey,
                'NombreAutorizador' => $authName,
                '_raw' => $r
            ];
        }

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $out
        ];
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
        $orderColumnIndex = (int) (($input['order'][0]['column'] ?? 1));
        $orderDir = (strtolower($input['order'][0]['dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

        $docEntry = isset($input['docEntry']) ? (int) $input['docEntry'] : 0;
        if ($docEntry <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'docEntry requerido'
            ]);
        }

        // mapear columna de DataTables a campo real
        $columnsMap = [
            0 => 'ItemCode', // No (no ordenable)
            1 => 'ItemCode', // Articulo
            2 => 'ItemDescription', // Descripcion
            3 => 'Quantity'         // Cantidad
        ];
        $orderField = $columnsMap[$orderColumnIndex] ?? 'ItemCode';

        // --- obtener configuración SL y login ---
        $dataSL = $this->serviceLayerModel->select('*')->first();
        if (empty($dataSL)) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'No hay configuración Service Layer'
            ]);
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
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'Error login SL: ' . $e->getMessage()
            ]);
        }

        if (empty($conexionSap->SessionId)) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'No se obtuvo SessionId de Service Layer'
            ]);
        }

        $cookie = "B1SESSION=" . $conexionSap->SessionId . "; ROUTEID=.node1";

        // helper curl
        $doCurl = function ($url) use ($dataSL, $cookie) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_PORT => $dataSL['port'],
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_COOKIE => $cookie,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "User-Agent: PHP cURL",
                    "B1S-CaseInsensitive: true"
                ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return ['err' => $err, 'httpCode' => $httpCode, 'body' => $response];
        };

        // normalizar SL root (una sola /b1s/v1)
        $slRoot = rtrim($dataSL['url'], '/');
        if (stripos($slRoot, '/b1s/v1') === false) {
            $slRoot .= '/b1s/v1';
        } else {
            $pos = stripos($slRoot, '/b1s/v1');
            $slRoot = substr($slRoot, 0, $pos) . '/b1s/v1';
        }

        // --- Traer las DocumentLines en una sola llamada al padre ---
        // pedimos la propiedad DocumentLines completa
        $url = $slRoot . "/PurchaseRequests({$docEntry})?\$select=DocumentLines";
        $res = $doCurl($url);
        if ($res['err']) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'cURL Error #: ' . $res['err']
            ]);
        }
        if ($res['httpCode'] < 200 || $res['httpCode'] >= 300) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'Service Layer HTTP error (get lines)', 'httpCode' => $res['httpCode'], 'body' => $res['body']
            ]);
        }

        $dec = json_decode($res['body'], true);
        if ($dec === null) {
            return $this->response->setStatusCode(500)->setJSON([
                        'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [],
                        'error' => 'No JSON en respuesta de DocumentLines', 'body' => $res['body']
            ]);
        }

        // Extraer array de líneas: puede venir en value[0].DocumentLines o DocumentLines directo
        $lines = [];
        if (isset($dec['value']) && is_array($dec['value']) && isset($dec['value'][0]['DocumentLines'])) {
            $lines = $dec['value'][0]['DocumentLines'];
        } elseif (isset($dec['DocumentLines']) && is_array($dec['DocumentLines'])) {
            $lines = $dec['DocumentLines'];
        } elseif (isset($dec['value']) && is_array($dec['value']) && !empty($dec['value'])) {
            // fallback: si el response fue value([...]) y la primera tiene otras estructuras
            $first = $dec['value'][0];
            if (isset($first['DocumentLines']) && is_array($first['DocumentLines'])) {
                $lines = $first['DocumentLines'];
            }
        }

        // Si no obtuvimos líneas, devolvemos vacío
        if (!is_array($lines) || empty($lines)) {
            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
            ]);
        }

        // --- recordsTotal antes de filtrar ---
        $recordsTotal = count($lines);

        // --- Filtrado por búsqueda (en PHP) ---
        $filtered = $lines;
        if ($searchValue !== '') {
            $sEsc = mb_strtolower($searchValue);
            $filtered = array_filter($lines, function ($ln) use ($sEsc) {
                $code = mb_strtolower((string) ($ln['ItemCode'] ?? $ln['ItemCode'] ?? ''));
                $desc = mb_strtolower((string) ($ln['ItemDescription'] ?? $ln['ItemDescription'] ?? ($ln['ItemName'] ?? '')));
                return (strpos($code, $sEsc) !== false) || (strpos($desc, $sEsc) !== false);
            });
            // reindex
            $filtered = array_values($filtered);
        }

        $recordsFiltered = count($filtered);

        // --- Ordenar en PHP ---
        $orderFieldKey = $orderField;
        usort($filtered, function ($a, $b) use ($orderFieldKey, $orderDir) {
            $va = $a[$orderFieldKey] ?? ($a[strtolower($orderFieldKey)] ?? null);
            $vb = $b[$orderFieldKey] ?? ($b[strtolower($orderFieldKey)] ?? null);

            // normalizar nulls
            if ($va === null)
                $va = '';
            if ($vb === null)
                $vb = '';

            // si es numeric (cantidad), comparar numérico
            if (is_numeric($va) && is_numeric($vb)) {
                $cmp = $va <=> $vb;
            } else {
                $cmp = strcasecmp((string) $va, (string) $vb);
            }

            return ($orderDir === 'desc') ? -$cmp : $cmp;
        });

        // --- Paginación en PHP ---
        if ($length > 0) {
            $paged = array_slice($filtered, $start, $length);
        } else {
            $paged = $filtered;
        }

        // --- Mapear salida para DataTables ---
        $out = [];
        $idx = $start;
        foreach ($paged as $line) {
            $idx++;
            $out[] = [
                'No' => $idx,
                'Articulo' => $line['ItemCode'] ?? '',
                'Descripcion' => $line['ItemDescription'] ?? ($line['ItemName'] ?? ''),
                'Cantidad' => $line['Quantity'] ?? ($line['RequiredQuantity'] ?? 0),
                '_raw' => $line
            ];
        }

        return $this->response->setJSON([
                    'draw' => $draw,
                    'recordsTotal' => (int) $recordsTotal,
                    'recordsFiltered' => (int) $recordsFiltered,
                    'data' => $out
        ]);
    }
}
