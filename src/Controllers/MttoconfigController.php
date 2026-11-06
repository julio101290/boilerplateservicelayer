<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;

class MttoconfigController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $serviceLayerModel;

    // Nombre por defecto de la tabla detalle en caso de no hallarse en UDO1
    protected $defaultDetailTable = '@MTTOCONFIG1';

    public function __construct()
    {
        $this->log = new LogModel();
        $this->serviceLayerModel = new SapservicelayerModel();

        helper(['menu', 'utilerias', 'auth']);
    }

    /**
     * Obtiene la conexión ODBC activa y establece el esquema de SAP HANA
     */
    private function getOdbcConnection()
    {
        $conectionData = $this->serviceLayerModel->select("*")->first();

        if (!$conectionData) {
            throw new \Exception('No se encontraron datos de conexión SAP.');
        }

        $conn = odbc_connect(
            $conectionData["nameODBC"],
            $conectionData["userODBC"],
            $conectionData["passwordODBC"]
        );

        if (!$conn) {
            throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
        }

        $schema = $conectionData["companyDB"];
        if (!odbc_exec($conn, 'SET SCHEMA "' . $schema . '"')) {
            throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
        }

        return [$conn, $schema];
    }

    /**
     * Detecta automáticamente el nombre de la tabla detalle del UDO 'ConfigMtto' en SAP
     */
    private function getDetailTableName($conn, $schema)
    {
        $sql = 'SELECT "TableName" FROM "' . $schema . '"."UDO1" WHERE "Code" = \'ConfigMtto\'';
        $rs = @odbc_exec($conn, $sql);
        if ($rs && ($row = odbc_fetch_array($rs))) {
            $tableName = trim($row['TableName']);
            odbc_free_result($rs);
            if ($tableName !== '') {
                return '@' . ltrim($tableName, '@');
            }
        }
        if ($rs) {
            odbc_free_result($rs);
        }
        return $this->defaultDetailTable;
    }

    /**
     * Vista principal y listado Server-Side para DataTables
     */
    public function index()
    {
        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');
            $searchValue = trim($request->getGet('search')['value'] ?? '');
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 1);
            $orderDir = strtoupper($request->getGet('order')[0]['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

            $columns = [
                0 => 'DocEntry',
                1 => 'DocEntry',
                2 => 'U_Tipo',
                3 => 'U_Code',
                4 => 'U_Name',
                5 => 'CreateDate'
            ];

            $orderField = $columns[$orderColumnIndex] ?? 'DocEntry';

            try {
                [$conn, $schema] = $this->getOdbcConnection();
                $tableHeader = '"' . $schema . '"."@MTTOCONFIG"';
                $detailName  = $this->getDetailTableName($conn, $schema);
                $tableDetail = '"' . $schema . '"."' . $detailName . '"';

                // 1) Total de registros sin filtro
                $sqlTotal = "SELECT COUNT(*) AS \"TOTAL\" FROM {$tableHeader}";
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $rowTotal = odbc_fetch_array($rsTotal);
                $recordsTotal = (int) ($rowTotal['TOTAL'] ?? 0);
                odbc_free_result($rsTotal);

                // 2) Filtro de búsqueda
                $where = "";
                if ($searchValue !== '') {
                    $escaped = str_replace("'", "''", $searchValue);
                    $where = ' WHERE CAST(T0."DocEntry" AS VARCHAR) LIKE \'%' . $escaped . '%\'
                                  OR UPPER(T0."U_Tipo") LIKE UPPER(\'%' . $escaped . '%\')
                                  OR UPPER(T0."U_Code") LIKE UPPER(\'%' . $escaped . '%\')
                                  OR UPPER(T0."U_Name") LIKE UPPER(\'%' . $escaped . '%\')';
                }

                // 3) Total de registros filtrados
                $sqlFiltered = "SELECT COUNT(*) AS \"TOTAL\" FROM {$tableHeader} T0" . $where;
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $rowFiltered = odbc_fetch_array($rsFiltered);
                $recordsFiltered = (int) ($rowFiltered['TOTAL'] ?? 0);
                odbc_free_result($rsFiltered);

                // 4) Consulta paginada incluyendo conteo de servicios configurados
                $sqlData = 'SELECT T0."DocEntry",
                                   T0."U_Tipo",
                                   T0."U_Code",
                                   T0."U_Name",
                                   TO_VARCHAR(T0."CreateDate", \'YYYY-MM-DD\') AS "CreateDate",
                                   (SELECT COUNT(*) FROM ' . $tableDetail . ' D WHERE D."DocEntry" = T0."DocEntry") AS "TotalServicios"
                            FROM ' . $tableHeader . ' T0' . $where . '
                            ORDER BY T0."' . $orderField . '" ' . $orderDir . '
                            LIMIT ' . $length . ' OFFSET ' . $start;

                $rsData = odbc_exec($conn, $sqlData);
                $data = [];

                while ($row = odbc_fetch_array($rsData)) {
                    $data[] = $this->utf8ize($row);
                }

                odbc_free_result($rsData);
                odbc_close($conn);

                return $this->response->setJSON([
                    'draw'            => $draw,
                    'recordsTotal'    => $recordsTotal,
                    'recordsFiltered' => $recordsFiltered,
                    'data'            => $data,
                ]);
            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'draw'            => $draw,
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => $e->getMessage()
                ]);
            }
        }

        $titulos["title"]    = "Configuración de Mantenimiento";
        $titulos["subtitle"] = "Administración de Planes por Equipo y Tipo (@MTTOCONFIG)";

        return view('julio101290\boilerplateservicelayer\Views\mttoconfig', $titulos);
    }

    /**
     * Obtiene los códigos disponibles según U_Tipo ('Equipo' o 'TipoMq')
     * Si se envía currentCode (en modo edición), lo incluye aunque ya esté en @MTTOCONFIG
     */
    public function getCodigosDisponibles()
    {
        $tipo = trim((string) $this->request->getPost('tipo'));
        $currentCode = trim((string) $this->request->getPost('currentCode'));
        $escCurrent = str_replace("'", "''", $currentCode);

        try {
            [$conn, $schema] = $this->getOdbcConnection();

            $condCurrent = $currentCode !== '' ? " OR T1.\"U_Code\" = '{$escCurrent}'" : "";

            if ($tipo === 'Equipo') {
                $sql = 'SELECT T0."ItemCode" AS "Code", T0."ItemName" AS "Name"
                        FROM "' . $schema . '"."OITM" T0
                        LEFT JOIN "' . $schema . '"."@MTTOCONFIG" T1 ON T1."U_Code" = T0."ItemCode"
                        WHERE IFNULL(T0."AssetClass", \'\') <> \'\'
                          AND (T1."U_Code" IS NULL' . $condCurrent . ')
                        ORDER BY T0."ItemCode" ASC';
            } else {
                $sql = 'SELECT T0."Code", T0."Name"
                        FROM "' . $schema . '"."@TIPOEQUIPO" T0
                        LEFT JOIN "' . $schema . '"."@MTTOCONFIG" T1 ON T1."U_Code" = T0."Code"
                        WHERE (T1."U_Code" IS NULL' . $condCurrent . ')
                        ORDER BY T0."Code" ASC';
            }

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL al cargar códigos: ' . odbc_errormsg($conn));
            }

            $lista = [];
            while ($row = odbc_fetch_array($rs)) {
                $lista[] = $this->utf8ize($row);
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON($lista);
        } catch (\Throwable $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene el catálogo de Tipos de Mantenimiento desde @TYPEMTTO
     */
    public function getTiposMtto()
    {
        try {
            [$conn, $schema] = $this->getOdbcConnection();

            $sql = 'SELECT T0."Code" FROM "' . $schema . '"."@TYPEMTTO" T0 ORDER BY T0."Code" ASC';
            $rs = odbc_exec($conn, $sql);

            if (!$rs) {
                throw new \Exception('Error SQL @TYPEMTTO: ' . odbc_errormsg($conn));
            }

            $tipos = [];
            while ($row = odbc_fetch_array($rs)) {
                $rowUtf8 = $this->utf8ize($row);
                $tipos[] = $rowUtf8['Code'];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON($tipos);
        } catch (\Throwable $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener encabezado (@MTTOCONFIG) y líneas de detalle por DocEntry
     */
    public function getMttoconfig()
    {
        $docEntry = (int) $this->request->getPost("DocEntry");

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $tableHeader = '"' . $schema . '"."@MTTOCONFIG"';
            $detailName  = $this->getDetailTableName($conn, $schema);
            $tableDetail = '"' . $schema . '"."' . $detailName . '"';

            // 1) Encabezado
            $sqlHeader = "SELECT \"DocEntry\", \"U_Tipo\", \"U_Code\", \"U_Name\"
                          FROM {$tableHeader}
                          WHERE \"DocEntry\" = {$docEntry}";

            $rsH = odbc_exec($conn, $sqlHeader);
            if (!$rsH) {
                throw new \Exception('Error SQL Encabezado: ' . odbc_errormsg($conn));
            }

            $header = odbc_fetch_array($rsH);
            odbc_free_result($rsH);

            if (!$header) {
                odbc_close($conn);
                return $this->respond(['status' => 404, 'message' => 'Registro no encontrado'], 404);
            }

            // 2) Detalle
            $sqlDetail = "SELECT \"LineId\", \"VisOrder\", \"U_TipoMtto\", \"U_Periodi\",
                                 \"U_Kms\", \"U_Horas\", \"U_Semana\"
                          FROM {$tableDetail}
                          WHERE \"DocEntry\" = {$docEntry}
                          ORDER BY \"VisOrder\" ASC, \"LineId\" ASC";

            $rsD = odbc_exec($conn, $sqlDetail);
            $details = [];
            if ($rsD) {
                while ($row = odbc_fetch_array($rsD)) {
                    $details[] = $this->utf8ize($row);
                }
                odbc_free_result($rsD);
            }

            odbc_close($conn);

            return $this->response->setJSON([
                'header'  => $this->utf8ize($header),
                'details' => $details
            ]);
        } catch (\Throwable $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Guardar (Alta) o Actualizar (Cambio) Maestro-Detalle en @MTTOCONFIG
     */
    public function save()
    {
        $userName = user()->username ?? 'manager';
        $datos    = $this->request->getPost();

        $isEdit   = (int) ($datos["isEdit"] ?? 0);
        $docEntry = (int) ($datos["DocEntry"] ?? 0);
        $uTipo    = trim((string) ($datos["U_Tipo"] ?? ''));
        $uCode    = trim((string) ($datos["U_Code"] ?? ''));
        $uName    = trim((string) ($datos["U_Name"] ?? ''));
        $details  = json_decode($datos["details"] ?? '[]', true);

        if ($uTipo === '' || $uCode === '') {
            return $this->respond(['status' => 400, 'message' => 'El Tipo y el Código Tipo son obligatorios.'], 400);
        }

        if (empty($details) || !is_array($details)) {
            return $this->respond(['status' => 400, 'message' => 'Debes agregar al menos una línea de mantenimiento en el detalle.'], 400);
        }

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $tableHeader = '"' . $schema . '"."@MTTOCONFIG"';
            $detailName  = $this->getDetailTableName($conn, $schema);
            $tableDetail = '"' . $schema . '"."' . $detailName . '"';

            $escTipo    = str_replace("'", "''", $uTipo);
            $escCode    = str_replace("'", "''", $uCode);
            $escName    = str_replace("'", "''", $uName);
            $escCreator = str_replace("'", "''", substr($userName, 0, 15));
            $nowTime    = (int) date('Hi'); // Formato HHMM de SAP B1 (ej. 845 o 1430)

            if ($isEdit === 0) {
                // Validar que no exista ya una configuración para este U_Code
                $sqlCheck = "SELECT \"DocEntry\" FROM {$tableHeader} WHERE \"U_Code\" = '{$escCode}'";
                $rsCheck  = odbc_exec($conn, $sqlCheck);
                if ($rsCheck && odbc_fetch_array($rsCheck)) {
                    odbc_free_result($rsCheck);
                    odbc_close($conn);
                    return $this->respond(['status' => 400, 'message' => "El código '{$uCode}' ya tiene una configuración registrada."], 400);
                }
                if ($rsCheck) {
                    odbc_free_result($rsCheck);
                }

                // Obtener el siguiente DocEntry
                $sqlMax = "SELECT COALESCE(MAX(\"DocEntry\"), 0) + 1 AS \"NextEntry\" FROM {$tableHeader}";
                $rsMax  = odbc_exec($conn, $sqlMax);
                $rowMax = odbc_fetch_array($rsMax);
                $docEntry = (int) ($rowMax['NextEntry'] ?? 1);
                odbc_free_result($rsMax);

                // Insertar Encabezado UDO (@MTTOCONFIG)
                $sqlInsertHeader = "INSERT INTO {$tableHeader}
                    (\"DocEntry\", \"DocNum\", \"Period\", \"Instance\", \"Series\", \"Handwrtten\", \"Canceled\",
                     \"Object\", \"LogInst\", \"UserSign\", \"Transfered\", \"Status\",
                     \"CreateDate\", \"CreateTime\", \"UpdateDate\", \"UpdateTime\", \"DataSource\",
                     \"RequestStatus\", \"Creator\", \"Remark\", \"U_Tipo\", \"U_Code\", \"U_Name\")
                    VALUES
                    ({$docEntry}, {$docEntry}, 4, 0, -1, 'N', 'N',
                     'ConfigMtto', NULL, 1, 'N', 'O',
                     CURRENT_DATE, {$nowTime}, CURRENT_DATE, {$nowTime}, 'O',
                     'W', '{$escCreator}', NULL, '{$escTipo}', '{$escCode}', '{$escName}')";

                $resH = odbc_exec($conn, $sqlInsertHeader);
                if (!$resH) {
                    throw new \Exception('Error al insertar encabezado en SAP: ' . odbc_errormsg($conn));
                }
            } else {
                if ($docEntry <= 0) {
                    throw new \Exception('DocEntry inválido para edición.');
                }

                // Actualizar Encabezado
                $sqlUpdateHeader = "UPDATE {$tableHeader} SET
                    \"U_Tipo\" = '{$escTipo}',
                    \"U_Code\" = '{$escCode}',
                    \"U_Name\" = '{$escName}',
                    \"UpdateDate\" = CURRENT_DATE,
                    \"UpdateTime\" = {$nowTime}
                    WHERE \"DocEntry\" = {$docEntry}";

                $resH = odbc_exec($conn, $sqlUpdateHeader);
                if (!$resH) {
                    throw new \Exception('Error al actualizar encabezado en SAP: ' . odbc_errormsg($conn));
                }

                // Limpiar líneas anteriores para reinsertar el detalle actualizado
                $sqlDelLines = "DELETE FROM {$tableDetail} WHERE \"DocEntry\" = {$docEntry}";
                odbc_exec($conn, $sqlDelLines);
            }

            // Insertar Líneas de Detalle
            $lineId   = 1;
            $visOrder = 0;

            foreach ($details as $item) {
                $tipoMtto = trim((string) ($item['U_TipoMtto'] ?? ''));
                if ($tipoMtto === '') {
                    continue;
                }

                $periodi = trim((string) ($item['U_Periodi'] ?? ''));
                $kms     = (float) str_replace(',', '', $item['U_Kms'] ?: 0);
                $horas   = (float) str_replace(',', '', $item['U_Horas'] ?: 0);
                $semana  = trim((string) ($item['U_Semana'] ?? ''));

                $escTipoMtto = str_replace("'", "''", $tipoMtto);
                $valPeriodi  = $periodi !== '' ? "'" . str_replace("'", "''", $periodi) . "'" : "NULL";
                $valSemana   = $semana !== '' ? "'" . str_replace("'", "''", $semana) . "'" : "NULL";

                $sqlInsertLine = "INSERT INTO {$tableDetail}
                    (\"DocEntry\", \"LineId\", \"VisOrder\", \"Object\", \"LogInst\",
                     \"U_TipoMtto\", \"U_Periodi\", \"U_Kms\", \"U_Horas\", \"U_Semana\")
                    VALUES
                    ({$docEntry}, {$lineId}, {$visOrder}, 'ConfigMtto', NULL,
                     '{$escTipoMtto}', {$valPeriodi}, {$kms}, {$horas}, {$valSemana})";

                $resL = odbc_exec($conn, $sqlInsertLine);
                if (!$resL) {
                    throw new \Exception("Error al insertar línea {$lineId} ({$tipoMtto}): " . odbc_errormsg($conn));
                }

                $lineId++;
                $visOrder++;
            }

            // Actualizar numeración en ONNM si aplica para el objeto ConfigMtto
            @odbc_exec($conn, 'UPDATE "' . $schema . '"."ONNM" SET "AutoKey" = ' . ($docEntry + 1) . ' WHERE "ObjectCode" = \'ConfigMtto\' AND "AutoKey" <= ' . $docEntry);

            odbc_close($conn);

            $accion = $isEdit === 0 ? "Guardado" : "Actualizado";
            $this->log->save([
                "description" => "{$accion} en @MTTOCONFIG DocEntry {$docEntry} ({$uCode})",
                "user"        => $userName
            ]);

            return $this->respond([
                'status'   => $isEdit === 0 ? 201 : 200,
                'message'  => "{$accion} correctamente (DocEntry #{$docEntry})"
            ], $isEdit === 0 ? 201 : 200);

        } catch (\Throwable $ex) {
            return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar configuración completa (Detalle + Encabezado)
     */
    public function delete($docEntry = null)
    {
        $userName = user()->username ?? 'admin';
        $docEntry = (int) $docEntry;

        if ($docEntry <= 0) {
            return $this->respond(['status' => 400, 'message' => 'DocEntry inválido'], 400);
        }

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $tableHeader = '"' . $schema . '"."@MTTOCONFIG"';
            $detailName  = $this->getDetailTableName($conn, $schema);
            $tableDetail = '"' . $schema . '"."' . $detailName . '"';

            // 1) Eliminar líneas del detalle
            $sqlDeleteLines = "DELETE FROM {$tableDetail} WHERE \"DocEntry\" = {$docEntry}";
            odbc_exec($conn, $sqlDeleteLines);

            // 2) Eliminar encabezado
            $sqlDeleteHeader = "DELETE FROM {$tableHeader} WHERE \"DocEntry\" = {$docEntry}";
            $res = odbc_exec($conn, $sqlDeleteHeader);

            if (!$res) {
                throw new \Exception('Error al eliminar en SAP: ' . odbc_errormsg($conn));
            }

            odbc_close($conn);

            $this->log->save([
                "description" => "Eliminado en @MTTOCONFIG DocEntry: " . $docEntry,
                "user"        => $userName
            ]);

            return $this->respondDeleted(['DocEntry' => $docEntry], 'Configuración eliminada correctamente');
        } catch (\Throwable $e) {
            return $this->respond(['status' => 500, 'messages' => ['error' => $e->getMessage()]], 500);
        }
    }

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'ISO-8859-1');
        }
        return $mixed;
    }
}