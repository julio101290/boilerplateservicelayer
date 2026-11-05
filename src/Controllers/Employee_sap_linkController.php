<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplateservicelayer\Models\Employee_sap_linkModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplate\Models\UserModel;
use julio101290\boilerplatecompanies\Models\UsuariosempresaModel;

class Employee_sap_linkController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $employee_sap_link;
    protected $empresa;
    protected $serviceLayerController;
    protected $serviceLayerModel;
    protected $users;
    protected $usersPerCompanie;

    public function __construct() {
        $this->employee_sap_link = new Employee_sap_linkModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->users = new UserModel();
        $this->usersPerCompanie = new UsuariosempresaModel();

        helper(['menu', 'utilerias']);
    }

    public function index() {
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw = (int) $request->getGet('draw');
            $start = (int) $request->getGet('start');
            $length = (int) $request->getGet('length');
            $searchValue = $request->getGet('search')['value'] ?? '';
            $orderColumnIndex = (int) $request->getGet('order')[0]['column'] ?? 0;
            $orderDir = $request->getGet('order')[0]['dir'] ?? 'asc';

            $fields = $this->employee_sap_link->allowedFields;
            $orderField = $fields[$orderColumnIndex] ?? 'id';

            $builder = $this->employee_sap_link->mdlGetEmployee_sap_link($empresasID);

            $total = clone $builder;
            $recordsTotal = $total->countAllResults(false);

            if (!empty($searchValue)) {
                $builder->groupStart();
                foreach ($fields as $field) {
                    $builder->orLike("a." . $field, $searchValue);
                }
                $builder->orLike("c.username", $searchValue);
                $builder->orLike("b.nombre", $searchValue);
                $builder->groupEnd();
            }

            $filteredBuilder = clone $builder;
            $recordsFiltered = $filteredBuilder->countAllResults(false);

            $data = $builder->orderBy("a." . $orderField, $orderDir)
                    ->get($length, $start)
                    ->getResultArray();

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }

        $titulos["title"] = lang('employee_sap_link.title');
        $titulos["subtitle"] = lang('employee_sap_link.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\employee_sap_link', $titulos);
    }

    public function getEmployee_sap_link() {
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $idEmployee_sap_link = $this->request->getPost("idEmployee_sap_link");
        $dato = $this->employee_sap_link->whereIn('idEmpresa', $empresasID)
                ->where('id', $idEmployee_sap_link)
                ->first();

        if (!$dato) {
            return $this->response->setJSON([]);
        }

        $companie = $this->empresa->where("id", $dato["idEmpresa"])->first();
        $usuarioAsignado = $this->users->find($dato["iduser"]);

        if ($usuarioAsignado) {
            $uName = is_object($usuarioAsignado) ? $usuarioAsignado->username : ($usuarioAsignado["username"] ?? '');
            $fName = is_object($usuarioAsignado) ? $usuarioAsignado->firstname : ($usuarioAsignado["firstname"] ?? '');
            $lName = is_object($usuarioAsignado) ? $usuarioAsignado->lastname : ($usuarioAsignado["lastname"] ?? '');
            $dato["username"] = trim($uName . " " . $fName . " " . $lName);
        } else {
            $dato["username"] = "";
        }

        $dato["nameCompanie"] = $companie["nombre"] ?? "";

        return $this->response->setJSON($dato);
    }

    public function save() {
        helper('auth');

        $userName = user()->username;
        $datos = $this->request->getPost();
        $idKey = $datos["idEmployee_sap_link"] ?? 0;

        if ($idKey == 0) {
            try {
                if (!$this->employee_sap_link->save($datos)) {
                    $errores = implode(" ", $this->employee_sap_link->errors());
                    return $this->respond(['status' => 400, 'message' => $errores], 400);
                }
                $this->log->save([
                    "description" => lang("employee_sap_link.logDescription") . json_encode($datos),
                    "user"        => $userName
                ]);
                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } catch (\Throwable $ex) {
                return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
            }
        } else {
            if (!$this->employee_sap_link->update($idKey, $datos)) {
                $errores = implode(" ", $this->employee_sap_link->errors());
                return $this->respond(['status' => 400, 'message' => $errores], 400);
            }
            $this->log->save([
                "description" => lang("employee_sap_link.logUpdated") . json_encode($datos),
                "user"        => $userName
            ]);
            return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
        }
    }

    public function delete($id) {
        helper('auth');

        $userName = user()->username;
        $registro = $this->employee_sap_link->find($id);

        if (!$this->employee_sap_link->delete($id)) {
            return $this->respond(['status' => 404, 'message' => lang("employee_sap_link.msg.msg_get_fail")], 404);
        }

        $this->employee_sap_link->purgeDeleted();
        $this->log->save([
            "description" => lang("employee_sap_link.logDeleted") . json_encode($registro),
            "user"        => $userName
        ]);

        return $this->respondDeleted($registro, lang("employee_sap_link.msg_delete"));
    }

    /**
     * Consulta de vendedores vía Service Layer (SalesPersons)
     */
    public function showEmployes($cookie, $search, $port) {
        $curl = curl_init();

        $baseUrl = "https://192.168.0.190:50000/b1s/v1/SalesPersons";
        $selectParam = '$select=SlpCode,SlpName&$top=15';

        $search = trim((string) $search);
        $parts = [];

        if ($search !== '' && ctype_digit($search)) {
            $parts[] = "SlpCode eq {$search}";
        }

        $escaped = str_replace("'", "''", $search);

        if ($escaped !== '') {
            $parts[] = "contains(SlpName,'{$escaped}')";
        }

        $filterValue = '';
        if (!empty($parts)) {
            $filterValue = implode(' or ', $parts);
        }

        if ($filterValue !== '') {
            $url = $baseUrl . '?' . $selectParam . '&$filter=' . rawurlencode($filterValue);
        } else {
            $url = $baseUrl . '?' . $selectParam;
        }

        curl_setopt_array($curl, [
            CURLOPT_PORT           => $port,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_COOKIE         => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => [
                "Accept: application/json",
                "User-Agent: PHP cURL",
                "B1S-CaseInsensitive: true"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            return "cURL Error #: " . $err;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'httpCode' => $httpCode,
                'body'     => $response
            ];
        }

        return json_decode($response, true);
    }

    /**
     * Obtener Vendedores de SAP vía ODBC (Tabla OSLP) para Select2
     */
    public function employeesSAPSelect2() {
        $request = service('request');
        $searchTerm = trim((string) $request->getPost('searchTerm'));
        $idEmpresa = (int) ($request->getPost('idEmpresa') ?? 0);

        if ($idEmpresa > 0) {
            $conectionData = $this->serviceLayerModel->where('idEmpresa', $idEmpresa)->first();
        } else {
            $conectionData = $this->serviceLayerModel->select("*")->first();
        }

        try {
            if (!$conectionData) {
                throw new \Exception('No se encontraron datos de conexión SAP.');
            }

            // 1) Conexión ODBC
            $conn = odbc_connect(
                $conectionData["nameODBC"],
                $conectionData["userODBC"],
                $conectionData["passwordODBC"]
            );
            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // 2) Esquema (base de datos de la compañía en HANA)
            $schema = $conectionData["companyDB"];
            if (!odbc_exec($conn, 'SET SCHEMA "' . $schema . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // 3) Construir SQL sobre la tabla OSLP (Vendedores en SAP B1)
            $sql = 'SELECT "SlpCode", "SlpName" 
                    FROM "' . $schema . '"."OSLP" 
                    WHERE "Locked" = \'N\'';

            if (!empty($searchTerm)) {
                $escaped = str_replace("'", "''", $searchTerm);
                $sql .= ' AND (CAST("SlpCode" AS VARCHAR(20)) LIKE \'%' . $escaped . '%\' 
                           OR UPPER("SlpName") LIKE UPPER(\'%' . $escaped . '%\'))';
            }
            $sql .= ' ORDER BY "SlpName"';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            // 4) Construir array de resultados para Select2
            $results = [];
            while ($row = odbc_fetch_array($rs)) {
                $row     = $this->utf8ize($row);
                $slpCode = $row['SlpCode'];
                $slpName = trim((string) ($row['SlpName'] ?? ''));

                $results[] = [
                    'id'   => $slpCode,
                    'text' => $slpCode . " - " . $slpName
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'results' => $results
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'Error en employeesSAPSelect2 (ODBC): ' . $e->getMessage());
            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'results' => []
            ]);
        }
    }

    private function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'ISO-8859-1');
        }
        return $mixed;
    }

    /**
     * Obtener usuarios del sistema por empresa vía Ajax para Select2
     */
    public function getUsersAjaxSelect2() {
        $request = service('request');
        $postData = $request->getPost();

        $idEmpresa = $postData['idEmpresa'] ?? 0;
        $searchTerm = $postData['searchTerm'] ?? '';

        $listUsers = $this->employee_sap_link->mdlGetUsers($searchTerm, $idEmpresa)->getResultArray();

        $results = [];
        foreach ($listUsers as $user) {
            $results[] = [
                'id'   => $user["id"],
                'text' => $user["id"] . " - " . trim($user["username"] . " " . $user["firstname"] . " " . $user["lastname"])
            ];
        }

        return $this->response->setJSON([
            'token'   => csrf_hash(),
            'results' => $results
        ]);
    }
}