<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;

class Sihistorial_eqController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $serviceLayerModel;

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
                0 => 'Code', // Columna de acciones (usa Code por defecto)
                1 => 'Code',
                2 => 'Name',
                3 => 'U_ItemCode',
                4 => 'U_Fecha',
                5 => 'U_TimeUltReg',
                6 => 'U_OdoUltReg',
                7 => 'U_HoroUltReg',
                8 => 'U_OdoHist',
                9 => 'U_HoroHist'
            ];

            $orderField = $columns[$orderColumnIndex] ?? 'Code';

            try {
                [$conn, $schema] = $this->getOdbcConnection();
                $table = '"' . $schema . '"."@SIHISTORIAL_EQ"';

                // 1) Total de registros sin filtro
                $sqlTotal = "SELECT COUNT(*) AS \"TOTAL\" FROM {$table}";
                $rsTotal = odbc_exec($conn, $sqlTotal);
                $rowTotal = odbc_fetch_array($rsTotal);
                $recordsTotal = (int) ($rowTotal['TOTAL'] ?? 0);
                odbc_free_result($rsTotal);

                // 2) Filtro de búsqueda
                $where = "";
                if ($searchValue !== '') {
                    $escaped = str_replace("'", "''", $searchValue);
                    $where = ' WHERE UPPER("Code") LIKE UPPER(\'%' . $escaped . '%\')
                                  OR UPPER("Name") LIKE UPPER(\'%' . $escaped . '%\')
                                  OR UPPER("U_ItemCode") LIKE UPPER(\'%' . $escaped . '%\')';
                }

                // 3) Total de registros filtrados
                $sqlFiltered = "SELECT COUNT(*) AS \"TOTAL\" FROM {$table}" . $where;
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $rowFiltered = odbc_fetch_array($rsFiltered);
                $recordsFiltered = (int) ($rowFiltered['TOTAL'] ?? 0);
                odbc_free_result($rsFiltered);

                // 4) Consulta paginada
                $sqlData = 'SELECT "Code", "Name", "U_ItemCode", TO_VARCHAR("U_Fecha", \'YYYY-MM-DD\') AS "U_Fecha", 
                                   "U_TimeUltReg", "U_OdoUltReg", "U_HoroUltReg", "U_OdoHist", "U_HoroHist"
                            FROM ' . $table . $where . '
                            ORDER BY "' . $orderField . '" ' . $orderDir . '
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

        $titulos["title"] = "Historial de Equipos SAP";
        $titulos["subtitle"] = "Administración de @SIHISTORIAL_EQ";

        return view('julio101290\boilerplateservicelayer\Views\sihistorial_eq', $titulos);
    }

    /**
     * Obtener un registro por su Code para edición
     */
    public function getSihistorial_eq()
    {
        $code = trim((string) $this->request->getPost("Code"));

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $escapedCode = str_replace("'", "''", $code);

            $sql = 'SELECT "Code", "Name", "U_ItemCode", TO_VARCHAR("U_Fecha", \'YYYY-MM-DD\') AS "U_Fecha",
                           "U_TimeUltReg", "U_OdoUltReg", "U_HoroUltReg", "U_OdoHist", "U_HoroHist"
                    FROM "' . $schema . '"."@SIHISTORIAL_EQ"
                    WHERE "Code" = \'' . $escapedCode . '\'';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $dato = odbc_fetch_array($rs);
            odbc_free_result($rs);
            odbc_close($conn);

            if (!$dato) {
                return $this->response->setJSON([]);
            }

            return $this->response->setJSON($this->utf8ize($dato));
        } catch (\Throwable $e) {
            return $this->respond(['status' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Guardar (Alta) o Actualizar (Cambio) en @SIHISTORIAL_EQ
     */
    public function save()
    {
        $userName = user()->username ?? 'admin';
        $datos = $this->request->getPost();

        $isEdit = (int) ($datos["isEdit"] ?? 0);
        $code = trim((string) ($datos["Code"] ?? ''));
        $name = trim((string) ($datos["Name"] ?? ''));
        $uItemCode = trim((string) ($datos["U_ItemCode"] ?? ''));
        $uFecha = trim((string) ($datos["U_Fecha"] ?? ''));

        // Limpiar comas en valores numéricos
        $uTimeUltReg = (float) str_replace(',', '', $datos["U_TimeUltReg"] ?: 0);
        $uOdoUltReg  = (float) str_replace(',', '', $datos["U_OdoUltReg"] ?: 0);
        $uHoroUltReg = (float) str_replace(',', '', $datos["U_HoroUltReg"] ?: 0);
        $uOdoHist    = (float) str_replace(',', '', $datos["U_OdoHist"] ?: 0);
        $uHoroHist   = (float) str_replace(',', '', $datos["U_HoroHist"] ?: 0);

        if ($name === '' || $uItemCode === '' || $uFecha === '') {
            return $this->respond(['status' => 400, 'message' => 'Los campos Name, U_ItemCode y U_Fecha son obligatorios.'], 400);
        }

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $table = '"' . $schema . '"."@SIHISTORIAL_EQ"';

            // Escapar strings para SQL
            $escName = str_replace("'", "''", $name);
            $escItem = str_replace("'", "''", $uItemCode);
            $escFecha = str_replace("'", "''", $uFecha);

            if ($isEdit === 0) {
                // Si Code viene vacío en un registro nuevo, calculamos el siguiente consecutivo
                if ($code === '') {
                    $sqlMax = 'SELECT COALESCE(MAX(CAST("Code" AS INTEGER)), 0) + 1 AS "NextCode" 
                               FROM ' . $table . ' 
                               WHERE LENGTH(LTRIM("Code", \'+-.0123456789\')) = 0';
                    $rsMax = odbc_exec($conn, $sqlMax);
                    $rowMax = odbc_fetch_array($rsMax);
                    $code = (string) ($rowMax['NextCode'] ?? 1);
                    odbc_free_result($rsMax);
                }

                $escCode = str_replace("'", "''", $code);

                // Verificar que no exista el Code
                $rsCheck = odbc_exec($conn, "SELECT \"Code\" FROM {$table} WHERE \"Code\" = '{$escCode}'");
                if (odbc_fetch_array($rsCheck)) {
                    odbc_free_result($rsCheck);
                    odbc_close($conn);
                    return $this->respond(['status' => 400, 'message' => "El Code '{$code}' ya existe."], 400);
                }
                odbc_free_result($rsCheck);

                $sqlInsert = "INSERT INTO {$table} 
                    (\"Code\", \"Name\", \"U_ItemCode\", \"U_Fecha\", \"U_TimeUltReg\", \"U_OdoUltReg\", \"U_HoroUltReg\", \"U_OdoHist\", \"U_HoroHist\")
                    VALUES 
                    ('{$escCode}', '{$escName}', '{$escItem}', '{$escFecha}', {$uTimeUltReg}, {$uOdoUltReg}, {$uHoroUltReg}, {$uOdoHist}, {$uHoroHist})";

                $res = odbc_exec($conn, $sqlInsert);
                if (!$res) {
                    throw new \Exception('Error al insertar en SAP: ' . odbc_errormsg($conn));
                }

                odbc_close($conn);

                $this->log->save([
                    "description" => "Alta en @SIHISTORIAL_EQ: " . json_encode($datos),
                    "user"        => $userName
                ]);

                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } else {
                // Actualizar registro existente
                $escCode = str_replace("'", "''", $code);

                $sqlUpdate = "UPDATE {$table} SET
                    \"Name\" = '{$escName}',
                    \"U_ItemCode\" = '{$escItem}',
                    \"U_Fecha\" = '{$escFecha}',
                    \"U_TimeUltReg\" = {$uTimeUltReg},
                    \"U_OdoUltReg\" = {$uOdoUltReg},
                    \"U_HoroUltReg\" = {$uHoroUltReg},
                    \"U_OdoHist\" = {$uOdoHist},
                    \"U_HoroHist\" = {$uHoroHist}
                    WHERE \"Code\" = '{$escCode}'";

                $res = odbc_exec($conn, $sqlUpdate);
                if (!$res) {
                    throw new \Exception('Error al actualizar en SAP: ' . odbc_errormsg($conn));
                }

                odbc_close($conn);

                $this->log->save([
                    "description" => "Actualización en @SIHISTORIAL_EQ: " . json_encode($datos),
                    "user"        => $userName
                ]);

                return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
            }
        } catch (\Throwable $ex) {
            return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar registro (Baja) en @SIHISTORIAL_EQ
     */
    public function delete($code = null)
    {
        $userName = user()->username ?? 'admin';

        if ($code === null || trim($code) === '') {
            return $this->respond(['status' => 400, 'message' => 'Code inválido'], 400);
        }

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $table = '"' . $schema . '"."@SIHISTORIAL_EQ"';
            $escCode = str_replace("'", "''", trim($code));

            $sqlDelete = "DELETE FROM {$table} WHERE \"Code\" = '{$escCode}'";
            $res = odbc_exec($conn, $sqlDelete);

            if (!$res) {
                throw new \Exception('Error al eliminar en SAP: ' . odbc_errormsg($conn));
            }

            odbc_close($conn);

            $this->log->save([
                "description" => "Eliminado en @SIHISTORIAL_EQ Code: " . $code,
                "user"        => $userName
            ]);

            return $this->respondDeleted(['Code' => $code], 'Registro eliminado correctamente');
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