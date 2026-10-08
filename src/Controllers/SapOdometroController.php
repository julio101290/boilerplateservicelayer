<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Throwable;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;

class SapOdometroController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $serviceLayerModel;
    protected $serviceLayerController;

    public function __construct() {
        $this->log = new LogModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->serviceLayerController = new SapservicelayerController();
        helper(['menu', 'utilerias']);
    }

    /**
     * Vista principal del módulo
     */
    public function index() {
        helper('auth');

        $data = [
            'title'     => 'Corrección de Odómetro y Horómetro',
            'subtitle'  => 'Modificación en Salidas de Mercancía (OIGE / IGE1)',
            'box_title' => 'Buscar y Modificar Odómetro / Horómetro'
        ];

        return view('julio101290\boilerplateservicelayer\Views\sapOdometroView', $data);
    }

    /**
     * Catálogo de Salidas de Mercancía (OIGE) para inputs de texto o select2
     */
    public function getDocNumAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = ' WHERE 1=1 ';
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $isNumeric   = is_numeric($searchClean);
                $numFilter   = $isNumeric ? ' OR "DocNum" = ' . (int)$searchClean . ' OR "DocEntry" = ' . (int)$searchClean : '';

                $where .= " AND (
                    \"Comments\" LIKE '%{$searchClean}%'
                    {$numFilter}
                ) ";
            }

            $sql = "
                SELECT \"DocEntry\", \"DocNum\", \"DocDate\", \"Comments\"
                FROM OIGE
                {$where}
                ORDER BY \"DocNum\" DESC
                LIMIT 30
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new Exception('Error al consultar salidas: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $docNum   = (int) $row['DocNum'];
                $docEntry = (int) $row['DocEntry'];
                $comments = $this->toUtf8($row['Comments'] ?? '');
                $docDate  = substr($this->toUtf8($row['DocDate'] ?? ''), 0, 10);
                $label    = "Folio #{$docNum} (DocEntry: {$docEntry}) - {$docDate}" . ($comments ? " - {$comments}" : '');

                $data[] = [
                    'id'       => $docNum,
                    'text'     => $label,
                    'docNum'   => $docNum,
                    'docEntry' => $docEntry
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON(['data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'data'    => [],
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Catálogo de Activos / Centros de Costo (OPRC)
     */
    public function getOcrCodeAjax() {
        try {
            $search = $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = " WHERE \"Locked\" = 'N' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    \"PrcCode\" LIKE '%{$searchClean}%'
                    OR \"PrcName\" LIKE '%{$searchClean}%'
                ) ";
            }

            $sql = "
                SELECT \"PrcCode\", \"PrcName\"
                FROM OPRC
                {$where}
                ORDER BY \"PrcCode\" ASC
                LIMIT 30
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new Exception('Error al consultar centros de costo: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                $code = $this->toUtf8($row['PrcCode']);
                $name = $this->toUtf8($row['PrcName']);

                $data[] = [
                    'id'      => $code,
                    'text'    => "{$code} - {$name}",
                    'prcCode' => $code,
                    'prcName' => $name
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON(['data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'data'    => [],
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Consulta las líneas del documento por DocNum y opcionalmente OcrCode
     */
    public function searchLines() {
        try {
            $docNum  = (int) $this->request->getGet('DocNum');
            $ocrCode = trim($this->request->getGet('OcrCode') ?? '');

            if ($docNum <= 0) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => 'Debe ingresar un DocNum válido'
                ]);
            }

            $conn = $this->connectODBC();

            $filterOcr = '';
            if (!empty($ocrCode)) {
                $cleanOcr  = str_replace("'", "''", $ocrCode);
                $filterOcr = " AND T1.\"OcrCode\" = '{$cleanOcr}' ";
            }

            $sql = "
                SELECT 
                    T0.\"DocEntry\",
                    T0.\"DocNum\",
                    T0.\"DocDate\",
                    T0.\"Comments\",
                    T1.\"LineNum\",
                    T1.\"ItemCode\",
                    T1.\"Dscription\",
                    T1.\"Quantity\",
                    T1.\"WhsCode\",
                    T1.\"OcrCode\",
                    T1.\"U_Odometro\",
                    T1.\"U_Horometro\"
                FROM OIGE T0
                INNER JOIN IGE1 T1 ON T0.\"DocEntry\" = T1.\"DocEntry\"
                WHERE T0.\"DocNum\" = {$docNum}
                {$filterOcr}
                ORDER BY T1.\"LineNum\" ASC
            ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new Exception('Error al consultar datos en SAP: ' . odbc_errormsg($conn));
            }

            $lines = [];
            $header = null;

            while ($row = odbc_fetch_array($rs)) {
                if ($header === null) {
                    $header = [
                        'DocEntry' => (int) $row['DocEntry'],
                        'DocNum'   => (int) $row['DocNum'],
                        'DocDate'  => $this->toUtf8($row['DocDate'] ?? ''),
                        'Comments' => $this->toUtf8($row['Comments'] ?? '')
                    ];
                }

                $lines[] = [
                    'DocEntry'    => (int) $row['DocEntry'],
                    'LineNum'     => (int) $row['LineNum'],
                    'ItemCode'    => $this->toUtf8($row['ItemCode']),
                    'Dscription'  => $this->toUtf8($row['Dscription']),
                    'Quantity'    => (float) ($row['Quantity'] ?? 0),
                    'WhsCode'     => $this->toUtf8($row['WhsCode']),
                    'OcrCode'     => $this->toUtf8($row['OcrCode']),
                    'U_Odometro'  => (float) ($row['U_Odometro'] ?? 0),
                    'U_Horometro' => (float) ($row['U_Horometro'] ?? 0)
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            if (empty($lines)) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => 'No se encontraron registros para los filtros ingresados.'
                ]);
            }

            return $this->response->setJSON([
                'error'  => false,
                'header' => $header,
                'data'   => $lines
            ]);

        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualiza U_Odometro y U_Horometro en SAP Business One DIRECTAMENTE VÍA ODBC
     */
    public function update() {
        helper('auth');
        $currentUserName = user()->username;

        $post      = $this->request->getPost();
        $docEntry  = (int) ($post['DocEntry'] ?? 0);
        $lineNum   = (int) ($post['LineNum'] ?? -1);
        $odometro  = (float) ($post['U_Odometro'] ?? 0);
        $horometro = (float) ($post['U_Horometro'] ?? 0);

        if ($docEntry <= 0 || $lineNum < 0) {
            return $this->respond([
                'status'  => 400,
                'message' => 'DocEntry y LineNum son obligatorios'
            ], 400);
        }

        try {
            // 1. Conectamos directamente a HANA/SQL vía ODBC
            $conn = $this->connectODBC();

            // 2. Preparamos el UPDATE ultra rápido a la tabla de detalle (IGE1)
            $sql = "
                UPDATE IGE1 
                SET \"U_Odometro\" = {$odometro}, 
                    \"U_Horometro\" = {$horometro}
                WHERE \"DocEntry\" = {$docEntry} 
                  AND \"LineNum\" = {$lineNum}
            ";

            // 3. Ejecutamos
            $rs = odbc_exec($conn, $sql);
            
            if (!$rs) {
                $errMsg = odbc_errormsg($conn);
                odbc_close($conn);
                throw new Exception('Fallo la sentencia ODBC: ' . $errMsg);
            }

            odbc_close($conn);

            // 4. Dejamos huella en el log de tu sistema
            $this->log->save([
                'description' => "Actualización Rápida ODBC - Odómetro: '{$odometro}' y Horómetro: '{$horometro}' en DocEntry: {$docEntry}, LineNum: {$lineNum}",
                'user'        => $currentUserName
            ]);

            return $this->respond([
                'status'  => 200,
                'message' => 'Odómetro y Horómetro actualizados exitosamente vía BD.'
            ], 200);

        } catch (Exception $e) {
            return $this->respond([
                'status'  => 500,
                'message' => 'Error al actualizar en Base de Datos: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // =========================================================================

    private function connectODBC() {
        $dataConect = $this->serviceLayerModel->first();
        if (!$dataConect) {
            throw new Exception('No se encontró configuración de conexión SAP.');
        }

        $conn = odbc_connect(
            $dataConect['nameODBC'],
            $dataConect['userODBC'],
            $dataConect['passwordODBC']
        );

        if (!$conn) {
            throw new Exception('Error conexión ODBC: ' . odbc_errormsg());
        }

        if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"')) {
            throw new Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
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