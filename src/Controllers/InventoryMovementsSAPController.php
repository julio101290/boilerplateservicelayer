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

    /**
     * Filtra de forma segura por empresa detectando la columna correspondiente.
     */
    private function applyCompanyFilter($target, string $tableName, $companyVal)
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($tableName)) {
            return $target;
        }

        $col = null;
        if ($db->fieldExists('idEmpresa', $tableName)) {
            $col = 'idEmpresa';
        } elseif ($db->fieldExists('companie', $tableName)) {
            $col = 'companie';
        } elseif ($db->fieldExists('idCompany', $tableName)) {
            $col = 'idCompany';
        }

        if ($col !== null) {
            if (is_array($companyVal)) {
                $target->whereIn($col, $companyVal);
            } else {
                $target->where($col, $companyVal);
            }
        }

        return $target;
    }

    /**
     * Catálogo de documentos soportados para movimientos de almacén, compras y ventas.
     */
    private function getDocumentDefinitions(): array
    {
        return [
            '59' => ['H' => 'OIGN', 'L' => 'IGN1', 'name' => 'Entrada de Mercancía (Inv.)', 'short' => 'ENT-INV', 'badge' => 'success',   'group' => 'INVENTARIO', 'hasNumAtCard' => false, 'isTransfer' => false],
            '60' => ['H' => 'OIGE', 'L' => 'IGE1', 'name' => 'Salida de Mercancía (Inv.)',  'short' => 'SAL-INV', 'badge' => 'danger',    'group' => 'INVENTARIO', 'hasNumAtCard' => false, 'isTransfer' => false],
            '67' => ['H' => 'OWTR', 'L' => 'WTR1', 'name' => 'Transferencia de Stock',      'short' => 'TRANSF',  'badge' => 'info',      'group' => 'INVENTARIO', 'hasNumAtCard' => false, 'isTransfer' => true],
            '22' => ['H' => 'OPOR', 'L' => 'POR1', 'name' => 'Orden de Compra',             'short' => 'OC',      'badge' => 'primary',   'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false],
            '20' => ['H' => 'OPDN', 'L' => 'PDN1', 'name' => 'Entrada Mercancía OP',        'short' => 'EM-COM',  'badge' => 'success',   'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false],
            '21' => ['H' => 'ORPD', 'L' => 'RPD1', 'name' => 'Devolución de Mercancía',     'short' => 'DEV-COM', 'badge' => 'dark',      'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false],
            '18' => ['H' => 'OPCH', 'L' => 'PCH1', 'name' => 'Factura de Proveedor',        'short' => 'FAC-PRV', 'badge' => 'warning',   'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false],
            '19' => ['H' => 'ORPC', 'L' => 'RPC1', 'name' => 'Nota Crédito Proveedor',      'short' => 'NC-PRV',  'badge' => 'purple',    'group' => 'COMPRAS',    'hasNumAtCard' => true,  'isTransfer' => false],
            '15' => ['H' => 'ODLN', 'L' => 'DLN1', 'name' => 'Entrega (Ventas)',            'short' => 'ENT-VTA', 'badge' => 'secondary', 'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false],
            '16' => ['H' => 'ORDN', 'L' => 'RDN1', 'name' => 'Devolución (Ventas)',         'short' => 'DEV-VTA', 'badge' => 'info',      'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false],
            '13' => ['H' => 'OINV', 'L' => 'INV1', 'name' => 'Factura de Cliente',          'short' => 'FAC-CLI', 'badge' => 'danger',    'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false],
            '14' => ['H' => 'ORIN', 'L' => 'RIN1', 'name' => 'Nota Crédito Cliente',        'short' => 'NC-CLI',  'badge' => 'purple',    'group' => 'VENTAS',     'hasNumAtCard' => true,  'isTransfer' => false],
        ];
    }

    /**
     * Vista principal del reporte detallado de movimientos de almacén y compras.
     */
    public function index()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser   = (int) user()->id;
        $empresas = $this->empresa->mdlEmpresasPorUsuario($idUser);

        $data = [
            'title'     => 'Reporte Detallado de Movimientos de Almacén y Compras SAP',
            'subtitle'  => 'Entradas, salidas, transferencias, órdenes de compra y facturas filtrables por proveedor, documentos, almacenes y artículos',
            'empresas'  => $empresas,
            'idEmpresa' => (int) ($empresas[0]['id'] ?? 1),
            'fechaIni'  => date('Y-m-01'),
            'fechaFin'  => date('Y-m-d'),
            'docTypes'  => $this->getDocumentDefinitions(),
        ];

        return view('julio101290\boilerplateservicelayer\Views\inventoryMovementsSAP', $data);
    }

    /**
     * Carga sucursales SAP (OBPL) permitidas para el usuario y empresa.
     */
    public function getBranchesAjax()
    {
        try {
            $idUser     = (int) user()->id;
            $idEmpresa  = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $sucursales = $this->getAllowedSAPBranches($idUser, $idEmpresa);

            return $this->response->setJSON([
                'error'      => false,
                'sucursales' => $sucursales,
                'token'      => csrf_hash(),
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'      => true,
                'message'    => $e->getMessage(),
                'sucursales' => [],
                'token'      => csrf_hash(),
            ]);
        }
    }

    /**
     * Carga catálogo de almacenes activos (OWHS) en SAP HANA.
     */
    public function getWarehousesAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $bplId     = trim((string) ($this->request->getPost('bplId') ?? 'TODOS'));

            $conn       = $this->connectODBC($idEmpresa);
            $warehouses = [];

            $sql = 'SELECT "WhsCode", IFNULL("WhsName", \'\') AS "WhsName" FROM "OWHS" WHERE IFNULL("Inactive", \'N\') = \'N\' ';
            if ($bplId !== '' && $bplId !== 'TODOS' && is_numeric($bplId)) {
                $bplInt    = (int) $bplId;
                $sqlBranch = $sql . " AND \"BPLid\" = {$bplInt} ORDER BY \"WhsCode\" ASC";
                $rs        = @odbc_exec($conn, $sqlBranch);
                if (!$rs) {
                    $rs = @odbc_exec($conn, $sql . ' ORDER BY "WhsCode" ASC');
                }
            } else {
                $rs = @odbc_exec($conn, $sql . ' ORDER BY "WhsCode" ASC');
            }

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code         = trim($this->toUtf8($row['WhsCode'] ?? ''));
                    $name         = trim($this->toUtf8($row['WhsName'] ?? ''));
                    $warehouses[] = [
                        'id'   => $code,
                        'text' => "{$code} - {$name}",
                    ];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);

            return $this->response->setJSON([
                'error'      => false,
                'warehouses' => $warehouses,
                'token'      => csrf_hash(),
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'      => true,
                'message'    => $e->getMessage(),
                'warehouses' => [],
                'token'      => csrf_hash(),
            ]);
        }
    }

    /**
     * Buscador Select2 AJAX de artículos (OITM) en SAP HANA.
     */
    public function searchItemsAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getGet('idEmpresa') ?? 1);
            $term      = trim((string) ($this->request->getGet('q') ?? ''));

            $conn      = $this->connectODBC($idEmpresa);
            $termClean = str_replace("'", "''", strtoupper($term));

            $whereSearch = '';
            if ($termClean !== '') {
                $whereSearch = " AND (
                    UPPER(t0.\"ItemCode\") LIKE '%{$termClean}%'
                    OR UPPER(IFNULL(t0.\"ItemName\", '')) LIKE '%{$termClean}%'
                    OR UPPER(IFNULL(t0.\"CodeBars\", '')) LIKE '%{$termClean}%'
                ) ";
            }

            $sql = "
                SELECT
                    t0.\"ItemCode\",
                    IFNULL(t0.\"ItemName\", '') AS \"ItemName\",
                    IFNULL(t0.\"InvntryUom\", '') AS \"Uom\"
                FROM \"OITM\" t0
                WHERE 1 = 1
                {$whereSearch}
                ORDER BY t0.\"ItemCode\" ASC
                LIMIT 35
            ";

            $results = [];
            $rs      = @odbc_exec($conn, $sql);
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code  = trim($this->toUtf8($row['ItemCode'] ?? ''));
                    $name  = trim($this->toUtf8($row['ItemName'] ?? ''));
                    $uom   = trim($this->toUtf8($row['Uom'] ?? ''));
                    $label = "{$code} - {$name}" . ($uom !== '' ? " [{$uom}]" : '');

                    $results[] = [
                        'id'   => $code,
                        'text' => $label,
                    ];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);

            return $this->response->setJSON(['results' => $results]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'results' => [],
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Buscador Select2 AJAX de proveedores (OCRD CardType = 'S') en SAP HANA.
     */
    public function searchSuppliersAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getGet('idEmpresa') ?? 1);
            $term      = trim((string) ($this->request->getGet('q') ?? ''));

            $conn      = $this->connectODBC($idEmpresa);
            $termClean = str_replace("'", "''", strtoupper($term));

            $whereSearch = '';
            if ($termClean !== '') {
                $whereSearch = " AND (
                    UPPER(t0.\"CardCode\") LIKE '%{$termClean}%'
                    OR UPPER(IFNULL(t0.\"CardName\", '')) LIKE '%{$termClean}%'
                    OR UPPER(IFNULL(t0.\"LicTradNum\", '')) LIKE '%{$termClean}%'
                ) ";
            }

            $sql = "
                SELECT
                    t0.\"CardCode\",
                    IFNULL(t0.\"CardName\", '') AS \"CardName\",
                    IFNULL(t0.\"LicTradNum\", '') AS \"RFC\"
                FROM \"OCRD\" t0
                WHERE t0.\"CardType\" = 'S'
                {$whereSearch}
                ORDER BY t0.\"CardName\" ASC
                LIMIT 35
            ";

            $results = [];
            $rs      = @odbc_exec($conn, $sql);
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code  = trim($this->toUtf8($row['CardCode'] ?? ''));
                    $name  = trim($this->toUtf8($row['CardName'] ?? ''));
                    $rfc   = trim($this->toUtf8($row['RFC'] ?? ''));
                    $label = "{$code} - {$name}" . ($rfc !== '' ? " ({$rfc})" : '');

                    $results[] = [
                        'id'   => $code,
                        'text' => $label,
                    ];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);

            return $this->response->setJSON(['results' => $results]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'results' => [],
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consulta principal AJAX de movimientos detallados (con multiselects).
     */
    public function getMovementsAjax()
    {
        try {
            $params = [
                'idUser'      => (int) user()->id,
                'idEmpresa'   => (int) ($this->request->getPost('idEmpresa') ?? 1),
                'bplId'       => trim((string) ($this->request->getPost('bplId') ?? 'TODOS')),
                'fechaIni'    => trim((string) ($this->request->getPost('fechaIni') ?? date('Y-m-01'))),
                'fechaFin'    => trim((string) ($this->request->getPost('fechaFin') ?? date('Y-m-d'))),
                'tipoDocs'    => $this->normalizeMultiParam($this->request->getPost('tipoDocs')),
                'almacenes'   => $this->normalizeMultiParam($this->request->getPost('almacenes')),
                'articulos'   => $this->normalizeMultiParam($this->request->getPost('articulos')),
                'proveedores' => $this->normalizeMultiParam($this->request->getPost('proveedores')),
                'estatus'     => strtoupper(trim((string) ($this->request->getPost('estatus') ?? 'TODOS'))),
                'folio'       => trim((string) ($this->request->getPost('folio') ?? '')),
                'cancelados'  => strtoupper(trim((string) ($this->request->getPost('cancelados') ?? 'N'))),
            ];

            $result = $this->buildMovementsDataset($params, 5000);

            return $this->response->setJSON([
                'error'     => false,
                'kpis'      => $result['kpis'],
                'truncated' => $result['truncated'],
                'data'      => $result['rows'],
                'token'     => csrf_hash(),
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => [],
                'token'   => csrf_hash(),
            ]);
        }
    }

    /**
     * Obtiene el encabezado y todas las partidas de un documento consultado.
     */
    public function getDocumentDetailAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $objType   = trim((string) ($this->request->getPost('objType') ?? ''));
            $docEntry  = (int) ($this->request->getPost('docEntry') ?? 0);

            $defs = $this->getDocumentDefinitions();
            if (!isset($defs[$objType]) || $docEntry <= 0) {
                throw new Exception('Parámetros de documento inválidos.');
            }

            $conn   = $this->connectODBC($idEmpresa);
            $detail = $this->fetchDocumentDetail($conn, $objType, $docEntry, $defs[$objType]);
            odbc_close($conn);

            return $this->response->setJSON([
                'error'  => false,
                'detail' => $detail,
                'token'  => csrf_hash(),
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash(),
            ]);
        }
    }

    /**
     * Genera reporte PDF detallado en orientación horizontal (TCPDF).
     */
    public function report()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        try {
            $params = [
                'idUser'      => (int) user()->id,
                'idEmpresa'   => (int) ($this->request->getGet('idEmpresa') ?? 1),
                'bplId'       => trim((string) ($this->request->getGet('bplId') ?? 'TODOS')),
                'fechaIni'    => trim((string) ($this->request->getGet('fechaIni') ?? date('Y-m-01'))),
                'fechaFin'    => trim((string) ($this->request->getGet('fechaFin') ?? date('Y-m-d'))),
                'tipoDocs'    => $this->normalizeMultiParam($this->request->getGet('tipoDocs')),
                'almacenes'   => $this->normalizeMultiParam($this->request->getGet('almacenes')),
                'articulos'   => $this->normalizeMultiParam($this->request->getGet('articulos')),
                'proveedores' => $this->normalizeMultiParam($this->request->getGet('proveedores')),
                'estatus'     => strtoupper(trim((string) ($this->request->getGet('estatus') ?? 'TODOS'))),
                'folio'       => trim((string) ($this->request->getGet('folio') ?? '')),
                'cancelados'  => strtoupper(trim((string) ($this->request->getGet('cancelados') ?? 'N'))),
            ];

            $dataset       = $this->buildMovementsDataset($params, 2500);
            $datosEmpresa  = $this->empresa->find($params['idEmpresa']) ?: $this->empresa->first();
            $nombreEmpresa = !empty($datosEmpresa['nombre']) ? $datosEmpresa['nombre'] : 'MI EMPRESA SA DE CV';
            $rfcEmpresa    = !empty($datosEmpresa['rfc']) ? $datosEmpresa['rfc'] : '';

            $rutasLogoEmpresa = [
                'writable/templates/quotes/logoReportes.png',
                'public/img/logoReportes.png',
                'public/images/logoReportes.png',
            ];
            if (!empty($datosEmpresa['logo'])) {
                array_unshift($rutasLogoEmpresa, 'public/images/logo/' . $datosEmpresa['logo']);
            }
            if (!empty($datosEmpresa['rfc'])) {
                array_unshift($rutasLogoEmpresa, 'public/images/logo/' . $datosEmpresa['rfc'] . '_logo.png');
            }

            $logoHeaderPath = $this->prepararImagenTCPDF($rutasLogoEmpresa, 'pdf_logo_inv_mov_' . $params['idEmpresa']);
            $logoHtml       = $logoHeaderPath ? '<img src="' . $logoHeaderPath . '" style="width:120px;">' : '';

            $kpis          = $dataset['kpis'];
            $rows          = $dataset['rows'];
            $sucursalTexto = $dataset['sucursalTexto'];
            $filtroDocTxt  = $dataset['tipoDocTexto'];
            $almacenTxt    = $this->summarizeFilterList($params['almacenes'], 'TODOS', 4);
            $articuloTxt   = $this->summarizeFilterList($params['articulos'], 'TODOS', 4);
            $proveedorTxt  = $this->summarizeFilterList($params['proveedores'], 'TODOS', 3);
            $fechaEmision  = date('Y-m-d H:i');

            $htmlHeader = <<<EOF
<table cellpadding="3" cellspacing="0" style="width:100%; border-bottom: 2px solid #a82020;">
    <tr>
        <td style="width:22%; vertical-align:middle;">{$logoHtml}</td>
        <td style="width:46%; text-align:center; vertical-align:middle;">
            <span style="font-size:12px; font-weight:bold; color:#a82020;">{$this->escPdf($nombreEmpresa)}</span><br>
            <span style="font-size:8px; color:#555555;">RFC: {$this->escPdf($rfcEmpresa)}</span><br>
            <span style="font-size:10px; font-weight:bold; color:#222222;">REPORTE DETALLADO DE MOVIMIENTOS DE ALMACÉN Y COMPRAS</span><br>
            <span style="font-size:7.5px; color:#444444;">Sucursal: {$this->escPdf($sucursalTexto)}</span>
        </td>
        <td style="width:32%; text-align:right; font-size:7.2px; vertical-align:middle;">
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
        <td style="width:20%;">Partidas</td>
        <td style="width:20%;">Documentos Únicos</td>
        <td style="width:20%;">Cantidad Total</td>
        <td style="width:20%;">Costo Total (Inv.)</td>
        <td style="width:20%;">Importe Total (Precio)</td>
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
            $i        = 0;
            foreach ($rows as $r) {
                $i++;
                $bg       = ($i % 2 === 0) ? '#f7f7f7' : '#ffffff';
                $provInfo = $r['CardCode'] !== '' ? ($r['CardCode'] . ' - ' . $r['CardName']) : '-';

                $rowsHtml .= '
                <tr style="background-color:' . $bg . ';">
                    <td style="width:11%; text-align:left;"><b>' . $this->escPdf($r['TMA_NOMBRE']) . '</b></td>
                    <td style="width:5%; text-align:center;">' . $this->escPdf($r['ALM_CLAVE']) . '</td>
                    <td style="width:11%; text-align:left;">' . $this->escPdf(mb_substr($r['ALM_NOMBRE'], 0, 22)) . '</td>
                    <td style="width:6%; text-align:center;"><b>' . $this->escPdf($r['MAE_FOLIO']) . '</b></td>
                    <td style="width:6%; text-align:center;">' . $this->escPdf($r['MAE_ESTATUS']) . '</td>
                    <td style="width:7%; text-align:center;">' . $this->escPdf($r['MAE_FECHA']) . '</td>
                    <td style="width:12%; text-align:left;">' . $this->escPdf(mb_substr($provInfo, 0, 26)) . '</td>
                    <td style="width:8%; text-align:left;">' . $this->escPdf($r['ART_CLAVE']) . '</td>
                    <td style="width:13%; text-align:left;">' . $this->escPdf(mb_substr($r['ART_NOMBRE'], 0, 28)) . '</td>
                    <td style="width:7%; text-align:right;">' . $this->fmt($r['MAD_CANT'], 3) . '</td>
                    <td style="width:7%; text-align:right;">' . $this->fmt($r['MAD_COSTO'], 4) . '</td>
                    <td style="width:7%; text-align:right;">' . $this->fmt($r['MAD_PRECIO'], 4) . '</td>
                </tr>';
            }

            if ($rowsHtml === '') {
                $rowsHtml = '<tr><td colspan="12" style="text-align:center; padding:10px;">No existen movimientos con los filtros seleccionados.</td></tr>';
            }

            $htmlTable = <<<EOF
<table cellpadding="2.3" cellspacing="0" border="0.2" style="width:100%; font-size:6.4px; border-color:#cccccc;">
    <thead>
        <tr style="background-color:#a82020; color:#ffffff; text-align:center; font-weight:bold;">
            <th style="width:11%;">Documento</th>
            <th style="width:5%;">Alm.</th>
            <th style="width:11%;">Nombre Almacén</th>
            <th style="width:6%;">Folio</th>
            <th style="width:6%;">Estatus</th>
            <th style="width:7%;">Fecha</th>
            <th style="width:12%;">Proveedor / Socio</th>
            <th style="width:8%;">Clave Art.</th>
            <th style="width:13%;">Artículo</th>
            <th style="width:7%;">Cantidad</th>
            <th style="width:7%;">Costo</th>
            <th style="width:7%;">Precio</th>
        </tr>
    </thead>
    <tbody>
        {$rowsHtml}
    </tbody>
</table>
EOF;

            $pdf = new \TCPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(6, 7, 6);
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

    /**
     * Exportación directa a Excel / CSV (UTF-8 con BOM).
     */
    public function exportCsv()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        try {
            $params = [
                'idUser'      => (int) user()->id,
                'idEmpresa'   => (int) ($this->request->getGet('idEmpresa') ?? 1),
                'bplId'       => trim((string) ($this->request->getGet('bplId') ?? 'TODOS')),
                'fechaIni'    => trim((string) ($this->request->getGet('fechaIni') ?? date('Y-m-01'))),
                'fechaFin'    => trim((string) ($this->request->getGet('fechaFin') ?? date('Y-m-d'))),
                'tipoDocs'    => $this->normalizeMultiParam($this->request->getGet('tipoDocs')),
                'almacenes'   => $this->normalizeMultiParam($this->request->getGet('almacenes')),
                'articulos'   => $this->normalizeMultiParam($this->request->getGet('articulos')),
                'proveedores' => $this->normalizeMultiParam($this->request->getGet('proveedores')),
                'estatus'     => strtoupper(trim((string) ($this->request->getGet('estatus') ?? 'TODOS'))),
                'folio'       => trim((string) ($this->request->getGet('folio') ?? '')),
                'cancelados'  => strtoupper(trim((string) ($this->request->getGet('cancelados') ?? 'N'))),
            ];

            $dataset = $this->buildMovementsDataset($params, 25000);
            $fp      = fopen('php://temp', 'r+');

            fwrite($fp, "\xEF\xBB\xBF");

            fputcsv($fp, [
                'TMA_NOMBRE',
                'ALM_CLAVE',
                'ALM_NOMBRE',
                'MAE_FOLIO',
                'MAE_ESTATUS',
                'MAE_FECHA',
                'PROV_CLAVE',
                'PROV_NOMBRE',
                'ART_CLAVE',
                'ART_NOMBRE',
                'UNIDAD',
                'MAD_CANT',
                'MAD_COSTO',
                'MAD_PRECIO',
                'IMPORTE_LINEA',
                'SUCURSAL',
                'REFERENCIA',
            ]);

            foreach ($dataset['rows'] as $r) {
                fputcsv($fp, [
                    $r['TMA_NOMBRE'],
                    $r['ALM_CLAVE'],
                    $r['ALM_NOMBRE'],
                    $r['MAE_FOLIO'],
                    $r['MAE_ESTATUS'],
                    $r['MAE_FECHA'],
                    $r['CardCode'],
                    $r['CardName'],
                    $r['ART_CLAVE'],
                    $r['ART_NOMBRE'],
                    $r['UNIDAD'],
                    $r['MAD_CANT'],
                    $r['MAD_COSTO'],
                    $r['MAD_PRECIO'],
                    $r['IMPORTE'],
                    $r['BPLName'],
                    $r['Referencia'],
                ]);
            }

            rewind($fp);
            $csvContent = stream_get_contents($fp);
            fclose($fp);

            $fileName = "MOVIMIENTOS_ALMACEN_SAP_{$params['fechaIni']}_{$params['fechaFin']}.csv";

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->setBody($csvContent);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('Error al exportar CSV: ' . esc($e->getMessage()));
        }
    }

    /**
     * Núcleo compartido: arma el UNION ALL filtrado en SAP HANA y calcula KPIs.
     */
    private function buildMovementsDataset(array $params, int $limitRows = 5000): array
    {
        $idUser      = (int) $params['idUser'];
        $idEmpresa   = (int) $params['idEmpresa'];
        $bplId       = trim((string) $params['bplId']);
        $fechaIni    = $this->sanitizeDate($params['fechaIni'], date('Y-m-01'));
        $fechaFin    = $this->sanitizeDate($params['fechaFin'], date('Y-m-d'));
        $tipoDocs    = $params['tipoDocs'] ?? [];
        $almacenes   = $params['almacenes'] ?? [];
        $articulos   = $params['articulos'] ?? [];
        $proveedores = $params['proveedores'] ?? [];
        $estatus     = strtoupper(trim((string) ($params['estatus'] ?? 'TODOS')));
        $folio       = trim((string) ($params['folio'] ?? ''));
        $cancelados  = strtoupper(trim((string) ($params['cancelados'] ?? 'N')));

        if ($fechaIni > $fechaFin) {
            $tmp      = $fechaIni;
            $fechaIni = $fechaFin;
            $fechaFin = $tmp;
        }

        // Resolver documentos seleccionados (acepta múltiples ObjType o grupos)
        $allDefs      = $this->getDocumentDefinitions();
        $selectedDefs = [];

        if (empty($tipoDocs)) {
            $selectedDefs = $allDefs;
            $tipoDocTexto = 'TODOS';
        } else {
            $shortLabels = [];
            foreach ($tipoDocs as $td) {
                $tdUpper = strtoupper($td);
                if (in_array($tdUpper, ['INVENTARIO', 'COMPRAS', 'VENTAS'], true)) {
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
                foreach ($selectedDefs as $def) {
                    $shortLabels[] = $def['short'];
                }
                $tipoDocTexto = $this->summarizeFilterList($shortLabels, 'TODOS', 5);
            }
        }

        $sucursales    = $this->getAllowedSAPBranches($idUser, $idEmpresa);
        $allowedBplIn  = array_column($sucursales, 'bplId');
        $sucursalTexto = 'TODAS LAS SUCURSALES';

        $conn = $this->connectODBC($idEmpresa);

        // Filtro por sucursal (OBPL / USR6)
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
            $inStr    = implode(',', array_map('intval', $allowedBplIn));
            $whereBpl = " AND (h.\"BPLId\" IN ({$inStr}) OR h.\"BPLId\" IS NULL) ";
        }

        $sapUserId = $this->resolveLinkedSapUserId($conn, $idEmpresa, $idUser);
        if ($sapUserId > 0) {
            $whereBpl .= " AND (h.\"BPLId\" IS NULL OR EXISTS (SELECT 1 FROM \"USR6\" u6 WHERE u6.\"BPLId\" = h.\"BPLId\" AND u6.\"UserID\" = {$sapUserId})) ";
        }

        // Filtro múltiple de artículos
        $whereItem = '';
        if (!empty($articulos)) {
            $artIn     = $this->buildSqlInList($articulos, true);
            $whereItem = " AND UPPER(l.\"ItemCode\") IN ({$artIn}) ";
        }

        // Filtro múltiple de proveedores (CardCode)
        $whereProv = '';
        if (!empty($proveedores)) {
            $provIn    = $this->buildSqlInList($proveedores, true);
            $whereProv = " AND UPPER(IFNULL(h.\"CardCode\", '')) IN ({$provIn}) ";
        }

        // Filtro de estatus y cancelados
        $whereStatus = '';
        if ($estatus === 'O' || $estatus === 'C') {
            $whereStatus = " AND h.\"DocStatus\" = '{$estatus}' ";
        }

        $whereCancel = '';
        if ($cancelados === 'N') {
            $whereCancel = " AND IFNULL(h.\"CANCELED\", 'N') = 'N' ";
        }

        $folioClean = str_replace("'", "''", strtoupper($folio));
        $almIn      = !empty($almacenes) ? $this->buildSqlInList($almacenes, true) : '';

        $unionParts = [];
        foreach ($selectedDefs as $objType => $def) {
            $tH       = $def['H'];
            $tL       = $def['L'];
            $docLabel = str_replace("'", "''", $def['name']);

            // Filtro múltiple de almacenes (en transferencias evalúa destino y origen)
            $whereWhs = '';
            if ($almIn !== '') {
                if ($def['isTransfer']) {
                    $whereWhs = " AND (UPPER(IFNULL(l.\"WhsCode\", '')) IN ({$almIn}) OR UPPER(IFNULL(l.\"FromWhsCod\", '')) IN ({$almIn})) ";
                } else {
                    $whereWhs = " AND UPPER(IFNULL(l.\"WhsCode\", '')) IN ({$almIn}) ";
                }
            }

            if ($def['hasNumAtCard']) {
                $refExpr = "COALESCE(NULLIF(h.\"NumAtCard\", ''), NULLIF(h.\"Comments\", ''), '')";
            } else {
                $refExpr = "COALESCE(NULLIF(h.\"Ref2\", ''), NULLIF(h.\"Comments\", ''), '')";
            }

            if ($def['isTransfer']) {
                $refExpr = "CASE WHEN IFNULL(l.\"FromWhsCod\", '') <> '' THEN 'Orig: ' || l.\"FromWhsCod\" || ' -> Dest: ' || IFNULL(l.\"WhsCode\", '') || ' ' || {$refExpr} ELSE {$refExpr} END";
            }

            $whereFolio = '';
            if ($folioClean !== '' && $folioClean !== '*') {
                $whereFolio = " AND (
                    CAST(h.\"DocNum\" AS NVARCHAR(30)) LIKE '%{$folioClean}%'
                    OR UPPER({$refExpr}) LIKE '%{$folioClean}%'
                    OR UPPER(IFNULL(h.\"CardCode\", '')) LIKE '%{$folioClean}%'
                    OR UPPER(IFNULL(h.\"CardName\", '')) LIKE '%{$folioClean}%'
                ) ";
            }

            $unionParts[] = "
                SELECT
                    '{$objType}' AS \"ObjType\",
                    '{$docLabel}' AS \"TMA_NOMBRE\",
                    h.\"DocEntry\",
                    CAST(h.\"DocNum\" AS NVARCHAR(30)) AS \"MAE_FOLIO\",
                    IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\",
                    IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\",
                    TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"MAE_FECHA\",
                    l.\"LineNum\",
                    IFNULL(l.\"WhsCode\", '') AS \"ALM_CLAVE\",
                    IFNULL(w.\"WhsName\", '') AS \"ALM_NOMBRE\",
                    IFNULL(l.\"ItemCode\", '') AS \"ART_CLAVE\",
                    COALESCE(NULLIF(l.\"Dscription\", ''), IFNULL(it.\"ItemName\", ''), '') AS \"ART_NOMBRE\",
                    IFNULL(l.\"unitMsr\", IFNULL(it.\"InvntryUom\", '')) AS \"UNIDAD\",
                    IFNULL(l.\"Quantity\", 0) AS \"MAD_CANT\",
                    IFNULL(l.\"StockPrice\", 0) AS \"MAD_COSTO\",
                    IFNULL(l.\"Price\", 0) AS \"MAD_PRECIO\",
                    IFNULL(l.\"LineTotal\", 0) AS \"IMPORTE\",
                    IFNULL(b.\"BPLName\", '') AS \"BPLName\",
                    IFNULL(h.\"CardCode\", '') AS \"CardCode\",
                    IFNULL(h.\"CardName\", '') AS \"CardName\",
                    {$refExpr} AS \"Referencia\"
                FROM \"{$tH}\" h
                INNER JOIN \"{$tL}\" l ON l.\"DocEntry\" = h.\"DocEntry\"
                LEFT JOIN \"OWHS\" w ON w.\"WhsCode\" = l.\"WhsCode\"
                LEFT JOIN \"OITM\" it ON it.\"ItemCode\" = l.\"ItemCode\"
                LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
                WHERE h.\"DocDate\" BETWEEN '{$fechaIni}' AND '{$fechaFin}'
                  AND IFNULL(l.\"ItemCode\", '') <> ''
                  {$whereBpl}
                  {$whereWhs}
                  {$whereItem}
                  {$whereProv}
                  {$whereStatus}
                  {$whereCancel}
                  {$whereFolio}
            ";
        }

        $sqlLimit = $limitRows + 1;
        $fullSql  = "
            SELECT * FROM (
                " . implode("\nUNION ALL\n", $unionParts) . "
            ) AS movs
            ORDER BY movs.\"MAE_FECHA\" ASC, movs.\"ObjType\" ASC, movs.\"DocEntry\" ASC, movs.\"LineNum\" ASC
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

                $cancRaw = trim((string) ($r['Canceled'] ?? 'N'));
                $statRaw = trim((string) ($r['DocStatus'] ?? 'O'));

                if ($cancRaw === 'Y' || $cancRaw === 'C') {
                    $estatusTxt = 'CANCELADO';
                } elseif ($statRaw === 'C') {
                    $estatusTxt = 'CERRADO';
                } else {
                    $estatusTxt = 'ABIERTO';
                }

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
                    'ObjType'     => $objType,
                    'DocEntry'    => $docEntry,
                    'CodigoCorto' => $def['short'],
                    'BadgeColor'  => $def['badge'],
                    'TMA_NOMBRE'  => trim($this->toUtf8($r['TMA_NOMBRE'] ?? '')),
                    'ALM_CLAVE'   => trim($this->toUtf8($r['ALM_CLAVE'] ?? '')),
                    'ALM_NOMBRE'  => trim($this->toUtf8($r['ALM_NOMBRE'] ?? '')),
                    'MAE_FOLIO'   => trim($this->toUtf8($r['MAE_FOLIO'] ?? '')),
                    'MAE_ESTATUS' => $estatusTxt,
                    'Cancelado'   => ($cancRaw === 'Y' || $cancRaw === 'C') ? 'S' : 'N',
                    'MAE_FECHA'   => trim($this->toUtf8($r['MAE_FECHA'] ?? '')),
                    'ART_CLAVE'   => trim($this->toUtf8($r['ART_CLAVE'] ?? '')),
                    'ART_NOMBRE'  => trim($this->toUtf8($r['ART_NOMBRE'] ?? '')),
                    'UNIDAD'      => trim($this->toUtf8($r['UNIDAD'] ?? '')),
                    'MAD_CANT'    => $cant,
                    'MAD_COSTO'   => $costo,
                    'MAD_PRECIO'  => $precio,
                    'IMPORTE'     => $importe,
                    'BPLName'     => trim($this->toUtf8($r['BPLName'] ?? '')),
                    'CardCode'    => trim($this->toUtf8($r['CardCode'] ?? '')),
                    'CardName'    => trim($this->toUtf8($r['CardName'] ?? '')),
                    'Referencia'  => trim($this->toUtf8($r['Referencia'] ?? '')),
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

    /**
     * Detalle completo de un documento de inventario, compras o ventas.
     */
    private function fetchDocumentDetail($conn, string $objType, int $docEntry, array $def): array
    {
        $tH = $def['H'];
        $tL = $def['L'];

        $refCol   = $def['hasNumAtCard'] ? 'IFNULL(h."NumAtCard", \'\')' : 'IFNULL(h."Ref2", \'\')';
        $vatCol   = in_array($objType, ['59', '60', '67'], true) ? '0' : 'IFNULL(h."VatSum", 0)';
        $totalCol = ($objType === '67') ? '0' : 'IFNULL(h."DocTotal", 0)';
        $fromWhs  = $def['isTransfer'] ? 'IFNULL(l."FromWhsCod", \'\') AS "FromWhsCod",' : '\'\' AS "FromWhsCod",';

        $sqlH = "
            SELECT
                h.\"DocEntry\",
                h.\"DocNum\",
                TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                TO_VARCHAR(h.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\",
                IFNULL(h.\"CardCode\", '') AS \"CardCode\",
                IFNULL(h.\"CardName\", '') AS \"CardName\",
                {$refCol} AS \"Referencia\",
                IFNULL(h.\"Comments\", '') AS \"Comments\",
                IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\",
                IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\",
                IFNULL(h.\"DocCur\", 'MXP') AS \"DocCur\",
                {$totalCol} AS \"DocTotal\",
                {$vatCol} AS \"VatSum\",
                IFNULL(b.\"BPLName\", '') AS \"BPLName\"
            FROM \"{$tH}\" h
            LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
            WHERE h.\"DocEntry\" = {$docEntry}
            LIMIT 1
        ";

        $rsH    = odbc_exec($conn, $sqlH);
        $header = $rsH ? odbc_fetch_array($rsH) : null;
        if ($rsH) {
            odbc_free_result($rsH);
        }
        if (!$header) {
            throw new Exception("No se encontró el documento ({$def['name']}) #{$docEntry}.");
        }

        $sqlL = "
            SELECT
                l.\"LineNum\",
                IFNULL(l.\"ItemCode\", '') AS \"ItemCode\",
                COALESCE(NULLIF(l.\"Dscription\", ''), IFNULL(it.\"ItemName\", ''), '') AS \"Dscription\",
                IFNULL(l.\"Quantity\", 0) AS \"Quantity\",
                IFNULL(l.\"unitMsr\", '') AS \"UnitMsr\",
                IFNULL(l.\"WhsCode\", '') AS \"WhsCode\",
                IFNULL(w.\"WhsName\", '') AS \"WhsName\",
                {$fromWhs}
                IFNULL(l.\"StockPrice\", 0) AS \"StockPrice\",
                IFNULL(l.\"Price\", 0) AS \"Price\",
                IFNULL(l.\"LineTotal\", 0) AS \"LineTotal\"
            FROM \"{$tL}\" l
            LEFT JOIN \"OWHS\" w ON w.\"WhsCode\" = l.\"WhsCode\"
            LEFT JOIN \"OITM\" it ON it.\"ItemCode\" = l.\"ItemCode\"
            WHERE l.\"DocEntry\" = {$docEntry}
            ORDER BY l.\"VisOrder\" ASC, l.\"LineNum\" ASC
        ";

        $lines    = [];
        $sumLines = 0.0;
        $sumCost  = 0.0;

        $rsL = odbc_exec($conn, $sqlL);
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

                $whsTxt = trim($this->toUtf8($r['WhsCode'] ?? ''));
                $whsNam = trim($this->toUtf8($r['WhsName'] ?? ''));
                $origW  = trim($this->toUtf8($r['FromWhsCod'] ?? ''));
                $almCol = $origW !== '' ? "{$origW} -> {$whsTxt}" : "{$whsTxt} - {$whsNam}";

                $lines[] = [
                    'itemCode'   => trim($this->toUtf8($r['ItemCode'] ?? '')),
                    'dscription' => trim($this->toUtf8($r['Dscription'] ?? '')),
                    'warehouse'  => $almCol,
                    'quantity'   => $qty,
                    'uom'        => trim($this->toUtf8($r['UnitMsr'] ?? '')),
                    'cost'       => $cost,
                    'price'      => $price,
                    'lineTotal'  => $tot,
                ];
            }
            odbc_free_result($rsL);
        }

        $docTotal = round((float) ($header['DocTotal'] ?? 0), 2);
        $vatSum   = round((float) ($header['VatSum'] ?? 0), 2);
        if ($docTotal == 0.0 && $sumLines > 0) {
            $docTotal = round($sumLines, 2);
        }

        $canc    = in_array($header['Canceled'] ?? 'N', ['Y', 'C'], true);
        $estatus = $canc ? 'CANCELADO' : (($header['DocStatus'] ?? 'O') === 'C' ? 'CERRADO' : 'ABIERTO');
        $socio   = trim($this->toUtf8($header['CardCode'] ?? ''));
        if ($socio !== '') {
            $socio .= ' - ' . trim($this->toUtf8($header['CardName'] ?? ''));
        }

        return [
            'objType'  => $objType,
            'tipo'     => $def['name'],
            'badge'    => $def['badge'],
            'docEntry' => (int) $header['DocEntry'],
            'docNum'   => trim($this->toUtf8($header['DocNum'] ?? '')),
            'header'   => [
                'Fecha'             => trim($this->toUtf8($header['DocDate'] ?? '')),
                'Vencimiento'       => trim($this->toUtf8($header['DocDueDate'] ?? '')),
                'Estatus'           => $estatus,
                'Sucursal'          => trim($this->toUtf8($header['BPLName'] ?? '')),
                'Proveedor / Socio' => $socio !== '' ? $socio : '-',
                'Referencia'        => trim($this->toUtf8($header['Referencia'] ?? '')),
                'Moneda'            => trim($this->toUtf8($header['DocCur'] ?? 'MXP')),
                'Comentarios'       => trim($this->toUtf8($header['Comments'] ?? '')),
            ],
            'totals' => [
                'CostoTotal' => round($sumCost, 2),
                'Subtotal'   => round($sumLines, 2),
                'IVA'        => $vatSum,
                'Total'      => $docTotal,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Normaliza parámetros multiselect recibidos como array o lista separada por comas.
     */
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

    /**
     * Construye lista SQL segura para cláusula IN (...).
     */
    private function buildSqlInList(array $values, bool $upper = true): string
    {
        $escaped = [];
        foreach ($values as $v) {
            $val       = $upper ? strtoupper($v) : $v;
            $escaped[] = "'" . str_replace("'", "''", $val) . "'";
        }
        return implode(', ', $escaped);
    }

    /**
     * Resume una lista de filtros para el encabezado del PDF.
     */
    private function summarizeFilterList(array $values, string $default = 'TODOS', int $maxItems = 4): string
    {
        if (empty($values)) {
            return $default;
        }
        if (count($values) <= $maxItems) {
            return implode(', ', $values);
        }
        $slice = array_slice($values, 0, $maxItems);
        $rest  = count($values) - $maxItems;
        return implode(', ', $slice) . " (+{$rest} más)";
    }

    /**
     * Obtiene las sucursales de SAP (OBPL) permitidas para el usuario.
     */
    private function getAllowedSAPBranches(int $idUser, int $idEmpresa): array
    {
        $db         = \Config\Database::connect();
        $allowedBpl = [];

        if ($db->tableExists('usuarios_sucursal') && $db->tableExists('link_sap_branchoffice')) {
            $userBranches = $db->table('usuarios_sucursal')
                ->select('idSucursal')
                ->where('idUsuario', $idUser)
                ->where('status', 'on')
                ->get()
                ->getResultArray();

            if (!empty($userBranches)) {
                $branchIds  = array_column($userBranches, 'idSucursal');
                $enlBuilder = $this->linkSapBranchOffice->builder();
                $this->applyCompanyFilter($enlBuilder, 'link_sap_branchoffice', $idEmpresa);
                $links = $enlBuilder
                    ->whereIn('idBranchOffice', $branchIds)
                    ->where('deleted_at', null)
                    ->get()
                    ->getResultArray();

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
            $where = ' WHERE "Disabled" = \'N\' ';
            if (!empty($allowedBpl)) {
                $where .= ' AND "BPLId" IN (' . implode(',', array_unique($allowedBpl)) . ') ';
            }

            $rs = @odbc_exec($conn, 'SELECT "BPLId", "BPLName" FROM "OBPL" ' . $where . ' ORDER BY "BPLId" ASC');
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $id         = (int) $row['BPLId'];
                    $name       = trim($this->toUtf8($row['BPLName'] ?? ''));
                    $branches[] = [
                        'bplId' => $id,
                        'text'  => "{$id} - {$name}",
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);
        } catch (Throwable $ignore) {
        }

        return $branches;
    }

    /**
     * Resuelve UserID de SAP (OUSR/USR6) si el usuario web está enlazado.
     */
    private function resolveLinkedSapUserId($conn, int $idEmpresa, int $idUser): int
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('user_sap_link')) {
                return 0;
            }

            $builder = $this->userSapLink->builder();
            $this->applyCompanyFilter($builder, 'user_sap_link', $idEmpresa);
            $link = $builder
                ->where('iduser', $idUser)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$link || empty($link['sapuser'])) {
                return 0;
            }

            $sapUser = trim((string) $link['sapuser']);
            if (is_numeric($sapUser)) {
                $uid   = (int) $sapUser;
                $rsChk = @odbc_exec($conn, "SELECT COUNT(*) AS \"Cnt\" FROM \"USR6\" WHERE \"UserID\" = {$uid}");
                if ($rsChk && ($r = odbc_fetch_array($rsChk)) && (int) ($r['Cnt'] ?? 0) > 0) {
                    odbc_free_result($rsChk);
                    return $uid;
                }
            }

            $codeClean = str_replace("'", "''", $sapUser);
            $rsOusr    = @odbc_exec($conn, "SELECT \"USERID\" FROM \"OUSR\" WHERE UPPER(\"USER_CODE\") = UPPER('{$codeClean}') LIMIT 1");
            if ($rsOusr && ($row = odbc_fetch_array($rsOusr))) {
                $uid = (int) ($row['USERID'] ?? 0);
                odbc_free_result($rsOusr);
                if ($uid > 0) {
                    $rsChk = @odbc_exec($conn, "SELECT COUNT(*) AS \"Cnt\" FROM \"USR6\" WHERE \"UserID\" = {$uid}");
                    if ($rsChk && ($r = odbc_fetch_array($rsChk)) && (int) ($r['Cnt'] ?? 0) > 0) {
                        odbc_free_result($rsChk);
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
                    $w  = imagesx($img);
                    $h  = imagesy($img);
                    $bg = imagecreatetruecolor($w, $h);
                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagecopy($bg, $img, 0, 0, 0, 0, $w, $h);
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
        if (mb_check_encoding((string) $value, 'UTF-8')) {
            return (string) $value;
        }
        return mb_convert_encoding((string) $value, 'UTF-8', 'ISO-8859-1');
    }
}