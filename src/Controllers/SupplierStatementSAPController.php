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

class SupplierStatementSAPController extends BaseController
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
     * Vista principal de Movimientos y Estado de Cuenta de Proveedores SAP (Cuentas por Pagar - Solo Lectura).
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
            'title'     => 'Movimientos y Estado de Cuenta de Proveedores SAP',
            'subtitle'  => 'Consulta de facturas de proveedor, facturas de reserva, anticipos, pagos efectuados, saldos al corte y mapa de relaciones',
            'empresas'  => $empresas,
            'idEmpresa' => (int) ($empresas[0]['id'] ?? 1),
            'fechaIni'  => date('Y-m-01'),
            'fechaFin'  => date('Y-m-d'),
        ];

        return view('julio101290\boilerplateservicelayer\Views\supplierStatementSAP', $data);
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
                    OR UPPER(t0.\"CardName\") LIKE '%{$termClean}%'
                    OR UPPER(IFNULL(t0.\"LicTradNum\", '')) LIKE '%{$termClean}%'
                ) ";
            }

            $sql = "
                SELECT
                    t0.\"CardCode\",
                    t0.\"CardName\",
                    IFNULL(t0.\"LicTradNum\", '') AS \"RFC\",
                    IFNULL(t0.\"Balance\", 0) AS \"Balance\",
                    IFNULL(t0.\"Currency\", 'MXP') AS \"Currency\"
                FROM \"OCRD\" t0
                WHERE t0.\"CardType\" = 'S'
                {$whereSearch}
                ORDER BY t0.\"CardName\" ASC
                LIMIT 30
            ";

            $results = [];
            $rs      = @odbc_exec($conn, $sql);
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code = trim($this->toUtf8($row['CardCode'] ?? ''));
                    $name = trim($this->toUtf8($row['CardName'] ?? ''));
                    $rfc  = trim($this->toUtf8($row['RFC'] ?? ''));

                    $label = "{$code} - {$name}";
                    if ($rfc !== '') {
                        $label .= " ({$rfc})";
                    }

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
     * Consulta principal de estado de cuenta, saldos al corte y movimientos del proveedor.
     */
    public function getStatementAjax()
    {
        try {
            $params = [
                'idUser'     => (int) user()->id,
                'idEmpresa'  => (int) ($this->request->getPost('idEmpresa') ?? 1),
                'cardCode'   => trim((string) ($this->request->getPost('cardCode') ?? '')),
                'bplId'      => trim((string) ($this->request->getPost('bplId') ?? 'TODOS')),
                'fechaIni'   => trim((string) ($this->request->getPost('fechaIni') ?? date('Y-m-01'))),
                'fechaFin'   => trim((string) ($this->request->getPost('fechaFin') ?? date('Y-m-d'))),
                'tipoMov'    => strtoupper(trim((string) ($this->request->getPost('tipoMov') ?? 'AMBOS'))),
                'estatusSal' => strtoupper(trim((string) ($this->request->getPost('estatusSal') ?? 'TODOS'))),
                'concepto'   => strtoupper(trim((string) ($this->request->getPost('concepto') ?? 'TODOS'))),
                'referencia' => trim((string) ($this->request->getPost('referencia') ?? '')),
                'cancelados' => strtoupper(trim((string) ($this->request->getPost('cancelados') ?? 'N'))),
            ];

            if ($params['cardCode'] === '') {
                throw new Exception('Debes seleccionar un proveedor para realizar la consulta.');
            }

            $result = $this->buildStatementDataset($params);

            return $this->response->setJSON([
                'error'     => false,
                'proveedor' => $result['proveedor'],
                'kpis'      => $result['kpis'],
                'aging'     => $result['aging'],
                'data'      => $result['movimientos'],
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
     * Obtiene el encabezado y partidas detalladas de cualquier documento de compras/pagos SAP.
     */
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
     * Construye el Mapa de Relaciones (nodos y enlaces) de un documento de compras/CxP en SAP.
     */
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

            return $this->response->setJSON([
                'error' => false,
                'map'   => $map,
                'token' => csrf_hash(),
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
     * Genera el reporte PDF de Estado de Cuenta Detallado de Proveedor a la fecha de corte con TCPDF.
     */
    public function report()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        try {
            $params = [
                'idUser'     => (int) user()->id,
                'idEmpresa'  => (int) ($this->request->getGet('idEmpresa') ?? 1),
                'cardCode'   => trim((string) ($this->request->getGet('cardCode') ?? '')),
                'bplId'      => trim((string) ($this->request->getGet('bplId') ?? 'TODOS')),
                'fechaIni'   => trim((string) ($this->request->getGet('fechaIni') ?? date('Y-m-01'))),
                'fechaFin'   => trim((string) ($this->request->getGet('fechaFin') ?? date('Y-m-d'))),
                'tipoMov'    => strtoupper(trim((string) ($this->request->getGet('tipoMov') ?? 'AMBOS'))),
                'estatusSal' => strtoupper(trim((string) ($this->request->getGet('estatusSal') ?? 'TODOS'))),
                'concepto'   => strtoupper(trim((string) ($this->request->getGet('concepto') ?? 'TODOS'))),
                'referencia' => trim((string) ($this->request->getGet('referencia') ?? '')),
                'cancelados' => strtoupper(trim((string) ($this->request->getGet('cancelados') ?? 'N'))),
            ];

            if ($params['cardCode'] === '') {
                return $this->response->setStatusCode(400)->setBody('Debe indicar un proveedor (cardCode).');
            }

            $dataset       = $this->buildStatementDataset($params);
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

            $logoHeaderPath = $this->prepararImagenTCPDF($rutasLogoEmpresa, 'pdf_logo_supp_stmt_' . $params['idEmpresa']);
            $logoHtml       = $logoHeaderPath ? '<img src="' . $logoHeaderPath . '" style="width:125px;">' : '';

            $prov          = $dataset['proveedor'];
            $kpis          = $dataset['kpis'];
            $aging         = $dataset['aging'];
            $movs          = $dataset['movimientos'];
            $sucursalTexto = $dataset['sucursalTexto'];
            $fechaEmision  = date('Y-m-d H:i');

            $htmlHeader = <<<EOF
<table cellpadding="3" cellspacing="0" style="width:100%; border-bottom: 2px solid #a82020;">
    <tr>
        <td style="width:26%; vertical-align:middle;">{$logoHtml}</td>
        <td style="width:46%; text-align:center; vertical-align:middle;">
            <span style="font-size:12px; font-weight:bold; color:#a82020;">{$this->escPdf($nombreEmpresa)}</span><br>
            <span style="font-size:8px; color:#555555;">RFC: {$this->escPdf($rfcEmpresa)}</span><br>
            <span style="font-size:10px; font-weight:bold; color:#222222;">ESTADO DE CUENTA DETALLADO DE PROVEEDOR (CxP)</span><br>
            <span style="font-size:8px; color:#444444;">Sucursal: {$this->escPdf($sucursalTexto)}</span>
        </td>
        <td style="width:28%; text-align:right; font-size:8px; vertical-align:middle;">
            <b>Periodo:</b> {$this->escPdf($params['fechaIni'])} al {$this->escPdf($params['fechaFin'])}<br>
            <b>Fecha de Corte:</b> {$this->escPdf($params['fechaFin'])}<br>
            <b>Moneda:</b> {$this->escPdf($prov['Currency'])}<br>
            <b>Emitido:</b> {$fechaEmision}
        </td>
    </tr>
</table>
<br>
<table cellpadding="4" cellspacing="0" style="width:100%; font-size:8px; border:1px solid #dddddd;">
    <tr style="background-color:#f8f9fa;">
        <td style="width:15%;"><b>Clave Proveedor:</b></td>
        <td style="width:21%;">{$this->escPdf($prov['CardCode'])}</td>
        <td style="width:14%;"><b>Razón Social:</b></td>
        <td style="width:50%;"><b>{$this->escPdf($prov['CardName'])}</b></td>
    </tr>
    <tr>
        <td><b>RFC:</b></td>
        <td>{$this->escPdf($prov['RFC'])}</td>
        <td><b>Condición Pago:</b></td>
        <td>{$this->escPdf($prov['CondicionPago'])} &nbsp;|&nbsp; <b>Límite Crédito:</b> $ {$this->fmt($prov['CreditLine'])}</td>
    </tr>
    <tr style="background-color:#f8f9fa;">
        <td><b>Teléfono:</b></td>
        <td>{$this->escPdf($prov['Phone1'])}</td>
        <td><b>Correo:</b></td>
        <td>{$this->escPdf($prov['EmailAddress'])}</td>
    </tr>
</table>
<br>
<table cellpadding="4" cellspacing="0" style="width:100%; font-size:8px; border:1px solid #a82020;">
    <tr style="background-color:#a82020; color:#ffffff; text-align:center; font-weight:bold;">
        <td style="width:16%;">Saldo Inicial</td>
        <td style="width:14%;">Cargos (Fact.)</td>
        <td style="width:14%;">Abonos (Pag/NC)</td>
        <td style="width:16%;">Saldo por Pagar</td>
        <td style="width:14%;">Saldo Vencido</td>
        <td style="width:13%;">Pagos a Cta.</td>
        <td style="width:13%;">Anticipos Fact.</td>
    </tr>
    <tr style="text-align:right; font-weight:bold; background-color:#fff8f8;">
        <td>$ {$this->fmt($kpis['saldoInicial'])}</td>
        <td>$ {$this->fmt($kpis['totalCargos'])}</td>
        <td>$ {$this->fmt($kpis['totalAbonos'])}</td>
        <td style="color:#a82020;">$ {$this->fmt($kpis['saldoCorte'])}</td>
        <td style="color:#c0392b;">$ {$this->fmt($kpis['vencido'])}</td>
        <td style="color:#1e7e34;">$ {$this->fmt($kpis['anticipoEfectivo'])}</td>
        <td style="color:#117a8b;">$ {$this->fmt($kpis['anticipoFacturacion'])}</td>
    </tr>
</table>
<br>
<table cellpadding="3" cellspacing="0" style="width:100%; font-size:7.5px; border:1px solid #cccccc;">
    <tr style="background-color:#343a40; color:#ffffff; text-align:center; font-weight:bold;">
        <td style="width:20%;">Corriente / Por Vencer</td>
        <td style="width:20%;">Vencido 1 - 30 días</td>
        <td style="width:20%;">Vencido 31 - 60 días</td>
        <td style="width:20%;">Vencido 61 - 90 días</td>
        <td style="width:20%;">Vencido +90 días</td>
    </tr>
    <tr style="text-align:right;">
        <td>$ {$this->fmt($aging['porVencer'])}</td>
        <td>$ {$this->fmt($aging['d1_30'])}</td>
        <td>$ {$this->fmt($aging['d31_60'])}</td>
        <td>$ {$this->fmt($aging['d61_90'])}</td>
        <td style="color:#c0392b; font-weight:bold;">$ {$this->fmt($aging['d90Mas'])}</td>
    </tr>
</table>
<br>
EOF;

            $rowsHtml = '';
            $i        = 0;
            foreach ($movs as $m) {
                $i++;
                $bg       = ($i % 2 === 0) ? '#f7f7f7' : '#ffffff';
                $colorSal = $m['SaldoPendiente'] > 0.005 ? '#a82020' : ($m['SaldoPendiente'] < -0.005 ? '#1e7e34' : '#666666');
                $cancelTx = $m['Cancelado'] === 'S' ? ' [CANC]' : '';

                $rowsHtml .= '
                <tr style="background-color:' . $bg . ';">
                    <td style="width:8%; text-align:center;">' . $this->escPdf($m['Fecha']) . '</td>
                    <td style="width:9%; text-align:center;"><b>' . $this->escPdf($m['CodigoTipo']) . '</b>' . $cancelTx . '</td>
                    <td style="width:8%; text-align:center;">' . $this->escPdf($m['Folio']) . '</td>
                    <td style="width:10%; text-align:center;">' . $this->escPdf($m['FacturaRel']) . '</td>
                    <td style="width:8%; text-align:center;">' . $this->escPdf($m['FechaVen']) . '</td>
                    <td style="width:19%; text-align:left;">' . $this->escPdf(mb_substr($m['Referencia'], 0, 42)) . '</td>
                    <td style="width:9.5%; text-align:right;">' . ($m['Cargo'] > 0 ? $this->fmt($m['Cargo']) : '') . '</td>
                    <td style="width:9.5%; text-align:right;">' . ($m['Abono'] > 0 ? $this->fmt($m['Abono']) : '') . '</td>
                    <td style="width:9.5%; text-align:right; color:' . $colorSal . ';">' . $this->fmt($m['SaldoPendiente']) . '</td>
                    <td style="width:9.5%; text-align:right; font-weight:bold;">' . $this->fmt($m['SaldoAcumulado']) . '</td>
                </tr>';
            }

            if ($rowsHtml === '') {
                $rowsHtml = '<tr><td colspan="10" style="text-align:center; padding:10px;">No existen movimientos en el rango y filtros seleccionados.</td></tr>';
            }

            $htmlTable = <<<EOF
<table cellpadding="3" cellspacing="0" border="0.2" style="width:100%; font-size:7px; border-color:#cccccc;">
    <thead>
        <tr style="background-color:#a82020; color:#ffffff; text-align:center; font-weight:bold;">
            <th style="width:8%;">Fecha</th>
            <th style="width:9%;">Tipo</th>
            <th style="width:8%;">Folio</th>
            <th style="width:10%;">Doc. Aplicado</th>
            <th style="width:8%;">Fec. Ven.</th>
            <th style="width:19%;">Referencia / Fact. Prov.</th>
            <th style="width:9.5%;">Cargo (Haber)</th>
            <th style="width:9.5%;">Abono (Debe)</th>
            <th style="width:9.5%;">Saldo Doc.</th>
            <th style="width:9.5%;">Saldo Acum.</th>
        </tr>
    </thead>
    <tbody>
        {$rowsHtml}
    </tbody>
</table>
<br>
<p style="font-size:7px; color:#666666; text-align:center;">
    Documento informativo generado desde SAP Business One HANA (Cuentas por Pagar). Los saldos reflejan pólizas y reconciliaciones hasta la fecha de corte ({$this->escPdf($params['fechaFin'])}).
</p>
EOF;

            $pdf = new \TCPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(8, 8, 8);
            $pdf->SetAutoPageBreak(true, 10);
            $pdf->AddPage();
            $pdf->writeHTML($htmlHeader . $htmlTable, true, false, true, false, '');

            if (ob_get_length()) {
                ob_end_clean();
            }

            $safeCode  = preg_replace('/[^A-Za-z0-9_\-]/', '', $prov['CardCode']);
            $fileName  = "EDO_CUENTA_PROV_{$safeCode}_{$params['fechaFin']}.pdf";
            $pdfBinary = $pdf->Output($fileName, 'S');

            $disposition = $this->request->getGet('download') ? 'attachment' : 'inline';

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', $disposition . '; filename="' . $fileName . '"')
                ->setHeader('Content-Length', (string) strlen($pdfBinary))
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Expires', '0')
                ->setBody($pdfBinary);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('Error al generar PDF: ' . esc($e->getMessage()));
        }
    }

    /**
     * Núcleo compartido para consultar HANA y armar proveedor, KPIs al corte, antigüedad y movimientos.
     * En Cuentas por Pagar (naturaleza acreedora):
     *   - Credit = Facturas / Anticipos (Cargo que aumenta la cuenta por pagar)
     *   - Debit  = Pagos Efectuados / Notas de Crédito (Abono que disminuye la cuenta por pagar)
     */
    private function buildStatementDataset(array $params): array
    {
        $idUser     = (int) $params['idUser'];
        $idEmpresa  = (int) $params['idEmpresa'];
        $cardCode   = trim((string) $params['cardCode']);
        $bplId      = trim((string) $params['bplId']);
        $fechaIni   = $this->sanitizeDate($params['fechaIni'], date('Y-m-01'));
        $fechaFin   = $this->sanitizeDate($params['fechaFin'], date('Y-m-d'));
        $tipoMov    = $params['tipoMov'];
        $estatusSal = $params['estatusSal'];
        $concepto   = $params['concepto'];
        $referencia = trim((string) $params['referencia']);
        $cancelados = $params['cancelados'];

        if ($fechaIni > $fechaFin) {
            $tmp      = $fechaIni;
            $fechaIni = $fechaFin;
            $fechaFin = $tmp;
        }

        $sucursales    = $this->getAllowedSAPBranches($idUser, $idEmpresa);
        $allowedBplIn  = array_column($sucursales, 'bplId');
        $sucursalTexto = 'TODAS LAS SUCURSALES';

        $conn          = $this->connectODBC($idEmpresa);
        $cardCodeClean = str_replace("'", "''", $cardCode);

        $whereBplJdt = '';
        $whereBplDoc = '';
        if ($bplId !== '' && $bplId !== 'TODOS' && is_numeric($bplId)) {
            $bplInt      = (int) $bplId;
            $whereBplJdt = " AND t0.\"BPLId\" = {$bplInt} ";
            $whereBplDoc = " AND \"BPLId\" = {$bplInt} ";
            foreach ($sucursales as $s) {
                if ((int) $s['bplId'] === $bplInt) {
                    $sucursalTexto = $s['text'];
                    break;
                }
            }
        } elseif (!empty($allowedBplIn)) {
            $inStr       = implode(',', array_map('intval', $allowedBplIn));
            $whereBplJdt = " AND (t0.\"BPLId\" IN ({$inStr}) OR t0.\"BPLId\" IS NULL) ";
            $whereBplDoc = " AND \"BPLId\" IN ({$inStr}) ";
        }

        $sapUserId = $this->resolveLinkedSapUserId($conn, $idEmpresa, $idUser);
        if ($sapUserId > 0) {
            $whereBplJdt .= " AND (t0.\"BPLId\" IS NULL OR EXISTS (SELECT 1 FROM \"USR6\" u6 WHERE u6.\"BPLId\" = t0.\"BPLId\" AND u6.\"UserID\" = {$sapUserId})) ";
        }

        // 1. Datos generales del proveedor (OCRD + OCTG)
        $proveedor = [
            'CardCode'      => $cardCode,
            'CardName'      => '',
            'RFC'           => '',
            'Phone1'        => '',
            'EmailAddress'  => '',
            'Currency'      => 'MXP',
            'CreditLine'    => 0.0,
            'BalanceActual' => 0.0,
            'CondicionPago' => 'Contado',
        ];

        $sqlProv = "
            SELECT
                c.\"CardCode\",
                c.\"CardName\",
                IFNULL(c.\"LicTradNum\", '') AS \"RFC\",
                IFNULL(c.\"Phone1\", '') AS \"Phone1\",
                IFNULL(c.\"E_Mail\", '') AS \"EmailAddress\",
                IFNULL(c.\"Currency\", 'MXP') AS \"Currency\",
                IFNULL(c.\"CreditLine\", 0) AS \"CreditLine\",
                IFNULL(c.\"Balance\", 0) AS \"Balance\",
                IFNULL(g.\"PymntGroup\", 'Contado') AS \"CondicionPago\"
            FROM \"OCRD\" c
            LEFT JOIN \"OCTG\" g ON g.\"GroupNum\" = c.\"GroupNum\"
            WHERE c.\"CardCode\" = '{$cardCodeClean}'
            LIMIT 1
        ";
        $rsProv = @odbc_exec($conn, $sqlProv);
        if ($rsProv && ($rProv = odbc_fetch_array($rsProv))) {
            $proveedor = [
                'CardCode'      => trim($this->toUtf8($rProv['CardCode'] ?? $cardCode)),
                'CardName'      => trim($this->toUtf8($rProv['CardName'] ?? '')),
                'RFC'           => trim($this->toUtf8($rProv['RFC'] ?? '')),
                'Phone1'        => trim($this->toUtf8($rProv['Phone1'] ?? '')),
                'EmailAddress'  => trim($this->toUtf8($rProv['EmailAddress'] ?? '')),
                'Currency'      => trim($this->toUtf8($rProv['Currency'] ?? 'MXP')),
                'CreditLine'    => round((float) ($rProv['CreditLine'] ?? 0), 2),
                'BalanceActual' => round((float) ($rProv['Balance'] ?? 0), 2),
                'CondicionPago' => trim($this->toUtf8($rProv['CondicionPago'] ?? 'Contado')),
            ];
            odbc_free_result($rsProv);
        }

        // 2. Saldo Inicial por pagar (anterior a fechaIni): Credit - Debit
        $saldoInicial = 0.0;
        $sqlIni       = "
            SELECT IFNULL(SUM(t0.\"Credit\" - t0.\"Debit\"), 0) AS \"SaldoInicial\"
            FROM \"JDT1\" t0
            WHERE t0.\"ShortName\" = '{$cardCodeClean}'
              AND t0.\"RefDate\" < '{$fechaIni}'
              {$whereBplJdt}
        ";
        $rsIni = @odbc_exec($conn, $sqlIni);
        if ($rsIni && ($rIni = odbc_fetch_array($rsIni))) {
            $saldoInicial = round((float) ($rIni['SaldoInicial'] ?? 0), 2);
            odbc_free_result($rsIni);
        }

        // 3. Saldos al Corte y Antigüedad histórica a fechaFin (OITR/ITR1 <= fechaFin)
        $sqlCorte = "
            WITH recon AS (
                SELECT
                    r1.\"TransId\",
                    r1.\"TransRowId\",
                    SUM(CASE WHEN r1.\"IsCredit\" = 'D' THEN r1.\"ReconSum\" ELSE 0 END) AS \"ReconDeb\",
                    SUM(CASE WHEN r1.\"IsCredit\" = 'C' THEN r1.\"ReconSum\" ELSE 0 END) AS \"ReconCred\"
                FROM \"ITR1\" r1
                INNER JOIN \"OITR\" r0 ON r0.\"ReconNum\" = r1.\"ReconNum\"
                WHERE r0.\"Canceled\" = 'N'
                  AND r0.\"ReconDate\" <= '{$fechaFin}'
                  AND r1.\"ShortName\" = '{$cardCodeClean}'
                GROUP BY r1.\"TransId\", r1.\"TransRowId\"
            ),
            movs_corte AS (
                SELECT
                    t0.\"TransType\",
                    t0.\"DueDate\",
                    (t0.\"Credit\" - t0.\"Debit\") AS \"Neto\",
                    CASE
                        WHEN t0.\"Credit\" > 0 THEN (t0.\"Credit\" - IFNULL(rc.\"ReconCred\", 0))
                        ELSE -(t0.\"Debit\" - IFNULL(rc.\"ReconDeb\", 0))
                    END AS \"SaldoPendiente\"
                FROM \"JDT1\" t0
                LEFT JOIN recon rc ON rc.\"TransId\" = t0.\"TransId\" AND rc.\"TransRowId\" = t0.\"Line_ID\"
                WHERE t0.\"ShortName\" = '{$cardCodeClean}'
                  AND t0.\"RefDate\" <= '{$fechaFin}'
                  {$whereBplJdt}
            )
            SELECT
                IFNULL(SUM(\"Neto\"), 0) AS \"SaldoCorte\",
                IFNULL(SUM(CASE WHEN \"SaldoPendiente\" > 0.005 AND \"DueDate\" < '{$fechaFin}' THEN \"SaldoPendiente\" ELSE 0 END), 0) AS \"Vencido\",
                IFNULL(SUM(CASE WHEN \"TransType\" = '46' AND \"SaldoPendiente\" < -0.005 THEN ABS(\"SaldoPendiente\") ELSE 0 END), 0) AS \"AnticipoEfectivo\",
                IFNULL(SUM(CASE WHEN \"SaldoPendiente\" > 0.005 AND DAYS_BETWEEN(\"DueDate\", '{$fechaFin}') <= 0 THEN \"SaldoPendiente\" ELSE 0 END), 0) AS \"PorVencer\",
                IFNULL(SUM(CASE WHEN \"SaldoPendiente\" > 0.005 AND DAYS_BETWEEN(\"DueDate\", '{$fechaFin}') BETWEEN 1 AND 30 THEN \"SaldoPendiente\" ELSE 0 END), 0) AS \"D1_30\",
                IFNULL(SUM(CASE WHEN \"SaldoPendiente\" > 0.005 AND DAYS_BETWEEN(\"DueDate\", '{$fechaFin}') BETWEEN 31 AND 60 THEN \"SaldoPendiente\" ELSE 0 END), 0) AS \"D31_60\",
                IFNULL(SUM(CASE WHEN \"SaldoPendiente\" > 0.005 AND DAYS_BETWEEN(\"DueDate\", '{$fechaFin}') BETWEEN 61 AND 90 THEN \"SaldoPendiente\" ELSE 0 END), 0) AS \"D61_90\",
                IFNULL(SUM(CASE WHEN \"SaldoPendiente\" > 0.005 AND DAYS_BETWEEN(\"DueDate\", '{$fechaFin}') > 90 THEN \"SaldoPendiente\" ELSE 0 END), 0) AS \"D90Mas\"
            FROM movs_corte
        ";

        $saldoCorte       = 0.0;
        $vencido          = 0.0;
        $anticipoEfectivo = 0.0;
        $aging            = [
            'porVencer' => 0.0,
            'd1_30'     => 0.0,
            'd31_60'    => 0.0,
            'd61_90'    => 0.0,
            'd90Mas'    => 0.0,
        ];

        $rsCorte = @odbc_exec($conn, $sqlCorte);
        if ($rsCorte && ($rC = odbc_fetch_array($rsCorte))) {
            $saldoCorte         = round((float) ($rC['SaldoCorte'] ?? 0), 2);
            $vencido            = round((float) ($rC['Vencido'] ?? 0), 2);
            $anticipoEfectivo   = round((float) ($rC['AnticipoEfectivo'] ?? 0), 2);
            $aging['porVencer'] = round((float) ($rC['PorVencer'] ?? 0), 2);
            $aging['d1_30']     = round((float) ($rC['D1_30'] ?? 0), 2);
            $aging['d31_60']    = round((float) ($rC['D31_60'] ?? 0), 2);
            $aging['d61_90']    = round((float) ($rC['D61_90'] ?? 0), 2);
            $aging['d90Mas']    = round((float) ($rC['D90Mas'] ?? 0), 2);
            odbc_free_result($rsCorte);
        }

        // 4. Anticipos de Facturación a Proveedores disponibles por aplicar (ODPO pagados menos aplicados)
        $anticipoFacturacion = 0.0;
        $sqlAntFact          = "
            SELECT IFNULL(SUM(
                CASE WHEN (IFNULL(\"PaidToDate\", 0) - IFNULL(\"DpmAppl\", 0)) > 0
                     THEN (IFNULL(\"PaidToDate\", 0) - IFNULL(\"DpmAppl\", 0))
                     ELSE 0 END
            ), 0) AS \"AnticipoFact\"
            FROM \"ODPO\"
            WHERE \"CardCode\" = '{$cardCodeClean}'
              AND \"CANCELED\" = 'N'
              AND \"DocDate\" <= '{$fechaFin}'
              {$whereBplDoc}
        ";
        $rsAntFact = @odbc_exec($conn, $sqlAntFact);
        if ($rsAntFact && ($rAf = odbc_fetch_array($rsAntFact))) {
            $anticipoFacturacion = round((float) ($rAf['AnticipoFact'] ?? 0), 2);
            odbc_free_result($rsAntFact);
        }

        // 5. Movimientos detallados en el periodo [fechaIni .. fechaFin]
        $sqlMovs = "
            WITH recon AS (
                SELECT
                    r1.\"TransId\",
                    r1.\"TransRowId\",
                    SUM(CASE WHEN r1.\"IsCredit\" = 'D' THEN r1.\"ReconSum\" ELSE 0 END) AS \"ReconDeb\",
                    SUM(CASE WHEN r1.\"IsCredit\" = 'C' THEN r1.\"ReconSum\" ELSE 0 END) AS \"ReconCred\"
                FROM \"ITR1\" r1
                INNER JOIN \"OITR\" r0 ON r0.\"ReconNum\" = r1.\"ReconNum\"
                WHERE r0.\"Canceled\" = 'N'
                  AND r0.\"ReconDate\" <= '{$fechaFin}'
                  AND r1.\"ShortName\" = '{$cardCodeClean}'
                GROUP BY r1.\"TransId\", r1.\"TransRowId\"
            ),
            pagos_docs AS (
                SELECT
                    p2.\"DocNum\" AS \"PayDocEntry\",
                    STRING_AGG(
                        CAST(COALESCE(fi.\"DocNum\", fd.\"DocNum\", fn.\"DocNum\", p2.\"DocEntry\") AS NVARCHAR(20)),
                        ', '
                    ) AS \"DocsPagados\"
                FROM \"VPM2\" p2
                INNER JOIN \"OVPM\" p0 ON p0.\"DocEntry\" = p2.\"DocNum\"
                LEFT JOIN \"OPCH\" fi ON p2.\"InvType\" = '18'  AND fi.\"DocEntry\" = p2.\"DocEntry" . '"' . "
                LEFT JOIN \"ODPO\" fd ON p2.\"InvType\" = '204' AND fd.\"DocEntry\" = p2.\"DocEntry" . '"' . "
                LEFT JOIN \"ORPC\" fn ON p2.\"InvType\" = '19'  AND fn.\"DocEntry\" = p2.\"DocEntry" . '"' . "
                WHERE p0.\"CardCode\" = '{$cardCodeClean}'
                  AND p0.\"DocDate\" BETWEEN '{$fechaIni}' AND '{$fechaFin}'
                GROUP BY p2.\"DocNum\"
            )
            SELECT
                t0.\"TransId\",
                t0.\"Line_ID\",
                t0.\"TransType\" AS \"ObjType\",
                IFNULL(t0.\"CreatedBy\", t0.\"TransId\") AS \"DocEntry\",
                TO_VARCHAR(t0.\"RefDate\", 'YYYY-MM-DD') AS \"Fecha\",
                TO_VARCHAR(t0.\"DueDate\", 'YYYY-MM-DD') AS \"FechaVen\",
                IFNULL(t0.\"BPLId\", 0) AS \"BPLId\",
                IFNULL(bpl.\"BPLName\", IFNULL(t0.\"BPLName\", '')) AS \"BPLName\",
                COALESCE(
                    CAST(pch.\"DocNum\" AS NVARCHAR(30)),
                    CAST(rpc.\"DocNum\" AS NVARCHAR(30)),
                    CAST(dpo.\"DocNum\" AS NVARCHAR(30)),
                    CAST(vpm.\"DocNum\" AS NVARCHAR(30)),
                    CAST(t1.\"Number\" AS NVARCHAR(30))
                ) AS \"Folio\",
                COALESCE(
                    pd.\"DocsPagados\",
                    CASE WHEN t0.\"TransType\" = '18' THEN CAST(pch.\"DocNum\" AS NVARCHAR(30)) ELSE '' END,
                    ''
                ) AS \"FacturaRel\",
                COALESCE(
                    NULLIF(pch.\"NumAtCard\", ''),
                    NULLIF(rpc.\"NumAtCard\", ''),
                    NULLIF(dpo.\"NumAtCard\", ''),
                    NULLIF(vpm.\"TrsfrRef\", ''),
                    NULLIF(t0.\"Ref2\", ''),
                    NULLIF(t0.\"LineMemo\", ''),
                    IFNULL(t1.\"Memo\", '')
                ) AS \"Referencia\",
                IFNULL(t0.\"Credit\", 0) AS \"Cargo\",
                IFNULL(t0.\"Debit\", 0) AS \"Abono\",
                CASE
                    WHEN t0.\"Credit\" > 0 THEN (t0.\"Credit\" - IFNULL(rc.\"ReconCred\", 0))
                    ELSE -(t0.\"Debit\" - IFNULL(rc.\"ReconDeb\", 0))
                END AS \"SaldoPendiente\",
                CASE
                    WHEN t0.\"TransType\" = '18'  AND pch.\"CANCELED\" IN ('Y', 'C') THEN 'S'
                    WHEN t0.\"TransType\" = '19'  AND rpc.\"CANCELED\" IN ('Y', 'C') THEN 'S'
                    WHEN t0.\"TransType\" = '204' AND dpo.\"CANCELED\" IN ('Y', 'C') THEN 'S'
                    WHEN t0.\"TransType\" = '46'  AND vpm.\"Canceled\" = 'Y' THEN 'S'
                    WHEN IFNULL(t1.\"StornoToTr\", 0) > 0 THEN 'S'
                    ELSE 'N'
                END AS \"Cancelado\",
                IFNULL(pch.\"isIns\", 'N') AS \"EsReserva\",
                COALESCE(pch.\"DocCur\", rpc.\"DocCur\", dpo.\"DocCur\", vpm.\"DocCurr\", 'MXP') AS \"Moneda\"
            FROM \"JDT1\" t0
            INNER JOIN \"OJDT\" t1 ON t1.\"TransId\" = t0.\"TransId\"
            LEFT JOIN recon rc ON rc.\"TransId\" = t0.\"TransId\" AND rc.\"TransRowId\" = t0.\"Line_ID\"
            LEFT JOIN \"OBPL\" bpl ON bpl.\"BPLId\" = t0.\"BPLId\"
            LEFT JOIN \"OPCH\" pch ON t0.\"TransType\" = '18'  AND pch.\"DocEntry\" = t0.\"CreatedBy\"
            LEFT JOIN \"ORPC\" rpc ON t0.\"TransType\" = '19'  AND rpc.\"DocEntry\" = t0.\"CreatedBy\"
            LEFT JOIN \"ODPO\" dpo ON t0.\"TransType\" = '204' AND dpo.\"DocEntry\" = t0.\"CreatedBy\"
            LEFT JOIN \"OVPM\" vpm ON t0.\"TransType\" = '46'  AND vpm.\"DocEntry\" = t0.\"CreatedBy\"
            LEFT JOIN pagos_docs pd ON t0.\"TransType\" = '46' AND pd.\"PayDocEntry\" = t0.\"CreatedBy\"
            WHERE t0.\"ShortName\" = '{$cardCodeClean}'
              AND t0.\"RefDate\" BETWEEN '{$fechaIni}' AND '{$fechaFin}'
              {$whereBplJdt}
            ORDER BY t0.\"RefDate\" ASC, t0.\"TransId\" ASC, t0.\"Line_ID\" ASC
        ";

        $movimientos = [];
        $saldoAcum   = $saldoInicial;
        $totalCargos = 0.0;
        $totalAbonos = 0.0;

        $rsMovs = odbc_exec($conn, $sqlMovs);
        if ($rsMovs) {
            while ($row = odbc_fetch_array($rsMovs)) {
                $objType   = trim((string) ($row['ObjType'] ?? '30'));
                $docEntry  = (int) ($row['DocEntry'] ?? 0);
                $transId   = (int) ($row['TransId'] ?? 0);
                $cargo     = round((float) ($row['Cargo'] ?? 0), 2);
                $abono     = round((float) ($row['Abono'] ?? 0), 2);
                $salPend   = round((float) ($row['SaldoPendiente'] ?? 0), 2);
                $canc      = trim($this->toUtf8($row['Cancelado'] ?? 'N'));
                $esReserva = ($objType === '18' && trim((string) ($row['EsReserva'] ?? 'N')) === 'Y');
                $refText   = trim($this->toUtf8($row['Referencia'] ?? ''));
                $folio     = trim($this->toUtf8($row['Folio'] ?? ''));
                $facRel    = trim($this->toUtf8($row['FacturaRel'] ?? ''));
                $fecha     = trim($this->toUtf8($row['Fecha'] ?? ''));
                $fechaVen  = trim($this->toUtf8($row['FechaVen'] ?? ''));

                // Saldo acumulado cronológico real del periodo (CxP: +Facturas -Pagos/NC)
                $saldoAcum = round($saldoAcum + ($cargo - $abono), 2);

                if ($cancelados === 'N' && $canc === 'S') {
                    continue;
                }
                if ($tipoMov === 'CARGOS' && $cargo <= 0) {
                    continue;
                }
                if ($tipoMov === 'ABONOS' && $abono <= 0) {
                    continue;
                }
                if ($estatusSal === 'SIN_SALDAR' && abs($salPend) <= 0.005) {
                    continue;
                }
                if ($estatusSal === 'SALDADOS' && abs($salPend) > 0.005) {
                    continue;
                }
                if ($concepto !== '' && $concepto !== 'TODOS') {
                    if ($concepto === '18_RES' && !($objType === '18' && $esReserva)) {
                        continue;
                    } elseif ($concepto === '18_NORM' && !($objType === '18' && !$esReserva)) {
                        continue;
                    } elseif (!in_array($concepto, ['18_RES', '18_NORM'], true) && $objType !== $concepto) {
                        continue;
                    }
                }
                if ($referencia !== '' && $referencia !== '*') {
                    $haystack = mb_strtoupper("{$refText} {$folio} {$facRel}");
                    if (mb_strpos($haystack, mb_strtoupper($referencia)) === false) {
                        continue;
                    }
                }

                $totalCargos += $cargo;
                $totalAbonos += $abono;

                $metaTipo = $this->getObjTypeMeta($objType, $esReserva);
                $estatus  = 'SALDADO';
                if ($canc === 'S') {
                    $estatus = 'CANCELADO';
                } elseif (abs($salPend) > 0.005) {
                    if ($salPend > 0 && $fechaVen !== '' && $fechaVen < $fechaFin) {
                        $estatus = 'VENCIDO';
                    } elseif ($salPend < 0) {
                        $estatus = 'A FAVOR';
                    } else {
                        $estatus = 'PENDIENTE';
                    }
                }

                $movimientos[] = [
                    'TransId'        => $transId,
                    'ObjType'        => $objType,
                    'DocEntry'       => $docEntry,
                    'EsReserva'      => $esReserva ? 'S' : 'N',
                    'CodigoTipo'     => $metaTipo['code'],
                    'NombreTipo'     => $metaTipo['name'],
                    'BadgeColor'     => $metaTipo['badge'],
                    'Fecha'          => $fecha,
                    'FechaVen'       => $fechaVen,
                    'Folio'          => $folio,
                    'FacturaRel'     => $facRel,
                    'BPLName'        => trim($this->toUtf8($row['BPLName'] ?? '')),
                    'Referencia'     => $refText,
                    'Moneda'         => trim($this->toUtf8($row['Moneda'] ?? 'MXP')),
                    'Cargo'          => $cargo,
                    'Abono'          => $abono,
                    'SaldoPendiente' => $salPend,
                    'SaldoAcumulado' => $saldoAcum,
                    'Cancelado'      => $canc,
                    'Estatus'        => $estatus,
                ];
            }
            odbc_free_result($rsMovs);
        }

        odbc_close($conn);

        return [
            'proveedor'     => $proveedor,
            'sucursalTexto' => $sucursalTexto,
            'kpis'          => [
                'saldoInicial'        => round($saldoInicial, 2),
                'totalCargos'         => round($totalCargos, 2),
                'totalAbonos'         => round($totalAbonos, 2),
                'saldoCorte'          => round($saldoCorte, 2),
                'vencido'             => round($vencido, 2),
                'anticipoEfectivo'    => round($anticipoEfectivo, 2),
                'anticipoFacturacion' => round($anticipoFacturacion, 2),
            ],
            'aging'       => $aging,
            'movimientos' => $movimientos,
        ];
    }

    /**
     * Obtiene el detalle completo de un documento de Compras / CxP en SAP
     * (Solicitudes, Ofertas, Órdenes de Compra, Entradas, Devoluciones, Facturas, Reservas, Anticipos, NC, Pagos Efectuados y Asientos).
     */
    private function fetchDocumentDetail($conn, string $objType, int $docEntry, int $transId = 0): array
    {
        // 0. Solicitud de Compra (OPRQ / PRQ1) — esquema especial sin CardCode/PaidToDate
        if ($objType === '1470000113') {
            $sqlReqH = "
                SELECT
                    h.\"DocEntry\",
                    h.\"DocNum\",
                    TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                    TO_VARCHAR(h.\"ReqDate\", 'YYYY-MM-DD') AS \"ReqDate\",
                    IFNULL(h.\"Requester\", '') AS \"Requester\",
                    IFNULL(h.\"ReqName\", '') AS \"ReqName\",
                    IFNULL(h.\"Comments\", '') AS \"Comments\",
                    IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\",
                    IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\",
                    IFNULL(h.\"DocCur\", 'MXP') AS \"DocCur\",
                    IFNULL(h.\"DocTotal\", 0) AS \"DocTotal\",
                    IFNULL(h.\"VatSum\", 0) AS \"VatSum\",
                    IFNULL(b.\"BPLName\", '') AS \"BPLName\"
                FROM \"OPRQ\" h
                LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
                WHERE h.\"DocEntry\" = {$docEntry}
                LIMIT 1
            ";

            $rsH    = odbc_exec($conn, $sqlReqH);
            $header = $rsH ? odbc_fetch_array($rsH) : null;
            if ($rsH) {
                odbc_free_result($rsH);
            }
            if (!$header) {
                throw new Exception("No se encontró la Solicitud de Compra #{$docEntry}.");
            }

            $meta = $this->getObjTypeMeta($objType);
            $sqlL = "
                SELECT
                    \"LineNum\",
                    IFNULL(\"ItemCode\", '') AS \"ItemCode\",
                    IFNULL(\"Dscription\", '') AS \"Dscription\",
                    IFNULL(\"Quantity\", 0) AS \"Quantity\",
                    IFNULL(\"unitMsr\", '') AS \"UnitMsr\",
                    IFNULL(\"Price\", 0) AS \"Price\",
                    IFNULL(\"DiscPrcnt\", 0) AS \"DiscPrcnt\",
                    IFNULL(\"VatSum\", 0) AS \"VatSum\",
                    IFNULL(\"LineTotal\", 0) AS \"LineTotal\",
                    IFNULL(\"WhsCode\", '') AS \"WhsCode\"
                FROM \"PRQ1\"
                WHERE \"DocEntry\" = {$docEntry}
                ORDER BY \"VisOrder\" ASC, \"LineNum\" ASC
            ";

            $lines = [];
            $rsL   = odbc_exec($conn, $sqlL);
            if ($rsL) {
                while ($r = odbc_fetch_array($rsL)) {
                    $qty     = round((float) ($r['Quantity'] ?? 0), 2);
                    $uom     = trim($this->toUtf8($r['UnitMsr'] ?? ''));
                    $lines[] = [
                        'col1'  => trim($this->toUtf8($r['ItemCode'] ?? '')),
                        'col2'  => trim($this->toUtf8($r['Dscription'] ?? '')),
                        'col3'  => "{$qty} {$uom}",
                        'col4'  => round((float) ($r['Price'] ?? 0), 2),
                        'col5'  => round((float) ($r['DiscPrcnt'] ?? 0), 2),
                        'col6'  => round((float) ($r['VatSum'] ?? 0), 2),
                        'col7'  => round((float) ($r['LineTotal'] ?? 0), 2),
                        'extra' => trim($this->toUtf8($r['WhsCode'] ?? '')),
                    ];
                }
                odbc_free_result($rsL);
            }

            $docTotal = round((float) ($header['DocTotal'] ?? 0), 2);
            $vatSum   = round((float) ($header['VatSum'] ?? 0), 2);

            return [
                'mode'     => 'MARKETING',
                'objType'  => $objType,
                'tipo'     => $meta['name'],
                'badge'    => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'],
                'docNum'   => trim($this->toUtf8($header['DocNum'] ?? '')),
                'transId'  => 0,
                'header'   => [
                    'Fecha'           => trim($this->toUtf8($header['DocDate'] ?? '')),
                    'Fecha Requerida' => trim($this->toUtf8($header['ReqDate'] ?? '')),
                    'Solicitante'     => trim($this->toUtf8($header['Requester'] ?? '')) . ' - ' . trim($this->toUtf8($header['ReqName'] ?? '')),
                    'Sucursal'        => trim($this->toUtf8($header['BPLName'] ?? '')),
                    'Estatus'         => ($header['Canceled'] === 'Y' || $header['Canceled'] === 'C')
                        ? 'CANCELADO'
                        : ($header['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO'),
                    'Moneda'          => trim($this->toUtf8($header['DocCur'] ?? 'MXP')),
                    'Comentarios'     => trim($this->toUtf8($header['Comments'] ?? '')),
                ],
                'totals' => [
                    'Subtotal'    => round($docTotal - $vatSum, 2),
                    'AnticipoApl' => 0.0,
                    'IVA'         => $vatSum,
                    'Total'       => $docTotal,
                    'Pagado'      => 0.0,
                    'SaldoActual' => 0.0,
                ],
                'lines' => $lines,
            ];
        }

        // 1. Documentos de Marketing de Compras
        $marketingTables = [
            '540000006' => ['H' => 'OPQT', 'L' => 'PQT1'],
            '22'        => ['H' => 'OPOR', 'L' => 'POR1'],
            '20'        => ['H' => 'OPDN', 'L' => 'PDN1'],
            '21'        => ['H' => 'ORPD', 'L' => 'RPD1'],
            '18'        => ['H' => 'OPCH', 'L' => 'PCH1'],
            '19'        => ['H' => 'ORPC', 'L' => 'RPC1'],
            '204'       => ['H' => 'ODPO', 'L' => 'DPO1'],
        ];

        if (isset($marketingTables[$objType])) {
            $tH = $marketingTables[$objType]['H'];
            $tL = $marketingTables[$objType]['L'];

            $extraCols = ($objType === '18')
                ? ', IFNULL(h."isIns", \'N\') AS "isIns", IFNULL(h."InvntSttus", \'\') AS "InvntSttus"'
                : ', \'N\' AS "isIns", \'\' AS "InvntSttus"';

            $sqlH = "
                SELECT
                    h.\"DocEntry\",
                    h.\"DocNum\",
                    IFNULL(h.\"TransId\", 0) AS \"TransId\",
                    TO_VARCHAR(h.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                    TO_VARCHAR(h.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\",
                    h.\"CardCode\",
                    h.\"CardName\",
                    IFNULL(h.\"NumAtCard\", '') AS \"NumAtCard\",
                    IFNULL(h.\"Comments\", '') AS \"Comments\",
                    IFNULL(h.\"DocStatus\", 'O') AS \"DocStatus\",
                    IFNULL(h.\"CANCELED\", 'N') AS \"Canceled\",
                    IFNULL(h.\"DocCur\", 'MXP') AS \"DocCur\",
                    IFNULL(h.\"DocTotal\", 0) AS \"DocTotal\",
                    IFNULL(h.\"VatSum\", 0) AS \"VatSum\",
                    IFNULL(h.\"PaidToDate\", 0) AS \"PaidToDate\",
                    IFNULL(h.\"DpmAmnt\", 0) AS \"DpmAmnt\",
                    IFNULL(b.\"BPLName\", '') AS \"BPLName\",
                    IFNULL(s.\"SlpName\", '') AS \"SlpName\",
                    IFNULL(h.\"EDocNum\", '') AS \"UUID\"
                    {$extraCols}
                FROM \"{$tH}\" h
                LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = h.\"BPLId\"
                LEFT JOIN \"OSLP\" s ON s.\"SlpCode\" = h.\"SlpCode\"
                WHERE h.\"DocEntry\" = {$docEntry}
                LIMIT 1
            ";

            $rsH    = odbc_exec($conn, $sqlH);
            $header = $rsH ? odbc_fetch_array($rsH) : null;
            if ($rsH) {
                odbc_free_result($rsH);
            }
            if (!$header) {
                throw new Exception("No se encontró el documento ({$objType}) #{$docEntry}.");
            }

            $esReserva = ($objType === '18' && trim((string) ($header['isIns'] ?? 'N')) === 'Y');
            $meta      = $this->getObjTypeMeta($objType, $esReserva);

            $sqlL = "
                SELECT
                    \"LineNum\",
                    IFNULL(\"ItemCode\", '') AS \"ItemCode\",
                    IFNULL(\"Dscription\", '') AS \"Dscription\",
                    IFNULL(\"Quantity\", 0) AS \"Quantity\",
                    IFNULL(\"OpenQty\", 0) AS \"OpenQty\",
                    IFNULL(\"unitMsr\", '') AS \"UnitMsr\",
                    IFNULL(\"Price\", 0) AS \"Price\",
                    IFNULL(\"DiscPrcnt\", 0) AS \"DiscPrcnt\",
                    IFNULL(\"VatSum\", 0) AS \"VatSum\",
                    IFNULL(\"LineTotal\", 0) AS \"LineTotal\",
                    IFNULL(\"WhsCode\", '') AS \"WhsCode\"
                FROM \"{$tL}\"
                WHERE \"DocEntry\" = {$docEntry}
                ORDER BY \"VisOrder\" ASC, \"LineNum\" ASC
            ";

            $lines = [];
            $rsL   = odbc_exec($conn, $sqlL);
            if ($rsL) {
                while ($r = odbc_fetch_array($rsL)) {
                    $qty     = round((float) ($r['Quantity'] ?? 0), 2);
                    $openQty = round((float) ($r['OpenQty'] ?? 0), 2);
                    $uom     = trim($this->toUtf8($r['UnitMsr'] ?? ''));

                    $qtyText = "{$qty} {$uom}";
                    if ($esReserva) {
                        $recibido = round($qty - $openQty, 2);
                        $qtyText .= " (Rec: {$recibido} / Pend: {$openQty})";
                    }

                    $lines[] = [
                        'col1'  => trim($this->toUtf8($r['ItemCode'] ?? '')),
                        'col2'  => trim($this->toUtf8($r['Dscription'] ?? '')),
                        'col3'  => $qtyText,
                        'col4'  => round((float) ($r['Price'] ?? 0), 2),
                        'col5'  => round((float) ($r['DiscPrcnt'] ?? 0), 2),
                        'col6'  => round((float) ($r['VatSum'] ?? 0), 2),
                        'col7'  => round((float) ($r['LineTotal'] ?? 0), 2),
                        'extra' => trim($this->toUtf8($r['WhsCode'] ?? '')),
                    ];
                }
                odbc_free_result($rsL);
            }

            $docTotal   = round((float) ($header['DocTotal'] ?? 0), 2);
            $vatSum     = round((float) ($header['VatSum'] ?? 0), 2);
            $paidToDate = round((float) ($header['PaidToDate'] ?? 0), 2);
            $dpmAmnt    = round((float) ($header['DpmAmnt'] ?? 0), 2);
            $subtotal   = round($docTotal - $vatSum + $dpmAmnt, 2);

            $headerInfo = [
                'Fecha'           => trim($this->toUtf8($header['DocDate'] ?? '')),
                'Vencimiento'     => trim($this->toUtf8($header['DocDueDate'] ?? '')),
                'Proveedor'       => trim($this->toUtf8($header['CardCode'] ?? '')) . ' - ' . trim($this->toUtf8($header['CardName'] ?? '')),
                'Sucursal'        => trim($this->toUtf8($header['BPLName'] ?? '')),
                'Comprador'       => trim($this->toUtf8($header['SlpName'] ?? '')),
                'Ref. Fact. Prov' => trim($this->toUtf8($header['NumAtCard'] ?? '')),
                'UUID / Folio'    => trim($this->toUtf8($header['UUID'] ?? '')),
                'Estatus'         => ($header['Canceled'] === 'Y' || $header['Canceled'] === 'C')
                    ? 'CANCELADO'
                    : ($header['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO'),
                'Moneda'          => trim($this->toUtf8($header['DocCur'] ?? 'MXP')),
                'Comentarios'     => trim($this->toUtf8($header['Comments'] ?? '')),
            ];

            if ($esReserva) {
                $invSt                                  = trim((string) ($header['InvntSttus'] ?? ''));
                $headerInfo['Estatus Recepción (Inv.)'] = ($invSt === 'C') ? 'RECIBIDO TOTAL' : 'ENTRADAS PENDIENTES';
            }

            return [
                'mode'     => 'MARKETING',
                'objType'  => $objType,
                'tipo'     => $meta['name'],
                'badge'    => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'],
                'docNum'   => trim($this->toUtf8($header['DocNum'] ?? '')),
                'transId'  => (int) ($header['TransId'] ?? 0),
                'header'   => $headerInfo,
                'totals'   => [
                    'Subtotal'    => $subtotal,
                    'AnticipoApl' => $dpmAmnt,
                    'IVA'         => $vatSum,
                    'Total'       => $docTotal,
                    'Pagado'      => $paidToDate,
                    'SaldoActual' => round($docTotal - $paidToDate, 2),
                ],
                'lines' => $lines,
            ];
        }

        $meta = $this->getObjTypeMeta($objType);

        // 2. Pagos Efectuados (OVPM / VPM2)
        if ($objType === '46') {
            $sqlP = "
                SELECT
                    p.\"DocEntry\", p.\"DocNum\", IFNULL(p.\"TransId\", 0) AS \"TransId\",
                    TO_VARCHAR(p.\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                    TO_VARCHAR(p.\"DocDueDate\", 'YYYY-MM-DD') AS \"DocDueDate\",
                    p.\"CardCode\", p.\"CardName\", IFNULL(p.\"Comments\", '') AS \"Comments\",
                    IFNULL(p.\"Canceled\", 'N') AS \"Canceled\", IFNULL(p.\"DocCurr\", 'MXP') AS \"DocCurr\",
                    IFNULL(p.\"DocTotal\", 0) AS \"DocTotal\", IFNULL(p.\"CashSum\", 0) AS \"CashSum\",
                    IFNULL(p.\"TrsfrSum\", 0) AS \"TrsfrSum\", IFNULL(p.\"CheckSum\", 0) AS \"CheckSum\",
                    IFNULL(p.\"CreditSum\", 0) AS \"CreditSum\", IFNULL(p.\"NoDocSum\", 0) AS \"NoDocSum\",
                    IFNULL(p.\"TrsfrRef\", '') AS \"TrsfrRef\", IFNULL(b.\"BPLName\", '') AS \"BPLName\"
                FROM \"OVPM\" p
                LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = p.\"BPLId\"
                WHERE p.\"DocEntry\" = {$docEntry}
                LIMIT 1
            ";

            $rsP    = odbc_exec($conn, $sqlP);
            $header = $rsP ? odbc_fetch_array($rsP) : null;
            if ($rsP) {
                odbc_free_result($rsP);
            }
            if (!$header) {
                throw new Exception("No se encontró el Pago Efectuado #{$docEntry}.");
            }

            $sqlL = "
                SELECT
                    v2.\"InvType\",
                    v2.\"DocEntry\" AS \"TargetEntry\",
                    COALESCE(fi.\"DocNum\", fd.\"DocNum\", fn.\"DocNum\", v2.\"DocEntry\") AS \"TargetDocNum\",
                    TO_VARCHAR(COALESCE(fi.\"DocDate\", fd.\"DocDate\", fn.\"DocDate\"), 'YYYY-MM-DD') AS \"TargetDate\",
                    IFNULL(v2.\"SumApplied\", 0) AS \"SumApplied\",
                    COALESCE(fi.\"DocTotal\", fd.\"DocTotal\", fn.\"DocTotal\", 0) AS \"TargetTotal\",
                    IFNULL(fi.\"isIns\", 'N') AS \"EsReserva\"
                FROM \"VPM2\" v2
                LEFT JOIN \"OPCH\" fi ON v2.\"InvType\" = '18'  AND fi.\"DocEntry\" = v2.\"DocEntry\"
                LEFT JOIN \"ODPO\" fd ON v2.\"InvType\" = '204' AND fd.\"DocEntry\" = v2.\"DocEntry\"
                LEFT JOIN \"ORPC\" fn ON v2.\"InvType\" = '19'  AND fn.\"DocEntry\" = v2.\"DocEntry\"
                WHERE v2.\"DocNum\" = {$docEntry}
                ORDER BY v2.\"InvoiceId\" ASC
            ";

            $lines = [];
            $rsL   = odbc_exec($conn, $sqlL);
            if ($rsL) {
                while ($r = odbc_fetch_array($rsL)) {
                    $invType = trim((string) ($r['InvType'] ?? ''));
                    $esRes   = ($invType === '18' && trim((string) ($r['EsReserva'] ?? 'N')) === 'Y');
                    $mInv    = $this->getObjTypeMeta($invType, $esRes);
                    $lines[] = [
                        'objType'  => $invType,
                        'docEntry' => (int) ($r['TargetEntry'] ?? 0),
                        'tipoDoc'  => $mInv['name'],
                        'folio'    => trim($this->toUtf8($r['TargetDocNum'] ?? '')),
                        'fecha'    => trim($this->toUtf8($r['TargetDate'] ?? '')),
                        'totalDoc' => round((float) ($r['TargetTotal'] ?? 0), 2),
                        'aplicado' => round((float) ($r['SumApplied'] ?? 0), 2),
                    ];
                }
                odbc_free_result($rsL);
            }

            return [
                'mode'     => 'PAYMENT',
                'objType'  => '46',
                'tipo'     => $meta['name'],
                'badge'    => $meta['badge'],
                'docEntry' => (int) $header['DocEntry'],
                'docNum'   => trim($this->toUtf8($header['DocNum'] ?? '')),
                'transId'  => (int) ($header['TransId'] ?? 0),
                'header'   => [
                    'Fecha'        => trim($this->toUtf8($header['DocDate'] ?? '')),
                    'Vencimiento'  => trim($this->toUtf8($header['DocDueDate'] ?? '')),
                    'Proveedor'    => trim($this->toUtf8($header['CardCode'] ?? '')) . ' - ' . trim($this->toUtf8($header['CardName'] ?? '')),
                    'Sucursal'     => trim($this->toUtf8($header['BPLName'] ?? '')),
                    'Ref. Transf.' => trim($this->toUtf8($header['TrsfrRef'] ?? '')),
                    'Estatus'      => $header['Canceled'] === 'Y' ? 'CANCELADO' : 'APLICADO',
                    'Moneda'       => trim($this->toUtf8($header['DocCurr'] ?? 'MXP')),
                    'Comentarios'  => trim($this->toUtf8($header['Comments'] ?? '')),
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

        // 3. Asiento Contable (OJDT / JDT1) o cualquier otro TransType
        $targetTransId = $transId > 0 ? $transId : $docEntry;
        $sqlJ          = "
            SELECT \"TransId\", \"Number\",
                   TO_VARCHAR(\"RefDate\", 'YYYY-MM-DD') AS \"RefDate\",
                   TO_VARCHAR(\"DueDate\", 'YYYY-MM-DD') AS \"DueDate\",
                   IFNULL(\"Memo\", '') AS \"Memo\", IFNULL(\"Ref1\", '') AS \"Ref1\",
                   IFNULL(\"Ref2\", '') AS \"Ref2\", IFNULL(\"StornoToTr\", 0) AS \"StornoToTr\"
            FROM \"OJDT\" WHERE \"TransId\" = {$targetTransId} LIMIT 1
        ";
        $rsJ    = odbc_exec($conn, $sqlJ);
        $header = $rsJ ? odbc_fetch_array($rsJ) : null;
        if ($rsJ) {
            odbc_free_result($rsJ);
        }
        if (!$header) {
            throw new Exception("No se encontró el Asiento Contable #{$targetTransId}.");
        }

        $sqlJL = "
            SELECT j.\"Account\", IFNULL(a.\"AcctName\", '') AS \"AcctName\",
                   IFNULL(j.\"ShortName\", '') AS \"ShortName\", IFNULL(j.\"LineMemo\", '') AS \"LineMemo\",
                   IFNULL(j.\"Debit\", 0) AS \"Debit\", IFNULL(j.\"Credit\", 0) AS \"Credit\",
                   IFNULL(b.\"BPLName\", '') AS \"BPLName\"
            FROM \"JDT1\" j
            LEFT JOIN \"OACT\" a ON a.\"AcctCode\" = j.\"Account\"
            LEFT JOIN \"OBPL\" b ON b.\"BPLId\" = j.\"BPLId\"
            WHERE j.\"TransId\" = {$targetTransId}
            ORDER BY j.\"Line_ID\" ASC
        ";

        $lines = [];
        $sumD  = 0.0;
        $sumC  = 0.0;
        $rsJL  = odbc_exec($conn, $sqlJL);
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
            'mode'     => 'JOURNAL',
            'objType'  => $objType,
            'tipo'     => $meta['name'],
            'badge'    => $meta['badge'],
            'docEntry' => (int) $header['TransId'],
            'docNum'   => trim($this->toUtf8($header['Number'] ?? $header['TransId'])),
            'transId'  => (int) $header['TransId'],
            'header'   => [
                'Fecha Cont.' => trim($this->toUtf8($header['RefDate'] ?? '')),
                'Vencimiento' => trim($this->toUtf8($header['DueDate'] ?? '')),
                'Ref. 1'      => trim($this->toUtf8($header['Ref1'] ?? '')),
                'Ref. 2'      => trim($this->toUtf8($header['Ref2'] ?? '')),
                'Estatus'     => (int) ($header['StornoToTr'] ?? 0) > 0 ? 'ANULADO / STORNO' : 'CONTABILIZADO',
                'Comentarios' => trim($this->toUtf8($header['Memo'] ?? '')),
            ],
            'totals' => [
                'TotalDebito'  => round($sumD, 2),
                'TotalCredito' => round($sumC, 2),
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Construye el grafo del Mapa de Relaciones de Compras y Cuentas por Pagar
     * (Solicitud -> Oferta -> Orden de Compra -> Entrada Mercancía / Factura de Reserva -> Factura -> Anticipos, Devoluciones, NC, Pagos Efectuados y Reconciliaciones).
     */
    private function buildRelationshipMap($conn, string $rootObjType, int $rootDocEntry, string $cardCode = ''): array
    {
        $rootKey = "{$rootObjType}_{$rootDocEntry}";
        $nodes   = [];
        $edges   = [];
        $edgeSet = [];
        $visited = [];
        $queue   = [
            ['objType' => $rootObjType, 'docEntry' => $rootDocEntry, 'depth' => 0]
        ];

        $maxDepth = 3;
        $maxNodes = 150;

        $addEdge = function (string $from, string $to, string $label, string $type = 'flow') use (&$edges, &$edgeSet) {
            if ($from === $to) {
                return;
            }
            $sig = "{$from}=>{$to}|{$type}";
            if (isset($edgeSet[$sig])) {
                return;
            }
            $edgeSet[$sig] = true;
            $edges[]       = [
                'from'  => $from,
                'to'    => $to,
                'label' => $label,
                'type'  => $type,
            ];
        };

        while (!empty($queue) && count($nodes) < $maxNodes) {
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

            if ($depth >= $maxDepth) {
                continue;
            }

            // A) Documentos Base (hacia atrás por BaseType / BaseEntry)
            $lineTableMap = [
                '540000006' => 'PQT1',
                '22'        => 'POR1',
                '20'        => 'PDN1',
                '21'        => 'RPD1',
                '18'        => 'PCH1',
                '19'        => 'RPC1',
                '204'       => 'DPO1',
            ];

            if (isset($lineTableMap[$objType])) {
                $lt = $lineTableMap[$objType];
                $rs = @odbc_exec($conn, "
                    SELECT DISTINCT \"BaseType\", \"BaseEntry\"
                    FROM \"{$lt}\"
                    WHERE \"DocEntry\" = {$docEntry}
                      AND \"BaseEntry\" IS NOT NULL
                      AND \"BaseType\" IN (1470000113, 540000006, 22, 20, 21, 18, 204)
                    LIMIT 60
                ");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $bType  = (string) (int) $r['BaseType'];
                        $bEntry = (int) $r['BaseEntry'];
                        $bKey   = "{$bType}_{$bEntry}";
                        $lbl    = ($objType === '20' && $bType === '18') ? 'Entrada de Reserva' : 'Copiado a';
                        $addEdge($bKey, $key, $lbl, 'flow');
                        if (!isset($visited[$bKey])) {
                            $queue[] = ['objType' => $bType, 'docEntry' => $bEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }

            // B) Documentos Destino (hacia adelante — incluye 18 -> 20 para Facturas de Reserva de Proveedor)
            $targetTablesByBase = [
                '1470000113' => ['540000006' => 'PQT1', '22' => 'POR1'],
                '540000006'  => ['22' => 'POR1', '20' => 'PDN1', '18' => 'PCH1'],
                '22'         => ['20' => 'PDN1', '18' => 'PCH1', '204' => 'DPO1'],
                '20'         => ['18' => 'PCH1', '21' => 'RPD1'],
                '18'         => ['20' => 'PDN1', '19' => 'RPC1'],
                '204'        => ['19' => 'RPC1'],
            ];

            if (isset($targetTablesByBase[$objType])) {
                $objInt = (int) $objType;
                foreach ($targetTablesByBase[$objType] as $tType => $tTable) {
                    $rs = @odbc_exec($conn, "
                        SELECT DISTINCT \"DocEntry\"
                        FROM \"{$tTable}\"
                        WHERE \"BaseType\" = {$objInt}
                          AND \"BaseEntry\" = {$docEntry}
                        LIMIT 80
                    ");
                    if ($rs) {
                        while ($r = odbc_fetch_array($rs)) {
                            $tEntry = (int) $r['DocEntry'];
                            $tKey   = "{$tType}_{$tEntry}";
                            $lbl    = ($objType === '18' && (string) $tType === '20') ? 'Entrada de Reserva' : 'Generó';
                            $addEdge($key, $tKey, $lbl, 'flow');
                            if (!isset($visited[$tKey])) {
                                $queue[] = ['objType' => (string) $tType, 'docEntry' => $tEntry, 'depth' => $depth + 1];
                            }
                        }
                        odbc_free_result($rs);
                    }
                }
            }

            // C) Anticipos aplicados en Facturas de Proveedor (PCH9)
            if ($objType === '18') {
                $rs = @odbc_exec($conn, "
                    SELECT \"BaseAbs\" AS \"DpoEntry\", SUM(IFNULL(\"DrawnSum\", 0)) AS \"DrawnSum\"
                    FROM \"PCH9\"
                    WHERE \"DocEntry\" = {$docEntry} AND \"ObjType\" = 204
                    GROUP BY \"BaseAbs\"
                ");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $dEntry = (int) $r['DpoEntry'];
                        $drawn  = number_format((float) $r['DrawnSum'], 2);
                        $dKey   = "204_{$dEntry}";
                        $addEdge($dKey, $key, "Anticipo \${$drawn}", 'downpayment');
                        if (!isset($visited[$dKey])) {
                            $queue[] = ['objType' => '204', 'docEntry' => $dEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            } elseif ($objType === '204') {
                $rs = @odbc_exec($conn, "
                    SELECT \"DocEntry\" AS \"PchEntry\", SUM(IFNULL(\"DrawnSum\", 0)) AS \"DrawnSum\"
                    FROM \"PCH9\"
                    WHERE \"BaseAbs\" = {$docEntry} AND \"ObjType\" = 204
                    GROUP BY \"DocEntry\"
                ");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $iEntry = (int) $r['PchEntry'];
                        $drawn  = number_format((float) $r['DrawnSum'], 2);
                        $iKey   = "18_{$iEntry}";
                        $addEdge($key, $iKey, "Anticipo \${$drawn}", 'downpayment');
                        if (!isset($visited[$iKey])) {
                            $queue[] = ['objType' => '18', 'docEntry' => $iEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }

            // D) Pagos Efectuados (OVPM / VPM2)
            if (in_array($objType, ['18', '19', '204', '30'], true)) {
                $rs = @odbc_exec($conn, "
                    SELECT v2.\"DocNum\" AS \"PayEntry\", SUM(IFNULL(v2.\"SumApplied\", 0)) AS \"SumApplied\"
                    FROM \"VPM2\" v2
                    WHERE v2.\"InvType\" = '{$objType}' AND v2.\"DocEntry\" = {$docEntry}
                    GROUP BY v2.\"DocNum\"
                    LIMIT 40
                ");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $pEntry = (int) $r['PayEntry'];
                        $sumApl = number_format((float) $r['SumApplied'], 2);
                        $pKey   = "46_{$pEntry}";
                        $addEdge($key, $pKey, "Pago \${$sumApl}", 'payment');
                        if (!isset($visited[$pKey])) {
                            $queue[] = ['objType' => '46', 'docEntry' => $pEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            } elseif ($objType === '46') {
                $rs = @odbc_exec($conn, "
                    SELECT v2.\"InvType\", v2.\"DocEntry\" AS \"TargetEntry\", SUM(IFNULL(v2.\"SumApplied\", 0)) AS \"SumApplied\"
                    FROM \"VPM2\" v2
                    WHERE v2.\"DocNum\" = {$docEntry}
                    GROUP BY v2.\"InvType\", v2.\"DocEntry\"
                    LIMIT 60
                ");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $tType  = trim((string) $r['InvType']);
                        $tEntry = (int) $r['TargetEntry'];
                        $sumApl = number_format((float) $r['SumApplied'], 2);
                        $tKey   = "{$tType}_{$tEntry}";
                        $addEdge($tKey, $key, "Pago \${$sumApl}", 'payment');
                        if (!isset($visited[$tKey])) {
                            $queue[] = ['objType' => $tType, 'docEntry' => $tEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }

            // E) Reconciliaciones Internas (OITR / ITR1)
            $nodeTransId = (int) ($summary['transId'] ?? 0);
            if ($nodeTransId > 0 && $cardCode !== '') {
                $ccClean = str_replace("'", "''", $cardCode);
                $rs      = @odbc_exec($conn, "
                    SELECT DISTINCT
                        r0.\"ReconNum\",
                        j2.\"TransType\",
                        IFNULL(j2.\"CreatedBy\", j2.\"TransId\") AS \"RelEntry\",
                        r2.\"ReconSum\"
                    FROM \"ITR1\" r1
                    INNER JOIN \"OITR\" r0 ON r0.\"ReconNum\" = r1.\"ReconNum\" AND r0.\"Canceled\" = 'N'
                    INNER JOIN \"ITR1\" r2 ON r2.\"ReconNum\" = r1.\"ReconNum\"
                                          AND r2.\"TransId\" <> r1.\"TransId\"
                                          AND r2.\"ShortName\" = '{$ccClean}'
                    INNER JOIN \"OJDT\" j2 ON j2.\"TransId\" = r2.\"TransId\"
                    WHERE r1.\"TransId\" = {$nodeTransId}
                      AND r1.\"ShortName\" = '{$ccClean}'
                    LIMIT 20
                ");
                if ($rs) {
                    while ($r = odbc_fetch_array($rs)) {
                        $rType  = trim((string) $r['TransType']);
                        $rEntry = (int) $r['RelEntry'];
                        $recNum = trim((string) $r['ReconNum']);
                        $rKey   = "{$rType}_{$rEntry}";

                        if (
                            isset($edgeSet["{$key}=>{$rKey}|payment"])
                            || isset($edgeSet["{$rKey}=>{$key}|payment"])
                            || isset($edgeSet["{$key}=>{$rKey}|downpayment"])
                            || isset($edgeSet["{$rKey}=>{$key}|downpayment"])
                        ) {
                            continue;
                        }

                        $recAmt = number_format((float) $r['ReconSum'], 2);
                        $addEdge($key, $rKey, "Reconc. #{$recNum} (\${$recAmt})", 'recon');
                        if (!isset($visited[$rKey])) {
                            $queue[] = ['objType' => $rType, 'docEntry' => $rEntry, 'depth' => $depth + 1];
                        }
                    }
                    odbc_free_result($rs);
                }
            }
        }

        // Ajustar columnas para que Factura de Reserva de Proveedor -> Entradas de Mercancía lea de izquierda a derecha
        $validEdges = array_values(array_filter($edges, fn($e) => isset($nodes[$e['from']]) && isset($nodes[$e['to']])));
        foreach ($validEdges as $e) {
            $fromNode = $nodes[$e['from']];
            $toNode   = $nodes[$e['to']];
            if (!empty($fromNode['isReserve']) && $toNode['objType'] === '20') {
                $nodes[$e['from']]['stage'] = 3;
                $nodes[$e['to']]['stage']   = 4;
            }
            if ($fromNode['objType'] === '20' && $toNode['objType'] === '21') {
                $nodes[$e['to']]['stage'] = max(5, (int) $nodes[$e['from']]['stage'] + 1);
            }
        }

        return [
            'rootId' => $rootKey,
            'nodes'  => array_values($nodes),
            'edges'  => $validEdges,
        ];
    }

    /**
     * Resumen compacto de un documento de Compras/CxP para tarjeta en el Mapa de Relaciones.
     */
    private function fetchDocSummary($conn, string $objType, int $docEntry): ?array
    {
        if ($objType === '1470000113') {
            $rs = @odbc_exec($conn, "
                SELECT
                    \"DocEntry\", \"DocNum\",
                    TO_VARCHAR(\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                    IFNULL(\"DocTotal\", 0) AS \"DocTotal\",
                    IFNULL(\"DocCur\", 'MXP') AS \"DocCur\",
                    IFNULL(\"DocStatus\", 'O') AS \"DocStatus\",
                    IFNULL(\"CANCELED\", 'N') AS \"Canceled\",
                    IFNULL(\"ReqName\", '') AS \"Ref\"
                FROM \"OPRQ\"
                WHERE \"DocEntry\" = {$docEntry}
                LIMIT 1
            ");
            if (!$rs || !($r = odbc_fetch_array($rs))) {
                if ($rs) {
                    odbc_free_result($rs);
                }
                return null;
            }
            odbc_free_result($rs);

            $meta = $this->getObjTypeMeta($objType);
            $canc = in_array($r['Canceled'], ['Y', 'C'], true);

            return [
                'objType'   => $objType,
                'isReserve' => false,
                'docEntry'  => (int) $r['DocEntry'],
                'docNum'    => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId'   => 0,
                'cardCode'  => '',
                'tipo'      => $meta['name'],
                'code'      => $meta['code'],
                'badge'     => $meta['badge'],
                'icon'      => $meta['icon'],
                'stage'     => $meta['stage'],
                'fecha'     => trim($this->toUtf8($r['DocDate'] ?? '')),
                'total'     => round((float) $r['DocTotal'], 2),
                'saldo'     => 0.0,
                'moneda'    => trim($this->toUtf8($r['DocCur'] ?? 'MXP')),
                'estatus'   => $canc ? 'CANCELADO' : ($r['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO'),
                'cancelado' => $canc,
                'ref'       => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        $marketingMap = [
            '540000006' => 'OPQT',
            '22'        => 'OPOR',
            '20'        => 'OPDN',
            '21'        => 'ORPD',
            '18'        => 'OPCH',
            '19'        => 'ORPC',
            '204'       => 'ODPO',
        ];

        if (isset($marketingMap[$objType])) {
            $tbl       = $marketingMap[$objType];
            $extraCols = ($objType === '18')
                ? ', IFNULL("isIns", \'N\') AS "isIns", IFNULL("InvntSttus", \'\') AS "InvntSttus"'
                : ', \'N\' AS "isIns", \'\' AS "InvntSttus"';

            $rs = @odbc_exec($conn, "
                SELECT
                    \"DocEntry\", \"DocNum\", IFNULL(\"TransId\", 0) AS \"TransId\",
                    \"CardCode\", TO_VARCHAR(\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                    IFNULL(\"DocTotal\", 0) AS \"DocTotal\",
                    IFNULL(\"PaidToDate\", 0) AS \"PaidToDate\",
                    IFNULL(\"DocCur\", 'MXP') AS \"DocCur\",
                    IFNULL(\"DocStatus\", 'O') AS \"DocStatus\",
                    IFNULL(\"CANCELED\", 'N') AS \"Canceled\",
                    IFNULL(\"NumAtCard\", '') AS \"Ref\"
                    {$extraCols}
                FROM \"{$tbl}\"
                WHERE \"DocEntry\" = {$docEntry}
                LIMIT 1
            ");
            if (!$rs || !($r = odbc_fetch_array($rs))) {
                if ($rs) {
                    odbc_free_result($rs);
                }
                return null;
            }
            odbc_free_result($rs);

            $esReserva = ($objType === '18' && trim((string) ($r['isIns'] ?? 'N')) === 'Y');
            $meta      = $this->getObjTypeMeta($objType, $esReserva);

            $total = round((float) $r['DocTotal'], 2);
            $paid  = round((float) $r['PaidToDate'], 2);
            $canc  = in_array($r['Canceled'], ['Y', 'C'], true);

            $estatusTxt = $canc ? 'CANCELADO' : ($r['DocStatus'] === 'C' ? 'CERRADO' : 'ABIERTO');
            if ($esReserva && !$canc) {
                $estatusTxt .= (trim((string) ($r['InvntSttus'] ?? '')) === 'C') ? ' / RECIBIDA' : ' / POR RECIBIR';
            }

            return [
                'objType'   => $objType,
                'isReserve' => $esReserva,
                'docEntry'  => (int) $r['DocEntry'],
                'docNum'    => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId'   => (int) $r['TransId'],
                'cardCode'  => trim($this->toUtf8($r['CardCode'] ?? '')),
                'tipo'      => $meta['name'],
                'code'      => $meta['code'],
                'badge'     => $meta['badge'],
                'icon'      => $meta['icon'],
                'stage'     => $meta['stage'],
                'fecha'     => trim($this->toUtf8($r['DocDate'] ?? '')),
                'total'     => $total,
                'saldo'     => round($total - $paid, 2),
                'moneda'    => trim($this->toUtf8($r['DocCur'] ?? 'MXP')),
                'estatus'   => $estatusTxt,
                'cancelado' => $canc,
                'ref'       => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        $meta = $this->getObjTypeMeta($objType);

        if ($objType === '46') {
            $rs = @odbc_exec($conn, "
                SELECT
                    \"DocEntry\", \"DocNum\", IFNULL(\"TransId\", 0) AS \"TransId\",
                    \"CardCode\", TO_VARCHAR(\"DocDate\", 'YYYY-MM-DD') AS \"DocDate\",
                    IFNULL(\"DocTotal\", 0) AS \"DocTotal\",
                    IFNULL(\"NoDocSum\", 0) AS \"NoDocSum\",
                    IFNULL(\"DocCurr\", 'MXP') AS \"DocCur\",
                    IFNULL(\"Canceled\", 'N') AS \"Canceled\",
                    IFNULL(\"TrsfrRef\", '') AS \"Ref\"
                FROM \"OVPM\"
                WHERE \"DocEntry\" = {$docEntry}
                LIMIT 1
            ");
            if (!$rs || !($r = odbc_fetch_array($rs))) {
                if ($rs) {
                    odbc_free_result($rs);
                }
                return null;
            }
            odbc_free_result($rs);

            $canc = ($r['Canceled'] === 'Y');
            return [
                'objType'   => '46',
                'isReserve' => false,
                'docEntry'  => (int) $r['DocEntry'],
                'docNum'    => trim($this->toUtf8($r['DocNum'] ?? '')),
                'transId'   => (int) $r['TransId'],
                'cardCode'  => trim($this->toUtf8($r['CardCode'] ?? '')),
                'tipo'      => $meta['name'],
                'code'      => $meta['code'],
                'badge'     => $meta['badge'],
                'icon'      => $meta['icon'],
                'stage'     => $meta['stage'],
                'fecha'     => trim($this->toUtf8($r['DocDate'] ?? '')),
                'total'     => round((float) $r['DocTotal'], 2),
                'saldo'     => round((float) $r['NoDocSum'], 2),
                'moneda'    => trim($this->toUtf8($r['DocCur'] ?? 'MXP')),
                'estatus'   => $canc ? 'CANCELADO' : 'APLICADO',
                'cancelado' => $canc,
                'ref'       => trim($this->toUtf8($r['Ref'] ?? '')),
            ];
        }

        // Asiento Contable (30) u otro objeto genérico
        $rs = @odbc_exec($conn, "
            SELECT \"TransId\", \"Number\",
                   TO_VARCHAR(\"RefDate\", 'YYYY-MM-DD') AS \"RefDate\",
                   IFNULL(\"LocTotal\", 0) AS \"LocTotal\",
                   IFNULL(\"Memo\", '') AS \"Memo\",
                   IFNULL(\"StornoToTr\", 0) AS \"StornoToTr\"
            FROM \"OJDT\" WHERE \"TransId\" = {$docEntry} LIMIT 1
        ");
        if (!$rs || !($r = odbc_fetch_array($rs))) {
            if ($rs) {
                odbc_free_result($rs);
            }
            return null;
        }
        odbc_free_result($rs);

        $canc = ((int) ($r['StornoToTr'] ?? 0) > 0);
        return [
            'objType'   => $objType,
            'isReserve' => false,
            'docEntry'  => (int) $r['TransId'],
            'docNum'    => trim($this->toUtf8($r['Number'] ?? $r['TransId'])),
            'transId'   => (int) $r['TransId'],
            'cardCode'  => '',
            'tipo'      => $meta['name'],
            'code'      => $meta['code'],
            'badge'     => $meta['badge'],
            'icon'      => $meta['icon'],
            'stage'     => $meta['stage'],
            'fecha'     => trim($this->toUtf8($r['RefDate'] ?? '')),
            'total'     => round((float) $r['LocTotal'], 2),
            'saldo'     => 0.0,
            'moneda'    => 'MXP',
            'estatus'   => $canc ? 'ANULADO' : 'CONTABILIZADO',
            'cancelado' => $canc,
            'ref'       => trim($this->toUtf8($r['Memo'] ?? '')),
        ];
    }

    /**
     * Metadatos visuales por ObjType de Compras / CxP (diferenciando Factura de Reserva de Proveedor).
     */
    private function getObjTypeMeta(string $objType, bool $isReserve = false): array
    {
        if ($objType === '18' && $isReserve) {
            return [
                'code'  => 'RES',
                'name'  => 'Factura Reserva Prov.',
                'badge' => 'reserve',
                'icon'  => 'fa-boxes',
                'stage' => 3,
            ];
        }

        $map = [
            '1470000113' => ['code' => 'SOL', 'name' => 'Solicitud de Compra',  'badge' => 'secondary', 'icon' => 'fa-clipboard-list',      'stage' => 1],
            '540000006'  => ['code' => 'OFC', 'name' => 'Oferta de Compra',     'badge' => 'secondary', 'icon' => 'fa-file-alt',            'stage' => 1],
            '22'         => ['code' => 'OC',  'name' => 'Orden de Compra',      'badge' => 'info',      'icon' => 'fa-shopping-bag',        'stage' => 2],
            '20'         => ['code' => 'EM',  'name' => 'Entrada Mercancía',    'badge' => 'primary',   'icon' => 'fa-truck-loading',       'stage' => 3],
            '21'         => ['code' => 'DEV', 'name' => 'Devolución Mercancía', 'badge' => 'dark',      'icon' => 'fa-dolly',               'stage' => 5],
            '204'        => ['code' => 'ANT', 'name' => 'Anticipo Proveedor',   'badge' => 'warning',   'icon' => 'fa-hand-holding-usd',    'stage' => 4],
            '18'         => ['code' => 'FP',  'name' => 'Factura Proveedor',    'badge' => 'danger',    'icon' => 'fa-file-invoice-dollar', 'stage' => 4],
            '19'         => ['code' => 'NCP', 'name' => 'Nota Crédito Prov.',   'badge' => 'purple',    'icon' => 'fa-undo-alt',            'stage' => 5],
            '46'         => ['code' => 'PEF', 'name' => 'Pago Efectuado',       'badge' => 'success',   'icon' => 'fa-money-check-alt',     'stage' => 5],
            '24'         => ['code' => 'PRC', 'name' => 'Pago Recibido',        'badge' => 'dark',      'icon' => 'fa-money-bill-wave',     'stage' => 5],
            '30'         => ['code' => 'ASI', 'name' => 'Asiento Contable',     'badge' => 'dark',      'icon' => 'fa-book',                'stage' => 5],
        ];

        return $map[$objType] ?? [
            'code'  => "T{$objType}",
            'name'  => "Documento ({$objType})",
            'badge' => 'secondary',
            'icon'  => 'fa-file',
            'stage' => 5,
        ];
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

    private function fmt($num): string
    {
        return number_format((float) $num, 2, '.', ',');
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