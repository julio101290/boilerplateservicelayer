<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\User_pricelistModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplate\Models\UserModel;

class User_pricelistController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $user_pricelist;
    protected $empresa;
    protected $users;
    protected $serviceLayerModel;

    public function __construct()
    {
        $this->user_pricelist    = new User_pricelistModel();
        $this->log               = new LogModel();
        $this->empresa           = new EmpresasModel();
        $this->users             = new UserModel();
        $this->serviceLayerModel = new SapservicelayerModel();

        helper(['menu', 'utilerias']);
    }

    public function index()
    {
        helper('auth');

        $idUser              = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID          = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw             = (int) $request->getGet('draw');
            $start            = (int) $request->getGet('start');
            $length           = (int) $request->getGet('length');
            $searchValue      = $request->getGet('search')['value'] ?? '';
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir         = $request->getGet('order')[0]['dir'] ?? 'asc';

            $fields     = $this->user_pricelist->allowedFields;
            $orderField = $fields[$orderColumnIndex] ?? 'id';

            $builder = $this->user_pricelist->mdlGetUser_pricelist($empresasID);

            $total        = clone $builder;
            $recordsTotal = $total->countAllResults(false);

            if (!empty($searchValue)) {
                $builder->groupStart();
                $builder->like('b.nombre', $searchValue)
                        ->orLike('u.username', $searchValue)
                        ->orLike('u.firstname', $searchValue)
                        ->orLike('a.namePriceList', $searchValue);
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

        $titulos["title"]    = lang('user_pricelist.title');
        $titulos["subtitle"] = lang('user_pricelist.subtitle');

        return view('julio101290\boilerplateservicelayer\Views\user_pricelist', $titulos);
    }

    public function getUser_pricelist()
    {
        helper('auth');

        $idUser              = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID          = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $idUser_pricelist = $this->request->getPost("idUser_pricelist");

        $dato = $this->user_pricelist->mdlGetUser_pricelist($empresasID)
            ->where('a.id', $idUser_pricelist)
            ->get()
            ->getRowArray();

        if (!$dato) {
            return $this->respond(['status' => 404, 'message' => 'Registro no encontrado'], 404);
        }

        return $this->response->setJSON($dato);
    }

    public function save()
    {
        helper('auth');

        $userName = user()->username;
        $datos    = $this->request->getPost();
        $idKey    = $datos["idUser_pricelist"] ?? 0;

        unset($datos["idUser_pricelist"]);

        if ($idKey == 0) {
            try {
                if (!$this->user_pricelist->save($datos)) {
                    $errores = implode(" ", $this->user_pricelist->errors());
                    return $this->respond(['status' => 400, 'message' => $errores], 400);
                }
                $this->log->save([
                    "description" => lang("user_pricelist.logDescription") . json_encode($datos),
                    "user"        => $userName
                ]);
                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } catch (\Throwable $ex) {
                return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
            }
        } else {
            if (!$this->user_pricelist->update($idKey, $datos)) {
                $errores = implode(" ", $this->user_pricelist->errors());
                return $this->respond(['status' => 400, 'message' => $errores], 400);
            }
            $this->log->save([
                "description" => lang("user_pricelist.logUpdated") . json_encode($datos),
                "user"        => $userName
            ]);
            return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
        }
    }

    public function delete($id)
    {
        helper('auth');

        $userName = user()->username;
        $registro = $this->user_pricelist->find($id);

        if (!$this->user_pricelist->delete($id)) {
            return $this->respond(['status' => 404, 'message' => lang("user_pricelist.msg.msg_get_fail")], 404);
        }

        $this->user_pricelist->purgeDeleted();
        $this->log->save([
            "description" => lang("user_pricelist.logDeleted") . json_encode($registro),
            "user"        => $userName
        ]);

        return $this->respondDeleted($registro, lang("user_pricelist.msg_delete"));
    }

    /**
     * Obtener usuarios internos de MariaDB vía Ajax para Select2
     */
    public function getUsersAjaxSelect2()
    {
        $request    = service('request');
        $searchTerm = trim((string) $request->getPost('searchTerm'));
        $idEmpresa  = (int) $request->getPost('idEmpresa');

        $listUsers = $this->user_pricelist->mdlGetUsers($searchTerm, $idEmpresa)->getResultArray();

        $results = [];
        foreach ($listUsers as $user) {
            $results[] = [
                'id'   => $user['id'],
                'text' => $user['id'] . ' - ' . $user['username'] . ' ' . ($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')
            ];
        }

        return $this->response->setJSON([
            'token'   => csrf_hash(),
            'results' => $results
        ]);
    }

    /**
     * Obtener listas de precios desde SAP (Tabla OPLN) vía ODBC para Select2
     */
    public function getPriceListsAjaxSelect2()
    {
        $request    = service('request');
        $searchTerm = trim((string) $request->getPost('searchTerm'));
        $idEmpresa  = (int) $request->getPost('idEmpresa');

        $builder = $this->serviceLayerModel->select("*");
        if ($idEmpresa > 0) {
            $builder->where("idEmpresa", $idEmpresa);
        }
        $conectionData = $builder->first();

        if (!$conectionData) {
            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'results' => []
            ]);
        }

        try {
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

            // Consulta a la tabla OPLN (Listas de Precios en SAP Business One)
            $sql = 'SELECT "ListNum", "ListName" 
                    FROM "' . $schema . '"."OPLN" 
                    WHERE 1=1';

            if (!empty($searchTerm)) {
                $escaped = str_replace("'", "''", $searchTerm);
                if (ctype_digit($escaped)) {
                    $sql .= ' AND ("ListNum" = ' . $escaped . ' OR UPPER("ListName") LIKE UPPER(\'%' . $escaped . '%\'))';
                } else {
                    $sql .= ' AND UPPER("ListName") LIKE UPPER(\'%' . $escaped . '%\')';
                }
            }

            $sql .= ' ORDER BY "ListNum" ASC';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            $results = [];
            while ($row = odbc_fetch_array($rs)) {
                $row      = $this->utf8ize($row);
                $listNum  = $row['ListNum'];
                $listName = $row['ListName'];

                $results[] = [
                    'id'   => $listNum,
                    'text' => $listNum . " - " . $listName
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'results' => $results
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'Error en getPriceListsAjaxSelect2 (ODBC): ' . $e->getMessage());
            return $this->response->setJSON([
                'token'   => csrf_hash(),
                'results' => []
            ]);
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