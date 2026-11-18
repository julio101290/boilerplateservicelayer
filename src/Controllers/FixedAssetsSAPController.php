<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;

class FixedAssetsSAPController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $serviceLayerController;
    protected $branchoffice;
    protected $userLinkSap;

    public function __construct() {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->branchoffice = new BranchofficesModel();
        $this->userLinkSap = new User_sap_linkModel();
        helper(['menu', 'utilerias', 'auth']);
    }

    /**
     * Vista principal o respuesta Ajax al DataTable
     */
    public function index() {
        if ($this->request->isAJAX()) {
            return $this->indexDatatable();
        }

        $data = [
            'title'     => 'Activos Fijos SAP',
            'subtitle'  => 'Catálogo de Activos Fijos',
            'box_title' => 'Listado de Activos Fijos'
        ];

        return view('julio101290\boilerplateservicelayer\Views\fixed_assets', $data);
    }

    /**
     * Listado de Activos Fijos desde OITM (ItemType = 'F')
     */
    public function indexDatatable() {
        try {
            $conn = $this->connectODBC();

            $sql = '
                SELECT 
                    T0."ItemCode", 
                    T0."ItemName", 
                    T0."U_BPId",
                    COALESCE(T2."BPLName", T1."Name", \'\') AS "BPLName",
                    T0."U_TipoEquipo",
                    COALESCE(T3."Name", T0."U_TipoEquipo", \'\') AS "TipoEquipoName"
                FROM OITM T0
                LEFT JOIN OUBR T1 ON CAST(T0."U_BPId" AS VARCHAR(20)) = CAST(T1."Code" AS VARCHAR(20))
                LEFT JOIN OBPL T2 ON CAST(T0."U_BPId" AS VARCHAR(20)) = CAST(T2."BPLId" AS VARCHAR(20))
                LEFT JOIN "@TIPOEQUIPO" T3 ON T0."U_TipoEquipo" = T3."Code"
                WHERE T0."ItemType" = \'F\'
                ORDER BY T0."ItemCode" ASC
            ';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error al consultar activos fijos: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $data[] = [
                    'ItemCode'       => $this->toUtf8($row['ItemCode']),
                    'ItemName'       => $this->toUtf8($row['ItemName']),
                    'U_BPId'         => $row['U_BPId'] !== null ? $this->toUtf8($row['U_BPId']) : '',
                    'BPLName'        => $this->toUtf8($row['BPLName'] ?? ''),
                    'U_TipoEquipo'   => $row['U_TipoEquipo'] !== null ? $this->toUtf8($row['U_TipoEquipo']) : '',
                    'TipoEquipoName' => $this->toUtf8($row['TipoEquipoName'] ?? '')
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
     * Catálogo de sucursales (OBPL con fallback a OUBR)
     */
    public function getBranchesAjax() {
        try {
            $conn = $this->connectODBC();

            $sql = 'SELECT "BPLId", "BPLName" FROM OBPL WHERE "Disabled" = \'N\' ORDER BY "BPLId" ASC';
            $rs = @odbc_exec($conn, $sql);

            $data = [];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'BPLId'   => (int) $row['BPLId'],
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
                            'BPLId'   => (int) $row['Code'],
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
     * Catálogo de Tipos de Equipo desde "@TIPOEQUIPO"
     */
    public function getTipoEquipoAjax() {
        try {
            $conn = $this->connectODBC();

            $sql = 'SELECT "Code", "Name" FROM "@TIPOEQUIPO" ORDER BY "Name" ASC';
            $rs = @odbc_exec($conn, $sql);

            $data = [];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $data[] = [
                        'Code' => $this->toUtf8($row['Code']),
                        'Name' => $this->toUtf8($row['Name'])
                    ];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);
            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }
    }

    /**
     * Obtener datos individuales de un Activo Fijo por ItemCode
     */
    public function getAsset() {
        try {
            $itemCode = trim($this->request->getPost('ItemCode') ?? '');
            if ($itemCode === '') {
                return $this->response->setJSON(['error' => true, 'message' => 'Código de activo inválido']);
            }

            $conn = $this->connectODBC();

            $safeCode = str_replace("'", "''", $itemCode);
            $sql = "SELECT \"ItemCode\", \"ItemName\", \"U_BPId\", \"U_TipoEquipo\" 
                    FROM OITM 
                    WHERE \"ItemCode\" = '{$safeCode}'";

            $rs = odbc_exec($conn, $sql);
            $row = odbc_fetch_array($rs);

            odbc_free_result($rs);
            odbc_close($conn);

            if ($row) {
                return $this->response->setJSON([
                    'ItemCode'     => $this->toUtf8($row['ItemCode']),
                    'ItemName'     => $this->toUtf8($row['ItemName']),
                    'U_BPId'       => $row['U_BPId'] !== null ? $this->toUtf8($row['U_BPId']) : '',
                    'U_TipoEquipo' => $row['U_TipoEquipo'] !== null ? $this->toUtf8($row['U_TipoEquipo']) : ''
                ]);
            }

            return $this->response->setJSON(['error' => true, 'message' => 'Activo fijo no encontrado']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Actualiza U_BPId y U_TipoEquipo en SAP estrictamente vía Service Layer
     */
    public function save() {
        helper('auth');
        $userName = user()->username ?? 'admin';

        $post = $this->request->getPost();
        $itemCode = trim($post['ItemCode'] ?? '');

        if ($itemCode === '') {
            return $this->respond([
                'status'  => 400,
                'message' => 'El código del activo es obligatorio.'
            ], 400);
        }

        $dataSL = $this->serviceLayerModel->first();
        if (empty($dataSL)) {
            return $this->respond(['status' => 500, 'message' => 'No hay configuración Service Layer'], 500);
        }

        $rawBpId     = isset($post['U_BPId']) && trim($post['U_BPId']) !== '' ? trim($post['U_BPId']) : null;
        $uTipoEquipo = isset($post['U_TipoEquipo']) && trim($post['U_TipoEquipo']) !== '' ? trim($post['U_TipoEquipo']) : null;

        // Verificar en CUFD si U_BPId es numérico ('N') o alfanumérico ('A') en SAP
        $uBpId = $rawBpId;
        if ($rawBpId !== null && is_numeric($rawBpId)) {
            try {
                $conn = $this->connectODBC();
                $sqlType = "SELECT \"TypeID\" FROM CUFD WHERE \"TableID\" = 'OITM' AND \"AliasID\" = 'BPId'";
                $rsType = @odbc_exec($conn, $sqlType);
                if ($rsType && ($rowType = odbc_fetch_array($rsType))) {
                    if (($rowType['TypeID'] ?? '') === 'N') {
                        $uBpId = (int) $rawBpId;
                    }
                    odbc_free_result($rsType);
                }
                odbc_close($conn);
            } catch (\Throwable $e) {
                // Si falla la verificación de tipo, se envía el valor tal cual
            }
        }

        // Login a Service Layer igual que en SapMaterialController
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
            return $this->respond(['status' => 500, 'message' => 'No se obtuvo SessionId de Service Layer'], 500);
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

        $payload = [
            'U_BPId'       => $uBpId,
            'U_TipoEquipo' => $uTipoEquipo
        ];

        $url = $slRoot . "/Items('" . rawurlencode($itemCode) . "')";

        [$httpCode, $resp, $err] = $this->sendServiceLayerRequest($url, $dataSL['port'], 'PATCH', $payload, $cookie, $baseHeaders);

        if ($err) {
            return $this->respond(['status' => 500, 'message' => 'cURL Error: ' . $err], 500);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode($resp, true);
            $msgError = $body['error']['message']['value'] ?? $body['error']['message'] ?? 'Error en Service Layer';
            return $this->respond(['status' => $httpCode, 'message' => $msgError, 'body' => $body], 200);
        }

        // Bitácora de operaciones
        $this->log->save([
            "description" => "Actualización de activo fijo '{$itemCode}' (U_BPId: " . ($uBpId ?? 'NULL') . ", U_TipoEquipo: " . ($uTipoEquipo ?? 'NULL') . ")",
            "user"        => $userName
        ]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Activo fijo actualizado correctamente en SAP'
        ], 200);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

    private function sendServiceLayerRequest(string $url, $port, string $method, array $payload, string $cookie, array $headers): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_PORT           => $port,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_COOKIE         => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60
        ]);

        $resp     = curl_exec($ch);
        $err      = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$httpCode, $resp, $err];
    }

    private function connectODBC() {
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

        return $conn;
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
}