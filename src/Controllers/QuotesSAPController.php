<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Throwable;
use julio101290\boilerplate\Models\UserModel;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;
use julio101290\boilerplateservicelayer\Models\Employee_sap_linkModel;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;
use julio101290\boilerplateservicelayer\Models\User_pricelistModel;
use julio101290\boilerplateservicelayer\Models\QuotesSAPModel;
use julio101290\boilerplateservicelayer\Libraries\QuoteTemplateSecurity;

class QuotesSAPController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $empresa;
    protected $sucursales;
    protected $serviceLayerModel;
    protected $users;
    protected $userSapLink;
    protected $employeeSapLink;
    protected $linkSapBranchOffice;
    protected $userPricelistModel;
    protected $quotesSAPModel;
    public $catalogosSAT;

    public function __construct()
    {
        $this->log                 = new LogModel();
        $this->empresa             = new EmpresasModel();
        $this->sucursales          = new BranchofficesModel();
        $this->serviceLayerModel   = new SapservicelayerModel();
        $this->users               = new UserModel();
        $this->userSapLink         = new User_sap_linkModel();
        $this->employeeSapLink     = new Employee_sap_linkModel();
        $this->linkSapBranchOffice = new Link_sap_branchofficeModel();
        $this->userPricelistModel  = new User_pricelistModel();
        $this->quotesSAPModel      = new QuotesSAPModel();

        if (class_exists('\julio101290\boilerplateCFDI\Libraries\CatalogosSAT')) {
            $this->catalogosSAT = new \julio101290\boilerplateCFDI\Libraries\CatalogosSAT();
        }

        helper(['menu', 'utilerias', 'auth']);
    }

    /**
     * Filtra de forma segura por empresa detectando automáticamente si la columna
     * en la tabla se llama `idEmpresa`, `companie` o `idCompany`.
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

    // =========================================================================
    // 1. LISTADO PRINCIPAL DE COTIZACIONES SAP (DATATABLE MARIADB + FLUJO SAP)
    // =========================================================================

    public function index()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser              = (int) user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID          = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        // Sucursales a las que el usuario tiene derecho
        $allowedBranches = $this->getAllowedBranchIdsForUser($idUser, $empresasID);
        $canViewAll      = has_permission('quotesSAP-view-all') || in_groups('super-admin');
        $canAuthorize    = has_permission('quotesSAP-authorize') || in_groups('super-admin');

        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw         = (int) $request->getGet('draw');
            $start        = (int) $request->getGet('start');
            $length       = (int) $request->getGet('length');
            $searchValue  = trim($request->getGet('search')['value'] ?? '');
            $fechaIni     = trim($request->getGet('fechaInicial') ?? '');
            $fechaFin     = trim($request->getGet('fechaFinal') ?? '');
            $statusFilter = trim($request->getGet('status') ?? '');

            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir         = strtolower($request->getGet('order')[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

            $columns = [
                'id',
                'codigo',
                'sapDocNum',
                'cardName',
                'cotizarA',
                'nombreVendedor',
                'whsCode',
                'namePriceList',
                'neto',
                'total',
                'fecha',
                'fechaVencimiento',
                'status',
                'sapSynced'
            ];

            $orderField = $columns[$orderColumnIndex] ?? 'id';

            $builder = $this->quotesSAPModel->builder();
            $this->applyCompanyFilter($builder, 'quotes_sap', $empresasID);
            $builder->whereIn('idSucursal', $allowedBranches);
            $builder->where('deleted_at', null);

            // Si no tiene el permiso quotesSAP-view-all, solo ve las cotizaciones que él mismo capturó
            if (!$canViewAll) {
                $builder->where('idVendedor', $idUser);
            }

            if ($fechaIni !== '' && $fechaFin !== '') {
                $builder->where('fecha >=', $fechaIni)->where('fecha <=', $fechaFin);
            }

            if ($statusFilter !== '' && $statusFilter !== 'ALL') {
                if ($statusFilter === 'AUT') {
                    $builder->whereIn('status', ['AUT', 'LFA']);
                } else {
                    $builder->where('status', $statusFilter);
                }
            }

            $recordsTotal = $builder->countAllResults(false);

            if ($searchValue !== '') {
                $builder->groupStart()
                    ->like('codigo', $searchValue)
                    ->orLike('cardCode', $searchValue)
                    ->orLike('cardName', $searchValue)
                    ->orLike('cotizarA', $searchValue)
                    ->orLike('nombreVendedor', $searchValue)
                    ->orLike('sapDocNum', $searchValue)
                    ->orLike('folioFacturaStatus', $searchValue)
                    ->groupEnd();
            }

            $recordsFiltered = $builder->countAllResults(false);

            $data = $builder
                ->orderBy($orderField, $orderDir)
                ->limit($length, $start)
                ->get()
                ->getResultArray();

            // Consultar en SAP HANA si las cotizaciones sincronizadas ya tienen Pedido (ORDR) o Factura (OINV)
            $data = $this->enrichQuotesWithSAPFlow($data);

            $hoy = date('Y-m-d');
            foreach ($data as &$row) {
                $st = strtoupper(trim((string) ($row['status'] ?? 'PEN')));
                if ($st === 'LFA') {
                    $st = 'AUT';
                }

                if (!empty($row['sapInvoiceNum'])) {
                    $row['statusComputed']     = 'FAC';
                    $row['folioFacturaStatus'] = (string) $row['sapInvoiceNum'];
                } elseif (!empty($row['sapOrderNum'])) {
                    $row['statusComputed'] = 'PED';
                } elseif ($st === 'FAC') {
                    $row['statusComputed'] = 'FAC';
                } elseif ($st === 'PED') {
                    $row['statusComputed'] = 'PED';
                } elseif (($st === 'PEN' || $st === '') && $row['fechaVencimiento'] < $hoy && (int) ($row['sapSynced'] ?? 0) === 0) {
                    $row['statusComputed'] = 'VEN';
                } else {
                    $row['statusComputed'] = $st ?: 'PEN';
                }
            }
            unset($row);

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data
            ]);
        }

        $titulos["title"]        = 'Administración de Cotizaciones SAP';
        $titulos["subtitle"]     = 'Control de cotizaciones por sucursal y lista de precios';
        $titulos["canAuthorize"] = $canAuthorize;

        return view('julio101290\boilerplateservicelayer\Views\quotesSAP', $titulos);
    }

    /**
     * Consulta en lote por ODBC en SAP HANA si las cotizaciones (OQUT) ya pasaron
     * a Orden de Venta (ORDR) o Factura de Deudores (OINV) y sincroniza el estatus local.
     */
    private function enrichQuotesWithSAPFlow(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $byCompany = [];
        foreach ($rows as $idx => $r) {
            $rows[$idx]['sapOrderNum']   = '';
            $rows[$idx]['sapInvoiceNum'] = '';

            if ((int) ($r['sapSynced'] ?? 0) === 1 && (int) ($r['sapDocEntry'] ?? 0) > 0) {
                $idEmp = (int) ($r['idEmpresa'] ?? $r['companie'] ?? 1);
                $byCompany[$idEmp][(int) $r['sapDocEntry']][] = $idx;
            }
        }

        if (empty($byCompany)) {
            return $rows;
        }

        foreach ($byCompany as $idEmpresa => $entryMap) {
            $docEntries = array_keys($entryMap);
            if (empty($docEntries)) {
                continue;
            }

            try {
                $conn   = $this->connectODBC((int) $idEmpresa);
                $inList = implode(',', array_map('intval', $docEntries));

                $sql = "
                    SELECT DISTINCT
                        Q.\"DocEntry\" AS \"QuoteEntry\",
                        O.\"DocNum\" AS \"OrderDocNum\",
                        COALESCE(I1.\"DocNum\", I2.\"DocNum\", I3.\"DocNum\") AS \"InvoiceDocNum\"
                    FROM \"OQUT\" Q
                    LEFT JOIN \"RDR1\" R1 ON R1.\"BaseEntry\" = Q.\"DocEntry\" AND R1.\"BaseType\" = 23
                    LEFT JOIN \"ORDR\" O ON O.\"DocEntry\" = R1.\"DocEntry\" AND O.\"CANCELED\" = 'N'
                    LEFT JOIN \"INV1\" IV1 ON IV1.\"BaseEntry\" = Q.\"DocEntry\" AND IV1.\"BaseType\" = 23
                    LEFT JOIN \"OINV\" I1 ON I1.\"DocEntry\" = IV1.\"DocEntry\" AND I1.\"CANCELED\" = 'N'
                    LEFT JOIN \"INV1\" IV2 ON IV2.\"BaseEntry\" = O.\"DocEntry\" AND IV2.\"BaseType\" = 17
                    LEFT JOIN \"OINV\" I2 ON I2.\"DocEntry\" = IV2.\"DocEntry\" AND I2.\"CANCELED\" = 'N'
                    LEFT JOIN \"DLN1\" D1 ON D1.\"BaseEntry\" = O.\"DocEntry\" AND D1.\"BaseType\" = 17
                    LEFT JOIN \"ODLN\" D ON D.\"DocEntry\" = D1.\"DocEntry\" AND D.\"CANCELED\" = 'N'
                    LEFT JOIN \"INV1\" IV3 ON IV3.\"BaseEntry\" = D.\"DocEntry\" AND IV3.\"BaseType\" = 15
                    LEFT JOIN \"OINV\" I3 ON I3.\"DocEntry\" = IV3.\"DocEntry\" AND I3.\"CANCELED\" = 'N'
                    WHERE Q.\"DocEntry\" IN ({$inList})
                ";

                $rs = @odbc_exec($conn, $sql);
                if ($rs) {
                    while ($sapRow = odbc_fetch_array($rs)) {
                        $qEntry = (int) ($sapRow['QuoteEntry'] ?? 0);
                        $ordNum = trim((string) ($sapRow['OrderDocNum'] ?? ''));
                        $invNum = trim((string) ($sapRow['InvoiceDocNum'] ?? ''));

                        if (isset($entryMap[$qEntry])) {
                            foreach ($entryMap[$qEntry] as $rowIndex) {
                                if ($ordNum !== '') {
                                    $rows[$rowIndex]['sapOrderNum'] = $ordNum;
                                }
                                if ($invNum !== '') {
                                    $rows[$rowIndex]['sapInvoiceNum'] = $invNum;
                                }
                            }
                        }
                    }
                    odbc_free_result($rs);
                }
                odbc_close($conn);
            } catch (Throwable $ignore) {
            }
        }

        foreach ($rows as $r) {
            $idQuote = (int) ($r['id'] ?? 0);
            if ($idQuote <= 0) {
                continue;
            }
            if (!empty($r['sapInvoiceNum']) && ($r['status'] !== 'FAC' || $r['folioFacturaStatus'] !== $r['sapInvoiceNum'])) {
                $this->quotesSAPModel->update($idQuote, [
                    'status'             => 'FAC',
                    'folioFacturaStatus' => (string) $r['sapInvoiceNum']
                ]);
            } elseif (empty($r['sapInvoiceNum']) && !empty($r['sapOrderNum']) && $r['status'] !== 'PED' && $r['status'] !== 'FAC') {
                $this->quotesSAPModel->update($idQuote, [
                    'status'             => 'PED',
                    'folioFacturaStatus' => (string) $r['sapOrderNum']
                ]);
            }
        }

        return $rows;
    }

    // =========================================================================
    // 2. CREAR / EDITAR COTIZACIÓN SAP
    // =========================================================================

    public function newQuote()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser   = (int) user()->id;
        $userName = trim(user()->username . ' ' . (user()->firstname ?? '') . ' ' . (user()->lastname ?? ''));

        $titulos             = $this->buildQuoteFormData($idUser, $userName);
        $titulos["title"]    = "Crear Cotización SAP";
        $titulos["subtitle"] = "Captura de cotización";

        return view('julio101290\boilerplateservicelayer\Views\newQuoteSAP', $titulos);
    }

    public function editQuote($uuid)
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser   = (int) user()->id;
        $userName = trim(user()->username . ' ' . (user()->firstname ?? '') . ' ' . (user()->lastname ?? ''));

        $empresas        = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID      = count($empresas) === 0 ? [0] : array_column($empresas, "id");
        $allowedBranches = $this->getAllowedBranchIdsForUser($idUser, $empresasID);
        $canViewAll      = has_permission('quotesSAP-view-all') || in_groups('super-admin');

        $this->applyCompanyFilter($this->quotesSAPModel, 'quotes_sap', $empresasID);
        $this->quotesSAPModel->whereIn('idSucursal', $allowedBranches);

        if (!$canViewAll) {
            $this->quotesSAPModel->where('idVendedor', $idUser);
        }

        $quote = $this->quotesSAPModel->where('UUID', $uuid)->first();

        if (!$quote) {
            return redirect()->to(base_url('admin/quotesSAP'))->with('error', 'Cotización no encontrada o sin permiso de acceso.');
        }

        $titulos             = $this->buildQuoteFormData($idUser, $userName, $quote);
        $titulos["title"]    = "Editar Cotización #" . $quote["codigo"];
        $titulos["subtitle"] = "Cliente: " . $quote["cardCode"] . " - " . $quote["cardName"];

        return view('julio101290\boilerplateservicelayer\Views\newQuoteSAP', $titulos);
    }

    private function buildQuoteFormData(int $idUser, string $userName, ?array $quote = null): array
    {
        $empresas    = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $idEmpresa   = $quote ? (int) ($quote['idEmpresa'] ?? $quote['companie'] ?? 1) : (int) ($empresas[0]['id'] ?? 1);
        $fechaActual = date('Y-m-d');
        $fechaVenc   = date('Y-m-d', strtotime($fechaActual . ' + 7 days'));

        $metodosPago = [
            ['id' => 'PUE', 'texto' => 'Pago en una sola exhibición'],
            ['id' => 'PPD', 'texto' => 'Pago en parcialidades o diferido']
        ];
        $formasPago = [
            ['id' => '01', 'texto' => 'Efectivo'],
            ['id' => '02', 'texto' => 'Cheque nominativo'],
            ['id' => '03', 'texto' => 'Transferencia electrónica de fondos'],
            ['id' => '04', 'texto' => 'Tarjeta de crédito'],
            ['id' => '28', 'texto' => 'Tarjeta de débito'],
            ['id' => '99', 'texto' => 'Por definir'],
        ];
        $usosCFDI = [
            ['id' => 'G01', 'texto' => 'Adquisición de mercancías'],
            ['id' => 'G03', 'texto' => 'Gastos en general'],
            ['id' => 'I01', 'texto' => 'Construcciones'],
            ['id' => 'I04', 'texto' => 'Equipo de computo y accesorios'],
            ['id' => 'I08', 'texto' => 'Otra maquinaria y equipo'],
            ['id' => 'D10', 'texto' => 'Pagos por servicios educativos (colegiaturas)'],
            ['id' => 'S01', 'texto' => 'Sin efectos fiscales'],
        ];

        return [
            'empresas'         => $empresas,
            'idQuote'          => $quote['id'] ?? 0,
            'idEmpresa'        => $idEmpresa,
            'idSucursal'       => $quote['idSucursal'] ?? 0,
            'whsCode'          => $quote['whsCode'] ?? '',
            'idPriceList'      => $quote['idPriceList'] ?? 0,
            'idVendedor'       => $quote['idVendedor'] ?? $idUser,
            'nombreVendedor'   => $quote['nombreVendedor'] ?? $userName,
            'codigo'           => $quote['codigo'] ?? 1,
            'cardCode'         => $quote['cardCode'] ?? '',
            'cardName'         => $quote['cardName'] ?? '',
            'fecha'            => $quote['fecha'] ?? $fechaActual,
            'fechaVencimiento' => $quote['fechaVencimiento'] ?? $fechaVenc,
            'cotizarA'         => $quote['cotizarA'] ?? '',
            'observaciones'    => $quote['observaciones'] ?? '',
            'plazoEntrega'     => $quote['plazoEntrega'] ?? '',
            'codigoPostal'     => $quote['codigoPostal'] ?? '',
            'telefono'         => $quote['telefono'] ?? '',
            'metodoPago'       => $quote['metodoPago'] ?? 'PUE',
            'formaPago'        => $quote['formaPago'] ?? '03',
            'usoCFDI'          => $quote['usoCFDI'] ?? 'G01',
            'porcIva'          => $quote['porcIva'] ?? 16,
            'neto'             => number_format((float) ($quote['neto'] ?? 0), 2, '.', ''),
            'impuesto'         => number_format((float) ($quote['impuesto'] ?? 0), 2, '.', ''),
            'total'            => number_format((float) ($quote['total'] ?? 0), 2, '.', ''),
            'productos'        => !empty($quote['productos']) ? json_decode($quote['productos'], true) : [],
            'sapSynced'        => (int) ($quote['sapSynced'] ?? 0),
            'uuid'             => $quote['UUID'] ?? generaUUID(),
            'metodosPago'      => $metodosPago,
            'formasPago'       => $formasPago,
            'usosCFDI'         => $usosCFDI,
        ];
    }

    // =========================================================================
    // 3. PERMISOS POR USUARIO: SUCURSALES, LISTAS DE PRECIOS Y ALMACENES
    // =========================================================================

    public function getUserContextAjax()
    {
        try {
            $idUser     = (int) user()->id;
            $idEmpresa  = (int) ($this->request->getPost('idEmpresa') ?? $this->request->getPost('companie') ?? 0);
            $idSucursal = (int) ($this->request->getPost('idSucursal') ?? 0);

            $sucursales = $this->getUserBranchesWithSAP($idUser, $idEmpresa);

            if ($idSucursal === 0 && !empty($sucursales)) {
                $idSucursal = (int) $sucursales[0]['idSucursal'];
            }

            $listasPrecios = $this->getUserPriceLists($idUser, $idEmpresa);
            $almacenes     = $this->getUserWarehouses($idUser, $idEmpresa, $idSucursal);
            $nextFolio     = $this->quotesSAPModel->getNextFolio($idEmpresa, $idSucursal);

            return $this->response->setJSON([
                'error'         => false,
                'token'         => csrf_hash(),
                'sucursales'    => $sucursales,
                'listasPrecios' => $listasPrecios,
                'almacenes'     => $almacenes,
                'nextFolio'     => $nextFolio,
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    private function getAllowedBranchIdsForUser(int $idUser, array $empresasID): array
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('usuarios_sucursal')) {
            $rows = $db->table('usuarios_sucursal')
                ->select('idSucursal')
                ->where('idUsuario', $idUser)
                ->where('status', 'on')
                ->get()
                ->getResultArray();

            if (!empty($rows)) {
                return array_column($rows, 'idSucursal');
            }
        }

        $builder = $this->sucursales->builder();
        $this->applyCompanyFilter($builder, 'branchoffices', $empresasID);
        $rows = $builder->where('deleted_at', null)->get()->getResultArray();

        return !empty($rows) ? array_column($rows, 'id') : [0];
    }

    private function getUserBranchesWithSAP(int $idUser, int $idEmpresa): array
    {
        $db         = \Config\Database::connect();
        $allowedIds = $this->getAllowedBranchIdsForUser($idUser, [$idEmpresa]);

        $builder = $this->sucursales->builder();
        $this->applyCompanyFilter($builder, 'branchoffices', $idEmpresa);
        $localBranches = $builder
            ->whereIn('id', $allowedIds)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        if (empty($localBranches)) {
            return [];
        }

        $mapBpl = [];
        if ($db->tableExists('link_sap_branchoffice')) {
            $enlBuilder = $this->linkSapBranchOffice->builder();
            $this->applyCompanyFilter($enlBuilder, 'link_sap_branchoffice', $idEmpresa);
            $enlaces = $enlBuilder->where('deleted_at', null)->get()->getResultArray();

            foreach ($enlaces as $enl) {
                $mapBpl[(int) $enl['idBranchOffice']] = (int) $enl['idBranchOfficeSAP'];
            }
        }

        $sapIvaByBpl = [];
        try {
            $conn = $this->connectODBC($idEmpresa);
            $rs   = @odbc_exec($conn, 'SELECT "BPLId", "BPLName", "U_Impuesto" FROM "OBPL" WHERE "Disabled" = \'N\'');
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $bplId = (int) $row['BPLId'];
                    $sapIvaByBpl[$bplId] = [
                        'name'    => $this->toUtf8($row['BPLName'] ?? ''),
                        'porcIva' => isset($row['U_Impuesto']) ? (float) $row['U_Impuesto'] : 16.0
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);
        } catch (Throwable $ignore) {
        }

        $result = [];
        foreach ($localBranches as $br) {
            $idSuc    = (int) $br['id'];
            $bplId    = $mapBpl[$idSuc] ?? (int) ($br['key'] ?? $idSuc);
            $ivaLocal = isset($br['tax']) && is_numeric($br['tax']) && (float) $br['tax'] > 0 ? (float) $br['tax'] : null;
            $ivaSap   = $ivaLocal ?? ($sapIvaByBpl[$bplId]['porcIva'] ?? 16.0);
            $nombre   = ($br['key'] ?? $idSuc) . ' - ' . ($br['name'] ?? $sapIvaByBpl[$bplId]['name'] ?? 'Sucursal');

            $result[] = [
                'idSucursal' => $idSuc,
                'bplIdSAP'   => $bplId,
                'text'       => $nombre,
                'porcIva'    => $ivaSap
            ];
        }

        return $result;
    }

    private function getUserPriceLists(int $idUser, int $idEmpresa): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('user_pricelist')) {
            return [];
        }

        $builder = $this->userPricelistModel->builder();
        $this->applyCompanyFilter($builder, 'user_pricelist', $idEmpresa);

        if ($db->fieldExists('iduser', 'user_pricelist')) {
            $builder->groupStart()
                ->where('iduser', $idUser)
                ->orWhere('iduser', 0)
            ->groupEnd();
        }

        $rows = $builder
            ->where('deleted_at', null)
            ->orderBy('idPriceList', 'ASC')
            ->get()
            ->getResultArray();

        $lists = [];
        foreach ($rows as $r) {
            $lists[] = [
                'id'   => $r['idPriceList'],
                'text' => !empty($r['namePriceList'])
                    ? $r['namePriceList']
                    : ($r['idPriceList'] . ' - Lista ' . $r['idPriceList'])
            ];
        }

        return $lists;
    }

    private function getUserWarehouses(int $idUser, int $idEmpresa, int $idSucursal): array
    {
        $bplIdSAP = 0;
        $db       = \Config\Database::connect();

        if ($db->tableExists('link_sap_branchoffice')) {
            $enlBuilder = $this->linkSapBranchOffice->builder();
            $this->applyCompanyFilter($enlBuilder, 'link_sap_branchoffice', $idEmpresa);
            $enlace = $enlBuilder
                ->where('idBranchOffice', $idSucursal)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($enlace && !empty($enlace['idBranchOfficeSAP'])) {
                $bplIdSAP = (int) $enlace['idBranchOfficeSAP'];
            }
        }

        $allowedWhsCodes = [];
        foreach (['user_warehouse', 'usuarios_almacen', 'user_warehouses'] as $whsTable) {
            if ($db->tableExists($whsTable)) {
                $fields  = $db->getFieldNames($whsTable);
                $userCol = in_array('iduser', $fields) ? 'iduser' : (in_array('idUsuario', $fields) ? 'idUsuario' : 'idUser');
                $whsCol  = in_array('whsCode', $fields) ? 'whsCode' : (in_array('idAlmacen', $fields) ? 'idAlmacen' : 'warehouse');

                $q = $db->table($whsTable)->where($userCol, $idUser);
                $this->applyCompanyFilter($q, $whsTable, $idEmpresa);
                if (in_array('deleted_at', $fields)) {
                    $q->where('deleted_at', null);
                }
                $rows = $q->get()->getResultArray();
                if (!empty($rows)) {
                    $allowedWhsCodes = array_column($rows, $whsCol);
                }
                break;
            }
        }

        $warehouses = [];
        try {
            $conn  = $this->connectODBC($idEmpresa);
            $where = ' WHERE "Locked" = \'N\' ';

            if ($bplIdSAP > 0) {
                $where .= " AND \"BPLid\" = {$bplIdSAP} ";
            }

            if (!empty($allowedWhsCodes)) {
                $escapedCodes = array_map(fn($c) => "'" . str_replace("'", "''", trim((string) $c)) . "'", $allowedWhsCodes);
                $where       .= ' AND "WhsCode" IN (' . implode(',', $escapedCodes) . ') ';
            }

            $sql = 'SELECT "WhsCode", "WhsName" FROM "OWHS" ' . $where . ' ORDER BY "WhsCode" ASC';
            $rs  = odbc_exec($conn, $sql);
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code         = trim($this->toUtf8($row['WhsCode']));
                    $name         = trim($this->toUtf8($row['WhsName']));
                    $warehouses[] = [
                        'id'   => $code,
                        'text' => "{$code} - {$name}"
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);
        } catch (Throwable $e) {
            log_message('error', 'Error en getUserWarehouses: ' . $e->getMessage());
        }

        return $warehouses;
    }

    // =========================================================================
    // 4. BUSCADORES AJAX SAP: CLIENTES (OCRD) Y PRODUCTOS CON PRECIO > 0
    // =========================================================================

    public function getClientsSAPAjax()
    {
        try {
            $idEmpresa  = (int) ($this->request->getPost('idEmpresa') ?? 0);
            $searchTerm = trim((string) ($this->request->getPost('searchTerm') ?? ''));

            $conn  = $this->connectODBC($idEmpresa);
            $where = ' WHERE "CardType" = \'C\' AND "validFor" = \'Y\' AND "frozenFor" = \'N\' ';

            if ($searchTerm !== '') {
                $escaped = str_replace("'", "''", $searchTerm);
                $where  .= " AND (UPPER(\"CardCode\") LIKE UPPER('%{$escaped}%') OR UPPER(\"CardName\") LIKE UPPER('%{$escaped}%') OR UPPER(\"LicTradNum\") LIKE UPPER('%{$escaped}%')) ";
            }

            $sql = 'SELECT "CardCode", "CardName", "LicTradNum" FROM "OCRD" ' . $where . ' ORDER BY "CardName" ASC LIMIT 30';
            $rs  = odbc_exec($conn, $sql);

            $results = [];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code      = trim($this->toUtf8($row['CardCode']));
                    $name      = trim($this->toUtf8($row['CardName']));
                    $rfc       = trim($this->toUtf8($row['LicTradNum'] ?? ''));
                    $results[] = [
                        'id'       => $code,
                        'text'     => "{$code} - {$name}" . ($rfc ? " ({$rfc})" : ''),
                        'cardName' => $name
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'results' => $results
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'results' => []]);
        }
    }

    public function getClientDataSAPAjax()
    {
        try {
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 0);
            $cardCode  = trim((string) ($this->request->getPost('cardCode') ?? ''));

            if ($cardCode === '') {
                return $this->response->setJSON(['token' => csrf_hash(), 'telefono' => '', 'codigoPostal' => '']);
            }

            $conn      = $this->connectODBC($idEmpresa);
            $codeClean = str_replace("'", "''", $cardCode);

            $sql = "
                SELECT T0.\"CardName\", T0.\"Phone1\", T0.\"Cellular\", T0.\"ZipCode\", T0.\"ListNum\"
                FROM \"OCRD\" T0
                WHERE T0.\"CardCode\" = '{$codeClean}'
                LIMIT 1
            ";

            $rs   = odbc_exec($conn, $sql);
            $data = [
                'cardName'     => '',
                'telefono'     => '',
                'codigoPostal' => '',
                'listNum'      => ''
            ];

            if ($rs && ($row = odbc_fetch_array($rs))) {
                $data['cardName']     = trim($this->toUtf8($row['CardName'] ?? ''));
                $data['telefono']     = trim($this->toUtf8($row['Phone1'] ?: ($row['Cellular'] ?? '')));
                $data['codigoPostal'] = trim($this->toUtf8($row['ZipCode'] ?? ''));
                $data['listNum']      = (int) ($row['ListNum'] ?? 0);
                odbc_free_result($rs);
            }
            odbc_close($conn);

            $data['token'] = csrf_hash();
            return $this->response->setJSON($data);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'error' => $e->getMessage()]);
        }
    }

    public function getProductsSAPAjax()
    {
        $idUser      = (int) user()->id;
        $idEmpresa   = (int) ($this->request->getPost('idEmpresa') ?? 0);
        $idPriceList = (int) ($this->request->getPost('idPriceList') ?? 0);
        $whsCode     = trim((string) ($this->request->getPost('whsCode') ?? ''));
        $draw        = (int) ($this->request->getPost('draw') ?? 1);
        $start       = (int) ($this->request->getPost('start') ?? 0);
        $length      = (int) ($this->request->getPost('length') ?? 10);
        $searchValue = trim((string) ($this->request->getPost('search')['value'] ?? ''));

        $plBuilder = $this->userPricelistModel->builder();
        $this->applyCompanyFilter($plBuilder, 'user_pricelist', $idEmpresa);
        $hasAccessList = $plBuilder
            ->groupStart()
                ->where('iduser', $idUser)
                ->orWhere('iduser', 0)
            ->groupEnd()
            ->where('idPriceList', $idPriceList)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (!$hasAccessList || $idPriceList <= 0) {
            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'token'           => csrf_hash()
            ]);
        }

        try {
            $conn     = $this->connectODBC($idEmpresa);
            $whsClean = str_replace("'", "''", $whsCode);

            $joinWhs   = $whsClean !== ''
                ? " LEFT JOIN \"OITW\" T2 ON T0.\"ItemCode\" = T2.\"ItemCode\" AND T2.\"WhsCode\" = '{$whsClean}' "
                : "";
            $selectWhs = $whsClean !== ''
                ? ' COALESCE(T2."OnHand", 0) AS "Stock" '
                : ' COALESCE(T0."OnHand", 0) AS "Stock" ';

            // Excluir artículos con precio <= 0
            $where = " WHERE T0.\"SellItem\" = 'Y' AND T0.\"validFor\" = 'Y' AND T0.\"frozenFor\" = 'N' AND T1.\"PriceList\" = {$idPriceList} AND T1.\"Price\" > 0 ";

            if ($searchValue !== '') {
                $escaped = str_replace("'", "''", $searchValue);
                $where  .= " AND (UPPER(T0.\"ItemCode\") LIKE UPPER('%{$escaped}%') OR UPPER(T0.\"ItemName\") LIKE UPPER('%{$escaped}%')) ";
            }

            $sqlCount = "
                SELECT COUNT(*) AS \"Total\"
                FROM \"OITM\" T0
                INNER JOIN \"ITM1\" T1 ON T0.\"ItemCode\" = T1.\"ItemCode\"
                {$where}
            ";
            $rsCount      = odbc_exec($conn, $sqlCount);
            $rowCount     = $rsCount ? odbc_fetch_array($rsCount) : ['Total' => 0];
            $recordsTotal = (int) ($rowCount['Total'] ?? 0);
            if ($rsCount) {
                odbc_free_result($rsCount);
            }

            $sqlPaged = "
                SELECT * FROM (
                    SELECT
                        T0.\"ItemCode\",
                        T0.\"ItemName\",
                        T0.\"SalUnitMsr\",
                        T0.\"InvntryUom\",
                        COALESCE(T1.\"Price\", 0) AS \"Price\",
                        {$selectWhs},
                        ROW_NUMBER() OVER (ORDER BY T0.\"ItemCode\" ASC) AS rn
                    FROM \"OITM\" T0
                    INNER JOIN \"ITM1\" T1 ON T0.\"ItemCode\" = T1.\"ItemCode\"
                    {$joinWhs}
                    {$where}
                ) AS q
                WHERE rn > {$start} AND rn <= " . ($start + $length) . "
                ORDER BY rn
            ";

            $rsData = odbc_exec($conn, $sqlPaged);
            $data   = [];

            if ($rsData) {
                while ($row = odbc_fetch_array($rsData)) {
                    $itemCode = trim($this->toUtf8($row['ItemCode']));
                    $itemName = trim($this->toUtf8($row['ItemName']));
                    $unidad   = trim($this->toUtf8($row['InvntryUom'] ?: ($row['SalUnitMsr'] ?? 'PZA')));
                    $price    = round((float) ($row['Price'] ?? 0), 2);
                    $stock    = round((float) ($row['Stock'] ?? 0), 2);

                    $data[] = [
                        'ItemCode' => $itemCode,
                        'ItemName' => $itemName,
                        'Unidad'   => $unidad ?: 'PZA',
                        'Price'    => $price,
                        'Stock'    => $stock,
                    ];
                }
                odbc_free_result($rsData);
            }
            odbc_close($conn);

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsTotal,
                'data'            => $data,
                'token'           => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage(),
                'token'           => csrf_hash()
            ]);
        }
    }

    // =========================================================================
    // 5. GUARDAR, AUTORIZAR, ELIMINAR Y ENVIAR A SAP SERVICE LAYER (OQUT)
    // =========================================================================

    public function save()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return $this->response->setJSON(['error' => true, 'message' => 'Sesión expirada.', 'token' => csrf_hash()]);
        }

        $idUser   = (int) user()->id;
        $userName = user()->username;
        $fullName = trim(user()->username . ' ' . (user()->firstname ?? '') . ' ' . (user()->lastname ?? ''));
        $datos    = $this->request->getPost();

        try {
            $idQuote     = (int) ($datos['idQuote'] ?? 0);
            $idEmpresa   = (int) ($datos['idEmpresa'] ?? 0);
            $idSucursal  = (int) ($datos['idSucursal'] ?? 0);
            $idPriceList = (int) ($datos['idPriceList'] ?? 0);
            $whsCode     = trim((string) ($datos['whsCode'] ?? ''));
            $cardCode    = trim((string) ($datos['cardCode'] ?? ''));
            $uuid        = trim((string) ($datos['UUID'] ?? generaUUID()));
            $productos   = json_decode((string) ($datos['listaProductos'] ?? '[]'), true);

            if ($idEmpresa <= 0 || $idSucursal <= 0 || $idPriceList <= 0) {
                throw new Exception('Seleccione Empresa, Sucursal y Lista de Precios válidas.');
            }
            if ($cardCode === '' || $cardCode === '0') {
                throw new Exception('Seleccione un Cliente de SAP.');
            }
            if (empty($productos) || !is_array($productos)) {
                throw new Exception('La cotización no se puede guardar sin productos.');
            }

            $sucursalRow = $this->sucursales->find($idSucursal);
            $tplName     = !empty($sucursalRow['quoteTemplate']) ? $sucursalRow['quoteTemplate'] : 'quote_pdf_template.html';
            $tplCheck    = QuoteTemplateSecurity::validateTemplate($tplName);
            if (!$tplCheck['valid']) {
                throw new Exception($tplCheck['error']);
            }

            $plBuilder = $this->userPricelistModel->builder();
            $this->applyCompanyFilter($plBuilder, 'user_pricelist', $idEmpresa);
            $validList = $plBuilder
                ->groupStart()
                    ->where('iduser', $idUser)
                    ->orWhere('iduser', 0)
                ->groupEnd()
                ->where('idPriceList', $idPriceList)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$validList) {
                throw new Exception('No tienes permiso para cotizar con la Lista de Precios seleccionada.');
            }

            $db       = \Config\Database::connect();
            $bplIdSAP = $idSucursal;
            if ($db->tableExists('link_sap_branchoffice')) {
                $enlBuilder = $this->linkSapBranchOffice->builder();
                $this->applyCompanyFilter($enlBuilder, 'link_sap_branchoffice', $idEmpresa);
                $enlaceSucursal = $enlBuilder
                    ->where('idBranchOffice', $idSucursal)
                    ->where('deleted_at', null)
                    ->get()
                    ->getRowArray();
                if ($enlaceSucursal && !empty($enlaceSucursal['idBranchOfficeSAP'])) {
                    $bplIdSAP = (int) $enlaceSucursal['idBranchOfficeSAP'];
                }
            }

            $existing = $idQuote > 0 ? $this->quotesSAPModel->find($idQuote) : $this->quotesSAPModel->where('UUID', $uuid)->first();
            if ($existing && (int) ($existing['sapSynced'] ?? 0) === 1) {
                throw new Exception('Esta cotización ya fue sincronizada con SAP Business One y no puede modificarse.');
            }

            $codigo = $existing ? (int) $existing['codigo'] : $this->quotesSAPModel->getNextFolio($idEmpresa, $idSucursal);

            $saveData = [
                'idEmpresa'        => $idEmpresa,
                'idSucursal'       => $idSucursal,
                'bplIdSAP'         => $bplIdSAP,
                'whsCode'          => $whsCode,
                'idPriceList'      => $idPriceList,
                'namePriceList'    => $validList['namePriceList'] ?? (string) $idPriceList,
                'idVendedor'       => $existing['idVendedor'] ?? $idUser,
                'nombreVendedor'   => $existing['nombreVendedor'] ?? $fullName,
                'codigo'           => $codigo,
                'cardCode'         => $cardCode,
                'cardName'         => trim((string) ($datos['cardName'] ?? '')),
                'fecha'            => !empty($datos['fecha']) ? date('Y-m-d', strtotime($datos['fecha'])) : date('Y-m-d'),
                'fechaVencimiento' => !empty($datos['fechaVencimiento']) ? date('Y-m-d', strtotime($datos['fechaVencimiento'])) : date('Y-m-d', strtotime('+7 days')),
                'cotizarA'         => trim((string) ($datos['cotizarA'] ?? '')),
                'observaciones'    => trim((string) ($datos['observaciones'] ?? '')),
                'plazoEntrega'     => trim((string) ($datos['plazoEntrega'] ?? '')),
                'codigoPostal'     => trim((string) ($datos['codigoPostal'] ?? '')),
                'telefono'         => trim((string) ($datos['telefono'] ?? '')),
                'metodoPago'       => trim((string) ($datos['metodoPago'] ?? 'PUE')),
                'formaPago'        => trim((string) ($datos['formaPago'] ?? '03')),
                'usoCFDI'          => trim((string) ($datos['usoCFDI'] ?? 'G01')),
                'porcIva'          => (float) ($datos['porcIva'] ?? 16),
                'neto'             => (float) ($datos['neto'] ?? 0),
                'impuesto'         => (float) ($datos['impuesto'] ?? 0),
                'total'            => (float) ($datos['total'] ?? 0),
                'productos'        => json_encode($productos, JSON_UNESCAPED_UNICODE),
                'UUID'             => $uuid,
            ];

            if (!$existing) {
                $saveData['status'] = 'PEN';
                $this->quotesSAPModel->insert($saveData);
                $idQuote = (int) $this->quotesSAPModel->getInsertID();
                $msgLog  = "Se creó cotización #{$codigo} UUID: {$uuid}";
            } else {
                $idQuote = (int) $existing['id'];
                $this->quotesSAPModel->update($idQuote, $saveData);
                $msgLog  = "Se actualizó cotización #{$codigo} UUID: {$uuid}";
            }

            $this->log->save([
                'description' => $msgLog,
                'user'        => $userName
            ]);

            return $this->response->setJSON([
                'error'   => false,
                'message' => "Cotización #{$codigo} guardada correctamente.",
                'idQuote' => $idQuote,
                'uuid'    => $uuid,
                'token'   => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function showQuoteDetails()
    {
        $idQuote = (int) ($this->request->getPost('idQuote') ?? 0);
        $quote   = $this->quotesSAPModel->find($idQuote);

        $items = ($quote && !empty($quote['productos'])) ? json_decode($quote['productos'], true) : [];
        $out   = [];

        foreach ($items as $idx => $it) {
            $out[] = [
                'renglon'     => $idx + 1,
                'clave'       => $it['clave'] ?? '',
                'descripcion' => $it['descripcion'] ?? '',
                'unidad'      => $it['unidad'] ?? 'PZA',
                'cantidad'    => number_format((float) ($it['cantidad'] ?? 0), 2),
                'precio'      => '$' . number_format((float) ($it['precio'] ?? 0), 2),
                'total'       => '$' . number_format((float) ($it['total'] ?? 0), 2),
            ];
        }

        return $this->response->setJSON([
            'data'  => $out,
            'token' => csrf_hash()
        ]);
    }

    public function changeStatus()
    {
        try {
            if (!has_permission('quotesSAP-authorize') && !in_groups('super-admin')) {
                throw new Exception('No tienes permiso (quotesSAP-authorize) para autorizar o cambiar el estatus de cotizaciones.');
            }

            $idQuote      = (int) ($this->request->getPost('idQuote') ?? 0);
            $status       = strtoupper(trim((string) ($this->request->getPost('status') ?? 'PEN')));
            $folioFactura = trim((string) ($this->request->getPost('folioFacturaStatus') ?? ''));

            $quote = $this->quotesSAPModel->find($idQuote);
            if (!$quote) {
                throw new Exception('Cotización no encontrada.');
            }

            $this->quotesSAPModel->update($idQuote, [
                'status'             => $status,
                'folioFacturaStatus' => $folioFactura
            ]);

            $this->log->save([
                'description' => "Cambio de estatus cotización #{$quote['codigo']} a {$status}",
                'user'        => user()->username
            ]);

            return $this->response->setJSON([
                'error'   => false,
                'message' => 'Estatus actualizado correctamente.',
                'token'   => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function delete($id)
    {
        $quote = $this->quotesSAPModel->find($id);
        if (!$quote) {
            return $this->failNotFound('Cotización no encontrada.');
        }

        if ((int) ($quote['sapSynced'] ?? 0) === 1) {
            return $this->failValidationErrors('La cotización ya fue sincronizada con SAP y no puede eliminarse.');
        }

        $this->quotesSAPModel->delete($id);
        $this->quotesSAPModel->purgeDeleted();

        $this->log->save([
            'description' => "Se eliminó cotización #{$quote['codigo']}: " . json_encode($quote),
            'user'        => user()->username
        ]);

        return $this->respondDeleted($quote, 'Cotización eliminada correctamente.');
    }

    /**
     * Obtiene el SlpCode (SalesPersonCode) de SAP enlazado al vendedor de la cotización.
     * Busca en `employee_sap_link` y resuelve si el código guardado es un empID (OHEM)
     * o directamente un SlpCode (OSLP).
     */
    private function resolveLinkedSlpCode(int $idEmpresa, int $idVendedor): ?int
    {
        $db = \Config\Database::connect();
        $rawSapEmp = null;

        if ($db->tableExists('employee_sap_link')) {
            $builder = $this->employeeSapLink->builder();
            $this->applyCompanyFilter($builder, 'employee_sap_link', $idEmpresa);
            $linkRow = $builder
                ->where('iduser', $idVendedor)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if ($linkRow && isset($linkRow['sapemployee']) && trim((string) $linkRow['sapemployee']) !== '' && (int) $linkRow['sapemployee'] > 0) {
                $rawSapEmp = (int) $linkRow['sapemployee'];
            }
        }

        if ($rawSapEmp === null || $rawSapEmp <= 0) {
            return null;
        }

        // Verificar en SAP HANA si ese ID corresponde a OHEM.empID (y extraer su salesPrson = SlpCode)
        // o si ya es directamente un SlpCode en OSLP
        try {
            $conn = $this->connectODBC($idEmpresa);

            // 1. Intentar obtener el SlpCode desde OHEM ("salesPrson") cuando se enlazó con OHEM."empID"
            $sqlOhem = "SELECT \"salesPrson\" FROM \"OHEM\" WHERE \"empID\" = {$rawSapEmp} LIMIT 1";
            $rsOhem  = @odbc_exec($conn, $sqlOhem);
            if ($rsOhem && ($rowOhem = odbc_fetch_array($rsOhem))) {
                $salesPrson = (int) ($rowOhem['salesPrson'] ?? -1);
                odbc_free_result($rsOhem);
                if ($salesPrson > 0) {
                    odbc_close($conn);
                    return $salesPrson;
                }
            }

            // 2. Si no tiene salesPrson en OHEM o se enlazó directo a OSLP, verificar si existe como SlpCode en OSLP
            $sqlOslp = "SELECT \"SlpCode\" FROM \"OSLP\" WHERE \"SlpCode\" = {$rawSapEmp} AND \"SlpCode\" > 0 LIMIT 1";
            $rsOslp  = @odbc_exec($conn, $sqlOslp);
            if ($rsOslp && ($rowOslp = odbc_fetch_array($rsOslp))) {
                $slpCode = (int) ($rowOslp['SlpCode'] ?? -1);
                odbc_free_result($rsOslp);
                if ($slpCode > 0) {
                    odbc_close($conn);
                    return $slpCode;
                }
            }

            odbc_close($conn);
        } catch (Throwable $ignoreOdbc) {
            // Si falla la consulta auxiliar ODBC pero hay enlace numérico válido en la tabla local, usar el valor enlazado
            return $rawSapEmp;
        }

        return $rawSapEmp;
    }

    public function sendToSAP()
    {
        try {
            $idQuote = (int) ($this->request->getPost('idQuote') ?? 0);
            $quote   = $this->quotesSAPModel->find($idQuote);

            if (!$quote) {
                throw new Exception('No se encontró la cotización.');
            }
            if ((int) ($quote['sapSynced'] ?? 0) === 1) {
                throw new Exception("Esta cotización ya fue enviada a SAP (DocNum: {$quote['sapDocNum']}).");
            }

            // Validar que esté AUTORIZADA antes de permitir el envío a SAP
            $statusActual = strtoupper(trim((string) ($quote['status'] ?? 'PEN')));
            if (!in_array($statusActual, ['AUT', 'LFA'], true)) {
                throw new Exception('La cotización debe estar AUTORIZADA antes de poder enviarse a SAP.');
            }

            $productos = json_decode($quote['productos'], true);
            if (empty($productos)) {
                throw new Exception('La cotización no tiene partidas para enviar a SAP.');
            }

            $idEmpresa  = (int) ($quote['idEmpresa'] ?? 1);
            $idSucursal = (int) ($quote['idSucursal'] ?? 1);
            $idVendedor = (int) ($quote['idVendedor'] ?? user()->id);
            $bplIdSAP   = (int) ($quote['bplIdSAP'] ?: $idSucursal);
            $whsCode    = trim((string) ($quote['whsCode'] ?? ''));
            $porcIva    = (float) ($quote['porcIva'] ?? 16);
            $taxCode    = $porcIva == 8 ? 'IVA8' : ($porcIva == 0 ? 'IVA0' : 'IVA16');

            // Validar que el usuario/vendedor tenga su empleado de ventas enlazado
            $slpCode = $this->resolveLinkedSlpCode($idEmpresa, $idVendedor);
            if ($slpCode === null || $slpCode <= 0) {
                throw new Exception('No hay empleado ventas del SAP enlazado.');
            }

            try {
                $conn = $this->connectODBC($idEmpresa);

                if ($whsCode === '' && $bplIdSAP > 0) {
                    $rsWhs = @odbc_exec($conn, "SELECT \"WhsCode\" FROM \"OWHS\" WHERE \"Locked\" = 'N' AND \"BPLid\" = {$bplIdSAP} ORDER BY \"WhsCode\" ASC LIMIT 1");
                    if ($rsWhs && ($rowWhs = odbc_fetch_array($rsWhs))) {
                        $whsCode = trim($this->toUtf8($rowWhs['WhsCode'] ?? ''));
                        odbc_free_result($rsWhs);
                    }
                }

                $rsTax = @odbc_exec($conn, "SELECT \"Code\" FROM \"OSTC\" WHERE \"Code\" = '{$taxCode}' LIMIT 1");
                if (!$rsTax || !odbc_fetch_array($rsTax)) {
                    $rsTaxRate = @odbc_exec($conn, "SELECT \"Code\" FROM \"OSTC\" WHERE \"Rate\" = {$porcIva} AND \"ValidForAR\" = 'Y' ORDER BY \"Code\" ASC LIMIT 1");
                    if ($rsTaxRate && ($rowTax = odbc_fetch_array($rsTaxRate))) {
                        $taxCode = trim($this->toUtf8($rowTax['Code']));
                        odbc_free_result($rsTaxRate);
                    }
                } else {
                    odbc_free_result($rsTax);
                }

                odbc_close($conn);
            } catch (Throwable $ignoreOdbc) {
            }

            $docLines = [];
            foreach ($productos as $prod) {
                $line = [
                    'ItemCode'        => $prod['clave'],
                    'ItemDescription' => mb_substr((string) $prod['descripcion'], 0, 100),
                    'Quantity'        => (float) $prod['cantidad'],
                    'UnitPrice'       => (float) $prod['precio'],
                    'TaxCode'         => $taxCode,
                    'SalesPersonCode' => $slpCode, // SlpCode a nivel partida
                ];
                if ($whsCode !== '') {
                    $line['WarehouseCode'] = $whsCode;
                }
                $docLines[] = $line;
            }

            $payload = [
                'CardCode'                => $quote['cardCode'],
                'DocDate'                 => substr((string) $quote['fecha'], 0, 10),
                'DocDueDate'              => substr((string) $quote['fechaVencimiento'], 0, 10),
                'TaxDate'                 => substr((string) $quote['fecha'], 0, 10),
                'BPL_IDAssignedToInvoice' => $bplIdSAP,
                'SalesPersonCode'         => $slpCode, // En Service Layer SalesPersonCode equivale a OQUT.SlpCode
                'Comments'                => mb_substr("Cotización Web #{$quote['codigo']} | {$quote['observaciones']}", 0, 254),
                'DocumentLines'           => $docLines
            ];

            $sapRes   = $this->requestServiceLayer($idEmpresa, 'POST', 'Quotations', $payload);
            $docEntry = (int) ($sapRes['DocEntry'] ?? 0);
            $docNum   = (int) ($sapRes['DocNum'] ?? $docEntry);

            $this->quotesSAPModel->update($idQuote, [
                'sapDocEntry' => $docEntry,
                'sapDocNum'   => $docNum,
                'sapSynced'   => 1
            ]);

            return $this->response->setJSON([
                'error'   => false,
                'message' => "Cotización creada en SAP exitosamente (DocNum: {$docNum}).",
                'token'   => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    // =========================================================================
    // 6. ESCÁNER DE ARCHIVOS EN WRITABLE Y GENERACIÓN SEGURA DE PDF
    // =========================================================================

    public function getTemplatesListAjax()
    {
        return $this->response->setJSON([
            'results' => QuoteTemplateSecurity::listAvailableTemplates(),
            'token'   => csrf_hash()
        ]);
    }

    public function report(string $uuid)
    {
        $quote = $this->quotesSAPModel->where('UUID', $uuid)->first();
        if (!$quote) {
            return redirect()->to(base_url('admin/quotesSAP'))->with('error', 'Cotización no encontrada.');
        }

        $idEmpresa  = (int) ($quote['idEmpresa'] ?? 1);
        $idSucursal = (int) $quote['idSucursal'];
        $bplIdSAP   = (int) ($quote['bplIdSAP'] ?: $idSucursal);

        $datosEmpresa  = $this->empresa->find($idEmpresa) ?: $this->empresa->first();
        $sucursalLocal = $this->sucursales->find($idSucursal);
        $nombreEmpresa = !empty($datosEmpresa['nombre']) ? $datosEmpresa['nombre'] : 'MI EMPRESA SA DE CV';

        $nombrePlantilla = !empty($sucursalLocal['quoteTemplate'])
            ? trim((string) $sucursalLocal['quoteTemplate'])
            : 'quote_pdf_template.html';

        $validacion = QuoteTemplateSecurity::validateTemplate($nombrePlantilla);
        if (!$validacion['valid']) {
            return $this->response
                ->setStatusCode(400)
                ->setBody('Error de seguridad en plantilla: ' . esc($validacion['error']));
        }
        $templateHtml = $validacion['content'];

        $vendedor       = $this->users->find($quote['idVendedor']);
        $nombreVendedor = $vendedor
            ? (trim(($vendedor->firstname ?? '') . ' ' . ($vendedor->lastname ?? '')) ?: ($vendedor->username ?? $quote['nombreVendedor']))
            : ($quote['nombreVendedor'] ?? '');

        $iniciales          = $this->obtenerInicialesVendedor($nombreVendedor);
        $valorVenta         = $iniciales . '-' . str_pad((string) $quote['codigo'], 5, '0', STR_PAD_LEFT);
        $telefonoVendedor   = $vendedor->phone ?? $vendedor->telefono ?? '';
        $emailVendedor      = $vendedor->email ?? '';
        $correoVendedorHtml = strlen($emailVendedor) > 3 ? "<br> Correo Electronico: " . esc($emailVendedor) : '';
        $puestoVendedor     = 'Asesor Comercial';

        $metodoPagoDesc = $this->resolverDescripcionCatalogoSAT('metodoPago', (string) ($quote['metodoPago'] ?? ''));
        $formaPagoDesc  = $this->resolverDescripcionCatalogoSAT('formaPago', (string) ($quote['formaPago'] ?? ''));
        $usoCFDIDesc    = $this->resolverDescripcionCatalogoSAT('usoCFDI', (string) ($quote['usoCFDI'] ?? ''));

        $clienteSAP  = ['CardName' => $quote['cardName'], 'Phone1' => $quote['telefono'], 'EmailAddress' => ''];
        $sucursalSAP = ['Street' => '', 'Block' => '', 'City' => ''];
        $unidadesSAP = [];
        $productos   = json_decode($quote['productos'], true) ?: [];

        try {
            $conn = $this->connectODBC($idEmpresa);

            $cardCodeClean = str_replace("'", "''", trim((string) $quote['cardCode']));
            $rsCli = @odbc_exec($conn, "SELECT \"CardName\", \"Phone1\", \"EmailAddress\" FROM \"OCRD\" WHERE \"CardCode\" = '{$cardCodeClean}' LIMIT 1");
            if ($rsCli && ($rowCli = odbc_fetch_array($rsCli))) {
                $clienteSAP['CardName']     = trim($this->toUtf8($rowCli['CardName'] ?? $quote['cardName']));
                $clienteSAP['Phone1']       = trim($this->toUtf8($rowCli['Phone1'] ?? $quote['telefono']));
                $clienteSAP['EmailAddress'] = trim($this->toUtf8($rowCli['EmailAddress'] ?? ''));
                odbc_free_result($rsCli);
            }

            $rsSuc = @odbc_exec($conn, "SELECT \"Street\", \"Block\", \"City\" FROM \"OBPL\" WHERE \"BPLId\" = {$bplIdSAP} LIMIT 1");
            if ($rsSuc && ($rowSuc = odbc_fetch_array($rsSuc))) {
                $sucursalSAP['Street'] = trim($this->toUtf8($rowSuc['Street'] ?? ''));
                $sucursalSAP['Block']  = trim($this->toUtf8($rowSuc['Block'] ?? ''));
                $sucursalSAP['City']   = trim($this->toUtf8($rowSuc['City'] ?? ''));
                odbc_free_result($rsSuc);
            }

            $clavesProd = array_filter(array_column($productos, 'clave'));
            if (!empty($clavesProd)) {
                $inClaves = implode(',', array_map(fn($c) => "'" . str_replace("'", "''", trim((string) $c)) . "'", $clavesProd));
                $rsUom    = @odbc_exec($conn, "SELECT \"ItemCode\", \"InvntryUom\", \"SalUnitMsr\" FROM \"OITM\" WHERE \"ItemCode\" IN ({$inClaves})");
                if ($rsUom) {
                    while ($rowUom = odbc_fetch_array($rsUom)) {
                        $codeU = trim($this->toUtf8($rowUom['ItemCode']));
                        $uom   = trim($this->toUtf8($rowUom['InvntryUom'] ?: ($rowUom['SalUnitMsr'] ?? 'PZA')));
                        $unidadesSAP[$codeU] = $uom;
                    }
                    odbc_free_result($rsUom);
                }
            }

            odbc_close($conn);
        } catch (Throwable $ignoreOdbc) {
        }

        $direccionSucursal = !empty($sucursalLocal['customAddressQuote'])
            ? esc($sucursalLocal['customAddressQuote'])
            : esc(trim($sucursalSAP['Street'] . ' ' . $sucursalSAP['Block'] . ' ' . $sucursalSAP['City']));

        $telefonosSucursal = !empty($sucursalLocal['customPhonesQuote'])
            ? esc($sucursalLocal['customPhonesQuote'])
            : esc($sucursalLocal['phone'] ?? $sucursalLocal['telefono'] ?? '');

        $ciudadSucursal  = esc($sucursalSAP['City'] ?: ($sucursalLocal['city'] ?? ''));
        $leyendaSucursal = $sucursalLocal['quoteLegend'] ?? '';
        $noRefacturacion = esc($sucursalLocal['noReinvoiceNotice'] ?? '');

        $htmlBloqueProductos = '';
        $contador = 0;
        foreach ($productos as $item) {
            $contador++;
            $bgStyle = ($contador % 2 === 0)
                ? 'background-color:#ecf0f1; padding: 3px 4px 3px;'
                : 'background-color:white; padding: 3px 4px 3px;';

            $clave         = esc($item['clave'] ?? '');
            $descripcion   = esc($item['descripcion'] ?? '');
            $cantidad      = esc($item['cantidad'] ?? 1);
            $unidad        = esc($unidadesSAP[$item['clave'] ?? ''] ?? ($item['unidad'] ?? 'PZA'));
            $valorUnitario = number_format((float) ($item['precio'] ?? 0), 2);
            $precioTotal   = number_format((float) ($item['total'] ?? 0), 2);

            $htmlBloqueProductos .= <<<EOF
<table style="font-size:8px; padding:5px 10px;">
    <tr>
        <td style="{$bgStyle} width:110px; text-align:center">{$clave}</td>
        <td style="{$bgStyle} width:150px; text-align:left">{$descripcion}</td>
        <td style="{$bgStyle} width:40px; text-align:center">{$cantidad}</td>
        <td style="{$bgStyle} width:80px; text-align:center">{$unidad}</td>
        <td style="{$bgStyle} width:80px; text-align:right">$ {$valorUnitario}</td>
        <td style="{$bgStyle} width:80px; text-align:right">$ {$precioTotal}</td>
    </tr>
</table>
EOF;
        }

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

        $logoHeaderPath  = $this->prepararImagenTCPDF($rutasLogoEmpresa, 'pdf_logo_header_' . $idEmpresa);
        $logoCalidadPath = $this->prepararImagenTCPDF([
            'writable/templates/quotes/calidadGusa.png',
            'writable/templates/quotes/logoCalidad.png',
            'public/img/calidadGusa.png',
            'public/images/calidadGusa.png'
        ], 'pdf_logo_calidad_' . $idEmpresa);

        $logoHeaderHtml  = $logoHeaderPath ? '<img src="' . $logoHeaderPath . '" style="width:130px;">' : '';
        $logoCalidadHtml = $logoCalidadPath ? '<img src="' . $logoCalidadPath . '" style="width:100px;">' : '';

        $fechaCorta = substr((string) $quote['fecha'], 0, 10);
        $fechaVenc  = substr((string) $quote['fechaVencimiento'], 0, 10);
        $fechaHum   = function_exists('fechaHumanizada') ? fechaHumanizada($fechaCorta) : $this->formatearFechaHumanizada($fechaCorta);

        $nombreClienteMostrar = !empty($quote['cotizarA']) ? esc($quote['cotizarA']) : esc($clienteSAP['CardName']);
        $telefonoCliente      = !empty($quote['telefono']) ? esc($quote['telefono']) : esc($clienteSAP['Phone1']);

        $reemplazos = [
            '{{COLOR_PRIMARIO}}'       => '#a82020',
            '{{LOGO_HEADER_HTML}}'     => $logoHeaderHtml,
            '{{LOGO_CALIDAD_HTML}}'    => $logoCalidadHtml,
            '{{NOMBRE_EMPRESA}}'       => esc($nombreEmpresa),
            '{{DIRECCION_SUCURSAL}}'   => $direccionSucursal,
            '{{TELEFONOS_SUCURSAL}}'   => $telefonosSucursal,
            '{{CIUDAD_SUCURSAL}}'      => $ciudadSucursal,
            '{{FOLIO_COTIZACION}}'     => esc($valorVenta),
            '{{FECHA_HUMANIZADA}}'     => esc($fechaHum),
            '{{NOMBRE_CLIENTE}}'       => $nombreClienteMostrar,
            '{{TELEFONO_CLIENTE}}'     => $telefonoCliente,
            '{{EMAIL_CLIENTE}}'        => esc($clienteSAP['EmailAddress']),
            '{{OBSERVACIONES}}'        => esc($quote['observaciones'] ?? ''),
            '{{CODIGO_POSTAL}}'        => esc($quote['codigoPostal'] ?? ''),
            '{{METODO_PAGO}}'          => esc($metodoPagoDesc),
            '{{USO_CFDI}}'             => esc($usoCFDIDesc),
            '{{FORMA_PAGO}}'           => esc($formaPagoDesc),
            '{{NOMBRE_VENDEDOR}}'      => esc($nombreVendedor),
            '{{FECHA}}'                => esc($fechaCorta),
            '{{FECHA_VENCIMIENTO}}'    => esc($fechaVenc),
            '{{COTIZAR_A}}'            => esc($quote['cotizarA'] ?? ''),
            '{{BLOQUE_PRODUCTOS}}'     => $htmlBloqueProductos,
            '{{SUBTOTAL}}'             => number_format((float) $quote['neto'], 2),
            '{{IVA}}'                  => number_format((float) $quote['impuesto'], 2),
            '{{TOTAL}}'                => number_format((float) $quote['total'], 2),
            '{{NO_REFACTURACION}}'     => $noRefacturacion,
            '{{TELEFONO_VENDEDOR}}'    => esc($telefonoVendedor),
            '{{PUESTO_VENDEDOR}}'      => esc($puestoVendedor),
            '{{CORREO_VENDEDOR_HTML}}' => $correoVendedorHtml,
            '{{LEYENDA_SUCURSAL}}'     => $leyendaSucursal,
        ];

        $htmlFinal = strtr($templateHtml, $reemplazos);

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->startPageGroup();
        $pdf->AddPage();

        $bloques = explode('<!-- BLOCK_SEPARATOR -->', $htmlFinal);
        foreach ($bloques as $bloque) {
            $bloqueLimpio = trim($bloque);
            if ($bloqueLimpio !== '') {
                $pdf->writeHTML($bloqueLimpio, false, false, false, false, '');
            }
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $pdfDir = WRITEPATH . 'uploads/quotes_pdf/';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0775, true);
        }

        $fileName = "COTIZACION_{$valorVenta}.pdf";
        $pdf->Output($pdfDir . $fileName, 'F');

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output($fileName, 'I');
        exit;
    }

    private function obtenerInicialesVendedor(string $nombreCompleto): string
    {
        $partes    = preg_split('/\s+/', trim($nombreCompleto));
        $iniciales = '';
        foreach ($partes as $p) {
            if ($p !== '') {
                $iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
            }
        }
        return $iniciales ?: 'COT';
    }

    private function resolverDescripcionCatalogoSAT(string $tipo, string $clave): string
    {
        if ($clave === '') {
            return '';
        }

        $mapas = [
            'metodoPago' => [
                'PUE' => 'PUE - Pago en una sola exhibición',
                'PPD' => 'PPD - Pago en parcialidades o diferido',
            ],
            'formaPago' => [
                '01' => '01 - Efectivo',
                '02' => '02 - Cheque nominativo',
                '03' => '03 - Transferencia electrónica de fondos',
                '04' => '04 - Tarjeta de crédito',
                '28' => '28 - Tarjeta de débito',
                '99' => '99 - Por definir',
            ],
            'usoCFDI' => [
                'G01' => 'G01 - Adquisición de mercancías',
                'G03' => 'G03 - Gastos en general',
                'I01' => 'I01 - Construcciones',
                'I04' => 'I04 - Equipo de computo y accesorios',
                'I08' => 'I08 - Otra maquinaria y equipo',
                'D10' => 'D10 - Pagos por servicios educativos (colegiaturas)',
                'S01' => 'S01 - Sin efectos fiscales',
            ],
        ];

        return $mapas[$tipo][$clave] ?? $clave;
    }

    private function formatearFechaHumanizada(string $fechaYmd): string
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        $ts = strtotime($fechaYmd);
        if (!$ts) {
            return $fechaYmd;
        }
        return date('d', $ts) . ' de ' . $meses[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
    }

    /**
     * Localiza la imagen y genera una copia optimizada en JPG dentro de FCPATH (public/images/logo/)
     * para evitar el bug de DOCUMENT_ROOT y el fallo de canal alfa PNG en TCPDF.
     */
    private function prepararImagenTCPDF(array $rutasRelativas, string $cachePrefix): string
    {
        $archivoOrigen = '';
        foreach ($rutasRelativas as $rel) {
            $candidatoRoot = ROOTPATH . ltrim($rel, '/\\');
            $candidatoFc   = FCPATH . ltrim(preg_replace('#^public/#', '', $rel), '/\\');

            if (is_file($candidatoRoot)) {
                $archivoOrigen = $candidatoRoot;
                break;
            }
            if (is_file($candidatoFc)) {
                $archivoOrigen = $candidatoFc;
                break;
            }
        }

        if ($archivoOrigen === '') {
            return '';
        }

        $publicCacheDir = rtrim(FCPATH, '/\\') . '/images/logo/';
        if (!is_dir($publicCacheDir)) {
            @mkdir($publicCacheDir, 0775, true);
        }

        $destinoJpg = $publicCacheDir . $cachePrefix . '.jpg';

        if (!is_file($destinoJpg) || filemtime($archivoOrigen) > filemtime($destinoJpg)) {
            if (function_exists('imagecreatefromstring')) {
                $raw    = @file_get_contents($archivoOrigen);
                $srcImg = $raw ? @imagecreatefromstring($raw) : false;
                if ($srcImg !== false) {
                    $w      = imagesx($srcImg);
                    $h      = imagesy($srcImg);
                    $canvas = imagecreatetruecolor($w, $h);
                    $white  = imagecolorallocate($canvas, 255, 255, 255);
                    imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
                    imagecopy($canvas, $srcImg, 0, 0, 0, 0, $w, $h);
                    @imagejpeg($canvas, $destinoJpg, 95);
                    imagedestroy($srcImg);
                    imagedestroy($canvas);
                }
            }

            if (!is_file($destinoJpg)) {
                $ext          = pathinfo($archivoOrigen, PATHINFO_EXTENSION) ?: 'png';
                $destinoCopia = $publicCacheDir . $cachePrefix . '.' . $ext;
                @copy($archivoOrigen, $destinoCopia);
                return is_file($destinoCopia) ? $destinoCopia : $archivoOrigen;
            }
        }

        return $destinoJpg;
    }

    // =========================================================================
    // 7. CONEXIONES ODBC Y SERVICE LAYER
    // =========================================================================

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

    private function requestServiceLayer(int $idEmpresa, string $method, string $endpoint, array $payload = []): array
    {
        $tableSL = $this->serviceLayerModel->table ?? 'sapservicelayer';
        $builder = $this->serviceLayerModel->builder();
        if ($idEmpresa > 0) {
            $this->applyCompanyFilter($builder, $tableSL, $idEmpresa);
        }
        $dataConect = $builder->where('deleted_at', null)->get()->getRowArray()
            ?: $this->serviceLayerModel->where('deleted_at', null)->first();

        if (!$dataConect) {
            throw new Exception('No se encontró configuración de Service Layer.');
        }

        $baseUrl     = trim((string) ($dataConect['urlServiceLayer'] ?? $dataConect['url'] ?? ''), " \t\n\r\0\x0B/");
        $port        = trim((string) ($dataConect['port'] ?? $dataConect['portServiceLayer'] ?? ''));
        $companyDB   = trim((string) ($dataConect['companyDB'] ?? $dataConect['CompanyDB'] ?? ''));
        $userSAP     = trim((string) ($dataConect['username'] ?? $dataConect['userSAP'] ?? $dataConect['user'] ?? ''));
        $passwordSAP = (string) ($dataConect['password'] ?? $dataConect['passwordSAP'] ?? '');

        if ($companyDB === '' || $userSAP === '' || $passwordSAP === '') {
            throw new Exception('Las credenciales de Service Layer (companyDB, username o password) están incompletas en la configuración.');
        }

        if ($port !== '' && strpos($baseUrl, ':' . $port) === false) {
            if (preg_match('#^(https?://[^/]+)(/.*)?$#i', $baseUrl, $m)) {
                $baseUrl = $m[1] . ':' . $port . ($m[2] ?? '');
            }
        }
        if (strpos($baseUrl, '/b1s/v') === false) {
            $baseUrl .= '/b1s/v1';
        }

        $loginPayload = json_encode([
            'CompanyDB' => $companyDB,
            'UserName'  => $userSAP,
            'Password'  => $passwordSAP
        ], JSON_UNESCAPED_UNICODE);

        $chLogin = curl_init("{$baseUrl}/Login");
        curl_setopt_array($chLogin, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $loginPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($loginPayload)
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 30
        ]);

        $loginResult   = curl_exec($chLogin);
        $curlErrLogin  = curl_error($chLogin);
        $httpCodeLogin = curl_getinfo($chLogin, CURLINFO_HTTP_CODE);
        $headerSize    = curl_getinfo($chLogin, CURLINFO_HEADER_SIZE);
        $headersLogin  = substr((string) $loginResult, 0, $headerSize);
        $bodyLogin     = substr((string) $loginResult, $headerSize);
        curl_close($chLogin);

        if ($loginResult === false) {
            throw new Exception("No se pudo conectar al Service Layer ({$baseUrl}): {$curlErrLogin}");
        }

        if ($httpCodeLogin < 200 || $httpCodeLogin >= 300) {
            $err = json_decode($bodyLogin, true)['error']['message']['value'] ?? $bodyLogin;
            throw new Exception("Error Login Service Layer (Usuario: {$userSAP} / BD: {$companyDB}): {$err}");
        }

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headersLogin, $matches);
        $cookies = implode('; ', $matches[1] ?? []);

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $chDoc = curl_init("{$baseUrl}/" . ltrim($endpoint, '/'));
        curl_setopt_array($chDoc, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonBody),
                "Cookie: {$cookies}"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 60
        ]);

        $docResult   = curl_exec($chDoc);
        $httpCodeDoc = curl_getinfo($chDoc, CURLINFO_HTTP_CODE);
        curl_close($chDoc);

        $chLogout = curl_init("{$baseUrl}/Logout");
        curl_setopt_array($chLogout, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ["Cookie: {$cookies}"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 5
        ]);
        @curl_exec($chLogout);
        curl_close($chLogout);

        $decoded = json_decode((string) $docResult, true);
        if ($httpCodeDoc < 200 || $httpCodeDoc >= 300) {
            $msg = $decoded['error']['message']['value'] ?? $docResult;
            throw new Exception("SAP rechazó la cotización ({$httpCodeDoc}): {$msg}");
        }

        return is_array($decoded) ? $decoded : [];
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