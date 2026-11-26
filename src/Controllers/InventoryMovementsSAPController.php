<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Throwable;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;

class InventoryMovementsSAPController extends BaseController
{
    use ResponseTrait;

    protected $empresa;
    protected $sucursales;
    protected $serviceLayerModel;
    protected $linkSapBranchOffice;
    protected $userSapLink;

    public function __construct()
    {
        $this->empresa             = new EmpresasModel();
        $this->sucursales          = new BranchofficesModel();
        $this->serviceLayerModel   = new SapservicelayerModel();
        $this->linkSapBranchOffice = new Link_sap_branchofficeModel();
        $this->userSapLink         = new User_sap_linkModel();

        helper(['menu', 'utilerias', 'auth']);
    }

    private function applyCompanyFilter($target, string $tableName, $companyVal)
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($tableName)) {
            return $target;
        }

        foreach (['idEmpresa', 'companie', 'idCompany'] as $col) {
            if ($db->fieldExists($col, $tableName)) {
                is_array($companyVal) ? $target->whereIn($col, $companyVal) : $target->where($col, $companyVal);
                break;
            }
        }

        return $target;
    }

    private function getDocumentDefinitions(): array
    {
        return [
            '59' => ['H' => 'OIGN', 'L' => 'IGN1', 'name' => 'Entrada de Mercancía (Inv.)', 'short' => 'ENT-INV', 'badge' => 'success',   'group' => 'INVENTARIO', 'hasNumAtCard' => false, 'isTransfer' => false, 'isPayment' => false],
            '60' => ['H' => 'OIGE', 'L' => 'IGE1', 'name' => 'Salida de Mercancía (Inv.)',  'short' => 'SAL-INV', 'badge' => 'danger',    'group' => 'INVENTARIO', 'hasNumAtCard' => false, 'isTransfer' => false, 'isPayment' => false],
            '67' => ['H' => 'OWTR', 'L' => 'WTR1', 'name' => 'Transferencia de Stock',      'short' => 'TRANSF',  'badge' => 'info',      'group' => 'INVENTARIO', 'hasNumAtCard' => false, 'isTransfer' => true,  'isPayment' => false],
            '22' => ['H' => 'OPOR', 'L' => 'POR1', 'name' => 'Orden de Compra',             'short' => 'OC',      'badge' => 'primary',   'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '20' => ['H' => 'OPDN', 'L' => 'PDN1', 'name' => 'Entrada Mercancía OP',        'short' => 'EM-COM',  'badge' => 'success',   'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '21' => ['H' => 'ORPD', 'L' => 'RPD1', 'name' => 'Devolución de Mercancía',     'short' => 'DEV-COM', 'badge' => 'dark',      'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '18' => ['H' => 'OPCH', 'L' => 'PCH1', 'name' => 'Factura de Proveedor',        'short' => 'FAC-PRV', 'badge' => 'warning',   'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '19' => ['H' => 'ORPC', 'L' => 'RPC1', 'name' => 'Nota Crédito Proveedor',      'short' => 'NC-PRV',  'badge' => 'purple',    'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '15' => ['H' => 'ODLN', 'L' => 'DLN1', 'name' => 'Entrega (Ventas)',            'short' => 'ENT-VTA', 'badge' => 'secondary', 'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '16' => ['H' => 'ORDN', 'L' => 'RDN1', 'name' => 'Devolución (Ventas)',         'short' => 'DEV-VTA', 'badge' => 'info',      'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '13' => ['H' => 'OINV', 'L' => 'INV1', 'name' => 'Factura de Cliente',          'short' => 'FAC-CLI', 'badge' => 'danger',    'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '14' => ['H' => 'ORIN', 'L' => 'RIN1', 'name' => 'Nota Crédito Cliente',        'short' => 'NC-CLI',  'badge' => 'purple',    'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false, 'isPayment' => false],
            '46' => ['H' => 'OVPM', 'L' => 'VPM2', 'name' => 'Pago Efectuado (Prov.)',      'short' => 'PAG-EFV', 'badge' => 'success',   'group' => 'PAGOS',      'hasNumAtCard' => false, 'isTransfer' => false, 'isPayment' => true],
            '24' => ['H' => 'ORCT', 'L' => 'RCT2', 'name' => 'Pago Recibido (Cli.)',        'short' => 'PAG-REC', 'badge' => 'info',      'group' => 'PAGOS',      'hasNumAtCard' => false, 'isTransfer' => false, 'isPayment' => true],
        ];
    }

    private function getAllDocTableMap(): array
    {
        return [
            '1250000001' => ['H' => 'OWTQ', 'L' => 'WTQ1', 'hasNumAtCard' => false, 'isTransfer' => true,  'hasMoney' => false, 'hasCard' => true,  'hasTransId' => false],
            '59'         => ['H' => 'OIGN', 'L' => 'IGN1', 'hasNumAtCard' => false, 'isTransfer' => false, 'hasMoney' => false, 'hasCard' => true,  'hasTransId' => true],
            '60'         => ['H' => 'OIGE', 'L' => 'IGE1', 'hasNumAtCard' => false, 'isTransfer' => false, 'hasMoney' => false, 'hasCard' => true,  'hasTransId' => true],
            '67'         => ['H' => 'OWTR', 'L' => 'WTR1', 'hasNumAtCard' => false, 'isTransfer' => true,  'hasMoney' => false, 'hasCard' => true,  'hasTransId' => true],
            '1470000113' => ['H' => 'OPRQ', 'L' => 'PRQ1', 'hasNumAtCard' => false, 'isTransfer' => false, 'hasMoney' => false, 'hasCard' => false, 'hasTransId' => false],
            '540000006'  => ['H' => 'OPQT', 'L' => 'PQT1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => false],
            '22'         => ['H' => 'OPOR', 'L' => 'POR1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => false],
            '20'         => ['H' => 'OPDN', 'L' => 'PDN1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '21'         => ['H' => 'ORPD', 'L' => 'RPD1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '18'         => ['H' => 'OPCH', 'L' => 'PCH1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '19'         => ['H' => 'ORPC', 'L' => 'RPC1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '204'        => ['H' => 'ODPO', 'L' => 'DPO1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '23'         => ['H' => 'OQUT', 'L' => 'QUT1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => false],
            '17'         => ['H' => 'ORDR', 'L' => 'RDR1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => false],
            '15'         => ['H' => 'ODLN', 'L' => 'DLN1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '16'         => ['H' => 'ORDN', 'L' => 'RDN1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '13'         => ['H' => 'OINV', 'L' => 'INV1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '14'         => ['H' => 'ORIN', 'L' => 'RIN1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
            '203'        => ['H' => 'ODPI', 'L' => 'DPI1', 'hasNumAtCard' => true,  'isTransfer' => false, 'hasMoney' => true,  'hasCard' => true,  'hasTransId' => true],
        ];
    }

    private function extractFilterParams(bool $fromGet = false): array
    {
        $g = fn(string $key, $default = '') => $fromGet
            ? ($this->request->getGet($key) ?? $default)
            : ($this->request->getPost($key) ?? $default);

        return [
            'idUser'       => (int) user()->id,
            'idEmpresa'    => (int) $g('idEmpresa', 1),
            'bplId'        => trim((string) $g('bplId', 'TODOS')),
            'fechaIni'     => trim((string) $g('fechaIni', date('Y-m-01'))),
            'fechaFin'     => trim((string) $g('fechaFin', date('Y-m-d'))),
            'tipoDocs'     => $this->normalizeMultiParam($g('tipoDocs', null)),
            'almacenes'    => $this->normalizeMultiParam($g('almacenes', null)),
            'articulos'    => $this->normalizeMultiParam($g('articulos', null)),
            'proveedores'  => $this->normalizeMultiParam($g('proveedores', null)),
            'estatus'      => strtoupper(trim((string) $g('estatus', 'TODOS'))),
            'folio'        => trim((string) $g('folio', '')),
            'cancelados'   => strtoupper(trim((string) $g('cancelados', 'N'))),
            'conciliacion' => strtoupper(trim((string) $g('conciliacion', 'TODOS'))),
        ];
    }

    public function index()
    {
        if (!service('authentication')->check()) {
            return redirect()->route('admin');
        }

        $empresas = $this->empresa->mdlEmpresasPorUsuario((int) user()->id);

        return view('julio101290\boilerplateservicelayer\Views\inventoryMovementsSAP', [
            'title'     => 'Reporte Detallado de Movimientos de Almacén, Compras y Pagos SAP',
            'subtitle'  => 'Entradas, salidas, transferencias, órdenes de compra, facturas, pagos, conciliación, mapa de relaciones y log de modificaciones',
            'empresas'  => $empresas,
            'idEmpresa' => (int) ($empresas[0]['id'] ?? 1),
            'fechaIni'  => date('Y-m-01'),
            'fechaFin'  => date('Y-m-d'),
            'docTypes'  => $this->getDocumentDefinitions(),
        ]);
    }

    public function getBranchesAjax()
    {
        try {
            $idEmpresa  = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $sucursales = $this->getAllowedSAPBranches((int) user()->id, $idEmpresa);
            return $this->response->setJSON(['error' => false, 'sucursales' => $sucursales, 'token' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage(), 'sucursales' => [], 'token' => csrf_hash()]);
        }
    }

    public function getWarehousesAjax()
    {
        try {
            $idEmpresa  = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $bplId      = trim((string) ($this->request->getPost('bplId') ?? 'TODOS'));
            $conn       = $this->connectODBC($idEmpresa);
            $warehouses = [];

            $baseSql = 'SELECT "WhsCode", IFNULL("WhsName", \'\') AS "WhsName" FROM "OWHS" WHERE IFNULL("Inactive", \'N\') = \'N\' ';
            $rs      = ($bplId !== '' && $bplId !== 'TODOS' && is_numeric($bplId))
                ? (@odbc_exec($conn, $baseSql . ' AND "BPLid" = ' . (int) $bplId . ' ORDER BY "WhsCode" ASC')
                    ?: @odbc_exec($conn, $baseSql . ' ORDER BY "WhsCode" ASC'))
                : @odbc_exec($conn, $baseSql . ' ORDER BY "WhsCode" ASC');

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code         = trim($this->toUtf8($row['WhsCode'] ?? ''));
                    $name         = trim($this->toUtf8($row['WhsName'] ?? ''));
                    $warehouses[] = ['id' => $code, 'text' => "{$code} - {$name}"];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['error' => false, 'warehouses' => $warehouses, 'token' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage(), 'warehouses' => [], 'token' => csrf_hash()]);
        }
    }

    public function searchItemsAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getGet('idEmpresa') ?? 1);
            $termClean = str_replace("'", "''", strtoupper(trim((string) ($this->request->getGet('q') ?? ''))));
            $conn      = $this->connectODBC($idEmpresa);

            $where = $termClean !== ''
                ? " AND (UPPER(t0.\"ItemCode\") LIKE '%{$termClean}%' OR UPPER(IFNULL(t0.\"ItemName\", '')) LIKE '%{$termClean}%' OR UPPER(IFNULL(t0.\"CodeBars\", '')) LIKE '%{$termClean}%')"
                : '';

            $results = [];
            $rs      = @odbc_exec($conn, "SELECT t0.\"ItemCode\", IFNULL(t0.\"ItemName\", '') AS \"ItemName\", IFNULL(t0.\"InvntryUom\", '') AS \"Uom\" FROM \"OITM\" t0 WHERE 1 = 1 {$where} ORDER BY t0.\"ItemCode\" ASC LIMIT 35");
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code      = trim($this->toUtf8($row['ItemCode'] ?? ''));
                    $name      = trim($this->toUtf8($row['ItemName'] ?? ''));
                    $uom       = trim($this->toUtf8($row['Uom'] ?? ''));
                    $results[] = ['id' => $code, 'text' => "{$code} - {$name}" . ($uom !== '' ? " [{$uom}]" : '')];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['results' => $results]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    public function searchSuppliersAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getGet('idEmpresa') ?? 1);
            $termClean = str_replace("'", "''", strtoupper(trim((string) ($this->request->getGet('q') ?? ''))));
            $conn      = $this->connectODBC($idEmpresa);

            $where = $termClean !== ''
                ? " AND (UPPER(t0.\"CardCode\") LIKE '%{$termClean}%' OR UPPER(IFNULL(t0.\"CardName\", '')) LIKE '%{$termClean}%' OR UPPER(IFNULL(t0.\"LicTradNum\", '')) LIKE '%{$termClean}%')"
                : '';

            $results = [];
            $rs      = @odbc_exec($conn, "SELECT t0.\"CardCode\", IFNULL(t0.\"CardName\", '') AS \"CardName\", IFNULL(t0.\"LicTradNum\", '') AS \"RFC\" FROM \"OCRD\" t0 WHERE t0.\"CardType\" = 'S' {$where} ORDER BY t0.\"CardName\" ASC LIMIT 35");
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code      = trim($this->toUtf8($row['CardCode'] ?? ''));
                    $name      = trim($this->toUtf8($row['CardName'] ?? ''));
                    $rfc       = trim($this->toUtf8($row['RFC'] ?? ''));
                    $results[] = ['id' => $code, 'text' => "{$code} - {$name}" . ($rfc !== '' ? " ({$rfc})" : '')];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['results' => $results]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getMovementsAjax()
    {
        try {
            $result = $this->buildMovementsDataset($this->extractFilterParams(false), 5000);
            return $this->response->setJSON([
                'error'     => false,
                'kpis'      => $result['kpis'],
                'truncated' => $result['truncated'],
                'data'      => $result['rows'],
                'token'     => csrf_hash(),
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage(), 'data' => [], 'token' => csrf_hash()]);
        }
    }

    public function getDocumentDetailAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $objType   = trim((string) ($this->request->getPost('objType') ?? ''));
            $docEntry  = (int) ($this->request->getPost('docEntry') ?? 0);
            $transId   = (int) ($this->request->getPost('transId') ?? 0);

            if ($objType === '' || ($docEntry <= 0 && $transId <= 0)) {
                throw new Exception('Parámetros de documento inválidos.');
            }

            $conn   = $this->connectODBC($idEmpresa);
            $detail = $this->fetchDocumentDetail($conn, $objType, $docEntry, $transId);
            odbc_close($conn);

            return $this->response->setJSON(['error' => false, 'detail' => $detail, 'token' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage(), 'token' => csrf_hash()]);
        }
    }

    public function getRelationshipMapAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $objType   = trim((string) ($this->request->getPost('objType') ?? ''));
            $docEntry  = (int) ($this->request->getPost('docEntry') ?? 0);
            $transId   = (int) ($this->request->getPost('transId') ?? 0);
            $cardCode  = trim((string) ($this->request->getPost('cardCode') ?? ''));

            if ($objType === '' || ($docEntry <= 0 && $transId <= 0)) {
                throw new Exception('No se recibió un documento válido para el mapa de relaciones.');
            }
            if ($objType === '30' && $docEntry <= 0) {
                $docEntry = $transId;
            }

            $conn = $this->connectODBC($idEmpresa);
            $map  = $this->buildRelationshipMap($conn, $objType, $docEntry, $cardCode);
            odbc_close($conn);

            return $this->response->setJSON(['error' => false, 'map' => $map, 'token' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage(), 'token' => csrf_hash()]);
        }
    }

    public function getChangeLogAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $objType   = trim((string) ($this->request->getPost('objType') ?? ''));
            $docEntry  = (int) ($this->request->getPost('docEntry') ?? 0);

            if ($objType === '' || $docEntry <= 0) {
                throw new Exception('Parámetros de documento inválidos para consultar el log de modificaciones.');
            }

            $conn = $this->connectODBC($idEmpresa);
            $log  = $this->fetchDocumentChangeLog($conn, $objType, $docEntry);
            odbc_close($conn);

            return $this->response->setJSON(['error' => false, 'log' => $log, 'token' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['error' => true, 'message' => $e->getMessage(), 'token' => csrf_hash()]);
        }
    }

    public function report()
    {
        if (!service('authentication')->check()) {
            return redirect()->route('admin');
        }

        try {
            $params        = $this->extractFilterParams(true);
            $dataset       = $this->buildMovementsDataset($params, 2500);
            $datosEmpresa  = $this->empresa->find($params['idEmpresa']) ?: $this->empresa->first();
            $nombreEmpresa = !empty($datosEmpresa['nombre']) ? $datosEmpresa['nombre'] : 'MI EMPRESA SA DE CV';
            $rfcEmpresa    = !empty($datosEmpresa['rfc']) ? $datosEmpresa['rfc'] : '';

            $rutasLogo = ['writable/templates/quotes/logoReportes.png', 'public/img/logoReportes.png', 'public/images/logoReportes.png'];
            if (!empty($datosEmpresa['logo'])) {
                array_unshift($rutasLogo, 'public/images/logo/' . $datosEmpresa['logo']);
            }
            if (!empty($datosEmpresa['rfc'])) {
                array_unshift($rutasLogo, 'public/images/logo/' . $datosEmpresa['rfc'] . '_logo.png');
            }

            $logoPath = $this->prepararImagenTCPDF($rutasLogo, 'pdf_logo_inv_mov_' . $params['idEmpresa']);
            $logoHtml = $logoPath ? '<img src="' . $logoPath . '" style="width:120px;">' : '';

            $kpis          = $dataset['kpis'];
            $rows          = $dataset['rows'];
            $sucursalTexto = $dataset['sucursalTexto'];
            $filtroDocTxt  = $dataset['tipoDocTexto'];
            $almacenTxt    = $this->summarizeFilterList($params['almacenes'], 'TODOS', 4);
            $articuloTxt   = $this->summarizeFilterList($params['articulos'], 'TODOS', 4);
            $proveedorTxt  = $this->summarizeFilterList($params['proveedores'], 'TODOS', 3);
            $concilMap     = ['TODOS' => 'Todos', 'CONCILIADOS' => 'Solo Conciliados', 'NO_CONCILIADOS' => 'Solo No Conciliados'];
            $concilTxt     = $concilMap[$params['conciliacion']] ?? 'Todos';
            $fechaEmision  = date('Y-m-d H:i');

            $htmlHeader = <<<EOF
<table cellpadding="3" cellspacing="0" style="width:100%; border-bottom: 2px solid #a82020;">
    <tr>
        <td style="width:22%; vertical-align:middle;">{$logoHtml}</td>
        <td style="width:45%; text-align:center; vertical-align:middle;">
            <span style="font-size:12px; font-weight:bold; color:#a82020;">{$this->escPdf($nombreEmpresa)}</span><br>
            <span style="font-size:8px; color:#555555;">RFC: {$this->escPdf($rfcEmpresa)}</span><br>
            <span style="font-size:10px; font-weight:bold; color:#222222;">REPORTE DETALLADO DE MOVIMIENTOS DE ALMACÉN, COMPRAS Y PAGOS</span><br>
            <span style="font-size:7.5px; color:#444444;">Sucursal: {$this->escPdf($sucursalTexto)} &nbsp;|&nbsp; Conciliación: {$this->escPdf($concilTxt)}</span>
        </td>
        <td style="width:33%; text-align:right; font-size:7.2px; vertical-align:middle;">
            <b>Periodo:</b> {$this->escPdf($params['fechaIni'])} al {$this->escPdf($params['fechaFin'])}<br>
            <b>Documentos:</b> {$this->escPdf($filtroDocTxt)}<br>
            <b>Almacén(es):</b> {$this->escPdf($almacenTxt)}<br>
            <b>Artículo(s):</b> {$this->escPdf($articuloTxt)} &nbsp;|&nbsp; <b>Prov.:</b> {$this->escPdf($proveedorTxt)}<br>
            <b>Emitido:</b> {$fechaEmision}
        </td>
    </tr>
</table>
<br>
<table cellpadding="4" cellspacing="0" style="width:100%; font-size:8px; border:1px solid #a82020;">
    <tr style="background-color:#a82020; color:#ffffff; text-align:center; font-weight:bold;">
        <td style="width:20%;">Partidas / Registros</td>
        <td style="width:20%;">Documentos Únicos</td>
        <td style="width:20%;">Cantidad Total</td>
        <td style="width:20%;">Costo Total (Inv.)</td>
        <td style="width:20%;">Importe Total</td>
    </tr>
    <tr style="text-align:right; font-weight:bold; background-color:#fff8f8;">
        <td style="text-align:center;">{$kpis['totalPartidas']}</td>
        <td style="text-align:center;">{$kpis['totalDocumentos']}</td>
        <td>{$this->fmt($kpis['totalCantidad'], 3)}</td>
        <td>$ {$this->fmt($kpis['totalCosto'], 2)}</td>
        <td style="color:#a82020;">$ {$this->fmt($kpis['totalImporte'], 2)}</td>
    </tr>
</table>
<br>
EOF;

            $rowsHtml = '';
            foreach ($rows as $idx => $r) {
                $bg       = (($idx + 1) % 2 === 0) ? '#f7f7f7' : '#ffffff';
                $provInfo = $r['CardCode'] !== '' ? ($r['CardCode'] . ' - ' . $r['CardName']) : '-';
                $concLbl  = $r['ESTATUS_CONCILIACION'] === 'NO_APLICA' ? 'N/A' : str_replace('_', ' ', $r['ESTATUS_CONCILIACION']);

                $rowsHtml .= '<tr style="background-color:' . $bg . ';">'
                    . '<td style="width:10%; text-align:left;"><b>' . $this->escPdf($r['TMA_NOMBRE']) . '</b></td>'
                    . '<td style="width:8%; text-align:left;">' . $this->escPdf(mb_substr($r['BPLName'], 0, 16)) . '</td>'
                    . '<td style="width:5%; text-align:center;">' . $this->escPdf($r['ALM_CLAVE']) . '</td>'
                    . '<td style="width:9%; text-align:left;">' . $this->escPdf(mb_substr($r['ALM_NOMBRE'], 0, 18)) . '</td>'
                    . '<td style="width:5.5%; text-align:center;"><b>' . $this->escPdf($r['MAE_FOLIO']) . '</b></td>'
                    . '<td style="width:6%; text-align:center;">' . $this->escPdf($r['MAE_ESTATUS']) . '</td>'
                    . '<td style="width:6.5%; text-align:center;">' . $this->escPdf($concLbl) . '</td>'
                    . '<td style="width:6.5%; text-align:center;">' . $this->escPdf($r['MAE_FECHA']) . '</td>'
                    . '<td style="width:10.5%; text-align:left;">' . $this->escPdf(mb_substr($provInfo, 0, 22)) . '</td>'
                    . '<td style="width:7%; text-align:left;">' . $this->escPdf($r['ART_CLAVE']) . '</td>'
                    . '<td style="width:10%; text-align:left;">' . $this->escPdf(mb_substr($r['ART_NOMBRE'], 0, 22)) . '</td>'
                    . '<td style="width:5.5%; text-align:right;">' . $this->fmt($r['MAD_CANT'], 3) . '</td>'
                    . '<td style="width:5.25%; text-align:right;">' . $this->fmt($r['MAD_COSTO'], 4) . '</td>'
                    . '<td style="width:5.25%; text-align:right;">' . $this->fmt($r['MAD_PRECIO'], 4) . '</td>'
                    . '</tr>';
            }

            if ($rowsHtml === '') {
                $rowsHtml = '<tr><td colspan="14" style="text-align:center; padding:10px;">No existen movimientos con los filtros seleccionados.</td></tr>';
            }

            $htmlTable = <<<EOF
<table cellpadding="2" cellspacing="0" border="0.2" style="width:100%; font-size:6px; border-color:#cccccc;">
    <thead>
        <tr style="background-color:#a82020; color:#ffffff; text-align:center; font-weight:bold;">
            <th style="width:10%;">Documento</th><th style="width:8%;">Sucursal</th><th style="width:5%;">Alm.</th>
            <th style="width:9%;">Nombre Almacén</th><th style="width:5.5%;">Folio</th><th style="width:6%;">Estatus</th>
            <th style="width:6.5%;">Concil.</th><th style="width:6.5%;">Fecha</th><th style="width:10.5%;">Proveedor / Socio</th>
            <th style="width:7%;">Clave Art.</th><th style="width:10%;">Artículo / Concepto</th>
            <th style="width:5.5%;">Cantidad</th><th style="width:5.25%;">Costo</th><th style="width:5.25%;">Precio</th>
        </tr>
    </thead>
    <tbody>{$rowsHtml}</tbody>
</table>
EOF;

            $pdf = new \TCPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(5, 7, 5);
            $pdf->SetAutoPageBreak(true, 9);
            $pdf->AddPage();
            $pdf->writeHTML($htmlHeader . $htmlTable, true, false, true, false, '');

            if (ob_get_length()) {
                ob_end_clean();
            }

            $fileName  = "MOVIMIENTOS_ALMACEN_SAP_{$params['fechaIni']}_{$params['fechaFin']}.pdf";
            $pdfBinary = $pdf->Output($fileName, 'S');

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->setHeader('Content-Length', (string) strlen($pdfBinary))
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->setBody($pdfBinary);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('Error al generar PDF: ' . esc($e->getMessage()));
        }
    }

    public function exportCsv()
    {
        if (!service('authentication')->check()) {
            return redirect()->route('admin');
        }

        try {
            $params  = $this->extractFilterParams(true);
            $dataset = $this->buildMovementsDataset($params, 25000);
            $fp      = fopen('php://temp', 'r+');

            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, [
                'TMA_NOMBRE', 'SUCURSAL', 'ALM_CLAVE', 'ALM_NOMBRE', 'MAE_FOLIO', 'MAE_ESTATUS',
                'CONCILIACION', 'NUM_RECONCILIACION', 'SALDO_SIN_CONCILIAR', 'MAE_FECHA',
                'PROV_CLAVE', 'PROV_NOMBRE', 'ART_CLAVE', 'ART_NOMBRE', 'UNIDAD',
                'MAD_CANT', 'MAD_COSTO', 'MAD_PRECIO', 'IMPORTE_LINEA', 'REFERENCIA',
            ]);

            foreach ($dataset['rows'] as $r) {
                fputcsv($fp, [
                    $r['TMA_NOMBRE'], $r['BPLName'], $r['ALM_CLAVE'], $r['ALM_NOMBRE'], $r['MAE_FOLIO'], $r['MAE_ESTATUS'],
                    $r['ESTATUS_CONCILIACION'], $r['RECON_NUMS'], $r['SALDO_PENDIENTE'], $r['MAE_FECHA'],
                    $r['CardCode'], $r['CardName'], $r['ART_CLAVE'], $r['ART_NOMBRE'], $r['UNIDAD'],
                    $r['MAD_CANT'], $r['MAD_COSTO'], $r['MAD_PRECIO'], $r['IMPORTE'], $r['Referencia'],
                ]);
            }

            rewind($fp);
            $csv = stream_get_contents($fp);
            fclose($fp);

            $fileName = "MOVIMIENTOS_ALMACEN_SAP_{$params['fechaIni']}_{$params['fechaFin']}.csv";
            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->setBody($csv);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('Error al exportar CSV: ' . esc($e->getMessage()));
        }
    }

    /* =========================================================================
     * DATASET PRINCIPAL (UNION ALL + PAGOS + CONCILIACIÓN JDT1 / OITR / ITR1)
     * ========================================================================= */
    private function buildMovementsDataset(array $params, int $limitRows = 5000): array
    {
        $idUser       = (int) $params['idUser'];
        $idEmpresa    = (int) $params['idEmpresa'];
        $bplId        = trim((string) $params['bplId']);
        $fechaIni     = $this->sanitizeDate($params['fechaIni'], date('Y-m-01'));
        $fechaFin     = $this->sanitizeDate($params['fechaFin'], date('Y-m-d'));
        $tipoDocs     = $params['tipoDocs'] ?? [];
        $almacenes    = $params['almacenes'] ?? [];
        $articulos    = $params['articulos'] ?? [];
        $proveedores  = $params['proveedores'] ?? [];
        $estatus      = strtoupper(trim((string) ($params['estatus'] ?? 'TODOS')));
        $folio        = trim((string) ($params['folio'] ?? ''));
        $cancelados   = strtoupper(trim((string) ($params['cancelados'] ?? 'N')));
        $conciliacion = strtoupper(trim((string) ($params['conciliacion'] ?? 'TODOS')));

        if ($fechaIni > $fechaFin) {
            [$fechaIni, $fechaFin] = [$fechaFin, $fechaIni];
        }

        $allDefs      = $this->getDocumentDefinitions();
        $selectedDefs = [];

        if (empty($tipoDocs)) {
            $selectedDefs = $allDefs;
            $tipoDocTexto = 'TODOS';
        } else {
            foreach ($tipoDocs as $td) {
                $tdUpper = strtoupper($td);
                if (in_array($tdUpper, ['INVENTARIO', 'COMPRAS', 'VENTAS', 'PAGOS'], true)) {
                    foreach ($allDefs as $obj => $def) {
                        if ($def['group'] === $tdUpper) {
                            $selectedDefs[$obj] = $def;
                        }
                    }
                } elseif (isset($allDefs[$td])) {
                    $selectedDefs[$td] = $allDefs[$td];
                }
            }
            if (empty($selectedDefs)) {
                $selectedDefs = $allDefs;
                $tipoDocTexto = 'TODOS';
            } else {
                $tipoDocTexto = $this->summarizeFilterList(array_column($selectedDefs, 'short'), 'TODOS', 5);
            }
        }

        $sucursales    = $this->getAllowedSAPBranches($idUser, $idEmpresa);
        $allowedBplIn  = array_column($sucursales, 'bplId');
        $sucursalTexto = 'TODAS LAS SUCURSALES';
        $conn          = $this->connectODBC($idEmpresa);

        $whereBpl = '';
        if ($bplId !== '' && $bplId !== 'TODOS' && is_numeric($bplId)) {
            $bplInt   = (int) $bplId;
            $whereBpl = " AND h.\"BPLId\" = {$bplInt} ";
            foreach ($sucursales as $s) {
                if ((int) $s['bplId'] === $bplInt) {
                    $sucursalTexto = $s['text'];
                    break;
                }
            }
        } elseif (!empty($allowedBplIn)) {
            $whereBpl = ' AND (h."BPLId" IN (' . implode(',', array_map('intval', $allowedBplIn)) . ') OR h."BPLId" IS NULL) ';
        }

        $sapUserId = $this->resolveLinkedSapUserId($conn, $idEmpresa, $idUser);
        if ($sapUserId > 0) {
            $whereBpl .= " AND (h.\"BPLId\" IS NULL OR EXISTS (SELECT 1 FROM \"USR6\" u6 WHERE u6.\"BPLId\" = h.\"BPLId\" AND u6.\"UserID\" = {$sapUserId})) ";
        }

        $whereItem   = !empty($articulos) ? ' AND UPPER(l."ItemCode") IN (' . $this->buildSqlInList($articulos) . ') ' : '';
        $whereProv   = !empty($proveedores) ? ' AND UPPER(IFNULL(h."CardCode", \'\')) IN (' . $this->buildSqlInList($proveedores) . ') ' : '';
        $whereStatus = ($estatus === 'O' || $estatus === 'C') ? " AND h.\"DocStatus\" = '{$estatus}' " : '';
        $whereCancel = ($cancelados === 'N') ? " AND IFNULL(h.\"CANCELED\", 'N') = 'N' " : '';
        $folioClean  = str_replace("'", "''", strtoupper($folio));
        $almIn       = !empty($almacenes) ? $this->buildSqlInList($almacenes) : '';
        $bplNameExpr = "CAST(COALESCE(NULLIF(b.\"BPLName\", ''), CASE WHEN IFNULL(h.\"BPLId\", 0) > 0 THEN 'Sucursal #' || CAST(h.\"BPLId\" AS NVARCHAR(20)) ELSE 'Sin Sucursal' END) AS NVARCHAR(150))";

        $unionParts = [];
        foreach ($selectedDefs as $objType => $def) {
            $tH       = $def['H'];
            $tL       = $def['L'];
            $docLabel = str_replace("'", "''", $def['name']);

            // Rama Pagos (46: OVPM/VPM2 y 24: ORCT/RCT2)
            if (!empty($def['isPayment'])) {
                if ($almIn !== '' || !empty($articulos)) {
                    continue;
                }

                $isOut   = ($objType === '46');
                $payCode = $isOut ? 'PEF' : 'PRC';
                $tInv    = $isOut ? 'OPCH' : 'OINV';
                $tDpm    = $isOut ? 'ODPO' : 'ODPI';
                $tCn     = $isOut ? 'ORPC' : 'ORIN';
                $invT    = $isOut ? '18' : '13';
                $dpmT    = $isOut ? '204' : '203';
                $cnT     = $isOut ? '19' : '14';

                $whereCancelP = ($cancelados === 'N') ? " AND IFNULL(h.\"Canceled\", 'N') = 'N' " : '';
                $whereStatusP = ($estatus === 'O')
                    ? ' AND IFNULL(h."NoDocSum", 0) > 0.005 '
                    : (($estatus === 'C') ? ' AND IFNULL(h."NoDocSum", 0) <= 0.005 ' : '');

                $refExprP    = "CAST(COALESCE(NULLIF(h.\"TrsfrRef\", ''), NULLIF(h.\"Comments\", ''), '') AS NVARCHAR(255))";
                $whereFolioP = ($folioClean !== '' && $folioClean !== '*')
                    ? " AND (CAST(h.\"DocNum\" AS NVARCHAR(30)) LIKE '%{$folioClean}%' OR UPPER({$refExprP}) LIKE '%{$folioClean}%' OR UPPER(IFNULL(h.\"CardCode\", '')) LIKE '%{$folioClean}%' OR UPPER(IFNULL(h.\"CardName\", '')) LIKE '%{$folioClean}%') "
                    : '';

                $unionParts[] = "
                    SELECT
                        CAST('{$objType}' AS NVARCHAR(20)) AS \"ObjType\",
                        CAST('{$docLabel}' AS NVARCHAR(80)) AS \"TMA_NOMBRE\",
                        h.\"DocEntry\",
                        IFNULL(h.\"TransId\", 0) AS \"TransId\",
                        CAST(h.\"DocNum\" AS NVARCHAR(30)) AS \"MAE_FOLIO\",
                        CAST(CASE WHEN IFNULL(h.\"NoDocSum\", 0) > 0.005 THEN 'O' ELSE 'C' END AS NVARCHAR(2)) AS \"DocStatus\",
                        CAST(IFNULL(h.\"Canceled\", 'N') AS NVARCHAR(2)) AS \"Canceled\",
                        TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"MAE_FECHA\",
                        0 AS \"LineNum\",
                        CAST('TES' AS NVARCHAR(30)) AS \"ALM_CLAVE\",
                        CAST('TESORERÍA / BANCOS' AS NVARCHAR(120)) AS \"ALM_NOMBRE\",
                        CAST('{$payCode}' AS NVARCHAR(50)) AS \"ART_CLAVE\",
                        CAST(CASE
                            WHEN IFNULL(pd.\"DocsApl\", '') <> '' THEN LEFT('Docs. aplicados: ' || pd.\"DocsApl\", 240)
                            WHEN IFNULL(h.\"NoDocSum\", 0) > 0.005 THEN 'Pago a Cuenta (sin factura directa)'
                            ELSE '{$docLabel}'
                        END AS NVARCHAR(250)) AS \"ART_NOMBRE\",
                        CAST(IFNULL(h.\"DocCurr\", 'MXP') AS NVARCHAR(30)) AS \"UNIDAD\",
                        CAST(0 AS DECIMAL(19,6)) AS \"MAD_CANT\",
                        CAST(0 AS DECIMAL(19,6)) AS \"MAD_COSTO\",
                        CAST(IFNULL(h.\"DocTotal\", 0) AS DECIMAL(19,6)) AS \"MAD_PRECIO\",
                        CAST(IFNULL(h.\"DocTotal\", 0) AS DECIMAL(19,6)) AS \"IMPORTE\",
                        {$bplNameExpr} AS \"BPLName\",
                        CAST(IFNULL(h.\"CardCode\", '') AS NVARCHAR(50)) AS \"CardCode\",
                        CAST(IFNULL(h.\"CardName\", '') AS NVARCHAR(150)) AS \"CardName\",
                        {$refExprP} AS \"Referencia\"
                    FROM \"{$tH}\" h
                    LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
                    LEFT JOIN (
                        SELECT p2.\"DocNum\" AS \"PayEntry\",
                               CAST(LEFT(STRING_AGG(CAST(COALESCE(fi.\"DocNum\", fd.\"DocNum\", fn.\"DocNum\", p2.\"DocEntry\") AS NVARCHAR(20)), ', '), 200) AS NVARCHAR(220)) AS \"DocsApl\"
                        FROM \"{$tL}\" p2
                        INNER JOIN \"{$tH}\" p0 ON p0.\"DocEntry\" = p2.\"DocNum\"
                        LEFT JOIN \"{$tInv}\" fi ON p2.\"InvType\" = '{$invT}' AND fi.\"DocEntry\" = p2.\"DocEntry\"
                        LEFT JOIN \"{$tDpm}\" fd ON p2.\"InvType\" = '{$dpmT}' AND fd.\"DocEntry\" = p2.\"DocEntry\"
                        LEFT JOIN \"{$tCn}\"  fn ON p2.\"InvType\" = '{$cnT}'  AND fn.\"DocEntry\" = p2.\"DocEntry\"
                        WHERE p0.\"DocDate\" BETWEEN '{$fechaIni}' AND '{$fechaFin}'
                        GROUP BY p2.\"DocNum\"
                    ) pd ON pd.\"PayEntry\" = h.\"DocEntry\"
                    WHERE h.\"DocDate\" BETWEEN '{$fechaIni}' AND '{$fechaFin}'
                      {$whereBpl} {$whereProv} {$whereStatusP} {$whereCancelP} {$whereFolioP}
                ";
                continue;
            }

            // Rama Inventario, Compras y Ventas
            $whereWhs = '';
            if ($almIn !== '') {
                $whereWhs = $def['isTransfer']
                    ? " AND (UPPER(IFNULL(l.\"WhsCode\", '')) IN ({$almIn}) OR UPPER(IFNULL(l.\"FromWhsCod\", '')) IN ({$almIn})) "
                    : " AND UPPER(IFNULL(l.\"WhsCode\", '')) IN ({$almIn}) ";
            }

            $rawRef = $def['hasNumAtCard']
                ? "COALESCE(NULLIF(h.\"NumAtCard\", ''), NULLIF(h.\"Comments\", ''), '')"
                : "COALESCE(NULLIF(h.\"Ref2\", ''), NULLIF(h.\"Comments\", ''), '')";
            $refExpr = $def['isTransfer']
                ? "CAST(LEFT(CASE WHEN IFNULL(l.\"FromWhsCod\", '') <> '' THEN 'Orig: ' || l.\"FromWhsCod\" || ' -> Dest: ' || IFNULL(l.\"WhsCode\", '') || ' ' || {$rawRef} ELSE {$rawRef} END, 255) AS NVARCHAR(255))"
                : "CAST(LEFT({$rawRef}, 255) AS NVARCHAR(255))";

            $whereFolio = ($folioClean !== '' && $folioClean !== '*')
                ? " AND (CAST(h.\"DocNum\" AS NVARCHAR(30)) LIKE '%{$folioClean}%' OR UPPER({$refExpr}) LIKE '%{$folioClean}%' OR UPPER(IFNULL(h.\"CardCode\", '')) LIKE '%{$folioClean}%' OR UPPER(IFNULL(h.\"CardName\", '')) LIKE '%{$folioClean}%') "
                : '';

            $transCol = ($objType === '22') ? '0' : 'IFNULL(h."TransId", 0)';

            $unionParts[] = "
                SELECT
                    CAST('{$objType}' AS NVARCHAR(20)) AS \"ObjType\",
                    CAST('{$docLabel}' AS NVARCHAR(80)) AS \"TMA_NOMBRE\",
                    h.\"DocEntry\",
                    {$transCol} AS \"TransId\",
                    CAST(h.\"DocNum\" AS NVARCHAR(30)) AS \"MAE_FOLIO\",
                    CAST(IFNULL(h.\"DocStatus\", 'O') AS NVARCHAR(2)) AS \"DocStatus\",
                    CAST(IFNULL(h.\"CANCELED\", 'N') AS NVARCHAR(2)) AS \"Canceled\",
                    TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"MAE_FECHA\",
                    l.\"LineNum\",
                    CAST(IFNULL(l.\"WhsCode\", '') AS NVARCHAR(30)) AS \"ALM_CLAVE\",
                    CAST(IFNULL(w.\"WhsName\", '') AS NVARCHAR(120)) AS \"ALM_NOMBRE\",
                    CAST(IFNULL(l.\"ItemCode\", '') AS NVARCHAR(50)) AS \"ART_CLAVE\",
                    CAST(LEFT(COALESCE(NULLIF(l.\"Dscription\", ''), IFNULL(it.\"ItemName\", ''), ''), 250) AS NVARCHAR(250)) AS \"ART_NOMBRE\",
                    CAST(IFNULL(l.\"unitMsr\", IFNULL(it.\"InvntryUom\", '')) AS NVARCHAR(30)) AS \"UNIDAD\",
                    CAST(IFNULL(l.\"Quantity\", 0) AS DECIMAL(19,6)) AS \"MAD_CANT\",
                    CAST(IFNULL(l.\"StockPrice\", 0) AS DECIMAL(19,6)) AS \"MAD_COSTO\",
                    CAST(IFNULL(l.\"Price\", 0) AS DECIMAL(19,6)) AS \"MAD_PRECIO\",
                    CAST(IFNULL(l.\"LineTotal\", 0) AS DECIMAL(19,6)) AS \"IMPORTE\",
                    {$bplNameExpr} AS \"BPLName\",
                    CAST(IFNULL(h.\"CardCode\", '') AS NVARCHAR(50)) AS \"CardCode\",
                    CAST(IFNULL(h.\"CardName\", '') AS NVARCHAR(150)) AS \"CardName\",
                    {$refExpr} AS \"Referencia\"
                FROM \"{$tH}\" h
                INNER JOIN \"{$tL}\" l ON l.\"DocEntry\" = h.\"DocEntry\"
                LEFT JOIN \"OWHS\" w  ON w.\"WhsCode\"  = l.\"WhsCode\"
                LEFT JOIN \"OITM\" it ON it.\"ItemCode\" = l.\"ItemCode\"
                LEFT JOIN \"OBPL\" b  ON b.\"BPLId\"     = h.\"BPLId\"
                WHERE h.\"DocDate\" BETWEEN '{$fechaIni}' AND '{$fechaFin}'
                  AND IFNULL(l.\"ItemCode\", '') <> ''
                  {$whereBpl} {$whereWhs} {$whereItem} {$whereProv} {$whereStatus} {$whereCancel} {$whereFolio}
            ";
        }

        if (empty($unionParts)) {
            odbc_close($conn);
            return [
                'sucursalTexto' => $sucursalTexto,
                'tipoDocTexto'  => $tipoDocTexto,
                'truncated'     => false,
                'kpis'          => ['totalPartidas' => 0, 'totalDocumentos' => 0, 'totalCantidad' => 0.0, 'totalCosto' => 0.0, 'totalImporte' => 0.0],
                'rows'          => [],
            ];
        }

        $whereConcil = '';
        if ($conciliacion === 'CONCILIADOS') {
            $whereConcil = " AND \"ESTATUS_CONCILIACION\" = 'CONCILIADO' ";
        } elseif ($conciliacion === 'NO_CONCILIADOS') {
            $whereConcil = " AND \"ESTATUS_CONCILIACION\" IN ('NO_CONCILIADO', 'PARCIAL') ";
        }

        $sqlLimit = $limitRows + 1;
        $unionSql = implode("\nUNION ALL\n", $unionParts);

        $fullSql = "
            WITH movs_base AS ({$unionSql}),
            trans_scope AS (
                SELECT DISTINCT \"TransId\", \"CardCode\" FROM movs_base WHERE \"TransId\" > 0 AND \"CardCode\" <> ''
            ),
            jdt_bp AS (
                SELECT j.\"TransId\", j.\"ShortName\",
                       SUM(IFNULL(j.\"Debit\", 0) + IFNULL(j.\"Credit\", 0)) AS \"OrigAmt\",
                       SUM(IFNULL(j.\"BalDueDeb\", 0) + IFNULL(j.\"BalDueCred\", 0)) AS \"OpenAmt\"
                FROM \"JDT1\" j
                INNER JOIN trans_scope ts ON ts.\"TransId\" = j.\"TransId\" AND ts.\"CardCode\" = j.\"ShortName\"
                GROUP BY j.\"TransId\", j.\"ShortName\"
            ),
            recon_distinct AS (
                SELECT DISTINCT r1.\"TransId\", r1.\"ShortName\", r0.\"ReconNum\"
                FROM \"ITR1\" r1
                INNER JOIN \"OITR\" r0 ON r0.\"ReconNum\" = r1.\"ReconNum\" AND r0.\"Canceled\" = 'N'
                INNER JOIN trans_scope ts ON ts.\"TransId\" = r1.\"TransId\" AND ts.\"CardCode\" = r1.\"ShortName\"
            ),
            recon_info AS (
                SELECT rd.\"TransId\", rd.\"ShortName\", COUNT(*) AS \"ReconCount\",
                       CAST(LEFT(STRING_AGG(CAST(rd.\"ReconNum\" AS NVARCHAR(20)), ', '), 200) AS NVARCHAR(220)) AS \"ReconNums\"
                FROM recon_distinct rd
                GROUP BY rd.\"TransId\", rd.\"ShortName\"
            ),
            movs_final AS (
                SELECT m.*,
                       CASE
                           WHEN jb.\"TransId\" IS NULL THEN 'NO_APLICA'
                           WHEN ABS(IFNULL(jb.\"OpenAmt\", 0)) <= 0.005 AND (IFNULL(ri.\"ReconCount\", 0) > 0 OR ABS(IFNULL(jb.\"OrigAmt\", 0)) <= 0.005) THEN 'CONCILIADO'
                           WHEN IFNULL(ri.\"ReconCount\", 0) > 0 AND ABS(IFNULL(jb.\"OpenAmt\", 0)) > 0.005 THEN 'PARCIAL'
                           ELSE 'NO_CONCILIADO'
                       END AS \"ESTATUS_CONCILIACION\",
                       IFNULL(ri.\"ReconNums\", '') AS \"RECON_NUMS\",
                       IFNULL(jb.\"OpenAmt\", 0) AS \"SALDO_PENDIENTE\"
                FROM movs_base m
                LEFT JOIN jdt_bp jb ON jb.\"TransId\" = m.\"TransId\" AND jb.\"ShortName\" = m.\"CardCode\"
                LEFT JOIN recon_info ri ON ri.\"TransId\" = m.\"TransId\" AND ri.\"ShortName\" = m.\"CardCode\"
            )
            SELECT * FROM movs_final
            WHERE 1 = 1 {$whereConcil}
            ORDER BY \"MAE_FECHA\" ASC, \"ObjType\" ASC, \"DocEntry\" ASC, \"LineNum\" ASC
            LIMIT {$sqlLimit}
        ";

        $rows          = [];
        $uniqueDocs    = [];
        $totalCantidad = 0.0;
        $totalCosto    = 0.0;
        $totalImporte  = 0.0;
        $truncated     = false;

        $rs = odbc_exec($conn, $fullSql);
        if ($rs) {
            while ($r = odbc_fetch_array($rs)) {
                if (count($rows) >= $limitRows) {
                    $truncated = true;
                    break;
                }

                $objType  = trim((string) ($r['ObjType'] ?? ''));
                $docEntry = (int) ($r['DocEntry'] ?? 0);
                $def      = $allDefs[$objType] ?? ['short' => $objType, 'badge' => 'secondary'];
                $cancRaw  = trim((string) ($r['Canceled'] ?? 'N'));
                $statRaw  = trim((string) ($r['DocStatus'] ?? 'O'));
                $estatusT = ($cancRaw === 'Y' || $cancRaw === 'C') ? 'CANCELADO' : ($statRaw === 'C' ? 'CERRADO' : 'ABIERTO');

                $cant    = round((float) ($r['MAD_CANT'] ?? 0), 4);
                $costo   = round((float) ($r['MAD_COSTO'] ?? 0), 5);
                $precio  = round((float) ($r['MAD_PRECIO'] ?? 0), 5);
                $importe = round((float) ($r['IMPORTE'] ?? 0), 2);
                if ($importe == 0.0 && $cant > 0 && $precio > 0) {
                    $importe = round($cant * $precio, 2);
                }

                $uniqueDocs["{$objType}_{$docEntry}"] = true;
                $totalCantidad += $cant;
                $totalCosto    += ($cant * $costo);
                $totalImporte  += $importe;

                $rows[] = [
                    'ObjType'              => $objType,
                    'DocEntry'             => $docEntry,
                    'TransId'              => (int) ($r['TransId'] ?? 0),
                    'CodigoCorto'          => $def['short'],
                    'BadgeColor'           => $def['badge'],
                    'TMA_NOMBRE'           => trim($this->toUtf8($r['TMA_NOMBRE'] ?? '')),
                    'ALM_CLAVE'            => trim($this->toUtf8($r['ALM_CLAVE'] ?? '')),
                    'ALM_NOMBRE'           => trim($this->toUtf8($r['ALM_NOMBRE'] ?? '')),
                    'MAE_FOLIO'            => trim($this->toUtf8($r['MAE_FOLIO'] ?? '')),
                    'MAE_ESTATUS'          => $estatusT,
                    'ESTATUS_CONCILIACION' => trim($this->toUtf8($r['ESTATUS_CONCILIACION'] ?? 'NO_APLICA')),
                    'RECON_NUMS'           => trim($this->toUtf8($r['RECON_NUMS'] ?? '')),
                    'SALDO_PENDIENTE'      => round((float) ($r['SALDO_PENDIENTE'] ?? 0), 2),
                    'Cancelado'            => ($cancRaw === 'Y' || $cancRaw === 'C') ? 'S' : 'N',
                    'MAE_FECHA'            => trim($this->toUtf8($r['MAE_FECHA'] ?? '')),
                    'ART_CLAVE'            => trim($this->toUtf8($r['ART_CLAVE'] ?? '')),
                    'ART_NOMBRE'           => trim($this->toUtf8($r['ART_NOMBRE'] ?? '')),
                    'UNIDAD'               => trim($this->toUtf8($r['UNIDAD'] ?? '')),
                    'MAD_CANT'             => $cant,
                    'MAD_COSTO'            => $costo,
                    'MAD_PRECIO'           => $precio,
                    'IMPORTE'              => $importe,
                    'BPLName'              => trim($this->toUtf8($r['BPLName'] ?? '')),
                    'CardCode'             => trim($this->toUtf8($r['CardCode'] ?? '')),
                    'CardName'             => trim($this->toUtf8($r['CardName'] ?? '')),
                    'Referencia'           => trim($this->toUtf8($r['Referencia'] ?? '')),
                ];
            }
            odbc_free_result($rs);
        }

        odbc_close($conn);

        return [
            'sucursalTexto' => $sucursalTexto,
            'tipoDocTexto'  => $tipoDocTexto,
            'truncated'     => $truncated,
            'kpis'          => [
                'totalPartidas'   => count($rows),
                'totalDocumentos' => count($uniqueDocs),
                'totalCantidad'   => round($totalCantidad, 3),
                'totalCosto'      => round($totalCosto, 2),
                'totalImporte'    => round($totalImporte, 2),
            ],
            'rows' => $rows,
        ];
    }

    /* =========================================================================
     * DETALLE DE DOCUMENTOS (INVENTARIO, COMPRAS, VENTAS, PAGOS, PRODUCCIÓN Y ASIENTOS)
     * ========================================================================= */
    private function fetchDocumentDetail($conn, string $objType, int $docEntry, int $transId = 0): array
    {
        $allTables = $this->getAllDocTableMap();

        // 1. Solicitud de Compra (OPRQ / PRQ1)
        if ($objType === '1470000113') {
            $rsH = odbc_exec($conn, "SELECT h.\"DocEntry\", h.\"DocNum\", TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", TO_VARCHAR(h.\"ReqDate\", 'YYYY-MM-DD') AS \"ReqDate\", IFNULL(h.\"Requester\", '') AS \"Requester\", IFNULL(h.\"ReqName\", '') AS \"ReqName\", IFNULL(h.\"Comments\", '') AS \"Comments\", IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\", IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\", IFNULL(h.\"DocCur\", 'MXP') AS \"DocCur\", IFNULL(h.\"DocTotal\", 0) AS \"DocTotal\", IFNULL(h.\"VatSum\", 0) AS \"VatSum\", IFNULL(b.\"BPLName\", '') AS \"BPLName\" FROM \"OPRQ\" h LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\" WHERE h.\"DocEntry\" = {$docEntry} LIMIT 1");
            $header = $rsH ? odbc_fetch_array($rsH) : null;
            if ($rsH) {
                odbc_free_result($rsH);
            }
            if (!$header) {
                throw new Exception("No se encontró la Solicitud de Compra #{$docEntry}.");
            }

            $meta  = $this->getObjTypeMeta($objType);
            $lines = [];
            $rsL   = odbc_exec($conn, "SELECT IFNULL(\"ItemCode\", '') AS \"ItemCode\", IFNULL(\"Dscription\", '') AS \"Dscription\", IFNULL(\"Quantity\", 0) AS \"Quantity\", IFNULL(\"unitMsr\", '') AS \"UnitMsr\", IFNULL(\"WhsCode\", '') AS \"WhsCode\", IFNULL(\"Price\", 0) AS \"Price\", IFNULL(\"LineTotal\", 0) AS \"LineTotal\" FROM \"PRQ1\" WHERE \"DocEntry\" = {$docEntry} ORDER BY \"VisOrder\" ASC, \"LineNum\" ASC");
            if ($rsL) {
                while ($r = odbc_fetch_array($rsL)) {
                    $lines[] = [
                        'itemCode'   => trim($this->toUtf8($r['ItemCode'] ?? '')),
                        'dscription' => trim($this->toUtf8($r['Dscription'] ?? '')),
                        'warehouse'  => trim($this->toUtf8($r['WhsCode'] ?? '')),
                        'quantity'   => round((float) ($r['Quantity'] ?? 0), 4),
                        'uom'        => trim($this->toUtf8($r['UnitMsr'] ?? '')),
                        'cost'       => 0.0,
                        'price'      => round((float) ($r['Price'] ?? 0), 5),
                        'lineTotal'  => round((float) ($r['LineTotal'] ?? 0), 2),
                    ];
                }
                odbc_free_result($rsL);
            }

            $docTotal = round((float) ($header['DocTotal'] ?? 0), 2);
            $vatSum   = round((float) ($header['VatSum'] ?? 0), 2);
            return [
                'mode' => 'MARKETING', 'supportsLog' => true, 'objType' => $objType, 'tipo' => $meta['name'], 'badge' => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'], 'docNum' => trim($this->toUtf8($header['DocNum'] ?? '')), 'transId' => 0, 'cardCode' => '',
                'header' => [
                    'Fecha'           => trim($this->toUtf8($header['DocDate'] ?? '')),
                    'Fecha Requerida' => trim($this->toUtf8($header['ReqDate'] ?? '')),
                    'Solicitante'     => trim($this->toUtf8($header['Requester'] ?? '')) . ' - ' . trim($this->toUtf8($header['ReqName'] ?? '')),
                    'Sucursal'        => trim($this->toUtf8($header['BPLName'] ?? '')),
                    'Estatus'         => in_array($header['Canceled'], ['Y', 'C'], true) ? 'CANCELADO' : ($header['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO'),
                    'Moneda'          => trim($this->toUtf8($header['DocCur'] ?? 'MXP')),
                    'Comentarios'     => trim($this->toUtf8($header['Comments'] ?? '')),
                ],
                'totals' => ['CostoTotal' => 0.0, 'Subtotal' => round($docTotal - $vatSum, 2), 'IVA' => $vatSum, 'Total' => $docTotal],
                'lines'  => $lines,
            ];
        }

        // 2. Orden de Fabricación (OWOR / WOR1 - 202)
        if ($objType === '202') {
            $rsH = odbc_exec($conn, "SELECT h.\"DocEntry\", h.\"DocNum\", TO_VARCHAR(h.\"PostDate\", 'YYYY-MM-DD') AS \"PostDate\", TO_VARCHAR(h.\"DueDate\", 'YYYY-MM-DD') AS \"DueDate\", IFNULL(h.\"ItemCode\", '') AS \"ItemCode\", IFNULL(h.\"ProdName\", '') AS \"ProdName\", IFNULL(h.\"PlannedQty\", 0) AS \"PlannedQty\", IFNULL(h.\"CmpltQty\", 0) AS \"CmpltQty\", IFNULL(h.\"Warehouse\", '') AS \"Warehouse\", IFNULL(h.\"Status\", 'P') AS \"Status\", IFNULL(h.\"Comments\", '') AS \"Comments\" FROM \"OWOR\" h WHERE h.\"DocEntry\" = {$docEntry} LIMIT 1");
            $header = $rsH ? odbc_fetch_array($rsH) : null;
            if ($rsH) {
                odbc_free_result($rsH);
            }
            if (!$header) {
                throw new Exception("No se encontró la Orden de Fabricación #{$docEntry}.");
            }

            $meta  = $this->getObjTypeMeta('202');
            $lines = [];
            $rsL   = @odbc_exec($conn, "SELECT l.\"ItemCode\", IFNULL(it.\"ItemName\", '') AS \"ItemName\", IFNULL(l.\"PlannedQty\", 0) AS \"PlannedQty\", IFNULL(l.\"IssuedQty\", 0) AS \"IssuedQty\", IFNULL(l.\"wareHouse\", '') AS \"WhsCode\", IFNULL(it.\"InvntryUom\", '') AS \"Uom\" FROM \"WOR1\" l LEFT JOIN \"OITM\" it ON it.\"ItemCode\" = l.\"ItemCode\" WHERE l.\"DocEntry\" = {$docEntry} ORDER BY l.\"VisOrder\" ASC, l.\"LineNum\" ASC");
            if ($rsL) {
                while ($r = odbc_fetch_array($rsL)) {
                    $lines[] = [
                        'itemCode' => trim($this->toUtf8($r['ItemCode'] ?? '')), 'dscription' => trim($this->toUtf8($r['ItemName'] ?? '')),
                        'warehouse' => trim($this->toUtf8($r['WhsCode'] ?? '')), 'quantity' => round((float) ($r['PlannedQty'] ?? 0), 4),
                        'uom' => trim($this->toUtf8($r['Uom'] ?? '')), 'cost' => 0.0, 'price' => round((float) ($r['IssuedQty'] ?? 0), 4), 'lineTotal' => 0.0,
                    ];
                }
                odbc_free_result($rsL);
            }

            $stMap = ['P' => 'PLANIFICADA', 'R' => 'LIBERADA', 'L' => 'CERRADA', 'C' => 'CANCELADA'];
            return [
                'mode' => 'MARKETING', 'supportsLog' => false, 'objType' => '202', 'tipo' => $meta['name'], 'badge' => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'], 'docNum' => trim($this->toUtf8($header['DocNum'] ?? '')), 'transId' => 0, 'cardCode' => '',
                'header' => [
                    'Fecha'       => trim($this->toUtf8($header['PostDate'] ?? '')),
                    'Vencimiento' => trim($this->toUtf8($header['DueDate'] ?? '')),
                    'Producto'    => trim($this->toUtf8($header['ItemCode'] ?? '')) . ' - ' . trim($this->toUtf8($header['ProdName'] ?? '')),
                    'Planificado' => $this->fmt($header['PlannedQty'], 3) . ' / Completado: ' . $this->fmt($header['CmpltQty'], 3),
                    'Almacén'     => trim($this->toUtf8($header['Warehouse'] ?? '')),
                    'Estatus'     => $stMap[$header['Status'] ?? 'P'] ?? ($header['Status'] ?? ''),
                    'Comentarios' => trim($this->toUtf8($header['Comments'] ?? '')),
                ],
                'totals' => ['CostoTotal' => 0.0, 'Subtotal' => 0.0, 'IVA' => 0.0, 'Total' => 0.0],
                'lines'  => $lines,
            ];
        }

        // 3. Documentos de Inventario, Compras y Ventas
        if (isset($allTables[$objType])) {
            $cfg      = $allTables[$objType];
            $tH       = $cfg['H'];
            $tL       = $cfg['L'];
            $refCol   = $cfg['hasNumAtCard'] ? 'IFNULL(h."NumAtCard", \'\')' : 'IFNULL(h."Ref2", \'\')';
            $vatCol   = $cfg['hasMoney'] ? 'IFNULL(h."VatSum", 0)' : '0';
            $totalCol = $cfg['isTransfer'] ? '0' : 'IFNULL(h."DocTotal", 0)';
            $curCol   = $cfg['isTransfer'] ? '\'MXP\'' : 'IFNULL(h."DocCur", \'MXP\')';
            $transCol = $cfg['hasTransId'] ? 'IFNULL(h."TransId", 0)' : '0';
            $resCol   = in_array($objType, ['13', '18'], true) ? ', IFNULL(h."isIns", \'N\') AS "isIns", IFNULL(h."InvntSttus", \'\') AS "InvntSttus"' : ', \'N\' AS "isIns", \'\' AS "InvntSttus"';
            $fromWhs  = $cfg['isTransfer'] ? 'IFNULL(l."FromWhsCod", \'\') AS "FromWhsCod",' : '\'\' AS "FromWhsCod",';

            $rsH = odbc_exec($conn, "
                SELECT h.\"DocEntry\", h.\"DocNum\", {$transCol} AS \"TransId\", TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                       TO_VARCHAR(h.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\", IFNULL(h.\"CardCode\", '') AS \"CardCode\",
                       IFNULL(h.\"CardName\", '') AS \"CardName\", {$refCol} AS \"Referencia\", IFNULL(h.\"Comments\", '') AS \"Comments\",
                       IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\", IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\", {$curCol} AS \"DocCur\",
                       {$totalCol} AS \"DocTotal\", {$vatCol} AS \"VatSum\", IFNULL(b.\"BPLName\", '') AS \"BPLName\",
                       IFNULL(u1.\"U_NAME\", IFNULL(u1.\"USER_CODE\", '')) AS \"CreadoPor\",
                       IFNULL(u2.\"U_NAME\", IFNULL(u2.\"USER_CODE\", '')) AS \"ModificadoPor\" {$resCol}
                FROM \"{$tH}\" h
                LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
                LEFT JOIN \"OUSR\" u1 ON u1.\"USERID\" = h.\"UserSign\"
                LEFT JOIN \"OUSR\" u2 ON u2.\"USERID\" = h.\"UserSign2\"
                WHERE h.\"DocEntry\" = {$docEntry} LIMIT 1
            ");
            $header = $rsH ? odbc_fetch_array($rsH) : null;
            if ($rsH) {
                odbc_free_result($rsH);
            }
            if (!$header) {
                throw new Exception("No se encontró el documento ({$objType}) #{$docEntry}.");
            }

            $esReserva = (in_array($objType, ['13', '18'], true) && trim((string) ($header['isIns'] ?? 'N')) === 'Y');
            $meta      = $this->getObjTypeMeta($objType, $esReserva);

            $lines    = [];
            $sumLines = 0.0;
            $sumCost  = 0.0;
            $rsL      = odbc_exec($conn, "
                SELECT l.\"LineNum\", IFNULL(l.\"ItemCode\", '') AS \"ItemCode\",
                       COALESCE(NULLIF(l.\"Dscription\", ''), IFNULL(it.\"ItemName\", ''), '') AS \"Dscription\",
                       IFNULL(l.\"Quantity\", 0) AS \"Quantity\", IFNULL(l.\"unitMsr\", '') AS \"UnitMsr\",
                       IFNULL(l.\"WhsCode\", '') AS \"WhsCode\", IFNULL(w.\"WhsName\", '') AS \"WhsName\", {$fromWhs}
                       IFNULL(l.\"StockPrice\", 0) AS \"StockPrice\", IFNULL(l.\"Price\", 0) AS \"Price\", IFNULL(l.\"LineTotal\", 0) AS \"LineTotal\"
                FROM \"{$tL}\" l
                LEFT JOIN \"OWHS\" w ON w.\"WhsCode\" = l.\"WhsCode\"
                LEFT JOIN \"OITM\" it ON it.\"ItemCode\" = l.\"ItemCode\"
                WHERE l.\"DocEntry\" = {$docEntry} ORDER BY l.\"VisOrder\" ASC, l.\"LineNum\" ASC
            ");
            if ($rsL) {
                while ($r = odbc_fetch_array($rsL)) {
                    $qty   = round((float) ($r['Quantity'] ?? 0), 4);
                    $cost  = round((float) ($r['StockPrice'] ?? 0), 5);
                    $price = round((float) ($r['Price'] ?? 0), 5);
                    $tot   = round((float) ($r['LineTotal'] ?? 0), 2);
                    if ($tot == 0.0 && $qty > 0 && $price > 0) {
                        $tot = round($qty * $price, 2);
                    }
                    $sumLines += $tot;
                    $sumCost  += ($qty * $cost);
                    $whsTxt    = trim($this->toUtf8($r['WhsCode'] ?? ''));
                    $origW     = trim($this->toUtf8($r['FromWhsCod'] ?? ''));

                    $lines[] = [
                        'itemCode'   => trim($this->toUtf8($r['ItemCode'] ?? '')),
                        'dscription' => trim($this->toUtf8($r['Dscription'] ?? '')),
                        'warehouse'  => $origW !== '' ? "{$origW} -> {$whsTxt}" : "{$whsTxt} - " . trim($this->toUtf8($r['WhsName'] ?? '')),
                        'quantity'   => $qty,
                        'uom'        => trim($this->toUtf8($r['UnitMsr'] ?? '')),
                        'cost'       => $cost,
                        'price'      => $price,
                        'lineTotal'  => $tot,
                    ];
                }
                odbc_free_result($rsL);
            }

            $docTotal = round((float) ($header['DocTotal'] ?? 0), 2) ?: round($sumLines, 2);
            $vatSum   = round((float) ($header['VatSum'] ?? 0), 2);
            $canc     = in_array($header['Canceled'] ?? 'N', ['Y', 'C'], true);
            $cardCode = trim($this->toUtf8($header['CardCode'] ?? ''));

            $headerInfo = [
                'Fecha'             => trim($this->toUtf8($header['DocDate'] ?? '')),
                'Vencimiento'       => trim($this->toUtf8($header['DocDueDate'] ?? '')),
                'Estatus'           => $canc ? 'CANCELADO' : (($header['DocStatus'] ?? 'O') === 'C' ? 'CERRADO' : 'ABIERTO'),
                'Sucursal'          => trim($this->toUtf8($header['BPLName'] ?? '')),
                'Proveedor / Socio' => $cardCode !== '' ? "{$cardCode} - " . trim($this->toUtf8($header['CardName'] ?? '')) : '-',
                'Referencia'        => trim($this->toUtf8($header['Referencia'] ?? '')),
                'Moneda'            => trim($this->toUtf8($header['DocCur'] ?? 'MXP')),
                'Creado por'        => trim($this->toUtf8($header['CreadoPor'] ?? '')),
                'Últ. Modif. por'   => trim($this->toUtf8($header['ModificadoPor'] ?? '')),
                'Comentarios'       => trim($this->toUtf8($header['Comments'] ?? '')),
            ];
            if ($esReserva) {
                $headerInfo['Estatus Inv. (Reserva)'] = (trim((string) ($header['InvntSttus'] ?? '')) === 'C') ? 'COMPLETADA' : 'PENDIENTE';
            }

            return [
                'mode' => 'MARKETING', 'supportsLog' => true, 'objType' => $objType, 'tipo' => $meta['name'], 'badge' => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'], 'docNum' => trim($this->toUtf8($header['DocNum'] ?? '')),
                'transId' => (int) ($header['TransId'] ?? 0), 'cardCode' => $cardCode,
                'header' => $headerInfo,
                'totals' => ['CostoTotal' => round($sumCost, 2), 'Subtotal' => round($sumLines, 2), 'IVA' => $vatSum, 'Total' => $docTotal],
                'lines'  => $lines,
            ];
        }

        // 4. Pagos Efectuados (46: OVPM/VPM2) y Pagos Recibidos (24: ORCT/RCT2)
        if ($objType === '46' || $objType === '24') {
            $isOut = ($objType === '46');
            $pH    = $isOut ? 'OVPM' : 'ORCT';
            $pL    = $isOut ? 'VPM2' : 'RCT2';
            $tInv  = $isOut ? 'OPCH' : 'OINV';
            $tDpm  = $isOut ? 'ODPO' : 'ODPI';
            $tCn   = $isOut ? 'ORPC' : 'ORIN';
            $invT  = $isOut ? '18' : '13';
            $dpmT  = $isOut ? '204' : '203';
            $cnT   = $isOut ? '19' : '14';
            $meta  = $this->getObjTypeMeta($objType);

            $rsP = odbc_exec($conn, "
                SELECT p.\"DocEntry\", p.\"DocNum\", IFNULL(p.\"TransId\", 0) AS \"TransId\",
                       TO_VARCHAR(p.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", TO_VARCHAR(p.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\",
                       p.\"CardCode\", p.\"CardName\", IFNULL(p.\"Comments\", '') AS \"Comments\", IFNULL(p.\"Canceled\", 'N') AS \"Canceled\",
                       IFNULL(p.\"DocCurr\", 'MXP') AS \"DocCurr\", IFNULL(p.\"DocTotal\", 0) AS \"DocTotal\",
                       IFNULL(p.\"CashSum\", 0) AS \"CashSum\", IFNULL(p.\"TrsfrSum\", 0) AS \"TrsfrSum\",
                       IFNULL(p.\"CheckSum\", 0) AS \"CheckSum\", IFNULL(p.\"CreditSum\", 0) AS \"CreditSum\",
                       IFNULL(p.\"NoDocSum\", 0) AS \"NoDocSum\", IFNULL(p.\"TrsfrRef\", '') AS \"TrsfrRef\",
                       IFNULL(b.\"BPLName\", '') AS \"BPLName\",
                       IFNULL(u1.\"U_NAME\", IFNULL(u1.\"USER_CODE\", '')) AS \"CreadoPor\",
                       IFNULL(u2.\"U_NAME\", IFNULL(u2.\"USER_CODE\", '')) AS \"ModificadoPor\"
                FROM \"{$pH}\" p
                LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = p.\"BPLId\"
                LEFT JOIN \"OUSR\" u1 ON u1.\"USERID\" = p.\"UserSign\"
                LEFT JOIN \"OUSR\" u2 ON u2.\"USERID\" = p.\"UserSign2\"
                WHERE p.\"DocEntry\" = {$docEntry} LIMIT 1
            ");
            $header = $rsP ? odbc_fetch_array($rsP) : null;
            if ($rsP) {
                odbc_free_result($rsP);
            }
            if (!$header) {
                throw new Exception("No se encontró el {$meta['name']} #{$docEntry}.");
            }

            $lines = [];
            $rsPL  = odbc_exec($conn, "
                SELECT v2.\"InvType\", v2.\"DocEntry\" AS \"TargetEntry\",
                       COALESCE(fi.\"DocNum\", fd.\"DocNum\", fn.\"DocNum\", v2.\"DocEntry\") AS \"TargetDocNum\",
                       TO_VARCHAR(COALESCE(fi.\"DocDate\", fd.\"DocDate\", fn.\"DocDate\"), 'YYYY-MM-DD') AS \"TargetDate\",
                       IFNULL(v2.\"SumApplied\", 0) AS \"SumApplied\",
                       COALESCE(fi.\"DocTotal\", fd.\"DocTotal\", fn.\"DocTotal\", 0) AS \"TargetTotal\",
                       IFNULL(fi.\"isIns\", 'N') AS \"EsReserva\"
                FROM \"{$pL}\" v2
                LEFT JOIN \"{$tInv}\" fi ON v2.\"InvType\" = '{$invT}' AND fi.\"DocEntry\" = v2.\"DocEntry\"
                LEFT JOIN \"{$tDpm}\" fd ON v2.\"InvType\" = '{$dpmT}' AND fd.\"DocEntry\" = v2.\"DocEntry\"
                LEFT JOIN \"{$tCn}\"  fn ON v2.\"InvType\" = '{$cnT}'  AND fn.\"DocEntry\" = v2.\"DocEntry\"
                WHERE v2.\"DocNum\" = {$docEntry} ORDER BY v2.\"InvoiceId\" ASC
            ");
            if ($rsPL) {
                while ($r = odbc_fetch_array($rsPL)) {
                    $iType   = trim((string) ($r['InvType'] ?? ''));
                    $mInv    = $this->getObjTypeMeta($iType, in_array($iType, ['13', '18'], true) && trim((string) ($r['EsReserva'] ?? 'N')) === 'Y');
                    $lines[] = [
                        'objType'  => $iType,
                        'docEntry' => (int) ($r['TargetEntry'] ?? 0),
                        'tipoDoc'  => $mInv['name'],
                        'folio'    => trim($this->toUtf8($r['TargetDocNum'] ?? '')),
                        'fecha'    => trim($this->toUtf8($r['TargetDate'] ?? '')),
                        'totalDoc' => round((float) ($r['TargetTotal'] ?? 0), 2),
                        'aplicado' => round((float) ($r['SumApplied'] ?? 0), 2),
                    ];
                }
                odbc_free_result($rsPL);
            }

            return [
                'mode' => 'PAYMENT', 'supportsLog' => true, 'objType' => $objType, 'tipo' => $meta['name'], 'badge' => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'], 'docNum' => trim($this->toUtf8($header['DocNum'] ?? '')),
                'transId' => (int) ($header['TransId'] ?? 0), 'cardCode' => trim($this->toUtf8($header['CardCode'] ?? '')),
                'header' => [
                    'Fecha'           => trim($this->toUtf8($header['DocDate'] ?? '')),
                    'Vencimiento'     => trim($this->toUtf8($header['DocDueDate'] ?? '')),
                    'Socio'           => trim($this->toUtf8($header['CardCode'] ?? '')) . ' - ' . trim($this->toUtf8($header['CardName'] ?? '')),
                    'Sucursal'        => trim($this->toUtf8($header['BPLName'] ?? '')),
                    'Ref. Transf.'    => trim($this->toUtf8($header['TrsfrRef'] ?? '')),
                    'Estatus'         => $header['Canceled'] === 'Y' ? 'CANCELADO' : 'APLICADO',
                    'Moneda'          => trim($this->toUtf8($header['DocCurr'] ?? 'MXP')),
                    'Creado por'      => trim($this->toUtf8($header['CreadoPor'] ?? '')),
                    'Últ. Modif. por' => trim($this->toUtf8($header['ModificadoPor'] ?? '')),
                    'Comentarios'     => trim($this->toUtf8($header['Comments'] ?? '')),
                ],
                'totals' => [
                    'Efectivo'      => round((float) ($header['CashSum'] ?? 0), 2),
                    'Transferencia' => round((float) ($header['TrsfrSum'] ?? 0), 2),
                    'Cheque'        => round((float) ($header['CheckSum'] ?? 0), 2),
                    'Tarjeta'       => round((float) ($header['CreditSum'] ?? 0), 2),
                    'PagoACuenta'   => round((float) ($header['NoDocSum'] ?? 0), 2),
                    'Total'         => round((float) ($header['DocTotal'] ?? 0), 2),
                ],
                'lines' => $lines,
            ];
        }

        // 5. Asiento Contable (OJDT / JDT1)
        $meta          = $this->getObjTypeMeta($objType);
        $targetTransId = $transId > 0 ? $transId : $docEntry;
        $rsJ           = odbc_exec($conn, "SELECT \"TransId\", \"Number\", TO_VARCHAR(\"RefDate\", 'YYYY-MM-DD') AS \"RefDate\", TO_VARCHAR(\"DueDate\", 'YYYY-MM-DD') AS \"DueDate\", IFNULL(\"Memo\", '') AS \"Memo\", IFNULL(\"Ref1\", '') AS \"Ref1\", IFNULL(\"Ref2\", '') AS \"Ref2\", IFNULL(\"StornoToTr\", 0) AS \"StornoToTr\" FROM \"OJDT\" WHERE \"TransId\" = {$targetTransId} LIMIT 1");
        $header        = $rsJ ? odbc_fetch_array($rsJ) : null;
        if ($rsJ) {
            odbc_free_result($rsJ);
        }
        if (!$header) {
            throw new Exception("No se encontró el Asiento Contable #{$targetTransId}.");
        }

        $lines = [];
        $sumD  = 0.0;
        $sumC  = 0.0;
        $rsJL  = odbc_exec($conn, "SELECT j.\"Account\", IFNULL(a.\"AcctName\", '') AS \"AcctName\", IFNULL(j.\"ShortName\", '') AS \"ShortName\", IFNULL(j.\"LineMemo\", '') AS \"LineMemo\", IFNULL(j.\"Debit\", 0) AS \"Debit\", IFNULL(j.\"Credit\", 0) AS \"Credit\", IFNULL(b.\"BPLName\", '') AS \"BPLName\" FROM \"JDT1\" j LEFT JOIN \"OACT\" a ON a.\"AcctCode\" = j.\"Account\" LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = j.\"BPLId\" WHERE j.\"TransId\" = {$targetTransId} ORDER BY j.\"Line_ID\" ASC");
        if ($rsJL) {
            while ($r = odbc_fetch_array($rsJL)) {
                $d     = round((float) ($r['Debit'] ?? 0), 2);
                $c     = round((float) ($r['Credit'] ?? 0), 2);
                $sumD += $d;
                $sumC += $c;
                $lines[] = [
                    'account'   => trim($this->toUtf8($r['Account'] ?? '')),
                    'acctName'  => trim($this->toUtf8($r['AcctName'] ?? '')),
                    'shortName' => trim($this->toUtf8($r['ShortName'] ?? '')),
                    'memo'      => trim($this->toUtf8($r['LineMemo'] ?? '')),
                    'bplName'   => trim($this->toUtf8($r['BPLName'] ?? '')),
                    'debit'     => $d,
                    'credit'    => $c,
                ];
            }
            odbc_free_result($rsJL);
        }

        return [
            'mode' => 'JOURNAL', 'supportsLog' => false, 'objType' => $objType, 'tipo' => $meta['name'], 'badge' => $meta['badge'],
            'docEntry' => (int) $header['TransId'], 'docNum' => trim($this->toUtf8($header['Number'] ?? $header['TransId'])),
            'transId' => (int) $header['TransId'], 'cardCode' => '',
            'header' => [
                'Fecha Cont.' => trim($this->toUtf8($header['RefDate'] ?? '')),
                'Vencimiento' => trim($this->toUtf8($header['DueDate'] ?? '')),
                'Ref. 1'      => trim($this->toUtf8($header['Ref1'] ?? '')),
                'Ref. 2'      => trim($this->toUtf8($header['Ref2'] ?? '')),
                'Estatus'     => (int) ($header['StornoToTr'] ?? 0) > 0 ? 'ANULADO / STORNO' : 'CONTABILIZADO',
                'Comentarios' => trim($this->toUtf8($header['Memo'] ?? '')),
            ],
            'totals' => ['TotalDebito' => round($sumD, 2), 'TotalCredito' => round($sumC, 2)],
            'lines'  => $lines,
        ];
    }

    /* =========================================================================
     * MAPA DE RELACIONES (INVENTARIO, COMPRAS, VENTAS, PAGOS Y RECONCILIACIONES)
     * ========================================================================= */
    private function buildRelationshipMap($conn, string $rootObjType, int $rootDocEntry, string $cardCode = ''): array
    {
        $rootKey = "{$rootObjType}_{$rootDocEntry}";
        $nodes   = [];
        $edges   = [];
        $edgeSet = [];
        $visited = [];
        $queue   = [['objType' => $rootObjType, 'docEntry' => $rootDocEntry, 'depth' => 0]];

        $addEdge = function (string $from, string $to, string $label, string $type = 'flow') use (&$edges, &$edgeSet) {
            if ($from === $to || isset($edgeSet["{$from}=>{$to}|{$type}"])) {
                return;
            }
            $edgeSet["{$from}=>{$to}|{$type}"] = true;
            $edges[]                           = ['from' => $from, 'to' => $to, 'label' => $label, 'type' => $type];
        };

        $lineTableMap = [
            '59' => 'IGN1', '60' => 'IGE1', '67' => 'WTR1',
            '540000006' => 'PQT1', '22' => 'POR1', '20' => 'PDN1', '21' => 'RPD1', '18' => 'PCH1', '19' => 'RPC1', '204' => 'DPO1',
            '17' => 'RDR1', '15' => 'DLN1', '16' => 'RDN1', '13' => 'INV1', '14' => 'RIN1', '203' => 'DPI1',
        ];
        $targetTablesByBase = [
            '1250000001' => ['67' => 'WTR1'],
            '202'        => ['59' => 'IGN1', '60' => 'IGE1'],
            '1470000113' => ['540000006' => 'PQT1', '22' => 'POR1'],
            '540000006'  => ['22' => 'POR1', '20' => 'PDN1', '18' => 'PCH1'],
            '22'         => ['20' => 'PDN1', '18' => 'PCH1', '204' => 'DPO1'],
            '20'         => ['18' => 'PCH1', '21' => 'RPD1'],
            '18'         => ['20' => 'PDN1', '19' => 'RPC1'],
            '204'        => ['19' => 'RPC1'],
            '23'         => ['17' => 'RDR1', '15' => 'DLN1', '13' => 'INV1'],
            '17'         => ['15' => 'DLN1', '13' => 'INV1', '203' => 'DPI1'],
            '15'         => ['13' => 'INV1', '16' => 'RDN1'],
            '13'         => ['15' => 'DLN1', '14' => 'RIN1'],
            '203'        => ['14' => 'RIN1'],
        ];
        $allowedBaseTypes = '1470000113, 540000006, 22, 20, 21, 18, 204, 23, 17, 15, 16, 13, 203, 1250000001, 202, 67, 59, 60';

        while (!empty($queue) && count($nodes) < 150) {
            $item     = array_shift($queue);
            $objType  = (string) $item['objType'];
            $docEntry = (int) $item['docEntry'];
            $depth    = (int) $item['depth'];
            $key      = "{$objType}_{$docEntry}";

            if ($docEntry <= 0 || isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            $summary = $this->fetchDocSummary($conn, $objType, $docEntry);
            if (!$summary) {
                continue;
            }

            $summary['id']        = $key;
            $summary['isCurrent'] = ($key === $rootKey);
            $nodes[$key]          = $summary;
            if ($cardCode === '' && !empty($summary['cardCode'])) {
                $cardCode = $summary['cardCode'];
            }
            if ($depth >= 3) {
                continue;
            }

            // A) Documentos Base
            if (isset($lineTableMap[$objType])) {
                $rs = @odbc_exec($conn, "SELECT DISTINCT \"BaseType\", \"BaseEntry\" FROM \"{$lineTableMap[$objType]}\" WHERE \"DocEntry\" = {$docEntry} AND \"BaseEntry\" IS NOT NULL AND \"BaseType\" IN ({$allowedBaseTypes}) LIMIT 60");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $bType  = (string) (int) $r['BaseType'];
                        $bEntry = (int) $r['BaseEntry'];
                        $bKey   = "{$bType}_{$bEntry}";
                        $lbl    = (($objType === '20' && $bType === '18') || ($objType === '15' && $bType === '13'))
                            ? 'Mov. de Reserva'
                            : ($bType === '202' ? ($objType === '60' ? 'Consumo Prod.' : 'Recibo Prod.') : 'Copiado a');
                        $addEdge($bKey, $key, $lbl, 'flow');
                        if (!isset($visited[$bKey])) {
                            $queue[] = ['objType' => $bType, 'docEntry' => $bEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }

            // B) Documentos Destino
            if (isset($targetTablesByBase[$objType])) {
                $objInt = (int) $objType;
                foreach ($targetTablesByBase[$objType] as $tType => $tTable) {
                    $rs = @odbc_exec($conn, "SELECT DISTINCT \"DocEntry\" FROM \"{$tTable}\" WHERE \"BaseType\" = {$objInt} AND \"BaseEntry\" = {$docEntry} LIMIT 80");
                    if ($rs) {
                        while ($r = odbc_fetch_array($rs)) {
                            $tEntry = (int) $r['DocEntry'];
                            $tKey   = "{$tType}_{$tEntry}";
                            $lbl    = (($objType === '18' && (string) $tType === '20') || ($objType === '13' && (string) $tType === '15')) ? 'Mov. de Reserva' : 'Generó';
                            $addEdge($key, $tKey, $lbl, 'flow');
                            if (!isset($visited[$tKey])) {
                                $queue[] = ['objType' => (string) $tType, 'docEntry' => $tEntry, 'depth' => $depth + 1];
                            }
                        }
                        odbc_free_result($rs);
                    }
                }
            }

            // C) Anticipos (PCH9: 18<->204, INV9: 13<->203)
            $dpmRules = [
                '18'  => ['tbl' => 'PCH9', 'col' => 'BaseAbs',  'where' => 'DocEntry', 'target' => '204', 'obj' => 204, 'dir' => 'in'],
                '204' => ['tbl' => 'PCH9', 'col' => 'DocEntry', 'where' => 'BaseAbs',  'target' => '18',  'obj' => 204, 'dir' => 'out'],
                '13'  => ['tbl' => 'INV9', 'col' => 'BaseAbs',  'where' => 'DocEntry', 'target' => '203', 'obj' => 203, 'dir' => 'in'],
                '203' => ['tbl' => 'INV9', 'col' => 'DocEntry', 'where' => 'BaseAbs',  'target' => '13',  'obj' => 203, 'dir' => 'out'],
            ];
            if (isset($dpmRules[$objType])) {
                $dr = $dpmRules[$objType];
                $rs = @odbc_exec($conn, "SELECT \"{$dr['col']}\" AS \"RelEntry\", SUM(IFNULL(\"DrawnSum\", 0)) AS \"DrawnSum\" FROM \"{$dr['tbl']}\" WHERE \"{$dr['where']}\" = {$docEntry} AND \"ObjType\" = {$dr['obj']} GROUP BY \"{$dr['col']}\"");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $relEntry = (int) $r['RelEntry'];
                        $relKey   = "{$dr['target']}_{$relEntry}";
                        $lbl      = 'Anticipo $' . number_format((float) $r['DrawnSum'], 2);
                        $dr['dir'] === 'in' ? $addEdge($relKey, $key, $lbl, 'downpayment') : $addEdge($key, $relKey, $lbl, 'downpayment');
                        if (!isset($visited[$relKey])) {
                            $queue[] = ['objType' => $dr['target'], 'docEntry' => $relEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }

            // D) Pagos Efectuados (46: VPM2) y Pagos Recibidos (24: RCT2)
            foreach ([['46', 'VPM2', 'Pago', ['18', '19', '204', '30']], ['24', 'RCT2', 'Cobro', ['13', '14', '203', '30']]] as [$pObj, $pTbl, $pLbl, $invTypes]) {
                if (in_array($objType, $invTypes, true)) {
                    $rs = @odbc_exec($conn, "SELECT \"DocNum\" AS \"PayEntry\", SUM(IFNULL(\"SumApplied\", 0)) AS \"SumApplied\" FROM \"{$pTbl}\" WHERE \"InvType\" = '{$objType}' AND \"DocEntry\" = {$docEntry} GROUP BY \"DocNum\" LIMIT 40");
                    if ($rs) {
                        while ($r = odbc_fetch_array($rs)) {
                            $pKey = "{$pObj}_" . (int) $r['PayEntry'];
                            $addEdge($key, $pKey, "{$pLbl} \$" . number_format((float) $r['SumApplied'], 2), 'payment');
                            if (!isset($visited[$pKey])) {
                                $queue[] = ['objType' => $pObj, 'docEntry' => (int) $r['PayEntry'], 'depth' => $depth + 1];
                            }
                        }
                        odbc_free_result($rs);
                    }
                } elseif ($objType === $pObj) {
                    $rs = @odbc_exec($conn, "SELECT \"InvType\", \"DocEntry\" AS \"TargetEntry\", SUM(IFNULL(\"SumApplied\", 0)) AS \"SumApplied\" FROM \"{$pTbl}\" WHERE \"DocNum\" = {$docEntry} GROUP BY \"InvType\", \"DocEntry\" LIMIT 60");
                    if ($rs) {
                        while ($r = odbc_fetch_array($rs)) {
                            $tType = trim((string) $r['InvType']);
                            $tKey  = "{$tType}_" . (int) $r['TargetEntry'];
                            $addEdge($tKey, $key, "{$pLbl} \$" . number_format((float) $r['SumApplied'], 2), 'payment');
                            if (!isset($visited[$tKey])) {
                                $queue[] = ['objType' => $tType, 'docEntry' => (int) $r['TargetEntry'], 'depth' => $depth + 1];
                            }
                        }
                        odbc_free_result($rs);
                    }
                }
            }

            // E) Asiento directo de inventario y Reconciliaciones Internas
            $nodeTransId = (int) ($summary['transId'] ?? 0);
            if ($nodeTransId > 0 && in_array($objType, ['59', '60', '67'], true)) {
                $jKey = "30_{$nodeTransId}";
                $addEdge($key, $jKey, 'Póliza Cont.', 'recon');
                if (!isset($visited[$jKey])) {
                    $queue[] = ['objType' => '30', 'docEntry' => $nodeTransId, 'depth' => $depth + 1];
                }
            }

            if ($nodeTransId > 0 && $cardCode !== '') {
                $ccClean = str_replace("'", "''", $cardCode);
                $rs      = @odbc_exec($conn, "SELECT DISTINCT r0.\"ReconNum\", j2.\"TransType\", IFNULL(j2.\"CreatedBy\", j2.\"TransId\") AS \"RelEntry\", r2.\"ReconSum\" FROM \"ITR1\" r1 INNER JOIN \"OITR\" r0 ON r0.\"ReconNum\" = r1.\"ReconNum\" AND r0.\"Canceled\" = 'N' INNER JOIN \"ITR1\" r2 ON r2.\"ReconNum\" = r1.\"ReconNum\" AND r2.\"TransId\" <> r1.\"TransId\" AND r2.\"ShortName\" = '{$ccClean}' INNER JOIN \"OJDT\" j2 ON j2.\"TransId\" = r2.\"TransId\" WHERE r1.\"TransId\" = {$nodeTransId} AND r1.\"ShortName\" = '{$ccClean}' LIMIT 20");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $rType  = trim((string) $r['TransType']);
                        $rEntry = (int) $r['RelEntry'];
                        $rKey   = "{$rType}_{$rEntry}";
                        if (isset($edgeSet["{$key}=>{$rKey}|payment"]) || isset($edgeSet["{$rKey}=>{$key}|payment"]) || isset($edgeSet["{$key}=>{$rKey}|downpayment"]) || isset($edgeSet["{$rKey}=>{$key}|downpayment"])) {
                            continue;
                        }
                        $addEdge($key, $rKey, 'Reconc. #' . trim((string) $r['ReconNum']) . ' ($' . number_format((float) $r['ReconSum'], 2) . ')', 'recon');
                        if (!isset($visited[$rKey])) {
                            $queue[] = ['objType' => $rType, 'docEntry' => $rEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }
        }

        $validEdges = array_values(array_filter($edges, fn($e) => isset($nodes[$e['from']]) && isset($nodes[$e['to']])));
        foreach ($validEdges as $e) {
            if (!empty($nodes[$e['from']]['isReserve']) && in_array($nodes[$e['to']]['objType'], ['20', '15'], true)) {
                $nodes[$e['from']]['stage'] = 3;
                $nodes[$e['to']]['stage']   = 4;
            }
            if (in_array($nodes[$e['from']]['objType'], ['20', '15'], true) && in_array($nodes[$e['to']]['objType'], ['21', '16'], true)) {
                $nodes[$e['to']]['stage'] = max(5, (int) $nodes[$e['from']]['stage'] + 1);
            }
        }

        return ['rootId' => $rootKey, 'nodes' => array_values($nodes), 'edges' => $validEdges];
    }

    private function fetchDocSummary($conn, string $objType, int $docEntry): ?array
    {
        if ($objType === '1470000113') {
            $rs = @odbc_exec($conn, "SELECT \"DocEntry\", \"DocNum\", TO_VARCHAR(\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", IFNULL(\"DocTotal\", 0) AS \"DocTotal\", IFNULL(\"DocCur\", 'MXP') AS \"DocCur\", IFNULL(\"DocStatus\", 'O') AS \"DocStatus\", IFNULL(\"CANCELED\", 'N') AS \"Canceled\", IFNULL(\"ReqName\", '') AS \"Ref\" FROM \"OPRQ\" WHERE \"DocEntry\" = {$docEntry} LIMIT 1");
            $r  = $rs ? odbc_fetch_array($rs) : null;
            if ($rs) {
                odbc_free_result($rs);
            }
            if (!$r) {
                return null;
            }
            $meta = $this->getObjTypeMeta($objType);
            $canc = in_array($r['Canceled'], ['Y', 'C'], true);
            return [
                'objType' => $objType, 'isReserve' => false, 'docEntry' => (int) $r['DocEntry'], 'docNum' => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId' => 0, 'cardCode' => '', 'tipo' => $meta['name'], 'code' => $meta['code'], 'badge' => $meta['badge'], 'icon' => $meta['icon'],
                'stage' => $meta['stage'], 'fecha' => trim($this->toUtf8($r['DocDate'] ?? '')), 'total' => round((float) $r['DocTotal'], 2), 'saldo' => 0.0,
                'moneda' => trim($this->toUtf8($r['DocCur'] ?? 'MXP')), 'estatus' => $canc ? 'CANCELADO' : ($r['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO'),
                'cancelado' => $canc, 'ref' => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        if ($objType === '202') {
            $rs = @odbc_exec($conn, "SELECT \"DocEntry\", \"DocNum\", TO_VARCHAR(\"PostDate\", 'YYYY-MM-DD') AS \"DocDate\", IFNULL(\"PlannedQty\", 0) AS \"PlannedQty\", IFNULL(\"Status\", 'P') AS \"Status\", IFNULL(\"ItemCode\", '') AS \"Ref\" FROM \"OWOR\" WHERE \"DocEntry\" = {$docEntry} LIMIT 1");
            $r  = $rs ? odbc_fetch_array($rs) : null;
            if ($rs) {
                odbc_free_result($rs);
            }
            if (!$r) {
                return null;
            }
            $meta  = $this->getObjTypeMeta('202');
            $stMap = ['P' => 'PLANIFICADA', 'R' => 'LIBERADA', 'L' => 'CERRADA', 'C' => 'CANCELADA'];
            $st    = $r['Status'] ?? 'P';
            return [
                'objType' => '202', 'isReserve' => false, 'docEntry' => (int) $r['DocEntry'], 'docNum' => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId' => 0, 'cardCode' => '', 'tipo' => $meta['name'], 'code' => $meta['code'], 'badge' => $meta['badge'], 'icon' => $meta['icon'],
                'stage' => $meta['stage'], 'fecha' => trim($this->toUtf8($r['DocDate'] ?? '')), 'total' => round((float) $r['PlannedQty'], 2), 'saldo' => 0.0,
                'moneda' => 'UND', 'estatus' => $stMap[$st] ?? $st, 'cancelado' => ($st === 'C'), 'ref' => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        $allTables = $this->getAllDocTableMap();
        if (isset($allTables[$objType])) {
            $cfg      = $allTables[$objType];
            $transCol = $cfg['hasTransId'] ? 'IFNULL("TransId", 0)' : '0';
            $totCol   = $cfg['isTransfer'] ? '0' : 'IFNULL("DocTotal", 0)';
            $paidCol  = $cfg['hasMoney'] ? 'IFNULL("PaidToDate", 0)' : '0';
            $curCol   = $cfg['isTransfer'] ? '\'MXP\'' : 'IFNULL("DocCur", \'MXP\')';
            $refCol   = $cfg['hasNumAtCard'] ? 'IFNULL("NumAtCard", \'\')' : 'IFNULL("Ref2", \'\')';
            $resCols  = in_array($objType, ['13', '18'], true) ? ', IFNULL("isIns", \'N\') AS "isIns", IFNULL("InvntSttus", \'\') AS "InvntSttus"' : ', \'N\' AS "isIns", \'\' AS "InvntSttus"';

            $rs = @odbc_exec($conn, "SELECT \"DocEntry\", \"DocNum\", {$transCol} AS \"TransId\", IFNULL(\"CardCode\", '') AS \"CardCode\", TO_VARCHAR(\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", {$totCol} AS \"DocTotal\", {$paidCol} AS \"PaidToDate\", {$curCol} AS \"DocCur\", IFNULL(\"DocStatus\", 'O') AS \"DocStatus\", IFNULL(\"CANCELED\", 'N') AS \"Canceled\", {$refCol} AS \"Ref\" {$resCols} FROM \"{$cfg['H']}\" WHERE \"DocEntry\" = {$docEntry} LIMIT 1");
            $r  = $rs ? odbc_fetch_array($rs) : null;
            if ($rs) {
                odbc_free_result($rs);
            }
            if (!$r) {
                return null;
            }

            $esReserva  = (in_array($objType, ['13', '18'], true) && trim((string) ($r['isIns'] ?? 'N')) === 'Y');
            $meta       = $this->getObjTypeMeta($objType, $esReserva);
            $total      = round((float) $r['DocTotal'], 2);
            $paid       = round((float) $r['PaidToDate'], 2);
            $canc       = in_array($r['Canceled'], ['Y', 'C'], true);
            $estatusTxt = $canc ? 'CANCELADO' : ($r['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO');
            if ($esReserva && !$canc) {
                $estatusTxt .= (trim((string) ($r['InvntSttus'] ?? '')) === 'C') ? ' / SURTIDA' : ' / PENDIENTE';
            }

            return [
                'objType' => $objType, 'isReserve' => $esReserva, 'docEntry' => (int) $r['DocEntry'], 'docNum' => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId' => (int) $r['TransId'], 'cardCode' => trim($this->toUtf8($r['CardCode'] ?? '')), 'tipo' => $meta['name'],
                'code' => $meta['code'], 'badge' => $meta['badge'], 'icon' => $meta['icon'], 'stage' => $meta['stage'],
                'fecha' => trim($this->toUtf8($r['DocDate'] ?? '')), 'total' => $total, 'saldo' => $cfg['hasMoney'] ? round($total - $paid, 2) : 0.0,
                'moneda' => trim($this->toUtf8($r['DocCur'] ?? 'MXP')), 'estatus' => $estatusTxt, 'cancelado' => $canc, 'ref' => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        if ($objType === '46' || $objType === '24') {
            $tbl  = ($objType === '46') ? 'OVPM' : 'ORCT';
            $meta = $this->getObjTypeMeta($objType);
            $rs   = @odbc_exec($conn, "SELECT \"DocEntry\", \"DocNum\", IFNULL(\"TransId\", 0) AS \"TransId\", \"CardCode\", TO_VARCHAR(\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", IFNULL(\"DocTotal\", 0) AS \"DocTotal\", IFNULL(\"NoDocSum\", 0) AS \"NoDocSum\", IFNULL(\"DocCurr\", 'MXP') AS \"DocCur\", IFNULL(\"Canceled\", 'N') AS \"Canceled\", IFNULL(\"TrsfrRef\", '') AS \"Ref\" FROM \"{$tbl}\" WHERE \"DocEntry\" = {$docEntry} LIMIT 1");
            $r    = $rs ? odbc_fetch_array($rs) : null;
            if ($rs) {
                odbc_free_result($rs);
            }
            if (!$r) {
                return null;
            }
            $canc = ($r['Canceled'] === 'Y');
            return [
                'objType' => $objType, 'isReserve' => false, 'docEntry' => (int) $r['DocEntry'], 'docNum' => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId' => (int) $r['TransId'], 'cardCode' => trim($this->toUtf8($r['CardCode'] ?? '')), 'tipo' => $meta['name'],
                'code' => $meta['code'], 'badge' => $meta['badge'], 'icon' => $meta['icon'], 'stage' => $meta['stage'],
                'fecha' => trim($this->toUtf8($r['DocDate'] ?? '')), 'total' => round((float) $r['DocTotal'], 2), 'saldo' => round((float) $r['NoDocSum'], 2),
                'moneda' => trim($this->toUtf8($r['DocCur'] ?? 'MXP')), 'estatus' => $canc ? 'CANCELADO' : 'APLICADO', 'cancelado' => $canc,
                'ref' => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        $meta = $this->getObjTypeMeta($objType);
        $rs   = @odbc_exec($conn, "SELECT \"TransId\", \"Number\", TO_VARCHAR(\"RefDate\", 'YYYY-MM-DD') AS \"RefDate\", IFNULL(\"LocTotal\", 0) AS \"LocTotal\", IFNULL(\"Memo\", '') AS \"Memo\", IFNULL(\"StornoToTr\", 0) AS \"StornoToTr\" FROM \"OJDT\" WHERE \"TransId\" = {$docEntry} LIMIT 1");
        $r    = $rs ? odbc_fetch_array($rs) : null;
        if ($rs) {
            odbc_free_result($rs);
        }
        if (!$r) {
            return null;
        }
        $canc = ((int) ($r['StornoToTr'] ?? 0) > 0);
        return [
            'objType' => $objType, 'isReserve' => false, 'docEntry' => (int) $r['TransId'], 'docNum' => trim($this->toUtf8($r['Number'] ?? $r['TransId'])),
            'transId' => (int) $r['TransId'], 'cardCode' => '', 'tipo' => $meta['name'], 'code' => $meta['code'], 'badge' => $meta['badge'],
            'icon' => $meta['icon'], 'stage' => $meta['stage'], 'fecha' => trim($this->toUtf8($r['RefDate'] ?? '')),
            'total' => round((float) $r['LocTotal'], 2), 'saldo' => 0.0, 'moneda' => 'MXP',
            'estatus' => $canc ? 'ANULADO' : 'CONTABILIZADO', 'cancelado' => $canc, 'ref' => trim($this->toUtf8($r['Memo'] ?? '')),
        ];
    }

    /* =========================================================================
     * LOG DE MODIFICACIONES (ADOC/ADO1 + AUDITORÍA DE PAGOS OVPM/ORCT/AJDT)
     * ========================================================================= */
    private function fetchDocumentChangeLog($conn, string $objType, int $docEntry): array
    {
        if ($objType === '46' || $objType === '24') {
            return $this->fetchPaymentChangeLog($conn, $objType, $docEntry);
        }

        $allTables = $this->getAllDocTableMap();
        if (!isset($allTables[$objType])) {
            throw new Exception("El tipo de documento ({$objType}) no cuenta con historial de modificaciones en ADOC.");
        }

        $cfg      = $allTables[$objType];
        $meta     = $this->getObjTypeMeta($objType);
        $objClean = str_replace("'", "''", $objType);

        // 1. Versiones históricas (ADOC)
        $refColAdoc        = $cfg['hasNumAtCard'] ? 'IFNULL(a."NumAtCard", \'\')' : 'IFNULL(a."Ref2", \'\')';
        $historicalHeaders = [];
        $rsAdoc            = @odbc_exec($conn, "
            SELECT a.\"LogInstanc\", a.\"DocNum\", TO_VARCHAR(a.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                   TO_VARCHAR(a.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\", TO_VARCHAR(a.\"CreateDate\", 'YYYY-MM-DD') AS \"CreateDate\",
                   IFNULL(a.\"CreateTS\", 0) AS \"CreateTS\", TO_VARCHAR(a.\"UpdateDate\", 'YYYY-MM-DD') AS \"UpdateDate\",
                   IFNULL(a.\"UpdateTS\", 0) AS \"UpdateTS\", IFNULL(a.\"DocStatus\", 'O') AS \"DocStatus\",
                   IFNULL(a.\"CANCELED\", 'N') AS \"Canceled\", IFNULL(a.\"CardCode\", '') AS \"CardCode\",
                   IFNULL(a.\"CardName\", '') AS \"CardName\", {$refColAdoc} AS \"Referencia\", IFNULL(a.\"Comments\", '') AS \"Comments\",
                   IFNULL(a.\"DocTotal\", 0) AS \"DocTotal\", IFNULL(a.\"DocCur\", 'MXP') AS \"DocCur\", IFNULL(b.\"BPLName\", '') AS \"BPLName\",
                   IFNULL(u1.\"USER_CODE\", '') AS \"CreateUserCode\", IFNULL(u1.\"U_NAME\", '') AS \"CreateUserName\",
                   IFNULL(u2.\"USER_CODE\", '') AS \"UpdateUserCode\", IFNULL(u2.\"U_NAME\", '') AS \"UpdateUserName\"
            FROM \"ADOC\" a
            LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = a.\"BPLId\"
            LEFT JOIN \"OUSR\" u1 ON u1.\"USERID\" = a.\"UserSign\"
            LEFT JOIN \"OUSR\" u2 ON u2.\"USERID\" = a.\"UserSign2\"
            WHERE a.\"ObjType\" = '{$objClean}' AND a.\"DocEntry\" = {$docEntry}
            ORDER BY a.\"LogInstanc\" ASC
        ");
        if ($rsAdoc) {
            while ($r = odbc_fetch_array($rsAdoc)) {
                $inst                     = (int) ($r['LogInstanc'] ?? 0);
                $historicalHeaders[$inst] = $this->normalizeLogHeaderRow($r, $inst, false);
            }
            odbc_free_result($rsAdoc);
        }

        // 2. Partidas históricas (ADO1)
        $historicalLines = [];
        if (!empty($historicalHeaders)) {
            $rsAdo1 = @odbc_exec($conn, "SELECT l.\"LogInstanc\", l.\"LineNum\", IFNULL(l.\"ItemCode\", '') AS \"ItemCode\", IFNULL(l.\"Dscription\", '') AS \"Dscription\", IFNULL(l.\"WhsCode\", '') AS \"WhsCode\", IFNULL(l.\"Quantity\", 0) AS \"Quantity\", IFNULL(l.\"StockPrice\", 0) AS \"StockPrice\", IFNULL(l.\"Price\", 0) AS \"Price\", IFNULL(l.\"LineTotal\", 0) AS \"LineTotal\", IFNULL(l.\"LineStatus\", 'O') AS \"LineStatus\" FROM \"ADO1\" l WHERE l.\"ObjType\" = '{$objClean}' AND l.\"DocEntry\" = {$docEntry} ORDER BY l.\"LogInstanc\" ASC, l.\"VisOrder\" ASC, l.\"LineNum\" ASC");
            if ($rsAdo1) {
                while ($r = odbc_fetch_array($rsAdo1)) {
                    $historicalLines[(int) ($r['LogInstanc'] ?? 0)][(int) ($r['LineNum'] ?? 0)] = $this->normalizeLogLineRow($r);
                }
                odbc_free_result($rsAdo1);
            }
        }

        // 3. Versión actual (tabla viva)
        $refColLive = $cfg['hasNumAtCard'] ? 'IFNULL(h."NumAtCard", \'\')' : 'IFNULL(h."Ref2", \'\')';
        $totColLive = $cfg['isTransfer'] ? '0' : 'IFNULL(h."DocTotal", 0)';
        $curColLive = $cfg['isTransfer'] ? '\'MXP\'' : 'IFNULL(h."DocCur", \'MXP\')';
        $cardCols   = $cfg['hasCard'] ? 'IFNULL(h."CardCode", \'\') AS "CardCode", IFNULL(h."CardName", \'\') AS "CardName",' : '\'\' AS "CardCode", \'\' AS "CardName",';

        $rsLiveH = odbc_exec($conn, "
            SELECT h.\"DocNum\", TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", TO_VARCHAR(h.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\",
                   TO_VARCHAR(h.\"CreateDate\", 'YYYY-MM-DD') AS \"CreateDate\", IFNULL(h.\"CreateTS\", 0) AS \"CreateTS\",
                   TO_VARCHAR(h.\"UpdateDate\", 'YYYY-MM-DD') AS \"UpdateDate\", IFNULL(h.\"UpdateTS\", 0) AS \"UpdateTS\",
                   IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\", IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\", {$cardCols}
                   {$refColLive} AS \"Referencia\", IFNULL(h.\"Comments\", '') AS \"Comments\", {$totColLive} AS \"DocTotal\",
                   {$curColLive} AS \"DocCur\", IFNULL(b.\"BPLName\", '') AS \"BPLName\",
                   IFNULL(u1.\"USER_CODE\", '') AS \"CreateUserCode\", IFNULL(u1.\"U_NAME\", '') AS \"CreateUserName\",
                   IFNULL(u2.\"USER_CODE\", '') AS \"UpdateUserCode\", IFNULL(u2.\"U_NAME\", '') AS \"UpdateUserName\"
            FROM \"{$cfg['H']}\" h
            LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
            LEFT JOIN \"OUSR\" u1 ON u1.\"USERID\" = h.\"UserSign\"
            LEFT JOIN \"OUSR\" u2 ON u2.\"USERID\" = h.\"UserSign2\"
            WHERE h.\"DocEntry\" = {$docEntry} LIMIT 1
        ");
        $liveRow = $rsLiveH ? odbc_fetch_array($rsLiveH) : null;
        if ($rsLiveH) {
            odbc_free_result($rsLiveH);
        }
        if (!$liveRow) {
            throw new Exception("No se encontró el documento ({$meta['name']}) #{$docEntry}.");
        }

        $liveLines = [];
        $rsLiveL   = @odbc_exec($conn, "SELECT l.\"LineNum\", IFNULL(l.\"ItemCode\", '') AS \"ItemCode\", IFNULL(l.\"Dscription\", '') AS \"Dscription\", IFNULL(l.\"WhsCode\", '') AS \"WhsCode\", IFNULL(l.\"Quantity\", 0) AS \"Quantity\", IFNULL(l.\"StockPrice\", 0) AS \"StockPrice\", IFNULL(l.\"Price\", 0) AS \"Price\", IFNULL(l.\"LineTotal\", 0) AS \"LineTotal\", IFNULL(l.\"LineStatus\", 'O') AS \"LineStatus\" FROM \"{$cfg['L']}\" l WHERE l.\"DocEntry\" = {$docEntry} ORDER BY l.\"VisOrder\" ASC, l.\"LineNum\" ASC");
        if ($rsLiveL) {
            while ($r = odbc_fetch_array($rsLiveL)) {
                $liveLines[(int) ($r['LineNum'] ?? 0)] = $this->normalizeLogLineRow($r);
            }
            odbc_free_result($rsLiveL);
        }

        // 4. Línea de tiempo y comparativo de cambios
        $timeline = [];
        foreach ($historicalHeaders as $inst => $hdr) {
            $hdr['lines'] = $historicalLines[$inst] ?? [];
            $timeline[]   = $hdr;
        }
        $liveHdr          = $this->normalizeLogHeaderRow($liveRow, count($timeline) + 1, true);
        $liveHdr['lines'] = $liveLines;
        $timeline[]       = $liveHdr;

        $headerLabels = [
            'estatus' => 'Estatus', 'docDate' => 'Fecha Contab.', 'docDueDate' => 'Fecha Venc.',
            'cardCode' => 'Socio Negocio', 'referencia' => 'Referencia', 'comments' => 'Comentarios',
            'docTotal' => 'Total Doc.', 'bplName' => 'Sucursal',
        ];

        $versionsOut = [];
        $prev        = null;
        foreach ($timeline as $idx => $ver) {
            $headerChanges = [];
            $lineChanges   = [];

            if ($prev === null) {
                $eventType = 'CREACIÓN';
                $actor     = $ver['createUser'] ?: $ver['updateUser'];
                $eventDate = $ver['createDate'];
                $eventTime = $ver['createTime'];
            } else {
                $eventType = 'MODIFICACIÓN';
                $actor     = $ver['updateUser'] ?: $ver['createUser'];
                $eventDate = $ver['updateDate'] ?: $ver['createDate'];
                $eventTime = $ver['updateTime'] ?: $ver['createTime'];

                foreach ($headerLabels as $field => $label) {
                    $oldVal = (string) ($prev[$field] ?? '');
                    $newVal = (string) ($ver[$field] ?? '');
                    if ($field === 'docTotal') {
                        if (abs((float) $oldVal - (float) $newVal) > 0.005) {
                            $headerChanges[] = ['campo' => $label, 'antes' => number_format((float) $oldVal, 2), 'ahora' => number_format((float) $newVal, 2)];
                        }
                    } elseif ($oldVal !== $newVal) {
                        $headerChanges[] = ['campo' => $label, 'antes' => $oldVal !== '' ? $oldVal : '(vacío)', 'ahora' => $newVal !== '' ? $newVal : '(vacío)'];
                    }
                }

                $prevLines = $prev['lines'] ?? [];
                $currLines = $ver['lines'] ?? [];
                $allLnKeys = array_unique(array_merge(array_keys($prevLines), array_keys($currLines)));
                sort($allLnKeys);

                foreach ($allLnKeys as $ln) {
                    $pL = $prevLines[$ln] ?? null;
                    $cL = $currLines[$ln] ?? null;
                    if ($pL === null && $cL !== null) {
                        $lineChanges[] = ['lineNum' => $ln + 1, 'accion' => 'AGREGADA', 'item' => "{$cL['itemCode']} - {$cL['dscription']}", 'detalle' => "Cant: {$cL['quantity']} | Alm: {$cL['whsCode']} | Precio: {$cL['price']}"];
                    } elseif ($pL !== null && $cL === null) {
                        $lineChanges[] = ['lineNum' => $ln + 1, 'accion' => 'ELIMINADA', 'item' => "{$pL['itemCode']} - {$pL['dscription']}", 'detalle' => "Cant previa: {$pL['quantity']} | Alm: {$pL['whsCode']}"];
                    } elseif ($pL && $cL) {
                        $diffs = [];
                        if ($pL['itemCode'] !== $cL['itemCode']) {
                            $diffs[] = "Artículo: {$pL['itemCode']} → {$cL['itemCode']}";
                        }
                        if ($pL['whsCode'] !== $cL['whsCode']) {
                            $diffs[] = "Almacén: {$pL['whsCode']} → {$cL['whsCode']}";
                        }
                        if (abs($pL['quantity'] - $cL['quantity']) > 0.0001) {
                            $diffs[] = "Cantidad: {$pL['quantity']} → {$cL['quantity']}";
                        }
                        if (abs($pL['price'] - $cL['price']) > 0.0001) {
                            $diffs[] = "Precio: {$pL['price']} → {$cL['price']}";
                        }
                        if (abs($pL['stockPrice'] - $cL['stockPrice']) > 0.0001) {
                            $diffs[] = "Costo: {$pL['stockPrice']} → {$cL['stockPrice']}";
                        }
                        if ($pL['lineStatus'] !== $cL['lineStatus']) {
                            $stL     = ['O' => 'Abierta', 'C' => 'Cerrada'];
                            $diffs[] = 'Estatus Línea: ' . ($stL[$pL['lineStatus']] ?? $pL['lineStatus']) . ' → ' . ($stL[$cL['lineStatus']] ?? $cL['lineStatus']);
                        }
                        if (!empty($diffs)) {
                            $lineChanges[] = ['lineNum' => $ln + 1, 'accion' => 'MODIFICADA', 'item' => "{$cL['itemCode']} - {$cL['dscription']}", 'detalle' => implode(' | ', $diffs)];
                        }
                    }
                }
            }

            $versionsOut[] = [
                'version' => $idx + 1, 'logInstanc' => $ver['logInstanc'], 'isCurrent' => $ver['isCurrent'], 'eventType' => $eventType,
                'usuario' => $actor ?: 'SAP / Sistema', 'fecha' => $eventDate, 'hora' => $eventTime, 'estatus' => $ver['estatus'],
                'docTotal' => $ver['docTotal'], 'moneda' => $ver['docCur'], 'referencia' => $ver['referencia'], 'comments' => $ver['comments'],
                'headerChanges' => $headerChanges, 'lineChanges' => $lineChanges, 'linesCount' => count($ver['lines']), 'lines' => array_values($ver['lines']),
            ];
            $prev = $ver;
        }

        return [
            'objType'            => $objType,
            'tipo'               => $meta['name'],
            'badge'              => $meta['badge'],
            'docEntry'           => $docEntry,
            'docNum'             => $liveHdr['docNum'],
            'totalVersions'      => count($versionsOut),
            'totalModifications' => max(0, count($versionsOut) - 1),
            'creadoPor'          => $liveHdr['createUser'] ?: 'SAP',
            'fechaCreacion'      => trim($liveHdr['createDate'] . ' ' . $liveHdr['createTime']),
            'ultimaModifPor'     => $liveHdr['updateUser'] ?: $liveHdr['createUser'],
            'fechaUltimaModif'   => trim(($liveHdr['updateDate'] ?: $liveHdr['createDate']) . ' ' . ($liveHdr['updateTime'] ?: $liveHdr['createTime'])),
            'versions'           => array_reverse($versionsOut),
        ];
    }

    private function fetchPaymentChangeLog($conn, string $objType, int $docEntry): array
    {
        $tbl  = ($objType === '46') ? 'OVPM' : 'ORCT';
        $meta = $this->getObjTypeMeta($objType);

        $rsP = odbc_exec($conn, "
            SELECT p.\"DocEntry\", p.\"DocNum\", IFNULL(p.\"TransId\", 0) AS \"TransId\",
                   TO_VARCHAR(p.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\", TO_VARCHAR(p.\"CreateDate\", 'YYYY-MM-DD') AS \"CreateDate\",
                   TO_VARCHAR(p.\"UpdateDate\", 'YYYY-MM-DD') AS \"UpdateDate\", IFNULL(p.\"DocTime\", 0) AS \"DocTime\",
                   IFNULL(p.\"Canceled\", 'N') AS \"Canceled\", IFNULL(p.\"DocTotal\", 0) AS \"DocTotal\",
                   IFNULL(p.\"DocCurr\", 'MXP') AS \"DocCurr\", IFNULL(p.\"TrsfrRef\", '') AS \"Referencia\",
                   IFNULL(p.\"Comments\", '') AS \"Comments\",
                   IFNULL(u1.\"USER_CODE\", '') AS \"CreateUserCode\", IFNULL(u1.\"U_NAME\", '') AS \"CreateUserName\",
                   IFNULL(u2.\"USER_CODE\", '') AS \"UpdateUserCode\", IFNULL(u2.\"U_NAME\", '') AS \"UpdateUserName\"
            FROM \"{$tbl}\" p
            LEFT JOIN \"OUSR\" u1 ON u1.\"USERID\" = p.\"UserSign\"
            LEFT JOIN \"OUSR\" u2 ON u2.\"USERID\" = p.\"UserSign2\"
            WHERE p.\"DocEntry\" = {$docEntry} LIMIT 1
        ");
        $pRow = $rsP ? odbc_fetch_array($rsP) : null;
        if ($rsP) {
            odbc_free_result($rsP);
        }
        if (!$pRow) {
            throw new Exception("No se encontró el {$meta['name']} #{$docEntry}.");
        }

        $cCode      = trim($this->toUtf8($pRow['CreateUserCode'] ?? ''));
        $cName      = trim($this->toUtf8($pRow['CreateUserName'] ?? ''));
        $uCode      = trim($this->toUtf8($pRow['UpdateUserCode'] ?? ''));
        $uName      = trim($this->toUtf8($pRow['UpdateUserName'] ?? ''));
        $createUser = $cName !== '' ? ($cCode !== '' ? "{$cCode} - {$cName}" : $cName) : ($cCode ?: 'SAP');
        $updateUser = $uName !== '' ? ($uCode !== '' ? "{$uCode} - {$uName}" : $uName) : ($uCode ?: $createUser);
        $createDate = trim($this->toUtf8($pRow['CreateDate'] ?? $pRow['DocDate'] ?? ''));
        $updateDate = trim($this->toUtf8($pRow['UpdateDate'] ?? ''));
        $docTime    = $this->formatSapTime($pRow['DocTime'] ?? 0);
        $docTotal   = round((float) ($pRow['DocTotal'] ?? 0), 2);
        $moneda     = trim($this->toUtf8($pRow['DocCurr'] ?? 'MXP'));
        $transId    = (int) ($pRow['TransId'] ?? 0);

        $timeline = [];
        if ($transId > 0) {
            $rsAj = @odbc_exec($conn, "SELECT a.\"LogInstanc\", TO_VARCHAR(a.\"RefDate\", 'YYYY-MM-DD') AS \"RefDate\", TO_VARCHAR(a.\"UpdateDate\", 'YYYY-MM-DD') AS \"UpdateDate\", IFNULL(a.\"Memo\", '') AS \"Memo\", IFNULL(a.\"Ref1\", '') AS \"Ref1\", IFNULL(a.\"Ref2\", '') AS \"Ref2\", IFNULL(a.\"LocTotal\", 0) AS \"LocTotal\", IFNULL(u2.\"USER_CODE\", '') AS \"UCode\", IFNULL(u2.\"U_NAME\", '') AS \"UName\" FROM \"AJDT\" a LEFT JOIN \"OUSR\" u2 ON u2.\"USERID\" = a.\"UserSign2\" WHERE a.\"TransId\" = {$transId} ORDER BY a.\"LogInstanc\" ASC");
            if ($rsAj) {
                while ($ar = odbc_fetch_array($rsAj)) {
                    $ac         = trim($this->toUtf8($ar['UCode'] ?? ''));
                    $an         = trim($this->toUtf8($ar['UName'] ?? ''));
                    $timeline[] = [
                        'logInstanc' => (int) ($ar['LogInstanc'] ?? 0), 'isCurrent' => false,
                        'usuario'    => $an !== '' ? ($ac !== '' ? "{$ac} - {$an}" : $an) : ($ac ?: $createUser),
                        'fecha'      => trim($this->toUtf8($ar['UpdateDate'] ?? $ar['RefDate'] ?? $createDate)), 'hora' => '',
                        'estatus'    => 'APLICADO', 'docTotal' => round((float) ($ar['LocTotal'] ?? $docTotal), 2),
                        'referencia' => trim($this->toUtf8($ar['Ref2'] ?? $ar['Ref1'] ?? '')),
                        'comments'   => trim($this->toUtf8($ar['Memo'] ?? '')),
                    ];
                }
                odbc_free_result($rsAj);
            }
        }

        $timeline[] = [
            'logInstanc' => count($timeline) + 1, 'isCurrent' => true,
            'usuario'    => $updateDate !== '' ? $updateUser : $createUser,
            'fecha'      => $updateDate !== '' ? $updateDate : $createDate,
            'hora'       => $updateDate !== '' ? '' : $docTime,
            'estatus'    => ($pRow['Canceled'] ?? 'N') === 'Y' ? 'CANCELADO' : 'APLICADO',
            'docTotal'   => $docTotal,
            'referencia' => trim($this->toUtf8($pRow['Referencia'] ?? '')),
            'comments'   => trim($this->toUtf8($pRow['Comments'] ?? '')),
        ];

        $versionsOut = [];
        $prev        = null;
        foreach ($timeline as $idx => $ver) {
            $headerChanges = [];
            if ($prev === null) {
                $eventType = 'CREACIÓN';
            } else {
                $eventType = 'MODIFICACIÓN';
                if ($prev['estatus'] !== $ver['estatus']) {
                    $headerChanges[] = ['campo' => 'Estatus', 'antes' => $prev['estatus'], 'ahora' => $ver['estatus']];
                }
                if ($prev['referencia'] !== $ver['referencia']) {
                    $headerChanges[] = ['campo' => 'Referencia', 'antes' => $prev['referencia'] ?: '(vacío)', 'ahora' => $ver['referencia'] ?: '(vacío)'];
                }
                if ($prev['comments'] !== $ver['comments']) {
                    $headerChanges[] = ['campo' => 'Comentarios', 'antes' => $prev['comments'] ?: '(vacío)', 'ahora' => $ver['comments'] ?: '(vacío)'];
                }
                if (abs($prev['docTotal'] - $ver['docTotal']) > 0.005) {
                    $headerChanges[] = ['campo' => 'Total Pago', 'antes' => number_format($prev['docTotal'], 2), 'ahora' => number_format($ver['docTotal'], 2)];
                }
            }

            $versionsOut[] = [
                'version' => $idx + 1, 'logInstanc' => $ver['logInstanc'], 'isCurrent' => $ver['isCurrent'], 'eventType' => $eventType,
                'usuario' => $ver['usuario'], 'fecha' => $ver['fecha'], 'hora' => $ver['hora'], 'estatus' => $ver['estatus'],
                'docTotal' => $ver['docTotal'], 'moneda' => $moneda, 'referencia' => $ver['referencia'], 'comments' => $ver['comments'],
                'headerChanges' => $headerChanges, 'lineChanges' => [], 'linesCount' => 0, 'lines' => [],
            ];
            $prev = $ver;
        }

        return [
            'objType'            => $objType,
            'tipo'               => $meta['name'],
            'badge'              => $meta['badge'],
            'docEntry'           => $docEntry,
            'docNum'             => trim($this->toUtf8($pRow['DocNum'] ?? '')),
            'totalVersions'      => count($versionsOut),
            'totalModifications' => max(0, count($versionsOut) - 1),
            'creadoPor'          => $createUser,
            'fechaCreacion'      => trim($createDate . ' ' . $docTime),
            'ultimaModifPor'     => $updateDate !== '' ? $updateUser : $createUser,
            'fechaUltimaModif'   => $updateDate !== '' ? $updateDate : trim($createDate . ' ' . $docTime),
            'versions'           => array_reverse($versionsOut),
        ];
    }

    private function normalizeLogHeaderRow(array $r, int $inst, bool $isCurrent): array
    {
        $canc     = in_array(trim((string) ($r['Canceled'] ?? 'N')), ['Y', 'C'], true);
        $cCode    = trim($this->toUtf8($r['CreateUserCode'] ?? ''));
        $cName    = trim($this->toUtf8($r['CreateUserName'] ?? ''));
        $uCode    = trim($this->toUtf8($r['UpdateUserCode'] ?? ''));
        $uName    = trim($this->toUtf8($r['UpdateUserName'] ?? ''));
        $cardCode = trim($this->toUtf8($r['CardCode'] ?? ''));

        return [
            'logInstanc' => $inst, 'isCurrent' => $isCurrent,
            'docNum'     => trim($this->toUtf8($r['DocNum'] ?? '')),
            'docDate'    => trim($this->toUtf8($r['DocDate'] ?? '')),
            'docDueDate' => trim($this->toUtf8($r['DocDueDate'] ?? '')),
            'createDate' => trim($this->toUtf8($r['CreateDate'] ?? '')),
            'createTime' => $this->formatSapTime($r['CreateTS'] ?? 0),
            'updateDate' => trim($this->toUtf8($r['UpdateDate'] ?? '')),
            'updateTime' => $this->formatSapTime($r['UpdateTS'] ?? 0),
            'estatus'    => $canc ? 'CANCELADO' : (trim((string) ($r['DocStatus'] ?? 'O')) === 'C' ? 'CERRADO' : 'ABIERTO'),
            'cardCode'   => $cardCode !== '' ? "{$cardCode} - " . trim($this->toUtf8($r['CardName'] ?? '')) : '',
            'referencia' => trim($this->toUtf8($r['Referencia'] ?? '')),
            'comments'   => trim($this->toUtf8($r['Comments'] ?? '')),
            'docTotal'   => round((float) ($r['DocTotal'] ?? 0), 2),
            'docCur'     => trim($this->toUtf8($r['DocCur'] ?? 'MXP')),
            'bplName'    => trim($this->toUtf8($r['BPLName'] ?? '')),
            'createUser' => $cName !== '' ? ($cCode !== '' ? "{$cCode} - {$cName}" : $cName) : $cCode,
            'updateUser' => $uName !== '' ? ($uCode !== '' ? "{$uCode} - {$uName}" : $uName) : $uCode,
        ];
    }

    private function normalizeLogLineRow(array $r): array
    {
        return [
            'lineNum'    => (int) ($r['LineNum'] ?? 0),
            'itemCode'   => trim($this->toUtf8($r['ItemCode'] ?? '')),
            'dscription' => trim($this->toUtf8($r['Dscription'] ?? '')),
            'whsCode'    => trim($this->toUtf8($r['WhsCode'] ?? '')),
            'quantity'   => round((float) ($r['Quantity'] ?? 0), 4),
            'stockPrice' => round((float) ($r['StockPrice'] ?? 0), 5),
            'price'      => round((float) ($r['Price'] ?? 0), 5),
            'lineTotal'  => round((float) ($r['LineTotal'] ?? 0), 2),
            'lineStatus' => trim((string) ($r['LineStatus'] ?? 'O')),
        ];
    }

    private function formatSapTime($ts): string
    {
        $num = (int) $ts;
        if ($num <= 0) {
            return '';
        }
        $str = str_pad((string) $num, ($num > 2359 ? 6 : 4), '0', STR_PAD_LEFT);
        return strlen($str) === 6
            ? substr($str, 0, 2) . ':' . substr($str, 2, 2) . ':' . substr($str, 4, 2)
            : substr($str, 0, 2) . ':' . substr($str, 2, 2);
    }

    private function getObjTypeMeta(string $objType, bool $isReserve = false): array
    {
        if ($objType === '18' && $isReserve) {
            return ['code' => 'RES-P', 'name' => 'Factura Reserva Prov.', 'badge' => 'reserve', 'icon' => 'fa-boxes', 'stage' => 3];
        }
        if ($objType === '13' && $isReserve) {
            return ['code' => 'RES-C', 'name' => 'Factura Reserva Cli.', 'badge' => 'reserve', 'icon' => 'fa-boxes', 'stage' => 3];
        }

        $map = [
            '1250000001' => ['code' => 'SOL-TR',  'name' => 'Solicitud Traslado',     'badge' => 'secondary', 'icon' => 'fa-clipboard-list',      'stage' => 1],
            '202'        => ['code' => 'OF',      'name' => 'Orden de Fabricación',   'badge' => 'info',      'icon' => 'fa-cogs',                'stage' => 2],
            '59'         => ['code' => 'ENT-INV', 'name' => 'Entrada Mercancía Inv.', 'badge' => 'success',   'icon' => 'fa-arrow-circle-down',   'stage' => 3],
            '60'         => ['code' => 'SAL-INV', 'name' => 'Salida Mercancía Inv.',  'badge' => 'danger',    'icon' => 'fa-arrow-circle-up',     'stage' => 3],
            '67'         => ['code' => 'TRANSF',  'name' => 'Transferencia Stock',    'badge' => 'info',      'icon' => 'fa-exchange-alt',        'stage' => 3],
            '1470000113' => ['code' => 'SOL',     'name' => 'Solicitud de Compra',    'badge' => 'secondary', 'icon' => 'fa-clipboard-list',      'stage' => 1],
            '540000006'  => ['code' => 'OFC',     'name' => 'Oferta de Compra',       'badge' => 'secondary', 'icon' => 'fa-file-alt',            'stage' => 1],
            '22'         => ['code' => 'OC',      'name' => 'Orden de Compra',        'badge' => 'info',      'icon' => 'fa-shopping-bag',        'stage' => 2],
            '20'         => ['code' => 'EM',      'name' => 'Entrada Mercancía OP',   'badge' => 'primary',   'icon' => 'fa-truck-loading',       'stage' => 3],
            '21'         => ['code' => 'DEV-C',   'name' => 'Devolución Compras',     'badge' => 'dark',      'icon' => 'fa-dolly',               'stage' => 5],
            '204'        => ['code' => 'ANT-P',   'name' => 'Anticipo Proveedor',     'badge' => 'warning',   'icon' => 'fa-hand-holding-usd',    'stage' => 4],
            '18'         => ['code' => 'FP',      'name' => 'Factura Proveedor',      'badge' => 'danger',    'icon' => 'fa-file-invoice-dollar', 'stage' => 4],
            '19'         => ['code' => 'NCP',     'name' => 'Nota Crédito Prov.',     'badge' => 'purple',    'icon' => 'fa-undo-alt',            'stage' => 5],
            '46'         => ['code' => 'PEF',     'name' => 'Pago Efectuado',         'badge' => 'success',   'icon' => 'fa-money-check-alt',     'stage' => 5],
            '23'         => ['code' => 'COT',     'name' => 'Oferta de Venta',        'badge' => 'secondary', 'icon' => 'fa-file-signature',      'stage' => 1],
            '17'         => ['code' => 'PED',     'name' => 'Orden de Venta',         'badge' => 'info',      'icon' => 'fa-shopping-cart',       'stage' => 2],
            '15'         => ['code' => 'ENT-V',   'name' => 'Entrega (Ventas)',       'badge' => 'primary',   'icon' => 'fa-truck',               'stage' => 3],
            '16'         => ['code' => 'DEV-V',   'name' => 'Devolución (Ventas)',    'badge' => 'dark',      'icon' => 'fa-undo',                'stage' => 5],
            '203'        => ['code' => 'ANT-C',   'name' => 'Anticipo Cliente',       'badge' => 'warning',   'icon' => 'fa-hand-holding-usd',    'stage' => 4],
            '13'         => ['code' => 'FC',      'name' => 'Factura Cliente',        'badge' => 'danger',    'icon' => 'fa-file-invoice-dollar', 'stage' => 4],
            '14'         => ['code' => 'NCC',     'name' => 'Nota Crédito Cliente',   'badge' => 'purple',    'icon' => 'fa-undo-alt',            'stage' => 5],
            '24'         => ['code' => 'PRC',     'name' => 'Pago Recibido',          'badge' => 'success',   'icon' => 'fa-money-bill-wave',     'stage' => 5],
            '30'         => ['code' => 'ASI',     'name' => 'Asiento Contable',       'badge' => 'dark',      'icon' => 'fa-book',                'stage' => 5],
        ];

        return $map[$objType] ?? ['code' => "T{$objType}", 'name' => "Documento ({$objType})", 'badge' => 'secondary', 'icon' => 'fa-file', 'stage' => 5];
    }

    /* =========================================================================
     * UTILIDADES, CONEXIÓN ODBC Y SUCURSALES
     * ========================================================================= */
    private function normalizeMultiParam($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $items = is_array($raw) ? $raw : explode(',', (string) $raw);
        $clean = [];
        foreach ($items as $item) {
            $val = trim((string) $item);
            if ($val !== '' && strtoupper($val) !== 'TODOS') {
                $clean[] = $val;
            }
        }
        return array_values(array_unique($clean));
    }

    private function buildSqlInList(array $values, bool $upper = true): string
    {
        return implode(', ', array_map(
            fn($v) => "'" . str_replace("'", "''", $upper ? strtoupper($v) : $v) . "'",
            $values
        ));
    }

    private function summarizeFilterList(array $values, string $default = 'TODOS', int $maxItems = 4): string
    {
        if (empty($values)) {
            return $default;
        }
        if (count($values) <= $maxItems) {
            return implode(', ', $values);
        }
        return implode(', ', array_slice($values, 0, $maxItems)) . ' (+' . (count($values) - $maxItems) . ' más)';
    }

    private function getAllowedSAPBranches(int $idUser, int $idEmpresa): array
    {
        $db         = \Config\Database::connect();
        $allowedBpl = [];

        if ($db->tableExists('usuarios_sucursal') && $db->tableExists('link_sap_branchoffice')) {
            $userBranches = $db->table('usuarios_sucursal')
                ->select('idSucursal')->where('idUsuario', $idUser)->where('status', 'on')
                ->get()->getResultArray();

            if (!empty($userBranches)) {
                $enlBuilder = $this->linkSapBranchOffice->builder();
                $this->applyCompanyFilter($enlBuilder, 'link_sap_branchoffice', $idEmpresa);
                $links = $enlBuilder->whereIn('idBranchOffice', array_column($userBranches, 'idSucursal'))
                    ->where('deleted_at', null)->get()->getResultArray();

                foreach ($links as $l) {
                    if (!empty($l['idBranchOfficeSAP'])) {
                        $allowedBpl[] = (int) $l['idBranchOfficeSAP'];
                    }
                }
            }
        }

        $branches = [];
        try {
            $conn  = $this->connectODBC($idEmpresa);
            $where = ' WHERE "Disabled" = \'N\' ' . (!empty($allowedBpl) ? ' AND "BPLId" IN (' . implode(',', array_unique($allowedBpl)) . ') ' : '');
            $rs    = @odbc_exec($conn, 'SELECT "BPLId", "BPLName" FROM "OBPL" ' . $where . ' ORDER BY "BPLId" ASC');
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $id         = (int) $row['BPLId'];
                    $branches[] = ['bplId' => $id, 'text' => "{$id} - " . trim($this->toUtf8($row['BPLName'] ?? ''))];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);
        } catch (Throwable $ignore) {
        }

        return $branches;
    }

    private function resolveLinkedSapUserId($conn, int $idEmpresa, int $idUser): int
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('user_sap_link')) {
                return 0;
            }

            $builder = $this->userSapLink->builder();
            $this->applyCompanyFilter($builder, 'user_sap_link', $idEmpresa);
            $link = $builder->where('iduser', $idUser)->where('deleted_at', null)->get()->getRowArray();
            if (!$link || empty($link['sapuser'])) {
                return 0;
            }

            $sapUser = trim((string) $link['sapuser']);
            $uid     = 0;
            if (is_numeric($sapUser)) {
                $uid = (int) $sapUser;
            } else {
                $codeClean = str_replace("'", "''", $sapUser);
                $rsOusr    = @odbc_exec($conn, "SELECT \"USERID\" FROM \"OUSR\" WHERE UPPER(\"USER_CODE\") = UPPER('{$codeClean}') LIMIT 1");
                if ($rsOusr && ($row = odbc_fetch_array($rsOusr))) {
                    $uid = (int) ($row['USERID'] ?? 0);
                    odbc_free_result($rsOusr);
                }
            }

            if ($uid > 0) {
                $rsChk = @odbc_exec($conn, "SELECT COUNT(*) AS \"Cnt\" FROM \"USR6\" WHERE \"UserID\" = {$uid}");
                if ($rsChk && ($r = odbc_fetch_array($rsChk))) {
                    $cnt = (int) ($r['Cnt'] ?? 0);
                    odbc_free_result($rsChk);
                    if ($cnt > 0) {
                        return $uid;
                    }
                }
            }
        } catch (Throwable $ignore) {
        }

        return 0;
    }

    private function connectODBC(int $idEmpresa = 0)
    {
        $tableSL = $this->serviceLayerModel->table ?? 'sapservicelayer';
        $builder = $this->serviceLayerModel->builder();
        if ($idEmpresa > 0) {
            $this->applyCompanyFilter($builder, $tableSL, $idEmpresa);
        }
        $dataConect = $builder->where('deleted_at', null)->get()->getRowArray()
            ?: $this->serviceLayerModel->where('deleted_at', null)->first();

        if (!$dataConect) {
            throw new Exception('No se encontró configuración de conexión SAP.');
        }

        $conn = odbc_connect($dataConect['nameODBC'], $dataConect['userODBC'], $dataConect['passwordODBC']);
        if (!$conn) {
            throw new Exception('Error conexión ODBC: ' . odbc_errormsg());
        }
        if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"')) {
            throw new Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
        }

        return $conn;
    }

    private function prepararImagenTCPDF(array $rutasRelativas, string $cacheKey): string
    {
        foreach ($rutasRelativas as $rel) {
            $full = ROOTPATH . ltrim($rel, '/');
            if (!is_file($full)) {
                continue;
            }
            if (extension_loaded('gd') && strtolower(pathinfo($full, PATHINFO_EXTENSION)) === 'png') {
                $tmpDir = WRITEPATH . 'cache/tcpdf_img/';
                if (!is_dir($tmpDir)) {
                    @mkdir($tmpDir, 0775, true);
                }
                $dest = $tmpDir . $cacheKey . '.jpg';
                $img  = @imagecreatefrompng($full);
                if ($img) {
                    $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
                    imagejpeg($bg, $dest, 92);
                    imagedestroy($img);
                    imagedestroy($bg);
                    return $dest;
                }
            }
            return $full;
        }
        return '';
    }

    private function sanitizeDate(string $date, string $default): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $default;
    }

    private function fmt($num, int $decimals = 2): string
    {
        return number_format((float) $num, $decimals, '.', ',');
    }

    private function escPdf(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    private function toUtf8($value): string
    {
        if (is_null($value)) {
            return '';
        }
        return mb_check_encoding((string) $value, 'UTF-8')
            ? (string) $value
            : mb_convert_encoding((string) $value, 'UTF-8', 'ISO-8859-1');
    }
}