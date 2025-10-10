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

class PricelistController extends BaseController {

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
            
        }

        $titulos["title"] = lang('authrorder.title');
        $titulos["subtitle"] = lang('authrorder.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\pricelistSAP', $titulos);
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

    public function loadDatatable() {

        helper('auth');

        $idUser = user()->id;
        $userName = user()->username;

        // --- Controlador (parte que llama al Service Layer) ---
        $request = service('request');

        $draw = (int) $request->getGet('draw');
        $start = (int) $request->getGet('start');
        $length = (int) $request->getGet('length');
        $searchValue = $request->getGet('search')['value'] ?? '';
        $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
        $orderDir = $request->getGet('order')[0]['dir'] ?? 'asc';
        $itemCode = $_POST["articleCode"];

// columna de orden; aquí puedes mantener tu mapeo si usas columnas para orden,
// pero para búsqueda de precios realmente no hace falta.
        $fields = [
            'DocNum',
            'CardName',
            'DocDate',
            'DocStatus'
        ];
        $orderField = $fields[$orderColumnIndex] ?? 'DocEntry';

// Obtener datos de conexión al Service Layer (igual que antes)
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

// Llamada a la función que devuelve solo precios (nota: ahora le pasamos $itemCode)
        $result = $this->showItemPricesOnly(
                $cookie,
                $userLinkSap["sapuser"],
                $searchValue, // aunque no se use internamente, lo dejamos por compatibilidad
                $dataSL["url"],
                $dataSL["port"],
                $start,
                $length,
                $orderField,
                $orderDir,
                [], // fields (no necesario aquí)
                $itemCode              // <-- código de artículo
        );

// Manejo de errores
        if (!isset($result['success']) || $result['success'] === false) {
            // si vino mensaje de error, devolverlo; si no, devolver un 500 genérico
            $err = $result['error'] ?? 'Error al obtener precios del Service Layer.';
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => $err]);
        }

// Preparar respuesta compatible con DataTables
        $prices = $result['prices'] ?? [];
        $recordsTotal = count($prices);
        $recordsFiltered = $recordsTotal;

// Opcional: si quieres paginar en el servidor aquí puedes aplicar array_slice($prices, $start, $length)
// ya que la función original devolvía paginación para PurchaseOrders; para precios normalmente
// devolvemos todas las listas, pero si quieres paginar descomenta la línea siguiente:
//
// $pageData = ($length > 0) ? array_slice($prices, $start, $length) : $prices;

        $pageData = $prices; // por defecto devolvemos todo

        return $this->response->setJSON([
                    'draw' => $draw,
                    'recordsTotal' => (int) $recordsTotal,
                    'recordsFiltered' => (int) $recordsFiltered,
                    'data' => $pageData,
                    // info adicional (útil para debug / UI)
                    'item' => $result['item'] ?? null
        ]);
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

    /**
     * Adaptado por julio101290
     *
     * Obtiene exclusivamente los precios de todas las listas para un artículo dado.
     * Firma compatible con la función original; se agregó $itemCode al final.
     *
     * Devuelve:
     * [
     *   'success' => true|false,
     *   'item' => [ 'ItemCode' => '', 'ItemName' => '' ],
     *   'prices' => [ { PriceListKey, PriceListName, ItemName, Price, Currency?, _raw } , ... ],
     *   'error' => 'mensaje' (solo cuando success = false)
     * ]
     */
  public function showItemPricesOnly(
    $cookie,
    $userAuth,
    $search,
    $baseUrlRoot,
    $port,
    $start = 0,
    $length = 10,
    $orderField = 'DocEntry',
    $orderDir = 'asc',
    array $fields = [],
    $itemCode = '',
    $debug = false
) {
    $itemCode = trim((string)$itemCode);
    if ($itemCode === '') {
        return [
            'success' => false,
            'item' => null,
            'prices' => [],
            'error' => 'No se proporcionó código de artículo (itemCode).'
        ];
    }

    // cURL helpers
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
            CURLOPT_TIMEOUT => 60,
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

    // 1) Intentar traer PriceLists (map: key -> name). Hacemos intento con select y luego fallback a todo.
    $priceListsMap = [];
    $allPriceLists = []; // guardamos todo por debug/fallback
    $resPL = $doCurl(rtrim($baseUrlRoot, '/') . "/PriceLists?\$select=ListNum,ListName,PriceList,PriceListName,Name");
    if (!$resPL['err'] && $resPL['httpCode'] >= 200 && $resPL['httpCode'] < 300) {
        $decPL = json_decode($resPL['body'], true);
        foreach ($decPL['value'] ?? [] as $pl) {
            $key = $pl['ListNum'] ?? $pl['PriceList'] ?? null;
            $name = $pl['ListName'] ?? $pl['PriceListName'] ?? $pl['Name'] ?? '';
            if (!is_null($key)) $priceListsMap[(string)$key] = trim((string)$name);
            $allPriceLists[] = $pl;
        }
    } else {
        // fallback: intentar sin select (mayor cobertura)
        $resPL2 = $doCurl(rtrim($baseUrlRoot, '/') . "/PriceLists");
        if (!$resPL2['err'] && $resPL2['httpCode'] >= 200 && $resPL2['httpCode'] < 300) {
            $decPL = json_decode($resPL2['body'], true);
            foreach ($decPL['value'] ?? [] as $pl) {
                $key = $pl['ListNum'] ?? $pl['PriceList'] ?? null;
                $name = $pl['ListName'] ?? $pl['PriceListName'] ?? $pl['Name'] ?? '';
                if (!is_null($key)) $priceListsMap[(string)$key] = trim((string)$name);
                $allPriceLists[] = $pl;
            }
        }
    }

    // Si priceListsMap está vacío, guardamos allPriceLists para debug y seguiremos con intentos específicos.
    // Cache local para nombres solicitados que no estan en el map inicial
    $priceListFetchCache = [];

    // Función robusta para resolver nombre por clave
    $resolvePriceListName = function ($rawKey) use (&$priceListsMap, &$priceListFetchCache, &$allPriceLists, $doCurlTimeout, $baseUrlRoot) {
        // normalizar input
        if (is_array($rawKey) || is_object($rawKey)) {
            // si trae directamente ListName o PriceListName
            $kArr = (array)$rawKey;
            if (!empty($kArr['ListName'])) return trim((string)$kArr['ListName']);
            if (!empty($kArr['PriceListName'])) return trim((string)$kArr['PriceListName']);
            if (!empty($kArr['Name'])) return trim((string)$kArr['Name']);
            // intentar extraer ListNum/PriceList
            if (!empty($kArr['ListNum'])) $rawKey = (string)$kArr['ListNum'];
            elseif (!empty($kArr['PriceList'])) $rawKey = (string)$kArr['PriceList'];
            else $rawKey = '';
        }

        $rawKeyStr = trim((string)$rawKey);
        if ($rawKeyStr === '') return '';

        // ya en mapa directo
        if (isset($priceListsMap[$rawKeyStr]) && $priceListsMap[$rawKeyStr] !== '') {
            return $priceListsMap[$rawKeyStr];
        }
        if (isset($priceListFetchCache[$rawKeyStr])) return $priceListFetchCache[$rawKeyStr];

        $foundName = '';

        // 1) si tenemos listado completo ($allPriceLists), intentar match por campos
        if (!empty($allPriceLists)) {
            // si rawKey es numérico, comparar con ListNum
            if (ctype_digit($rawKeyStr)) {
                foreach ($allPriceLists as $pl) {
                    $listnum = isset($pl['ListNum']) ? (string)$pl['ListNum'] : (isset($pl['PriceList']) ? (string)$pl['PriceList'] : '');
                    if ($listnum !== '' && ((string)$listnum === $rawKeyStr || (int)$listnum === (int)$rawKeyStr)) {
                        $foundName = trim((string)($pl['ListName'] ?? $pl['PriceListName'] ?? $pl['Name'] ?? ''));
                        break;
                    }
                }
            }
            // si no numerico o no encontrado, intentar match por PriceList exacto o ListName contains
            if ($foundName === '') {
                foreach ($allPriceLists as $pl) {
                    $plPriceList = (string)($pl['PriceList'] ?? '');
                    $plListName = (string)($pl['ListName'] ?? $pl['PriceListName'] ?? $pl['Name'] ?? '');
                    if ($plPriceList !== '' && strtolower($plPriceList) === strtolower($rawKeyStr)) {
                        $foundName = trim($plListName);
                        break;
                    }
                    if ($plListName !== '' && stripos($plListName, $rawKeyStr) !== false) {
                        $foundName = trim($plListName);
                        break;
                    }
                }
            }
        }

        // 2) si no encontrado, probar endpoints puntuales (numérico preferente)
        if ($foundName === '' && ctype_digit($rawKeyStr)) {
            $num = (int)$rawKeyStr;
            $tries = [
                rtrim($baseUrlRoot, '/') . "/PriceLists(ListNum={$num})",
                rtrim($baseUrlRoot, '/') . "/PriceLists({$num})"
            ];
            foreach ($tries as $u) {
                $r = $doCurlTimeout($u . "?\$select=ListNum,ListName,PriceList,PriceListName,Name");
                if ($r['err'] || $r['httpCode'] < 200 || $r['httpCode'] >= 300) continue;
                $dec = json_decode($r['body'], true);
                $rec = null;
                if (is_array($dec) && isset($dec['value']) && is_array($dec['value'])) $rec = $dec['value'][0] ?? null;
                elseif (is_array($dec)) $rec = $dec;
                if (is_array($rec)) {
                    $foundName = trim((string)($rec['ListName'] ?? $rec['PriceListName'] ?? $rec['Name'] ?? ''));
                    if ($foundName !== '') break;
                }
            }
        }

        // 3) si no encontrado, intentar filter exacto por PriceList
        if ($foundName === '') {
            $u = rtrim($baseUrlRoot, '/') . "/PriceLists?\$filter=PriceList eq '" . str_replace("'", "''", $rawKeyStr) . "'&\$select=ListNum,ListName,PriceList,PriceListName,Name";
            $r = $doCurlTimeout($u);
            if (!$r['err'] && $r['httpCode'] >= 200 && $r['httpCode'] < 300) {
                $dec = json_decode($r['body'], true);
                $v0 = $dec['value'][0] ?? null;
                if (is_array($v0)) $foundName = trim((string)($v0['ListName'] ?? $v0['PriceListName'] ?? $v0['Name'] ?? ''));
            }
        }

        // 4) contains en ListName
        if ($foundName === '') {
            $cand = str_replace("'", "''", $rawKeyStr);
            $u = rtrim($baseUrlRoot, '/') . "/PriceLists?\$filter=contains(tolower(ListName),tolower('" . $cand . "'))&\$select=ListNum,ListName,PriceList,PriceListName,Name";
            $r = $doCurlTimeout($u);
            if (!$r['err'] && $r['httpCode'] >= 200 && $r['httpCode'] < 300) {
                $dec = json_decode($r['body'], true);
                $v0 = $dec['value'][0] ?? null;
                if (is_array($v0)) $foundName = trim((string)($v0['ListName'] ?? $v0['PriceListName'] ?? $v0['Name'] ?? ''));
            }
        }

        if ($foundName === '') $foundName = "Lista #{$rawKeyStr}";

        // cachear y actualizar mapa principal
        $priceListFetchCache[$rawKeyStr] = $foundName;
        $priceListsMap[$rawKeyStr] = $foundName;

        return $foundName;
    };

    // 2) Obtener item y sus precios
    $pricesOut = [];
    $itemInfo = ['ItemCode' => $itemCode, 'ItemName' => ''];
    $itemUrl = rtrim($baseUrlRoot, '/') . "/Items(ItemCode='" . rawurlencode($itemCode) . "')?\$select=ItemCode,ItemName,ItemPrices";
    $resItem = $doCurlTimeout($itemUrl);
    if ($resItem['err'] || $resItem['httpCode'] < 200 || $resItem['httpCode'] >= 300) {
        $itemUrlAlt = rtrim($baseUrlRoot, '/') . "/Items('" . rawurlencode($itemCode) . "')?\$select=ItemCode,ItemName,ItemPrices";
        $resItem = $doCurlTimeout($itemUrlAlt);
    }
    if ($resItem['err']) {
        return ['success' => false, 'item' => $itemInfo, 'prices' => [], 'error' => 'cURL Error #: ' . $resItem['err']];
    }
    if ($resItem['httpCode'] < 200 || $resItem['httpCode'] >= 300) {
        return ['success' => false, 'item' => $itemInfo, 'prices' => [], 'error' => 'Service Layer HTTP error (item fetch). Código: ' . $resItem['httpCode']];
    }

    $decItem = json_decode($resItem['body'], true);
    if (isset($decItem['value']) && is_array($decItem['value'])) $decItem = $decItem['value'][0] ?? $decItem;
    if (!is_array($decItem)) return ['success' => false, 'item' => $itemInfo, 'prices' => [], 'error' => 'Respuesta inesperada al obtener el item.'];

    if (!empty($decItem['ItemName'])) $itemInfo['ItemName'] = $decItem['ItemName'];
    if (!empty($decItem['ItemCode'])) $itemInfo['ItemCode'] = $decItem['ItemCode'];

    // extraer itemprices
    $rawPrices = [];
    if (isset($decItem['ItemPrices'])) {
        $rawPrices = $decItem['ItemPrices'];
        if (isset($rawPrices['value']) && is_array($rawPrices['value'])) $rawPrices = $rawPrices['value'];
    } elseif (isset($decItem['value']) && is_array($decItem['value']) && isset($decItem['value'][0]['ItemPrices'])) {
        $rawPrices = $decItem['value'][0]['ItemPrices'];
        if (isset($rawPrices['value'])) $rawPrices = $rawPrices['value'];
    }
    if (!is_array($rawPrices)) $rawPrices = [];

    $toFloat = function ($v) {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float)$v;
        $clean = str_replace([',', ' '], ['', ''], (string)$v);
        return is_numeric($clean) ? (float)$clean : null;
    };

    foreach ($rawPrices as $ip) {
        if (!is_array($ip) && !is_object($ip)) continue;

        // obtener raw key posible (puede ser array, object o string)
        $plKeyRaw = $ip['PriceList'] ?? $ip['ListNum'] ?? $ip['PriceListNum'] ?? $ip['PriceListId'] ?? $ip['PriceListNo'] ?? null;

        // si viene como objeto/array con ListName, usamos el nombre directo
        if (is_array($plKeyRaw) || is_object($plKeyRaw)) {
            $karr = (array)$plKeyRaw;
            if (!empty($karr['ListName'])) {
                $plKeyResolved = (string)($karr['ListNum'] ?? $karr['PriceList'] ?? '');
                $pricesOut[] = [
                    'PriceListKey' => $plKeyResolved,
                    'PriceListName' => trim((string)$karr['ListName']),
                    'ItemName' => $ip['ItemName'] ?? $itemInfo['ItemName'] ?? $itemCode,
                    'Price' => $toFloat($ip['Price'] ?? $ip['PriceListPrice'] ?? $ip['ListPrice'] ?? $ip['ItemPrice'] ?? $ip['UnitPrice'] ?? null) ?? ($ip['Price'] ?? ''),
                    'Currency' => $ip['Currency'] ?? '',
                    '_raw' => $ip
                ];
                continue;
            }
        }

        // si viene como string con @odata.id o ruta, extraer numero: PriceLists(...number...)
        $plKeyCandidate = '';
        if (is_string($plKeyRaw)) {
            $plKeyCandidate = trim($plKeyRaw);
            if (preg_match('/PriceLists.*?([0-9]+)/i', $plKeyCandidate, $m)) {
                $plKeyCandidate = (string)$m[1];
            }
        }

        // resolver nombre usando la función (intenta mapa, allPriceLists y endpoints)
        $plName = $resolvePriceListName($plKeyCandidate !== '' ? $plKeyCandidate : $plKeyRaw);

        // determinar key string para salida
        $plKeyStr = '';
        if (is_string($plKeyRaw) && $plKeyRaw !== '') $plKeyStr = (string)$plKeyRaw;
        elseif (is_numeric($plKeyCandidate)) $plKeyStr = (string)$plKeyCandidate;
        else $plKeyStr = (string)($ip['PriceList'] ?? $ip['ListNum'] ?? '');

        $priceVal = $ip['Price'] ?? $ip['PriceListPrice'] ?? $ip['ListPrice'] ?? $ip['ItemPrice'] ?? $ip['UnitPrice'] ?? null;
        $priceNumeric = $toFloat($priceVal);

        $pricesOut[] = [
            'PriceListKey' => $plKeyStr,
            'PriceListName' => $plName ?: ($ip['PriceListName'] ?? $ip['ListName'] ?? ($plKeyStr !== '' ? "Lista #{$plKeyStr}" : 'Sin nombre')),
            'ItemName' => $ip['ItemName'] ?? $itemInfo['ItemName'] ?? $itemCode,
            'Price' => is_null($priceNumeric) ? ($priceVal ?? '') : $priceNumeric,
            'Currency' => $ip['Currency'] ?? '',
            '_raw' => $ip
        ];
    }

    // fallback alternativo si no encontró nada (igual que antes)
    if (empty($pricesOut)) {
        $tryPaths = [
            rtrim($baseUrlRoot, '/') . "/Items('" . rawurlencode($itemCode) . "')/ItemPrices",
            rtrim($baseUrlRoot, '/') . "/ItemPrices?\$filter=ItemCode eq '" . str_replace("'", "''", $itemCode) . "'"
        ];
        foreach ($tryPaths as $p) {
            $resAlt = $doCurlTimeout($p . "?\$select=PriceList,Price,Currency,ItemName,PriceListName,ListName,Name,ListNum");
            if ($resAlt['err'] || $resAlt['httpCode'] < 200 || $resAlt['httpCode'] >= 300) continue;
            $decAlt = json_decode($resAlt['body'], true);
            $altValues = $decAlt['value'] ?? $decAlt;
            if (!is_array($altValues)) $altValues = [];
            foreach ($altValues as $ip) {
                $plKey = $ip['PriceList'] ?? $ip['ListNum'] ?? null;
                if (is_array($plKey)) $plKey = $plKey['ListNum'] ?? null;
                if (is_object($plKey)) $plKey = property_exists($plKey, 'ListNum') ? $plKey->ListNum : null;
                $plKeyStr = is_null($plKey) ? '' : (string)$plKey;
                $plName = $priceListsMap[$plKeyStr] ?? ($ip['PriceListName'] ?? $ip['ListName'] ?? $ip['Name'] ?? '');
                if ($plName === '' && $plKeyStr !== '') $plName = "Lista #{$plKeyStr}";
                $priceNumeric = $toFloat($ip['Price'] ?? $ip['ListPrice'] ?? null);
                $pricesOut[] = [
                    'PriceListKey' => $plKeyStr,
                    'PriceListName' => $plName,
                    'ItemName' => $ip['ItemName'] ?? $itemInfo['ItemName'] ?? $itemCode,
                    'Price' => is_null($priceNumeric) ? ($ip['Price'] ?? '') : $priceNumeric,
                    'Currency' => $ip['Currency'] ?? '',
                    '_raw' => $ip
                ];
            }
            if (!empty($pricesOut)) break;
        }
    }

    // ordenar por clave
    usort($pricesOut, function ($a, $b) {
        return strcmp((string)($a['PriceListKey'] ?? ''), (string)($b['PriceListKey'] ?? ''));
    });

    $result = ['success' => true, 'item' => $itemInfo, 'prices' => $pricesOut];
    if ($debug) {
        $result['debug'] = [
            'priceListsMap' => $priceListsMap,
            'firstRawPrice' => $rawPrices[0] ?? null,
            'allPriceLists' => $allPriceLists,
            'priceListFetchCache' => $priceListFetchCache
        ];
    }
    return $result;
}

    /**
     * Ejecuta un callable y atrapa cualquier excepción/throwable,
     * devolviendo string vacío en caso de error.
     */
    private function invokeIgnoreCallable(callable $fn) {
        try {
            return $fn();
        } catch (\Throwable $e) {
            // Opcional: log para debugging
            if (function_exists('log_message')) {
                // Si usas CodeIgniter
                log_message('error', 'invokeIgnoreCallable: ' . $e->getMessage());
            } else {
                error_log('invokeIgnoreCallable: ' . $e->getMessage());
            }
            return '';
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
        // añadimos índices para Precio y Total (index 4 y 5)
        $columnsMap = [
            0 => 'ItemCode', // No
            1 => 'ItemCode', // Articulo
            2 => 'ItemDescription', // Descripcion
            3 => 'Quantity', // Cantidad
            4 => 'Price', // Precio
            5 => 'Total'            // Total (derivado)
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
        $url = $slRoot . "/PurchaseOrders({$docEntry})?\$select=DocumentLines";
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

        // Extraer array de líneas
        $lines = [];
        if (isset($dec['value']) && is_array($dec['value']) && isset($dec['value'][0]['DocumentLines'])) {
            $lines = $dec['value'][0]['DocumentLines'];
        } elseif (isset($dec['DocumentLines']) && is_array($dec['DocumentLines'])) {
            $lines = $dec['DocumentLines'];
        } elseif (isset($dec['value']) && is_array($dec['value']) && !empty($dec['value'])) {
            $first = $dec['value'][0];
            if (isset($first['DocumentLines']) && is_array($first['DocumentLines'])) {
                $lines = $first['DocumentLines'];
            }
        }

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
                $code = mb_strtolower((string) ($ln['ItemCode'] ?? ''));
                $desc = mb_strtolower((string) ($ln['ItemDescription'] ?? $ln['ItemName'] ?? ''));
                return (strpos($code, $sEsc) !== false) || (strpos($desc, $sEsc) !== false);
            });
            $filtered = array_values($filtered);
        }

        $recordsFiltered = count($filtered);

        // --- Ordenar en PHP ---
        $orderFieldKey = $orderField;
        usort($filtered, function ($a, $b) use ($orderFieldKey, $orderDir) {
            // helper para extraer número limpiando comas
            $num = function ($v) {
                if ($v === null)
                    return null;
                if (is_numeric($v))
                    return (float) $v;
                $clean = str_replace([',', ' '], ['', ''], (string) $v);
                return is_numeric($clean) ? (float) $clean : null;
            };

            // obtener valores base
            $va = $a[$orderFieldKey] ?? ($a[strtolower($orderFieldKey)] ?? null);
            $vb = $b[$orderFieldKey] ?? ($b[strtolower($orderFieldKey)] ?? null);

            // special: Precio
            if (strtolower($orderFieldKey) === 'price') {
                $va = $a['Price'] ?? $a['UnitPrice'] ?? $a['PriceBefDi'] ?? null;
                $vb = $b['Price'] ?? $b['UnitPrice'] ?? $b['PriceBefDi'] ?? null;
                $nva = $num($va);
                $nvb = $num($vb);
                if ($nva !== null && $nvb !== null) {
                    $cmp = $nva <=> $nvb;
                    return ($orderDir === 'desc') ? -$cmp : $cmp;
                }
            }

            // special: Quantity
            if (strtolower($orderFieldKey) === 'quantity') {
                $va = $a['Quantity'] ?? $a['RequiredQuantity'] ?? $va;
                $vb = $b['Quantity'] ?? $b['RequiredQuantity'] ?? $vb;
                $nva = $num($va);
                $nvb = $num($vb);
                if ($nva !== null && $nvb !== null) {
                    $cmp = $nva <=> $nvb;
                    return ($orderDir === 'desc') ? -$cmp : $cmp;
                }
            }

            // special: Total (usar LineTotal si existe, sino precio*cantidad)
            if (in_array(strtolower($orderFieldKey), ['total', 'linetotal'])) {
                $ltA = $a['LineTotal'] ?? null;
                $ltB = $b['LineTotal'] ?? null;
                $priceA = $a['Price'] ?? $a['UnitPrice'] ?? $a['PriceBefDi'] ?? null;
                $priceB = $b['Price'] ?? $b['UnitPrice'] ?? $b['PriceBefDi'] ?? null;
                $qtyA = $a['Quantity'] ?? $a['RequiredQuantity'] ?? null;
                $qtyB = $b['Quantity'] ?? $b['RequiredQuantity'] ?? null;

                $nltA = $num($ltA);
                $nltB = $num($ltB);
                if ($nltA === null && $nltB === null) {
                    $npriceA = $num($priceA);
                    $nqtyA = $num($qtyA);
                    $npriceB = $num($priceB);
                    $nqtyB = $num($qtyB);
                    $nltA = (is_numeric($npriceA) && is_numeric($nqtyA)) ? $npriceA * $nqtyA : null;
                    $nltB = (is_numeric($npriceB) && is_numeric($nqtyB)) ? $npriceB * $nqtyB : null;
                }
                if ($nltA !== null && $nltB !== null) {
                    $cmp = $nltA <=> $nltB;
                    return ($orderDir === 'desc') ? -$cmp : $cmp;
                }
            }

            // fallback: comparación string (case-insensitive)
            if ($va === null)
                $va = '';
            if ($vb === null)
                $vb = '';

            // si ambos son numéricos comparar numérico
            if (is_numeric($va) && is_numeric($vb)) {
                $cmp = ($va + 0) <=> ($vb + 0);
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

            // cantidad fallback
            $cantidadRaw = $line['Quantity'] ?? $line['RequiredQuantity'] ?? 0;
            $cantidad = is_numeric($cantidadRaw) ? (float) $cantidadRaw : (float) str_replace([',', ' '], '', (string) $cantidadRaw);

            // precio fallback (Price, UnitPrice, PriceBefDi)
            $precioRaw = $line['Price'] ?? $line['UnitPrice'] ?? $line['PriceBefDi'] ?? null;
            $precio = null;
            if ($precioRaw !== null) {
                $precio = is_numeric($precioRaw) ? (float) $precioRaw : (is_numeric(str_replace([',', ' '], '', (string) $precioRaw)) ? (float) str_replace([',', ' '], '', (string) $precioRaw) : null);
            }

            // line total desde SL si existe
            $lineTotalRaw = $line['LineTotal'] ?? $line['LineTotal'] ?? null;
            $lineTotal = null;
            if ($lineTotalRaw !== null) {
                $lineTotal = is_numeric($lineTotalRaw) ? (float) $lineTotalRaw : (is_numeric(str_replace([',', ' '], '', (string) $lineTotalRaw)) ? (float) str_replace([',', ' '], '', (string) $lineTotalRaw) : null);
            }

            // calcular Total: si SL trae LineTotal usarlo, sino multiplicar precio*cantidad si ambos son numéricos
            $total = null;
            if ($lineTotal !== null) {
                $total = $lineTotal;
            } elseif ($precio !== null && $cantidad !== null) {
                $total = $precio * $cantidad;
            }

            // redondear a 2 decimales cuando corresponda (null queda null)
            $precioOut = $precio !== null ? round($precio, 2) : null;
            $totalOut = $total !== null ? round($total, 2) : null;
            $cantidadOut = $cantidad !== null ? (is_float($cantidad) ? $cantidad : (float) $cantidad) : 0;

            $out[] = [
                'No' => $idx,
                'Articulo' => $line['ItemCode'] ?? '',
                'Descripcion' => $line['ItemDescription'] ?? ($line['ItemName'] ?? ''),
                'Cantidad' => $cantidadOut,
                'Precio' => $precioOut,
                'Total' => $totalOut,
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
