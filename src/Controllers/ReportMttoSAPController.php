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

class ReportMttoSAPController extends BaseController
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

    /**
     * Vista principal del reporte de mantenimientos SAP (Solo lectura)
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
            'title'     => 'Reporte de Mantenimiento de Equipos SAP',
            'subtitle'  => 'Consulta de estatus por Horómetro, Odómetro y Semanas (Solo Lectura)',
            'empresas'  => $empresas,
            'idEmpresa' => (int) ($empresas[0]['id'] ?? 1),
        ];

        return view('julio101290\boilerplateservicelayer\Views\reportMttoSAP', $data);
    }

    /**
     * Carga vía AJAX los catálogos de filtros desde SAP HANA:
     * - Sucursales permitidas (OBPL)
     * - Artículos / Equipos configurados (OITM / @MTTOCONFIG)
     * - Tipos de Mantenimiento (@MTTOCONFIGDET)
     */
    public function getFiltersAjax()
    {
        try {
            $idUser    = (int) user()->id;
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $bplId     = trim((string) ($this->request->getPost('bplId') ?? 'TODOS'));

            $sucursales   = $this->getAllowedSAPBranches($idUser, $idEmpresa);
            $allowedBplIn = array_column($sucursales, 'bplId');

            $conn = $this->connectODBC($idEmpresa);

            // 1. Obtener Artículos / Equipos (OITM con AssetClass <> '')
            $whereBplItem = '';
            if ($bplId !== '' && $bplId !== 'TODOS' && is_numeric($bplId)) {
                $whereBplItem = ' AND t0."U_BPId" = ' . (int) $bplId . ' ';
            } elseif (!empty($allowedBplIn)) {
                $whereBplItem = ' AND t0."U_BPId" IN (' . implode(',', array_map('intval', $allowedBplIn)) . ') ';
            }

            $sqlItems = '
                SELECT DISTINCT
                    t0."ItemCode",
                    t0."ItemName"
                FROM "OITM" t0
                WHERE IFNULL(t0."AssetClass", \'\') <> \'\'
                ' . $whereBplItem . '
                ORDER BY t0."ItemCode" ASC
            ';

            $articulos = [];
            $rsItems   = @odbc_exec($conn, $sqlItems);
            if ($rsItems) {
                while ($row = odbc_fetch_array($rsItems)) {
                    $code = trim($this->toUtf8($row['ItemCode'] ?? ''));
                    $name = trim($this->toUtf8($row['ItemName'] ?? ''));
                    if ($code !== '') {
                        $articulos[] = [
                            'id'   => $code,
                            'text' => "{$code} - {$name}"
                        ];
                    }
                }
                odbc_free_result($rsItems);
            }

            // 2. Obtener Tipos de Mantenimiento (@MTTOCONFIGDET)
            $sqlTipos = '
                SELECT DISTINCT
                    a1."U_TipoMtto"
                FROM "@MTTOCONFIGDET" a1
                WHERE IFNULL(a1."U_TipoMtto", \'\') <> \'\'
                  AND (
                        IFNULL(a1."U_Kms", 0) BETWEEN 0.01 AND 99000
                     OR IFNULL(a1."U_Horas", 0) BETWEEN 0.01 AND 99000
                     OR IFNULL(a1."U_Semana", 0) <> 0
                  )
                ORDER BY a1."U_TipoMtto" ASC
            ';

            $tiposMtto = [];
            $rsTipos   = @odbc_exec($conn, $sqlTipos);
            if ($rsTipos) {
                while ($row = odbc_fetch_array($rsTipos)) {
                    $tipo = trim($this->toUtf8($row['U_TipoMtto'] ?? ''));
                    if ($tipo !== '') {
                        $tiposMtto[] = [
                            'id'   => $tipo,
                            'text' => $tipo
                        ];
                    }
                }
                odbc_free_result($rsTipos);
            }

            odbc_close($conn);

            return $this->response->setJSON([
                'error'      => false,
                'sucursales' => $sucursales,
                'articulos'  => $articulos,
                'tiposMtto'  => $tiposMtto,
                'token'      => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'      => true,
                'message'    => $e->getMessage(),
                'sucursales' => [],
                'articulos'  => [],
                'tiposMtto'  => [],
                'token'      => csrf_hash()
            ]);
        }
    }

    /**
     * Ejecuta el query de SAP HANA aplicando los filtros de:
     * - Estado (TODOS, VENCIDOS, POR VENCER, NO VENCIDOS)
     * - Sucursal (TODOS o BPLId específico)
     * - Número de Artículo (TODOS o ItemCode específico)
     * - Tipo MTTO (TODOS o U_TipoMtto específico)
     */
    public function getReportDataAjax()
    {
        try {
            $idUser    = (int) user()->id;
            $idEmpresa = (int) ($this->request->getPost('idEmpresa') ?? 1);
            $estado    = strtoupper(trim((string) ($this->request->getPost('estado') ?? 'TODOS')));
            $bplId     = trim((string) ($this->request->getPost('bplId') ?? 'TODOS'));
            $itemCode  = trim((string) ($this->request->getPost('itemCode') ?? 'TODOS'));
            $tipoMtto  = trim((string) ($this->request->getPost('tipoMtto') ?? 'TODOS'));

            $sucursales   = $this->getAllowedSAPBranches($idUser, $idEmpresa);
            $allowedBplIn = array_column($sucursales, 'bplId');

            $conn = $this->connectODBC($idEmpresa);

            // Construcción de condiciones dinámicas seguras
            $whereOuter = [];

            // 1. Filtro de Estado ([%0])
            if ($estado === 'VENCIDOS') {
                $whereOuter[] = '(C0."OdoEstatus" = \'VENCIDO\' OR C0."HoroEstatus" = \'VENCIDO\' OR C0."SemanasEstatus" = \'VENCIDO\')';
            } elseif ($estado === 'POR VENCER') {
                $whereOuter[] = '(C0."OroDif" BETWEEN 0.01 AND 50 OR C0."HoroDif" BETWEEN 0.01 AND 50)';
            } elseif ($estado === 'NO VENCIDOS') {
                $whereOuter[] = '(C0."OdoEstatus" <> \'VENCIDO\' AND C0."HoroEstatus" <> \'VENCIDO\' AND C0."SemanasEstatus" <> \'VENCIDO\')';
            }

            // 2. Filtro de Sucursal (BPLId)
            if ($bplId !== '' && $bplId !== 'TODOS' && is_numeric($bplId)) {
                $whereOuter[] = 'C0."BPLId" = ' . (int) $bplId;
            } elseif (!empty($allowedBplIn)) {
                $whereOuter[] = 'C0."BPLId" IN (' . implode(',', array_map('intval', $allowedBplIn)) . ')';
            }

            // 3. Filtro de Número de Artículo (ItemCode)
            if ($itemCode !== '' && $itemCode !== 'TODOS') {
                $itemClean    = str_replace("'", "''", $itemCode);
                $whereOuter[] = "C0.\"ItemCode\" = '{$itemClean}'";
            }

            // 4. Filtro de Tipo MTTO (U_TipoMtto)
            if ($tipoMtto !== '' && $tipoMtto !== 'TODOS') {
                $tipoClean    = str_replace("'", "''", $tipoMtto);
                $whereOuter[] = "C0.\"U_TipoMtto\" = '{$tipoClean}'";
            }

            $whereClause = !empty($whereOuter) ? ' WHERE ' . implode(' AND ', $whereOuter) : '';

            // Si el usuario tiene enlazado su usuario SAP en USR6, verificamos si aplica join USR6
            $sapUserId = $this->resolveLinkedSapUserId($conn, $idEmpresa, $idUser);
            $joinUsr6  = $sapUserId > 0
                ? " INNER JOIN \"USR6\" a5 ON a5.\"BPLId\" = a0.\"BPLId\" AND a5.\"UserID\" = {$sapUserId} "
                : "";

            $sql = "
                SELECT * FROM (
                    SELECT
                        a0.\"BPLId\",
                        a0.\"BPLName\",
                        a0.\"DocEntry\",
                        a4.\"ItemCode\",
                        a0.\"ItemName\",
                        a1.\"U_TipoMtto\",
                        a2.\"FechaHora\" AS \"FechaUltMtto\",
                        a2.\"DocNum\" AS \"FolioUltMtto\",
                        IFNULL(a1.\"U_Horas\", 0) AS \"HoroMtto\",
                        IFNULL(a2.\"Horometro\", 0) AS \"HrsUltMtto\",
                        IFNULL(a7.\"Horometro\", 0) AS \"HorometroActual\",
                        IFNULL(a3.\"SumHoro\", 0) AS \"HoroAcum\",
                        CASE WHEN IFNULL(a1.\"U_Horas\", 0) <> 0 THEN
                            (CASE WHEN IFNULL(TO_VARCHAR(a2.\"FechaHora\"), '') <> ''
                                  THEN IFNULL(a1.\"U_Horas\", 0) - IFNULL(a3.\"SumHoro\", 0)
                                  ELSE IFNULL(a1.\"U_Horas\", 0) - IFNULL(a7.\"HistHoro\", 0)
                             END)
                        ELSE 0 END AS \"HoroDif\",
                        CASE WHEN (
                            CASE WHEN IFNULL(a1.\"U_Horas\", 0) <> 0 THEN
                                (CASE WHEN IFNULL(TO_VARCHAR(a2.\"FechaHora\"), '') <> ''
                                      THEN IFNULL(a1.\"U_Horas\", 0) - IFNULL(a3.\"SumHoro\", 0)
                                      ELSE IFNULL(a1.\"U_Horas\", 0) - IFNULL(a7.\"HistHoro\", 0)
                                 END)
                            ELSE 0 END
                        ) < 0 THEN 'VENCIDO' ELSE '' END AS \"HoroEstatus\",
                        '' AS \"SepHoro\",
                        IFNULL(a1.\"U_Kms\", 0) AS \"OdoMtto\",
                        IFNULL(a2.\"Odometro\", 0) AS \"KmsUltMtto\",
                        IFNULL(a7.\"Odometro\", 0) AS \"OdometroActual\",
                        IFNULL(a3.\"SumOdo\", 0) AS \"OdoAcum\",
                        CASE WHEN IFNULL(a1.\"U_Kms\", 0) <> 0 THEN
                            (CASE WHEN IFNULL(TO_VARCHAR(a2.\"FechaHora\"), '') <> ''
                                  THEN IFNULL(a1.\"U_Kms\", 0) - IFNULL(a3.\"SumOdo\", 0)
                                  ELSE IFNULL(a1.\"U_Kms\", 0) - IFNULL(a7.\"HistOdo\", 0)
                             END)
                        ELSE 0 END AS \"OroDif\",
                        CASE WHEN (
                            CASE WHEN IFNULL(a1.\"U_Kms\", 0) <> 0 THEN
                                (CASE WHEN IFNULL(TO_VARCHAR(a2.\"FechaHora\"), '') <> ''
                                      THEN IFNULL(a1.\"U_Kms\", 0) - IFNULL(a3.\"SumOdo\", 0)
                                      ELSE IFNULL(a1.\"U_Kms\", 0) - IFNULL(a7.\"HistOdo\", 0)
                                 END)
                            ELSE 0 END
                        ) < 0 THEN 'VENCIDO' ELSE '' END AS \"OdoEstatus\",
                        '' AS \"SepOdo\",
                        IFNULL(a1.\"U_Semana\", 0) AS \"Semanas\",
                        CASE WHEN IFNULL(a1.\"U_Semana\", 0) <> 0
                              AND DAYS_BETWEEN(IFNULL(a2.\"FechaHora\", '20260101'), CURRENT_DATE) > IFNULL(a1.\"U_Semana\", 0)
                             THEN 'VENCIDO' ELSE '' END AS \"SemanasEstatus\"
                    FROM (
                        ----- BUSCA LA INFORMACION DE LOS MTTOS POR EQUIPOS
                        SELECT
                            t2.\"BPLId\",
                            t2.\"BPLName\",
                            t0.\"ItemCode\",
                            t0.\"ItemName\",
                            t1.\"DocEntry\",
                            t0.\"U_TipoEquipo\"
                        FROM \"OITM\" t0
                        INNER JOIN \"@MTTOCONFIG\" t1 ON t1.\"U_Code\" = t0.\"ItemCode\" AND t1.\"U_Tipo\" = 'Equipo'
                        INNER JOIN \"OBPL\" t2 ON t2.\"BPLId\" = t0.\"U_BPId\"
                        WHERE IFNULL(t0.\"AssetClass\", '') <> ''

                        UNION ALL

                        ----- BUSCA LA INFORMACION DE LOS MTTOS POR TIPO DE EQUIPO
                        SELECT
                            t3.\"BPLId\",
                            t3.\"BPLName\",
                            t0.\"ItemCode\",
                            t0.\"ItemName\",
                            t1.\"DocEntry\",
                            t0.\"U_TipoEquipo\"
                        FROM \"OITM\" t0
                        INNER JOIN \"@MTTOCONFIG\" t1 ON t1.\"U_Code\" = t0.\"U_TipoEquipo\" AND t1.\"U_Tipo\" = 'TipoMq'
                        LEFT JOIN \"@MTTOCONFIG\" t2 ON t2.\"U_Code\" = t0.\"ItemCode\"
                        INNER JOIN \"OBPL\" t3 ON t3.\"BPLId\" = t0.\"U_BPId\"
                        WHERE IFNULL(t0.\"AssetClass\", '') <> '' AND IFNULL(t2.\"U_Code\", '') = ''
                    ) a0
                    INNER JOIN \"@MTTOCONFIGDET\" a1
                        ON a0.\"DocEntry\" = a1.\"DocEntry\"
                       AND (
                            IFNULL(a1.\"U_Kms\", 0) BETWEEN 0.01 AND 99000
                         OR IFNULL(a1.\"U_Horas\", 0) BETWEEN 0.01 AND 99000
                         OR IFNULL(a1.\"U_Semana\", 0) <> 0
                       )
                    {$joinUsr6}
                    LEFT JOIN (
                        ---- OBTENER INFO ULTIMO MANTENIMIENTO
                        SELECT
                            t0.\"Code\" AS \"ItemCode\",
                            t0.\"Mtto\",
                            t0.\"FechaHora\",
                            t0.\"DocNum\",
                            t0.\"Horometro\",
                            t0.\"Odometro\"
                        FROM \"B1_GUSA_INFO_HORO_ODO\" t0
                        INNER JOIN (
                            SELECT MAX(\"Row\") AS \"Row\", \"Code\", \"Mtto\"
                            FROM \"B1_GUSA_INFO_HORO_ODO\"
                            WHERE \"Tipo\" IN ('T')
                            GROUP BY \"Code\", \"Mtto\"
                        ) t1 ON t0.\"Code\" = t1.\"Code\" AND t0.\"Row\" = t1.\"Row\"
                    ) a2 ON a2.\"ItemCode\" = a0.\"ItemCode\" AND IFNULL(a2.\"Mtto\", '') = a1.\"U_TipoMtto\"
                    LEFT JOIN (
                        ---- OBTENER ACUMULADO DE ULTIMO MANTENIMIENTO
                        SELECT
                            t0.\"Code\" AS \"ItemCode\",
                            t1.\"Mtto\",
                            SUM(\"OdoDiferencia\") AS \"SumOdo\",
                            SUM(\"HoroDiferencia\") AS \"SumHoro\"
                        FROM \"B1_GUSA_INFO_HORO_ODO\" t0
                        INNER JOIN (
                            SELECT MAX(\"Row\") AS \"Row\", \"Code\", \"Mtto\"
                            FROM \"B1_GUSA_INFO_HORO_ODO\"
                            WHERE \"Tipo\" IN ('T')
                            GROUP BY \"Code\", \"Mtto\"
                        ) t1 ON t0.\"Code\" = t1.\"Code\" AND t0.\"Row\" > t1.\"Row\"
                        GROUP BY t0.\"Code\", t1.\"Mtto\"
                    ) a3 ON a3.\"ItemCode\" = a0.\"ItemCode\" AND IFNULL(a3.\"Mtto\", '') = a1.\"U_TipoMtto\"
                    INNER JOIN \"OITM\" a4 ON a4.\"ItemCode\" = a0.\"ItemCode\"
                    LEFT JOIN (
                        ---- OBTENER LOS KMS Y HRS ACTUALES
                        SELECT
                            t0.\"Code\",
                            t0.\"FechaHora\",
                            t0.\"DocNum\",
                            t0.\"Horometro\",
                            t0.\"Odometro\",
                            t0.\"HistOdo\",
                            t0.\"HistHoro\"
                        FROM \"B1_GUSA_INFO_HORO_ODO\" t0
                        INNER JOIN (
                            SELECT MAX(\"Row\") AS \"Row\", \"Code\"
                            FROM \"B1_GUSA_INFO_HORO_ODO\"
                            WHERE \"Tipo\" IN ('M', 'T')
                            GROUP BY \"Code\"
                        ) t1 ON t0.\"Code\" = t1.\"Code\" AND t0.\"Row\" = t1.\"Row\"
                    ) a7 ON a7.\"Code\" = a0.\"ItemCode\"
                ) C0
                {$whereClause}
                ORDER BY C0.\"ItemCode\" ASC, C0.\"U_TipoMtto\" ASC
            ";

            $rs   = odbc_exec($conn, $sql);
            $data = [];

            $kpiTotal      = 0;
            $kpiVencidos   = 0;
            $kpiPorVencer  = 0;
            $kpiNoVencidos = 0;

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $horoDif = round((float) ($row['HoroDif'] ?? 0), 2);
                    $oroDif  = round((float) ($row['OroDif'] ?? 0), 2);

                    $horoEst = trim($this->toUtf8($row['HoroEstatus'] ?? ''));
                    $odoEst  = trim($this->toUtf8($row['OdoEstatus'] ?? ''));
                    $semEst  = trim($this->toUtf8($row['SemanasEstatus'] ?? ''));

                    $esVencido   = ($horoEst === 'VENCIDO' || $odoEst === 'VENCIDO' || $semEst === 'VENCIDO');
                    $esPorVencer = !$esVencido && (($horoDif >= 0.01 && $horoDif <= 50) || ($oroDif >= 0.01 && $oroDif <= 50));

                    $kpiTotal++;
                    if ($esVencido) {
                        $kpiVencidos++;
                        $estadoGeneral = 'VENCIDO';
                    } elseif ($esPorVencer) {
                        $kpiPorVencer++;
                        $estadoGeneral = 'POR VENCER';
                    } else {
                        $kpiNoVencidos++;
                        $estadoGeneral = 'NO VENCIDO';
                    }

                    $fechaRaw = trim($this->toUtf8($row['FechaUltMtto'] ?? ''));
                    $fechaFmt = $fechaRaw !== '' ? substr($fechaRaw, 0, 10) : '-';

                    $data[] = [
                        'BPLName'         => trim($this->toUtf8($row['BPLName'] ?? '')),
                        'DocEntry'        => (int) ($row['DocEntry'] ?? 0),
                        'ItemCode'        => trim($this->toUtf8($row['ItemCode'] ?? '')),
                        'ItemName'        => trim($this->toUtf8($row['ItemName'] ?? '')),
                        'U_TipoMtto'      => trim($this->toUtf8($row['U_TipoMtto'] ?? '')),
                        'FechaUltMtto'    => $fechaFmt,
                        'FolioUltMtto'    => $row['FolioUltMtto'] ?? '-',
                        'HoroMtto'        => round((float) ($row['HoroMtto'] ?? 0), 2),
                        'HrsUltMtto'      => round((float) ($row['HrsUltMtto'] ?? 0), 2),
                        'HorometroActual' => round((float) ($row['HorometroActual'] ?? 0), 2),
                        'HoroAcum'        => round((float) ($row['HoroAcum'] ?? 0), 2),
                        'HoroDif'         => $horoDif,
                        'HoroEstatus'     => $horoEst,
                        'OdoMtto'         => round((float) ($row['OdoMtto'] ?? 0), 2),
                        'KmsUltMtto'      => round((float) ($row['KmsUltMtto'] ?? 0), 2),
                        'OdometroActual'  => round((float) ($row['OdometroActual'] ?? 0), 2),
                        'OdoAcum'         => round((float) ($row['OdoAcum'] ?? 0), 2),
                        'OroDif'          => $oroDif,
                        'OdoEstatus'      => $odoEst,
                        'Semanas'         => round((float) ($row['Semanas'] ?? 0), 0),
                        'SemanasEstatus'  => $semEst,
                        'EstadoGeneral'   => $estadoGeneral,
                    ];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);

            return $this->response->setJSON([
                'error' => false,
                'data'  => $data,
                'kpis'  => [
                    'total'      => $kpiTotal,
                    'vencidos'   => $kpiVencidos,
                    'porVencer'  => $kpiPorVencer,
                    'noVencidos' => $kpiNoVencidos,
                ],
                'token' => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => [],
                'token'   => csrf_hash()
            ]);
        }
    }

    /**
     * Obtiene las sucursales de SAP (OBPL) a las que el usuario tiene acceso
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
                        'text'  => "{$id} - {$name}"
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
     * Si el usuario web está enlazado en `user_sap_link`, obtiene su INTERNAL_K (UserID en OUSR)
     * para respetar USR6 en caso de existir; si no, retorna 0 y filtra por sucursales web.
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