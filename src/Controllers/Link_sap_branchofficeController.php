<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;
use CodeIgniter\API\ResponseTrait;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;

class Link_sap_branchofficeController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $branchoffice;

    public function __construct()
    {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->branchoffice = new BranchofficesModel();
        helper(['menu', 'utilerias']);
    }

    public function index()
    {
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
            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir = $request->getGet('order')[0]['dir'] ?? 'asc';

            // Mapeo de columnas de DataTable a campos SQL
            $columnsMap = [
                0 => 'a.id',
                1 => 'b.nombre',
                2 => 'c.name',
                3 => 'a.idBranchOfficeSAP',
                4 => 'a.created_at',
                5 => 'a.updated_at',
            ];
            $orderField = $columnsMap[$orderColumnIndex] ?? 'a.id';

            $builder = $this->link_sap_branchoffice->mdlGetLink_sap_branchoffice($empresasID);

            $total = clone $builder;
            $recordsTotal = $total->countAllResults(false);

            // Búsqueda global (incluye nombre de empresa y nombre de sucursal local)
            if (!empty($searchValue)) {
                $builder->groupStart()
                    ->like('a.id', $searchValue)
                    ->orLike('b.nombre', $searchValue)
                    ->orLike('c.name', $searchValue)
                    ->orLike('a.idBranchOfficeSAP', $searchValue)
                ->groupEnd();
            }

            $filteredBuilder = clone $builder;
            $recordsFiltered = $filteredBuilder->countAllResults(false);

            $data = $builder->orderBy($orderField, $orderDir)
                ->get($length, $start)
                ->getResultArray();

            // Consultar nombres de sucursales SAP únicamente para los registros paginados
            $sapIds = array_column($data, 'idBranchOfficeSAP');
            $sapNamesMap = $this->getSapBranchOfficeNames($sapIds);

            // Inyectar nombres descriptivos para el DataTable
            foreach ($data as &$row) {
                $sapId = (int) $row['idBranchOfficeSAP'];
                $row['nombreBranchOffice']    = $row['nombreBranchOffice'] ?? 'Sin asignar';
                $row['nombreBranchOfficeSAP'] = $sapNamesMap[$sapId] ?? ($sapId === 0 ? '0 Todas las sucursales' : "SAP ID: {$sapId}");
            }
            unset($row);

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }

        $titulos["title"] = lang('link_sap_branchoffice.title');
        $titulos["subtitle"] = lang('link_sap_branchoffice.subtitle');
        return view('julio101290\boilerplateservicelayer\Views\link_sap_branchoffice', $titulos);
    }

    /**
     * Consulta en batch por ODBC los nombres de sucursales en SAP (OBPL)
     */
    private function getSapBranchOfficeNames(array $sapIds): array
    {
        $sapIds = array_unique(array_filter($sapIds, fn($id) => $id !== null && $id !== ''));
        $names = [0 => '0 Todas las sucursales'];

        $searchIds = array_diff($sapIds, [0]);
        if (empty($searchIds)) {
            return $names;
        }

        try {
            $dataConect = $this->serviceLayerModel->first();
            if (!$dataConect) {
                return $names;
            }

            $conn = @odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
            );

            if (!$conn) {
                return $names;
            }

            if (!empty($dataConect['companyDB'])) {
                @odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"');
            }

            $idsString = implode(',', array_map('intval', $searchIds));
            $sql = '
                SELECT "BPLId", "BPLName"
                FROM OBPL
                WHERE "Disabled" <> \'Y\'
                  AND "BPLId" IN (' . $idsString . ')
            ';

            $rs = @odbc_exec($conn, $sql);
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $names[(int)$row['BPLId']] = $row['BPLId'] . ' - ' . $row['BPLName'];
                }
                odbc_free_result($rs);
            }

            odbc_close($conn);
        } catch (\Throwable $e) {
            log_message('error', 'Error al consultar nombres SAP OBPL: ' . $e->getMessage());
        }

        return $names;
    }

    public function getLink_sap_branchoffice()
    {
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $idLink_sap_branchoffice = $this->request->getPost("idLink_sap_branchoffice");
        $dato = $this->link_sap_branchoffice->whereIn('idEmpresa', $empresasID)
            ->where('id', $idLink_sap_branchoffice)
            ->first();

        if (!$dato) {
            return $this->response->setJSON(['error' => true, 'message' => 'Registro no encontrado']);
        }

        // Sucursal Local
        $branchOfficeLocal = $this->branchoffice->where("id", $dato["idBranchOffice"])->asArray()->first();
        $dato["descriptionBranchofficelocal"] = $branchOfficeLocal["name"] ?? 'Sin asignar';

        // Sucursal SAP
        $sapNames = $this->getSapBranchOfficeNames([(int)$dato["idBranchOfficeSAP"]]);
        $dato["descriptionBranchOfficeSAP"] = $sapNames[(int)$dato["idBranchOfficeSAP"]] ?? 'No disponible';

        return $this->response->setJSON($dato);
    }

    public function save()
    {
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

    public function delete($id)
    {
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

    public function getSucursalesAjax()
    {
        try {
            $request = service('request');
            $postData = $request->getPost();

            $response = [];
            $response['token'] = csrf_hash();

            helper('auth');

            $dataConect = $this->serviceLayerModel->first();

            $conn = odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
            );

            if (!$conn) {
                throw new \Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            if (!odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"')) {
                throw new \Exception('Error SET SCHEMA: ' . odbc_errormsg($conn));
            }

            $whereSearch = '';
            if (!empty($postData['searchTerm'])) {
                $search = addslashes($postData['searchTerm']);
                $whereSearch = '
                AND (
                    "BPLName" LIKE \'%' . $search . '%\'
                    OR "BPLId" LIKE \'%' . $search . '%\'
                )';
            }

            $sql = '
                SELECT "BPLId", "BPLName"
                FROM OBPL
                WHERE "Disabled" <> \'Y\'
                  ' . $whereSearch . '
                ORDER BY "BPLId", "BPLName"
            ';

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

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
                'token'   => csrf_hash(),
                'data'    => [],
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}