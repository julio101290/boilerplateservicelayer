<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\{
    Link_sap_branchofficeModel
};
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;

class Link_sap_branchofficeController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $branchoffice;

    public function __construct() {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->branchoffice = new BranchofficesModel();
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

            $fields = $this->link_sap_branchoffice->allowedFields;
            $orderField = $fields[$orderColumnIndex] ?? 'id';

            $builder = $this->link_sap_branchoffice->mdlGetLink_sap_branchoffice($empresasID);

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

        $titulos["title"] = lang('link_sap_branchoffice.title');
        $titulos["subtitle"] = lang('link_sap_branchoffice.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\link_sap_branchoffice', $titulos);
    }

    public function getLink_sap_branchoffice() {
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $idLink_sap_branchoffice = $this->request->getPost("idLink_sap_branchoffice");
        $dato = $this->link_sap_branchoffice->whereIn('idEmpresa', $empresasID)
                ->where('id', $idLink_sap_branchoffice)
                ->first();

        //GET BRANCH OFFICE LOCAL
        $branchOfficeLocal = $this->branchoffice->where("id", $dato["idBranchOffice"])->asArray()->first();

        $dato["descriptionBranchofficelocal"] = $branchOfficeLocal["name"];

        //GET BRANCH OFFICE SAP
        // -----------------------------
// Conexión ODBC
// -----------------------------
        $dataConect = $this->serviceLayerModel->first();
        $conn = odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
        );

        if (!$conn) {
            throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
        }

// 🔥 FIJAR SCHEMA
        if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"')) {
            throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
        }

// -----------------------------
// SQL OBPL (1 solo registro)
// -----------------------------
        $sql = '
    SELECT
        "BPLId",
        "BPLName"
    FROM OBPL
    WHERE "Disabled" <> \'Y\'
      AND "BPLId" = ' . $dato["idBranchOfficeSAP"] . '
';

        $rs = odbc_exec($conn, $sql);
        if (!$rs) {
            throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
        }

// -----------------------------
// Fetch único
// -----------------------------
        $row = odbc_fetch_array($rs);

        odbc_free_result($rs);
        odbc_close($conn);

// Resultado directo
        $resultado = $row ? [
            'BPLId' => $row['BPLId'],
            'BPLName' => $row['BPLName'],
                ] : null;

        $dato["descriptionBranchOfficeSAP"] = $resultado["BPLName"];

        return $this->response->setJSON($dato);
    }

    public function save() {
        helper('auth');

        $userName = user()->username;
        $datos = $this->request->getPost();
        $idKey = $datos["idLink_sap_branchoffice"] ?? 0;

        if ($idKey == 0) {
            try {
                if (!$this->link_sap_branchoffice->save($datos)) {
                    $errores = implode(" ", $this->link_sap_branchoffice->errors());
                    return $this->respond(['status' => 400, 'message' => $errores], 400);
                }
                $this->log->save([
                    "description" => lang("link_sap_branchoffice.logDescription") . json_encode($datos),
                    "user" => $userName
                ]);
                return $this->respond(['status' => 201, 'message' => 'Guardado correctamente'], 201);
            } catch (\Throwable $ex) {
                return $this->respond(['status' => 500, 'message' => 'Error al guardar: ' . $ex->getMessage()], 500);
            }
        } else {
            if (!$this->link_sap_branchoffice->update($idKey, $datos)) {
                $errores = implode(" ", $this->link_sap_branchoffice->errors());
                return $this->respond(['status' => 400, 'message' => $errores], 400);
            }
            $this->log->save([
                "description" => lang("link_sap_branchoffice.logUpdated") . json_encode($datos),
                "user" => $userName
            ]);
            return $this->respond(['status' => 200, 'message' => 'Actualizado correctamente'], 200);
        }
    }

    public function delete($id) {
        helper('auth');

        $userName = user()->username;
        $registro = $this->link_sap_branchoffice->find($id);

        if (!$this->link_sap_branchoffice->delete($id)) {
            return $this->respond(['status' => 404, 'message' => lang("link_sap_branchoffice.msg.msg_get_fail")], 404);
        }

        $this->link_sap_branchoffice->purgeDeleted();
        $this->log->save([
            "description" => lang("link_sap_branchoffice.logDeleted") . json_encode($registro),
            "user" => $userName
        ]);

        return $this->respondDeleted($registro, lang("link_sap_branchoffice.msg_delete"));
    }

    /**
     * Get branchoffice for select2 via AJAX
     */

    /**
     * Get Storages via AJax
     */
    public function getSucursalesAjax() {
        try {

            $request = service('request');
            $postData = $request->getPost();

            $response = [];
            $response['token'] = csrf_hash();

            helper('auth');
            $idUser = user()->id;

            // --------------------------------
            // 2) Conexión ODBC HANA
            // --------------------------------
            $dataConect = $this->serviceLayerModel->first();

            $conn = odbc_connect(
                    $dataConect['nameODBC'],
                    $dataConect['userODBC'],
                    $dataConect['passwordODBC']
            );

            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            // FIJAR SCHEMA
            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            // --------------------------------
            // 3) SQL OBPL (Sucursales)
            // --------------------------------
            $whereSearch = '';
            if (!empty($postData['searchTerm'])) {
                $search = addslashes($postData['searchTerm']);
                $whereSearch = '
                AND (
                    "BPLName" LIKE \'%' . $search . '%\'
                    OR "BPLId" LIKE \'%' . $search . '%\'
                )
            ';
            }


            $sql = '
            SELECT
                "BPLId",
                "BPLName"
            FROM OBPL
            WHERE "Disabled" <> \'Y\'
              ' . $whereSearch . '
            ORDER BY "BPLId", "BPLName"
        ';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            // --------------------------------
            // 4) Formatear respuesta (Select2)
            // --------------------------------
            $data = [];
            $data[] = [
                'id' => 0,
                'text' => '0 Todas las sucursales'
            ];

            while ($row = odbc_fetch_array($rs)) {
                $data[] = [
                    'id' => $row['BPLId'],
                    'text' => $row['BPLId'] . ' ' . $row['BPLName'],
                ];
            }

            odbc_free_result($rs);
            odbc_close($conn);

            $response['data'] = $data;

            return $this->response->setJSON($response);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                        'token' => csrf_hash(),
                        'data' => [],
                        'error' => true,
                        'message' => $e->getMessage()
            ]);
        }
    }
}
