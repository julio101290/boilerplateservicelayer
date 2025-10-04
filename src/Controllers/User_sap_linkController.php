<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplateservicelayer\Models\{
    User_sap_linkModel
};
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplate\Models\UserModel;
use julio101290\boilerplatecompanies\Models\UsuariosempresaModel;

class User_sap_linkController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $user_sap_link;
    protected $empresa;
    protected $serviceLayerController;
    protected $serviceLayerModel;
    protected $users;
    protected $usersPerCompanie;

    public function __construct() {
        $this->user_sap_link = new User_sap_linkModel();
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

            $fields = $this->user_sap_link->allowedFields;
            $orderField = $fields[$orderColumnIndex] ?? 'id';

            $builder = $this->user_sap_link->mdlGetUser_sap_link($empresasID);

            $total = clone $builder;
            $recordsTotal = $total->countAllResults(false);

            if (!empty($searchValue)) {
                $builder->groupStart();
                foreach ($fields as $field) {
                    $builder->orLike("a." . $field, $searchValue);
                }
                $builder->groupEnd();
            }

            $filteredBuilder = clone $builder;
            $recordsFiltered = $filteredBuilder->countAllResults(false);

            $data = $builder->orderBy("a." . $orderField, $orderDir)
                    ->get($length, $start)
                    ->getResultArray();

            return $this->response->setJSON([
                        'draw' => $draw,
                        'recordsTotal' => $recordsTotal,
                        'recordsFiltered' => $recordsFiltered,
                        'data' => $data,
            ]);
        }

        $titulos["title"] = lang('user_sap_link.title');
        $titulos["subtitle"] = lang('user_sap_link.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\user_sap_link', $titulos);
    }

    public function getUser_sap_link() {
        helper('auth');

        $idUser = user()->id;
        $userName = user()->username;
        $firstname = user()->firstname;
        $lastname = user()->lastname;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $idUser_sap_link = $this->request->getPost("idUser_sap_link");
        $dato = $this->user_sap_link->whereIn('idEmpresa', $empresasID)
                ->where('id', $idUser_sap_link)
                ->first();

        $companie = $this->empresa->where("id", $dato["idEmpresa"])->first();

        $dato["username"] = $userName . " " . $firstname . " " . $lastname;
        $dato["nameCompanie"] = $companie["nombre"];

        return $this->response->setJSON($dato);
    }

    public function save() {
        helper('auth');

        $userName = user()->username;
        $datos = $this->request->getPost();
        $idKey = $datos["idUser_sap_link"] ?? 0;

        if ($idKey == 0) {
            try {
                if (!$this->user_sap_link->save($datos)) {
                    $errores = implode(" ", $this->user_sap_link->errors());
                    return $this->respond(['status' => 400, 'message' => $errores], 400);
                }
                $this->log->save([
                    "description" => lang("user_sap_link.logDescription") . json_encode($datos),
                    "user" => $userName
                ]);
                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } catch (\Throwable $ex) {
                return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
            }
        } else {
            if (!$this->user_sap_link->update($idKey, $datos)) {
                $errores = implode(" ", $this->user_sap_link->errors());
                return $this->respond(['status' => 400, 'message' => $errores], 400);
            }
            $this->log->save([
                "description" => lang("user_sap_link.logUpdated") . json_encode($datos),
                "user" => $userName
            ]);
            return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
        }
    }

    public function delete($id) {
        helper('auth');

        $userName = user()->username;
        $registro = $this->user_sap_link->find($id);

        if (!$this->user_sap_link->delete($id)) {
            return $this->respond(['status' => 404, 'message' => lang("user_sap_link.msg.msg_get_fail")], 404);
        }

        $this->user_sap_link->purgeDeleted();
        $this->log->save([
            "description" => lang("user_sap_link.logDeleted") . json_encode($registro),
            "user" => $userName
        ]);

        return $this->respondDeleted($registro, lang("user_sap_link.msg_delete"));
    }

    public function showEmployes($cookie, $search, $port) {
        $curl = curl_init();

        $baseUrl = "https://192.168.0.190:50000/b1s/v1/EmployeesInfo";
        $selectParam = '$select=EmployeeID,FirstName,LastName';

        // normalizar y escapar
        $search = trim((string) $search);
        $parts = [];

        // si es solo dígitos, comparar EmployeeID
        if ($search !== '' && ctype_digit($search)) {
            // si EmployeeID es numérico en SL, dejar sin comillas; si es string, cambia a '...'
            $parts[] = "EmployeeID eq {$search}";
        }

        // escapar comillas simples para OData ('' -> comilla escapada)
        $escaped = str_replace("'", "''", $search);

        if ($escaped !== '') {
            // usar contains (OData v4) — Service Layer soporta contains en ejemplos oficiales
            $parts[] = "contains(FirstName,'{$escaped}')";
            $parts[] = "contains(LastName,'{$escaped}')";
        }

        $filterValue = '';
        if (!empty($parts)) {
            $filterValue = implode(' or ', $parts);
        }

        // montar URL (encode del filtro)
        if ($filterValue !== '') {
            $url = $baseUrl . '?' . $selectParam . '&$filter=' . rawurlencode($filterValue);
        } else {
            $url = $baseUrl . '?' . $selectParam;
        }

        curl_setopt_array($curl, [
            CURLOPT_PORT => $port,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: PHP cURL",
                // ESTE HEADER es la clave para hacer la consulta insensible a mayúsculas/minúsculas (HANA)
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
                'body' => $response
            ];
        }

        return json_decode($response, true);
    }

    /**
     * Show users for use in select2
     * @param type $cookie
     * @param type $search
     * @param type $port
     * @return array
     */
    public function showUsers($cookie, $search, $port) {
        $curl = curl_init();

        $baseUrl = "https://192.168.0.190:50000/b1s/v1/Users";
        $selectParam = '$select=InternalKey,UserCode,UserName&$top=15';

        // normalizar y escapar
        $search = trim((string) $search);
        $parts = [];

        // si es solo dígitos, comparar InternalKey (USERID)
        if ($search !== '' && ctype_digit($search)) {
            $parts[] = "InternalKey eq {$search}";
        }

        // escapar comillas simples para OData
        $escaped = str_replace("'", "''", $search);

        if ($escaped !== '') {
            // usar contains en UserCode y UserName
            $parts[] = "contains(UserCode,'{$escaped}')";
            $parts[] = "contains(UserName,'{$escaped}')";
        }

        $filterValue = '';
        if (!empty($parts)) {
            $filterValue = implode(' or ', $parts);
        }

        // montar URL (encode del filtro)
        if ($filterValue !== '') {
            $url = $baseUrl . '?' . $selectParam . '&$filter=' . rawurlencode($filterValue);
        } else {
            $url = $baseUrl . '?' . $selectParam;
        }

        curl_setopt_array($curl, [
            CURLOPT_PORT => $port,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_COOKIE => $cookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: PHP cURL",
                // Para búsqueda case-insensitive
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
                'body' => $response
            ];
        }

        return json_decode($response, true);
    }

    public function usersSAPSelect2() {

        $request = service('request');
        $postData = $request->getPost();

        $conectionData = $this->serviceLayerModel->select("*")->first();

        $conexionSAP = $this->serviceLayerController->login($conectionData["url"]
                , $conectionData["port"]
                , $conectionData["password"]
                , $conectionData["username"]
                , $conectionData["companyDB"]);

        $cookie = "B1SESSION=" . $conexionSAP->SessionId . ";  ROUTEID=.node1";

        $usuariosSAP = $this->showUsers($cookie, $postData["searchTerm"], $conectionData["port"]);

        $usuariosSAPLista = $usuariosSAP["value"];

        $usuariosSAP = $this->serviceLayerController->logout($cookie
                , $conectionData["url"]
                , $conectionData["port"]
                , $conectionData["password"]
                , $conectionData["username"]
                , $conectionData["companyDB"]);

        $jsonVariable = ' { "results": [';

        foreach ($usuariosSAPLista as $keyUsuarios1 => $valueUsuarios1) {


            $jsonVariable .= ' {
                    "id": "' . $valueUsuarios1["InternalKey"] . '",
                    "text": "' . utf8_encode($valueUsuarios1["InternalKey"] . " - " . $valueUsuarios1["UserCode"] . " " . $valueUsuarios1["UserName"]) . '"
                  },';
        }

        $jsonVariable = substr($jsonVariable, 0, -1);

        $jsonVariable .= ' ]
                            }';

        echo ($jsonVariable);
    }

    /**
     * Get users via Ajax for select2
     */
    public function getUsersAjaxSelect2() {

        $request = service('request');
        $postData = $request->getPost();

        $response = array();

        // Read new token and assign in $response['token']
        $response['token'] = csrf_hash();
        $idEmpresa = $postData['idEmpresa'];

        $listUsers = $this->user_sap_link->mdlGetUsers($postData['searchTerm'], $idEmpresa)->getResultArray();

        $data = array();
        $data[] = array(
            "id" => 0,
            "text" => "0 Todos Los Productos",
        );

        $jsonVariable = ' { "results": [';

        foreach ($listUsers as $user) {

            $jsonVariable .= ' {
                    "id": "' . $user["id"] . '",
                    "text": "' . utf8_encode($user["id"] . " - " . $user["username"] . " " . $user["firstname"] . " " . $user["lastname"]) . '"
                  },';
        }


        $jsonVariable = substr($jsonVariable, 0, -1);

        $jsonVariable .= ' ]
                            }';

        echo ($jsonVariable);
    }
}
