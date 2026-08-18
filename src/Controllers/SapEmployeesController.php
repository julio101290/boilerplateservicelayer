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
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;

class SapEmployeesController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $link_sap_branchoffice;
    protected $empresa;
    protected $serviceLayerModel;
    protected $branchoffice;
    protected $userLinkSap;

    public function __construct() {
        $this->link_sap_branchoffice = new Link_sap_branchofficeModel();
        $this->log = new LogModel();
        $this->empresa = new EmpresasModel();
        $this->serviceLayerModel = new SapservicelayerModel();
        $this->branchoffice = new BranchofficesModel();
        $this->userLinkSap = new User_sap_linkModel();
        helper(['menu', 'utilerias']);
    }

    /**
     * Get branchoffice for select2 via AJAX
     */
    public function getEmployeesAjax() {
        try {
            $request = service('request');
            $postData = $request->getPost();

            $response = [];
            $response['token'] = csrf_hash();

            // --- Validar sucursal obligatoria ---
            $branchId = $postData['idBranchOffice'] ?? null;
            if (empty($branchId)) {
                return $this->response->setJSON([
                            'token' => csrf_hash(),
                            'data' => [],
                            'error' => true,
                            'message' => 'El parámetro "idBranchOffice" es obligatorio.'
                ]);
            }
            $branchId = (int) $branchId;

            // --- Conexión ODBC ---
            $dataConect = $this->serviceLayerModel->first();
            if (!$dataConect) {
                throw new \Exception('No se encontró configuración de conexión SAP.');
            }

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

            // --- Filtro de búsqueda ---
            $whereSearch = '';
            if (!empty($postData['searchTerm'])) {
                $search = addslashes($postData['searchTerm']);
                $whereSearch = "
                AND (
                    \"empID\" LIKE '%{$search}%'
                    OR \"firstName\" LIKE '%{$search}%'
                    OR \"lastName\" LIKE '%{$search}%'
                )
            ";
            }

            // --- Consulta SQL ---
            $sql = "
            SELECT
                \"empID\",
                \"firstName\",
                \"lastName\"
            FROM OHEM
            WHERE \"Active\" = 'Y'
              AND \"BPLId\" = {$branchId}
              {$whereSearch}
            ORDER BY \"empID\"
        ";

            $rs = odbc_exec($conn, $sql);
            if (!$rs) {
                throw new \Exception('Error SQL: ' . odbc_errormsg($conn));
            }

            // --- Procesar resultados con conversión UTF-8 ---
            $data = [];
            while ($row = odbc_fetch_array($rs)) {
                // Función auxiliar para convertir a UTF-8
                $toUtf8 = function ($value) {
                    if (is_null($value))
                        return '';
                    // Si ya es UTF-8, lo dejamos; si no, lo convertimos desde ISO-8859-1
                    if (mb_check_encoding($value, 'UTF-8')) {
                        return $value;
                    }
                    return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                    // Alternativa: return utf8_encode($value);
                };

                $empID = $toUtf8($row['empID']);
                $firstName = $toUtf8($row['firstName']);
                $lastName = $toUtf8($row['lastName']);
                $fullName = trim($firstName . ' ' . $lastName);

                $data[] = [
                    'id' => $empID,
                    'text' => $empID . ' - ' . $fullName
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
