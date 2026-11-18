<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;

class TypemttoController extends BaseController
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
                0 => 'Code', // Columna de acciones
                1 => 'Code',
                2 => 'Name'
            ];

            $orderField = $columns[$orderColumnIndex] ?? 'Code';

            try {
                [$conn, $schema] = $this->getOdbcConnection();
                $table = '"' . $schema . '"."@TYPEMTTO"';

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
                                  OR UPPER("Name") LIKE UPPER(\'%' . $escaped . '%\')';
                }

                // 3) Total de registros filtrados
                $sqlFiltered = "SELECT COUNT(*) AS \"TOTAL\" FROM {$table}" . $where;
                $rsFiltered = odbc_exec($conn, $sqlFiltered);
                $rowFiltered = odbc_fetch_array($rsFiltered);
                $recordsFiltered = (int) ($rowFiltered['TOTAL'] ?? 0);
                odbc_free_result($rsFiltered);

                // 4) Consulta paginada
                $sqlData = 'SELECT "Code", "Name"
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

        $titulos["title"] = "Catálogo de Tipos de Mantenimiento";
        $titulos["subtitle"] = "Administración de @TYPEMTTO";

        return view('julio101290\boilerplateservicelayer\Views\typemtto', $titulos);
    }

    /**
     * Obtener un registro por su Code para edición
     */
    public function getTypemtto()
    {
        $code = (string) $this->request->getPost("Code");

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $escapedCode = str_replace("'", "''", $code);

            $sql = 'SELECT "Code", "Name"
                    FROM "' . $schema . '"."@TYPEMTTO"
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
     * Guardar (Alta) o Actualizar (Cambio) en @TYPEMTTO
     */
    public function save()
    {
        $userName = user()->username ?? 'admin';
        $datos = $this->request->getPost();

        $isEdit = (int) ($datos["isEdit"] ?? 0);
        $code = $isEdit === 1
            ? (string) ($datos["Code"] ?? '')
            : trim((string) ($datos["Code"] ?? ''));
        $name = trim((string) ($datos["Name"] ?? ''));

        if ($code === '' || $name === '') {
            return $this->respond(['status' => 400, 'message' => 'Los campos Code y Name son obligatorios.'], 400);
        }

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $table = '"' . $schema . '"."@TYPEMTTO"';

            $escCode = str_replace("'", "''", $code);
            $escName = str_replace("'", "''", $name);

            if ($isEdit === 0) {
                // Verificar que no exista el Code (insensible a mayúsculas/minúsculas)
                $rsCheck = odbc_exec($conn, "SELECT \"Code\" FROM {$table} WHERE UPPER(\"Code\") = UPPER('{$escCode}')");
                if (odbc_fetch_array($rsCheck)) {
                    odbc_free_result($rsCheck);
                    odbc_close($conn);
                    return $this->respond(['status' => 400, 'message' => "El tipo de mantenimiento '{$code}' ya existe."], 400);
                }
                odbc_free_result($rsCheck);

                $sqlInsert = "INSERT INTO {$table} (\"Code\", \"Name\") VALUES ('{$escCode}', '{$escName}')";
                $res = odbc_exec($conn, $sqlInsert);

                if (!$res) {
                    throw new \Exception('Error al insertar en SAP: ' . odbc_errormsg($conn));
                }

                odbc_close($conn);

                $this->log->save([
                    "description" => "Alta en @TYPEMTTO: " . json_encode($datos),
                    "user"        => $userName
                ]);

                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } else {
                // Actualizar Name del registro existente
                $sqlUpdate = "UPDATE {$table} SET \"Name\" = '{$escName}' WHERE \"Code\" = '{$escCode}'";
                $res = odbc_exec($conn, $sqlUpdate);

                if (!$res) {
                    throw new \Exception('Error al actualizar en SAP: ' . odbc_errormsg($conn));
                }

                odbc_close($conn);

                $this->log->save([
                    "description" => "Actualización en @TYPEMTTO: " . json_encode($datos),
                    "user"        => $userName
                ]);

                return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
            }
        } catch (\Throwable $ex) {
            return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
        }
    }

    /**
     * Verifica si el tipo de mantenimiento ya está en uso en configuraciones o historial
     */
    private function verificarDependencias($conn, string $schema, string $code, string $name): array
    {
        $escCode = str_replace("'", "''", $code);
        $escName = str_replace("'", "''", $name);

        // 1) Revisar tabla de detalle de configuración de mantenimientos (@MTTOCONFIGDET)
        // Ajusta el nombre de la tabla/columna si en tu esquema tiene otro nombre
        $sqlConfig = 'SELECT COUNT(*) AS "TOTAL"
                      FROM "' . $schema . '"."@MTTOCONFIGDET"
                      WHERE UPPER("U_TipoMtto") = UPPER(\'' . $escCode . '\')
                         OR UPPER("U_TipoMtto") = UPPER(\'' . $escName . '\')';
        $rsConfig = odbc_exec($conn, $sqlConfig);
        if (!$rsConfig) {
            throw new \Exception('Error al verificar @MTTOCONFIGDET: ' . odbc_errormsg($conn));
        }
        $rowConfig = odbc_fetch_array($rsConfig);
        $totalConfig = (int) ($rowConfig['TOTAL'] ?? 0);
        odbc_free_result($rsConfig);

        // 2) Revisar historial de equipos (@SIHISTORIAL_EQ)
        $sqlHist = 'SELECT COUNT(*) AS "TOTAL"
                    FROM "' . $schema . '"."@SIHISTORIAL_EQ"
                    WHERE UPPER("Name") = UPPER(\'' . $escCode . '\')
                       OR UPPER("Name") = UPPER(\'' . $escName . '\')';
        $rsHist = odbc_exec($conn, $sqlHist);
        if (!$rsHist) {
            throw new \Exception('Error al verificar @SIHISTORIAL_EQ: ' . odbc_errormsg($conn));
        }
        $rowHist = odbc_fetch_array($rsHist);
        $totalHist = (int) ($rowHist['TOTAL'] ?? 0);
        odbc_free_result($rsHist);

        return [
            'config'    => $totalConfig,
            'historial' => $totalHist,
        ];
    }

    /**
     * Eliminar registro (Baja) en @TYPEMTTO validando que no tenga configuraciones ni historial
     */
    public function delete($code = null)
    {
        $userName = user()->username ?? 'admin';
        $code = $code !== null ? urldecode((string) $code) : (string) $this->request->getPost('Code');

        if (trim($code) === '') {
            return $this->respond(['status' => 400, 'message' => 'Code inválido'], 400);
        }

        try {
            [$conn, $schema] = $this->getOdbcConnection();
            $table = '"' . $schema . '"."@TYPEMTTO"';
            $escCode = str_replace("'", "''", $code);

            // 1) Verificar que el registro exista y obtener su Name
            $rsReg = odbc_exec($conn, "SELECT \"Code\", \"Name\" FROM {$table} WHERE \"Code\" = '{$escCode}'");
            $reg = odbc_fetch_array($rsReg);
            odbc_free_result($rsReg);

            if (!$reg) {
                odbc_close($conn);
                return $this->respond(['status' => 404, 'message' => "No se encontró el registro '{$code}'."], 404);
            }

            $regUtf8 = $this->utf8ize($reg);
            $regName = (string) ($regUtf8['Name'] ?? $code);

            // 2) Validar que no tenga configuraciones de mantenimiento ni historial
            $deps = $this->verificarDependencias($conn, $schema, $code, $regName);

            if ($deps['config'] > 0 || $deps['historial'] > 0) {
                odbc_close($conn);

                $motivos = [];
                if ($deps['config'] > 0) {
                    $motivos[] = "{$deps['config']} configuración(es) de mantenimiento";
                }
                if ($deps['historial'] > 0) {
                    $motivos[] = "{$deps['historial']} registro(s) en historial de equipos";
                }

                return $this->respond([
                    'status'  => 409,
                    'message' => "No se puede eliminar '{$code}' porque ya tiene " . implode(' y ', $motivos) . "."
                ], 409);
            }

            // 3) Eliminar registro
            $sqlDelete = "DELETE FROM {$table} WHERE \"Code\" = '{$escCode}'";
            $res = odbc_exec($conn, $sqlDelete);

            if (!$res) {
                throw new \Exception('Error al eliminar en SAP: ' . odbc_errormsg($conn));
            }

            odbc_close($conn);

            $this->log->save([
                "description" => "Eliminado en @TYPEMTTO Code: " . $code,
                "user"        => $userName
            ]);

            return $this->respond([
                'status'  => 200,
                'Code'    => $code,
                'message' => 'Registro eliminado correctamente'
            ], 200);
        } catch (\Throwable $e) {
            return $this->respond([
                'status'  => 500,
                'message' => $e->getMessage()
            ], 500);
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