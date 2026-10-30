<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use Throwable;
use julio101290\boilerplate\Models\UserModel;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplatecompanies\Models\UsuariosempresaModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplatesuppliers\Models\ProveedoresModel;
use julio101290\boilerplatecustumers\Models\CustumersModel;
use julio101290\boilerplateCFDI\Models\XmlModel;
use julio101290\boilerplateCFDI\Controllers\XmlController;
use julio101290\boilerplatesells\Models\EnlacexmlModel;
use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
use julio101290\boilerplateservicelayer\Controllers\SapservicelayerController;
use julio101290\boilerplateservicelayer\Models\User_sap_linkModel;
use julio101290\boilerplateservicelayer\Models\Link_sap_branchofficeModel;
use julio101290\boilerplateservicelayer\Models\RefundsModel;
use julio101290\boilerplateservicelayer\Models\RefundsDetailsModel;

class RefundsController extends BaseController
{
    use ResponseTrait;

    protected $log;
    protected $user_sap_link;
    protected $empresa;
    protected $sucursales;
    protected $serviceLayerController;
    protected $serviceLayerModel;
    protected $users;
    protected $usersPerCompanie;
    protected $refundsModel;
    protected $refundsDetailsModel;
    protected $suppliers;
    protected $custumer;
    protected $xml;
    protected $xmlController;
    protected $enlaceXML;
    protected $linkSapBranchOffice;
    public $catalogosSAT;

    public function __construct()
    {
        $this->user_sap_link          = new User_sap_linkModel();
        $this->log                    = new LogModel();
        $this->empresa                = new EmpresasModel();
        $this->sucursales             = new BranchofficesModel();
        $this->serviceLayerController = new SapservicelayerController();
        $this->serviceLayerModel      = new SapservicelayerModel();
        $this->users                  = new UserModel();
        $this->usersPerCompanie       = new UsuariosempresaModel();
        $this->refundsModel           = new RefundsModel();
        $this->refundsDetailsModel    = new RefundsDetailsModel();
        $this->suppliers              = new ProveedoresModel();
        $this->custumer               = new CustumersModel();
        $this->xml                    = new XmlModel();
        $this->xmlController          = new XmlController();
        $this->enlaceXML              = new EnlacexmlModel();
        $this->linkSapBranchOffice    = new Link_sap_branchofficeModel();

        if (class_exists('\julio101290\boilerplateCFDI\Libraries\CatalogosSAT')) {
            $this->catalogosSAT = new \julio101290\boilerplateCFDI\Libraries\CatalogosSAT();
        }

        helper(['menu', 'utilerias', 'auth']);
    }

    /**
     * Obtiene los datos enviados por POST o JSON sin disparar HTTPException::forInvalidJSON de CI4
     */
    private function getSafeInput(): array
    {
        $post = $this->request->getPost();
        if (!empty($post) && is_array($post)) {
            return $post;
        }

        $rawBody = $this->request->getBody();
        if (!empty($rawBody) && is_string($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $vars = $this->request->getVar();
        return is_array($vars) ? $vars : [];
    }

    // =========================================================================
    // 1. VISTAS PRINCIPALES Y LISTADO DATATABLE (MARIADB)
    // =========================================================================

    public function index()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser              = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) === 0) {
            $empresasID = [0];
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        if ($this->request->isAJAX()) {
            $request = service('request');

            $draw        = (int) $request->getGet('draw');
            $start       = (int) $request->getGet('start');
            $length      = (int) $request->getGet('length');
            $searchValue = trim($request->getGet('search')['value'] ?? '');

            $orderColumnIndex = (int) ($request->getGet('order')[0]['column'] ?? 0);
            $orderDir         = strtolower($request->getGet('order')[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

            $columns = [
                'id',
                'code',
                'U_Folio',
                'U_TypeVoucher',
                'U_Area',
                'U_Employee',
                'U_Date',
                'total',
                'U_Status',
                'sapSynced',
                'U_UserCode',
                'U_CodeMov'
            ];

            $orderField = $columns[$orderColumnIndex] ?? 'id';

            $builder = $this->refundsModel->builder();
            $builder->whereIn('idEmpresa', $empresasID);
            $builder->where('deleted_at', null);

            $recordsTotal = $builder->countAllResults(false);

            if ($searchValue !== '') {
                $builder->groupStart()
                    ->like('code', $searchValue)
                    ->orLike('U_Folio', $searchValue)
                    ->orLike('U_Area', $searchValue)
                    ->orLike('U_Employee', $searchValue)
                    ->orLike('empName', $searchValue)
                    ->orLike('U_Status', $searchValue)
                    ->orLike('U_UserCode', $searchValue)
                    ->orLike('U_CodeMov', $searchValue)
                    ->groupEnd();
            }

            $recordsFiltered = $builder->countAllResults(false);

            $data = $builder
                ->orderBy($orderField, $orderDir)
                ->limit($length, $start)
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data
            ]);
        }

        $titulos["title"]    = 'Reembolsos y Comprobación de Gastos';
        $titulos["subtitle"] = 'Administración de reembolsos y comprobantes SAP';

        return view('julio101290\boilerplateservicelayer\Views\refunds', $titulos);
    }

    public function newRefund()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $idUser   = user()->id;

        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $fechaActual         = fechaMySQLADateHTML5(fechaHoraActual());

        $titulos["idMax"]         = "0";
        $titulos["idRefund"]      = "0";
        $titulos["folio"]         = "0";
        $titulos["U_Folio"]       = "0";
        $titulos["U_Area"]        = "";
        $titulos["U_Employee"]    = "";
        $titulos["empName"]       = "";
        $titulos["U_CodeMov"]     = "";
        $titulos["U_TypeVoucher"] = "1";
        $titulos["U_Status"]      = "1";
        $titulos["accountCredit"] = "";
        $titulos["fecha"]         = $fechaActual;
        $titulos["dateVen"]       = $fechaActual;
        $titulos["userName"]      = $userName;
        $titulos["idUser"]        = $idUser;
        $titulos["observations"]  = "";
        $titulos["taxes"]         = "0.00";
        $titulos["IVARetenido"]   = "0.00";
        $titulos["ISRRetenido"]   = "0.00";
        $titulos["subTotal"]      = "0.00";
        $titulos["total"]         = "0.00";
        $titulos["totalExento"]   = "0.00";
        $titulos["listProducts"]  = [];
        $titulos["sapSynced"]     = 0;
        $titulos["uuid"]          = generaUUID();
        $titulos["title"]         = "Nuevo Reembolso / Comprobación";
        $titulos["subtitle"]      = "Captura de Comprobantes (XML y Notas)";

        return view('julio101290\boilerplateservicelayer\Views\newRefund', $titulos);
    }

    public function editRefund($uuid)
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $idUser   = user()->id;

        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID          = count($titulos["empresas"]) === 0 ? [0] : array_column($titulos["empresas"], "id");

        $refund = $this->refundsModel
            ->whereIn('idEmpresa', $empresasID)
            ->where('UUID', $uuid)
            ->first();

        if (!$refund) {
            return redirect()->to(base_url('admin/refunds'))->with('error', 'Reembolso no encontrado.');
        }

        $details = $this->refundsDetailsModel
            ->where('idRefund', $refund['id'])
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();

        $listDecoded = !empty($refund["list"]) ? json_decode($refund["list"], true) : [];
        if (!empty($listDecoded) && !empty($details)) {
            foreach ($listDecoded as $idx => &$itemJson) {
                if (isset($details[$idx])) {
                    $itemJson['code']          = $details[$idx]['code'] ?? ($itemJson['code'] ?? '');
                    $itemJson['U_CodeVoucher'] = $details[$idx]['U_CodeVoucher'] ?? ($itemJson['U_CodeVoucher'] ?? '');
                    $itemJson['U_DocEntry']    = $details[$idx]['U_DocEntry'] ?? ($itemJson['U_DocEntry'] ?? '');
                    $itemJson['U_DocNum']      = $details[$idx]['U_DocNum'] ?? ($itemJson['U_DocNum'] ?? '');
                    $itemJson['U_Status']      = $details[$idx]['U_Status'] ?? ($itemJson['U_Status'] ?? 'Abierto');
                }
            }
            unset($itemJson);
            $titulos["listProducts"] = $listDecoded;
        } else {
            $titulos["listProducts"] = !empty($listDecoded) ? $listDecoded : $details;
        }

        $sucursal = $this->sucursales->find($refund["idSucursal"]);

        $titulos["idRefund"]       = $refund["id"];
        $titulos["idEmpresa"]      = $refund["idEmpresa"];
        $titulos["idSucursal"]     = $refund["idSucursal"];
        $titulos["nombreSucursal"] = $sucursal ? ($sucursal["key"] . " " . $sucursal["name"]) : "";
        $titulos["code"]           = $refund["code"];
        $titulos["U_Folio"]        = $refund["U_Folio"];
        $titulos["U_Area"]         = $refund["U_Area"];
        $titulos["U_Employee"]     = $refund["U_Employee"];
        $titulos["empName"]        = $refund["empName"] ?? "";
        $titulos["U_CodeMov"]      = $refund["U_CodeMov"];
        $titulos["U_TypeVoucher"]  = $refund["U_TypeVoucher"];
        $titulos["U_Status"]       = $refund["U_Status"];
        $titulos["accountCredit"]  = $refund["accountCredit"] ?? "";
        $titulos["fecha"]          = $refund["U_Date"] ?? $refund["date"];
        $titulos["dateVen"]        = $refund["dateVen"] ?? $refund["U_Date"];
        $titulos["userName"]       = $userName;
        $titulos["idUser"]         = $idUser;
        $titulos["observations"]   = $refund["U_Coments"] ?? $refund["generalObservations"];
        $titulos["taxes"]          = number_format((float) $refund["taxes"], 2, ".", "");
        $titulos["IVARetenido"]    = number_format((float) $refund["IVARetenido"], 2, ".", "");
        $titulos["ISRRetenido"]    = number_format((float) $refund["ISRRetenido"], 2, ".", "");
        $titulos["subTotal"]       = number_format((float) $refund["subTotal"], 2, ".", "");
        $titulos["total"]          = number_format((float) $refund["total"], 2, ".", "");
        $titulos["totalExento"]    = number_format((float) ($refund["tasaCero"] ?? 0), 2, ".", "");
        $titulos["uuid"]           = $refund["UUID"];
        $titulos["sapSynced"]      = $refund["sapSynced"] ?? 0;
        $titulos["title"]          = "Editar Reembolso / Comprobación";
        $titulos["subtitle"]       = "Folio: " . $refund["U_Folio"];

        return view('julio101290\boilerplateservicelayer\Views\newRefund', $titulos);
    }

    // =========================================================================
    // 2. GUARDADO Y ELIMINACIÓN LOCAL EN MARIADB (STAGING)
    // =========================================================================

    public function save()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return $this->response->setJSON(['error' => true, 'message' => 'Sesión expirada.', 'token' => csrf_hash()]);
        }

        $userName = user()->username;
        $idUser   = user()->id;
        $datos    = $this->request->getPost();

        $this->refundsModel->db->transBegin();

        try {
            $uuid        = trim((string) ($datos['UUID'] ?? generaUUID()));
            $listItems   = json_decode((string) ($datos['listProducts'] ?? $datos['list'] ?? '[]'), true);
            $idEmpresa   = (int) ($datos['idEmpresa'] ?? 0);
            $idSucursal  = (int) ($datos['idSucursal'] ?? 0);
            $area        = trim((string) ($datos['U_Area'] ?? ''));
            $codeMov     = trim((string) ($datos['U_CodeMov'] ?? ''));
            $typeVoucher = (int) ($datos['U_TypeVoucher'] ?? 1);

            if ($idEmpresa <= 0 || $idSucursal <= 0) {
                throw new Exception('La empresa y la sucursal son obligatorias.');
            }
            if (empty($area) || $area === '0') {
                throw new Exception('Favor de seleccionar el Área / Centro de Costo.');
            }
            if (empty($datos['U_Employee']) || $datos['U_Employee'] === '0') {
                throw new Exception('Favor de seleccionar el Empleado.');
            }
            if (empty($listItems) || !is_array($listItems)) {
                throw new Exception('El reembolso no cuenta con líneas (facturas XML o notas).');
            }

            $existingRefund = $this->refundsModel->where('UUID', $uuid)->first();

            if ($existingRefund && (int) ($existingRefund['sapSynced'] ?? 0) === 1) {
                throw new Exception('Este reembolso ya fue sincronizado con SAP Business One y no puede modificarse localmente.');
            }

            $folio = trim((string) ($datos['U_Folio'] ?? ''));
            if (empty($folio) || $folio === '0') {
                if (!empty($codeMov)) {
                    $folio = $codeMov;
                } else {
                    $consecutivo = $this->getNextFolioByArea($idEmpresa, $area, $typeVoucher);
                    $folio       = "CG_{$area}_{$consecutivo}";
                }
            }

            $docDate = !empty($datos['date']) ? date('Y-m-d', strtotime($datos['date'])) : date('Y-m-d');

            $headerData = [
                'idEmpresa'           => $idEmpresa,
                'idSucursal'          => $idSucursal,
                'idUser'              => $idUser,
                'U_Folio'             => $folio,
                'U_Area'              => $area,
                'U_Employee'          => trim((string) $datos['U_Employee']),
                'empName'             => trim((string) ($datos['empName'] ?? '')),
                'U_Coments'           => mb_substr(trim((string) ($datos['generalObservations'] ?? $datos['U_Coments'] ?? '')), 0, 254),
                'U_Date'              => $docDate,
                'U_Total'             => (float) ($datos['total'] ?? 0),
                'U_Status'            => (int) ($datos['U_Status'] ?? 1),
                'U_UserCode'          => $userName,
                'U_CodeMov'           => $codeMov,
                'U_TypeVoucher'       => $typeVoucher,
                'U_Branch'            => (string) $idSucursal,
                'accountCredit'       => trim((string) ($datos['accountCredit'] ?? '')),
                'copyComments'        => !empty($datos['copyComments']) ? 'Y' : 'N',
                'list'                => json_encode($listItems, JSON_UNESCAPED_UNICODE),
                'taxes'               => (float) ($datos['taxes'] ?? 0),
                'subTotal'            => (float) ($datos['subTotal'] ?? 0),
                'total'               => (float) ($datos['total'] ?? 0),
                'balance'             => (float) ($datos['total'] ?? 0),
                'date'                => $docDate,
                'dateVen'             => !empty($datos['dateVen']) ? date('Y-m-d', strtotime($datos['dateVen'])) : $docDate,
                'generalObservations' => trim((string) ($datos['generalObservations'] ?? '')),
                'UUID'                => $uuid,
                'IVARetenido'         => (float) ($datos['IVARetenido'] ?? 0),
                'ISRRetenido'         => (float) ($datos['ISRRetenido'] ?? 0),
                'tasaCero'            => (float) ($datos['tasaCero'] ?? 0),
            ];

            $columnasHeader = $this->refundsModel->db->getFieldNames('refunds');
            $headerData     = array_intersect_key($headerData, array_flip($columnasHeader));

            if (!$existingRefund) {
                if ($this->refundsModel->insert($headerData) === false) {
                    throw new Exception('Error al guardar encabezado: ' . implode(' ', $this->refundsModel->errors()));
                }
                $idRefund = $this->refundsModel->getInsertID();
            } else {
                $idRefund = (int) $existingRefund['id'];
                if ($this->refundsModel->update($idRefund, $headerData) === false) {
                    throw new Exception('Error al actualizar encabezado: ' . implode(' ', $this->refundsModel->errors()));
                }
                $this->refundsDetailsModel->where('idRefund', $idRefund)->delete();
                $this->refundsDetailsModel->purgeDeleted();
            }

            $columnasDetalle = $this->refundsDetailsModel->db->getFieldNames('refundsdetails');

            foreach ($listItems as $idx => $item) {
                $lineNum  = $idx + 1;
                $tipoItem = strtoupper(trim((string) ($item['U_Type'] ?? 'XML'))) === 'NOTA' ? 'Nota' : 'XML';

                $detailData = [
                    'idRefund'           => $idRefund,
                    'U_NA'               => !empty($item['U_NA']) && $item['U_NA'] === 'Y' ? 'Y' : 'N',
                    'U_Line'             => (string) $lineNum,
                    'U_Date'             => !empty($item['U_Date']) ? date('Y-m-d', strtotime($item['U_Date'])) : $docDate,
                    'U_Type'             => $tipoItem,
                    'U_Provider'         => trim((string) ($item['U_Provider'] ?? $item['cardCode'] ?? '')),
                    'U_Status'           => $tipoItem === 'Nota' ? 'Cerrado' : 'Abierto',
                    'U_Coments'          => mb_substr(trim((string) ($item['U_Coments'] ?? $item['description'] ?? '')), 0, 254),
                    'U_Subtotal'         => (float) ($item['U_Subtotal'] ?? $item['total'] ?? 0),
                    'U_IVA'              => (float) ($item['U_IVA'] ?? $item['tax'] ?? 0),
                    'U_RetIVA'           => (float) ($item['U_RetIVA'] ?? $item['IVARetenido'] ?? 0),
                    'U_ISR'              => (float) ($item['U_ISR'] ?? $item['ISRRetenido'] ?? 0),
                    'U_IEPS'             => (float) ($item['U_IEPS'] ?? 0),
                    'U_Total'            => (float) ($item['U_Total'] ?? $item['neto'] ?? 0),
                    'U_Coment'           => mb_substr(trim((string) ($item['U_Coment'] ?? '')), 0, 254),
                    'U_UserCode'         => $userName,
                    'docFolio'           => trim((string) ($item['docFolio'] ?? '')),
                    'UUID'               => trim((string) ($item['UUID'] ?? '')),
                    'accountType'        => trim((string) ($item['accountType'] ?? 'Gastos/Costos')),
                    'account'            => trim((string) ($item['account'] ?? '')),
                    'affectable'         => trim((string) ($item['affectable'] ?? '')),
                    'auxEmployee'        => trim((string) ($item['auxEmployee'] ?? $datos['U_Employee'])),
                    'auxAffectable'      => trim((string) ($item['auxAffectable'] ?? $datos['U_Employee'])),
                    'codeMovLine'        => trim((string) ($item['codeMovLine'] ?? $codeMov)),
                    'project'            => trim((string) ($item['prjCode'] ?? $item['project'] ?? '')),
                    'adminOper'          => trim((string) ($item['adminOper'] ?? 'A')),
                    'whsCode'            => trim((string) ($item['whsCode'] ?? '')),
                    'dim1'               => trim((string) ($item['ocrCode'] ?? $item['dim1'] ?? $area)),
                    'dim2'               => trim((string) ($item['ocrCode2'] ?? $item['dim2'] ?? '')),
                    'dim3'               => trim((string) ($item['ocrCode3'] ?? $item['dim3'] ?? '')),
                    'dim4'               => trim((string) ($item['ocrCode4'] ?? $item['dim4'] ?? '')),
                    'dim5'               => trim((string) ($item['ocrCode5'] ?? $item['dim5'] ?? '')),
                    'xmlFile'            => trim((string) ($item['xmlFile'] ?? '')),
                    'pdfFile'            => trim((string) ($item['pdfFile'] ?? '')),
                    'codeProduct'        => trim((string) ($item['codeProduct'] ?? $item['itemCode'] ?? '')),
                    'description'        => mb_substr(trim((string) ($item['description'] ?? '')), 0, 512),
                    'cant'               => (float) ($item['cant'] ?? 1),
                    'price'              => (float) ($item['price'] ?? 0),
                    'porcentTax'         => (float) ($item['porcentTax'] ?? 0),
                    'tax'                => (float) ($item['tax'] ?? 0),
                    'porcentIVARetenido' => (float) ($item['porcentIVARetenido'] ?? 0),
                    'IVARetenido'        => (float) ($item['IVARetenido'] ?? 0),
                    'porcentISRRetenido' => (float) ($item['porcentISRRetenido'] ?? 0),
                    'ISRRetenido'        => (float) ($item['ISRRetenido'] ?? 0),
                    'neto'               => (float) ($item['neto'] ?? $item['U_Total'] ?? 0),
                ];

                $detailData = array_intersect_key($detailData, array_flip($columnasDetalle));

                if ($this->refundsDetailsModel->insert($detailData) === false) {
                    throw new Exception("Error al guardar línea #{$lineNum}: " . implode(' ', $this->refundsDetailsModel->errors()));
                }
            }

            $this->log->save([
                'description' => "Se guardó reembolso local Folio: {$folio}, UUID: {$uuid}",
                'user'        => $userName,
                'module'      => 'REFUNDS',
                'idMovModule' => $idRefund
            ]);

            $this->refundsModel->db->transCommit();

            return $this->response->setJSON([
                'error'    => false,
                'message'  => "Guardado correctamente en MariaDB. Folio: {$folio}",
                'idRefund' => $idRefund,
                'folio'    => $folio,
                'token'    => csrf_hash()
            ]);
        } catch (Throwable $ex) {
            $this->refundsModel->db->transRollback();
            return $this->response->setJSON([
                'error'   => true,
                'message' => $ex->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function delete($id)
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName   = user()->username;
        $idUser     = user()->id;
        $empresas   = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($empresas) === 0 ? [0] : array_column($empresas, "id");

        $registro = $this->refundsModel
            ->whereIn("idEmpresa", $empresasID)
            ->where("id", $id)
            ->first();

        if (!$registro) {
            return $this->failNotFound('Reembolso no encontrado o sin permisos.');
        }

        if ((int) ($registro['sapSynced'] ?? 0) === 1) {
            return $this->failValidationErrors('El reembolso ya fue enviado a SAP y no puede eliminarse localmente.');
        }

        $this->refundsModel->db->transBegin();

        $this->refundsDetailsModel->where('idRefund', $id)->delete();
        $this->refundsDetailsModel->purgeDeleted();

        $this->refundsModel->delete($id);
        $this->refundsModel->purgeDeleted();

        $this->log->save([
            "description" => "Se eliminó reembolso local: " . json_encode($registro),
            "user"        => $userName,
            "module"      => "REFUNDS",
            "idMovModule" => $id
        ]);

        $this->refundsModel->db->transCommit();

        return $this->respondDeleted($registro, 'Reembolso eliminado correctamente.');
    }

    // =========================================================================
    // 3. ENVÍO TRANSACCIONAL A SAP (CON UDFs EXACTOS DE InvoiceDI Y PolicyDI)
    // =========================================================================

    private function requestUdtByKey(string $method, string $udtEntity, $code, array $payload = []): array
    {
        $codeClean = trim((string) $code, "'\" ");

        if (is_numeric($codeClean)) {
            $intKey = (int) $codeClean;
            try {
                return $this->requestServiceLayer($method, "{$udtEntity}({$intKey})", $payload);
            } catch (Throwable $e) {
                if (stripos($e->getMessage(), 'query syntax') !== false || stripos($e->getMessage(), 'Bad Request') !== false) {
                    return $this->requestServiceLayer($method, "{$udtEntity}('{$codeClean}')", $payload);
                }
                throw $e;
            }
        }

        return $this->requestServiceLayer($method, "{$udtEntity}('{$codeClean}')", $payload);
    }

    public function sendToSAP()
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'No se ha iniciado sesión.',
                'token'   => csrf_hash()
            ]);
        }

        $conn         = null;
        $idRefund     = 0;
        $createdInSAP = [
            'vodeCodes'         => [],
            'invoices'          => [],
            'journals'          => [],
            'voucherCode'       => null,
            'voucherWasCreated' => false,
        ];

        try {
            $userName = user()->username;
            $idRefund = (int) $this->request->getPost('idRefund');
            $uuid     = trim((string) $this->request->getPost('UUID'));

            $refund = $idRefund > 0
                ? $this->refundsModel->find($idRefund)
                : $this->refundsModel->where('UUID', $uuid)->first();

            if (!$refund) {
                throw new Exception('No se encontró el reembolso en la base de datos local.');
            }
            $idRefund = (int) $refund['id'];

            if ((int) ($refund['sapSynced'] ?? 0) === 1 && !empty($refund['code'])) {
                throw new Exception("Este reembolso (Folio: {$refund['U_Folio']}) ya fue sincronizado completamente con SAP (Code: {$refund['code']}).");
            }

            $details = $this->refundsDetailsModel
                ->where('idRefund', $idRefund)
                ->where('deleted_at', null)
                ->orderBy('id', 'ASC')
                ->findAll();

            if (empty($details)) {
                throw new Exception('El reembolso no contiene líneas de detalle para enviar a SAP.');
            }

            // 1. Validar enlace de Sucursal Local con BPLId de SAP
            $enlaceSucursal = $this->linkSapBranchOffice
                ->where('idEmpresa', $refund['idEmpresa'])
                ->where('idBranchOffice', $refund['idSucursal'])
                ->where('deleted_at', null)
                ->first();

            if (!$enlaceSucursal || $enlaceSucursal['idBranchOfficeSAP'] === null || $enlaceSucursal['idBranchOfficeSAP'] === '') {
                throw new Exception('La sucursal seleccionada no está vinculada con SAP (BPLId). Configúrela en Enlace de Sucursales SAP.');
            }

            $bplIdSAP = (int) $enlaceSucursal['idBranchOfficeSAP'];

            // 2. Conectar a SAP vía ODBC (SOLO LECTURA)
            $conn       = $this->connectODBC();
            $attachPath = $this->getAttachPathODBC($conn);

            $accountCredit = trim((string) ($refund['accountCredit'] ?? ''));
            if (empty($accountCredit) || $accountCredit === '0') {
                $configKey     = empty($refund['U_CodeMov']) ? 'GLO_DEU_REEM' : 'MQ_DEUDORESVIAT';
                $accountCredit = $this->getConfigValueODBC($conn, $configKey);
            }

            // =================================================================
            // FASE 1: PRE-VALIDAR Y PREPARAR TODOS LOS PAYLOADS EN SECO
            // =================================================================
            $preparedXmlPayloads = [];
            $notasParaPoliza     = [];
            $totalCalculado      = 0.0;

            foreach ($details as $idx => $line) {
                $lineNum = $idx + 1;
                if ($line['U_Type'] === 'XML') {
                    $preparedXmlPayloads[$lineNum] = $this->prepararYValidarLineaXml(
                        $conn,
                        $attachPath,
                        $refund,
                        $line,
                        $lineNum,
                        $bplIdSAP,
                        $userName
                    );
                    $totalCalculado += (float) $line['U_Total'];
                } else {
                    $notasParaPoliza[] = $line;
                }
            }

            $preparedNotaPoliza = null;
            if (!empty($notasParaPoliza)) {
                $preparedNotaPoliza = $this->prepararYValidarNotasPoliza(
                    $conn,
                    $attachPath,
                    $refund,
                    $notasParaPoliza,
                    $bplIdSAP,
                    $accountCredit
                );
                $totalCalculado += (float) $preparedNotaPoliza['totalNotas'];
            }

            $refund['total']   = round($totalCalculado, 2);
            $refund['U_Total'] = round($totalCalculado, 2);

            // =================================================================
            // FASE 2: RESOLVER O CREAR ENCABEZADO @QSYS_GLO_VOUC CON TOTAL FINAL
            // =================================================================
            $headerResolution = $this->resolverEncabezadoVoucherSAP($conn, $refund, $bplIdSAP, $userName);
            $nextVoucherCode  = $headerResolution['code'];
            $folioFinal       = $headerResolution['folio'];

            $createdInSAP['voucherCode']       = (string) $nextVoucherCode;
            $createdInSAP['voucherWasCreated'] = $headerResolution['wasCreated'];

            if ($folioFinal !== $refund['U_Folio']) {
                $refund['U_Folio'] = $folioFinal;
                $this->refundsModel->update($idRefund, ['U_Folio' => $folioFinal]);
            }

            $lineUpdatesDB = [];

            // 2B. Enviar Facturas XML a PurchaseInvoices y registrar en @QSYS_GLO_VODE
            foreach ($details as $idx => $line) {
                $lineNum = $idx + 1;
                if ($line['U_Type'] !== 'XML') {
                    continue;
                }

                $prep        = $preparedXmlPayloads[$lineNum];
                $rutasAnexos = $this->subirAnexosSAP($attachPath, $prep['uuid'], $prep['xmlContent'], $prep['pdfContent']);

                $prep['payload']['U_ArchivoXML']       = $rutasAnexos['rutaXML'];
                $prep['payload']['U_ArchivoPDF']       = $rutasAnexos['rutaPDF'];
                $prep['payload']['U_MQ_OrigenFol']     = $folioFinal;
                $prep['payload']['U_MQ_OrigenFol_Det'] = (string) $lineNum;
                $prep['payload']['U_GLO_ObjTUG']       = 'frmReceipts';
                $prep['payload']['Comments']           = mb_substr((string) ($line['U_Coments'] ?: "Reembolso {$folioFinal}"), 0, 254);

                $sapInvoice = $this->requestServiceLayer('POST', 'PurchaseInvoices', $prep['payload']);
                $docEntry   = (int) ($sapInvoice['DocEntry'] ?? 0);
                $docNum     = (int) ($sapInvoice['DocNum'] ?? $docEntry);

                if ($docEntry > 0) {
                    $createdInSAP['invoices'][] = $docEntry;
                }

                $nextVodeCode = $this->getNextUdtCodeODBC($conn, '@QSYS_GLO_VODE');
                $vodePayload  = [
                    'Code'          => (string) $nextVodeCode,
                    'Name'          => (string) $nextVodeCode,
                    'U_NA'          => $line['U_NA'] ?? 'N',
                    'U_Line'        => (string) $lineNum,
                    'U_Date'        => date('Y-m-d', strtotime($line['U_Date'])),
                    'U_Type'        => 'XML',
                    'U_CodeVoucher' => (string) $nextVoucherCode,
                    'U_DocEntry'    => (string) $docEntry,
                    'U_DocNum'      => (string) $docNum,
                    'U_Provider'    => (string) $prep['cardCode'],
                    'U_Status'      => 'Abierto',
                    'U_Coments'     => mb_substr((string) ($line['U_Coments'] ?? ''), 0, 128),
                    'U_Subtotal'    => (float) $line['U_Subtotal'],
                    'U_IVA'         => (float) $line['U_IVA'],
                    'U_RetIVA'      => (float) $line['U_RetIVA'],
                    'U_ISR'         => (float) $line['U_ISR'],
                    'U_IEPS'        => (float) $line['U_IEPS'],
                    'U_Total'       => (float) $line['U_Total'],
                    'U_Coment'      => mb_substr((string) ($line['U_Coment'] ?? ''), 0, 128),
                    'U_UserCode'    => $userName
                ];

                $resVode      = $this->requestServiceLayer('POST', 'U_QSYS_GLO_VODE', $vodePayload);
                $realVodeCode = (string) ($resVode['Code'] ?? $nextVodeCode);
                $createdInSAP['vodeCodes'][] = $realVodeCode;

                $lineUpdatesDB[$idx] = [
                    'id'   => $line['id'],
                    'data' => [
                        'code'          => $realVodeCode,
                        'name'          => $realVodeCode,
                        'U_CodeVoucher' => (string) $nextVoucherCode,
                        'U_DocEntry'    => (string) $docEntry,
                        'U_DocNum'      => (string) $docNum,
                        'U_Provider'    => (string) $prep['cardCode'],
                        'xmlFile'       => $rutasAnexos['rutaXML'],
                        'pdfFile'       => $rutasAnexos['rutaPDF']
                    ]
                ];
            }

            // 2C. Enviar Póliza de Notas a JournalEntries (con estructura exacta de PolicyDI.cs) y registrar en @QSYS_GLO_VODE
            if ($preparedNotaPoliza !== null) {
                $refPoliza = ((int) ($refund['U_TypeVoucher'] ?? 1) === 2 && !empty($refund['U_CodeMov']))
                    ? $refund['U_CodeMov']
                    : $folioFinal;

                $preparedNotaPoliza['payload']['Reference'] = $refPoliza;
                foreach ($preparedNotaPoliza['payload']['JournalEntryLines'] as &$jLine) {
                    if (empty($jLine['U_GLO_CodeMov'])) {
                        $jLine['U_GLO_CodeMov'] = $refPoliza;
                    }
                }
                unset($jLine);

                $polizaResult = $this->requestServiceLayer('POST', 'JournalEntries', $preparedNotaPoliza['payload']);
                $jdtNum       = (int) ($polizaResult['JdtNum'] ?? $polizaResult['Number'] ?? 0);

                if ($jdtNum > 0) {
                    $createdInSAP['journals'][] = $jdtNum;
                }

                $nextVodeCode = $this->getNextUdtCodeODBC($conn, '@QSYS_GLO_VODE');
                $totalNotas   = $preparedNotaPoliza['totalNotas'];
                $lineNumNota  = count($details);

                $vodeNotaPayload = [
                    'Code'          => (string) $nextVodeCode,
                    'Name'          => (string) $nextVodeCode,
                    'U_NA'          => 'N',
                    'U_Line'        => (string) $lineNumNota,
                    'U_Date'        => date('Y-m-d', strtotime($refund['U_Date'])),
                    'U_Type'        => 'Nota',
                    'U_CodeVoucher' => (string) $nextVoucherCode,
                    'U_DocEntry'    => (string) $jdtNum,
                    'U_DocNum'      => (string) $jdtNum,
                    'U_Provider'    => mb_substr((string) ($notasParaPoliza[0]['U_Provider'] ?? 'NOTA'), 0, 64),
                    'U_Status'      => 'Cerrado',
                    'U_Coments'     => mb_substr((string) ($notasParaPoliza[0]['U_Coments'] ?? ''), 0, 128),
                    'U_Subtotal'    => (float) $totalNotas,
                    'U_IVA'         => 0.0,
                    'U_RetIVA'      => 0.0,
                    'U_ISR'         => 0.0,
                    'U_IEPS'        => 0.0,
                    'U_Total'       => (float) $totalNotas,
                    'U_Coment'      => '',
                    'U_UserCode'    => $userName
                ];

                $resVodeNota  = $this->requestServiceLayer('POST', 'U_QSYS_GLO_VODE', $vodeNotaPayload);
                $realVodeNota = (string) ($resVodeNota['Code'] ?? $nextVodeCode);
                $createdInSAP['vodeCodes'][] = $realVodeNota;

                foreach ($details as $idx => $dItem) {
                    if ($dItem['U_Type'] === 'Nota') {
                        $lineUpdatesDB[$idx] = [
                            'id'   => $dItem['id'],
                            'data' => [
                                'code'          => $realVodeNota,
                                'name'          => $realVodeNota,
                                'U_CodeVoucher' => (string) $nextVoucherCode,
                                'U_DocEntry'    => (string) $jdtNum,
                                'U_DocNum'      => (string) $jdtNum,
                                'U_Status'      => 'Cerrado'
                            ]
                        ];
                    }
                }
            }

            odbc_close($conn);
            $conn = null;

            // =================================================================
            // FASE 3: GUARDAR RESULTADOS DEFINITIVOS EN MARIADB (INCLUYENDO JSON LIST)
            // =================================================================
            $this->refundsModel->db->transBegin();

            $listJsonActual = !empty($refund['list']) ? json_decode($refund['list'], true) : [];

            foreach ($lineUpdatesDB as $idxUpd => $upd) {
                $this->refundsDetailsModel->update($upd['id'], $upd['data']);

                if (isset($listJsonActual[$idxUpd])) {
                    $listJsonActual[$idxUpd]['code']          = $upd['data']['code'] ?? '';
                    $listJsonActual[$idxUpd]['U_CodeVoucher'] = $upd['data']['U_CodeVoucher'] ?? '';
                    $listJsonActual[$idxUpd]['U_DocEntry']    = $upd['data']['U_DocEntry'] ?? '';
                    $listJsonActual[$idxUpd]['U_DocNum']      = $upd['data']['U_DocNum'] ?? '';
                    if (!empty($upd['data']['U_Status'])) {
                        $listJsonActual[$idxUpd]['U_Status'] = $upd['data']['U_Status'];
                    }
                }
            }

            $this->refundsModel->update($idRefund, [
                'code'      => (string) $nextVoucherCode,
                'name'      => (string) $nextVoucherCode,
                'U_Folio'   => $folioFinal,
                'U_Branch'  => (string) $bplIdSAP,
                'U_Total'   => round($totalCalculado, 2),
                'total'     => round($totalCalculado, 2),
                'list'      => json_encode($listJsonActual, JSON_UNESCAPED_UNICODE),
                'sapSynced' => 1
            ]);

            $this->refundsModel->db->transCommit();

            $this->log->save([
                'description' => "Reembolso sincronizado con SAP exitosamente. Folio: {$folioFinal}, Code SAP: {$nextVoucherCode}, Total: {$totalCalculado}",
                'user'        => $userName,
                'module'      => 'REFUNDS_SAP',
                'idMovModule' => $idRefund
            ]);

            return $this->response->setJSON([
                'error'       => false,
                'message'     => "Reembolso {$folioFinal} y todos sus documentos fueron creados correctamente en SAP Business One (Code: {$nextVoucherCode}).",
                'codeVoucher' => $nextVoucherCode,
                'folio'       => $folioFinal,
                'token'       => csrf_hash()
            ]);

        } catch (Throwable $e) {
            if ($conn) {
                @odbc_close($conn);
            }

            $rollbackLog = $this->rollbackSapChanges($createdInSAP, $idRefund);

            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage() . ($rollbackLog ? " | Rollback aplicado: {$rollbackLog}" : ''),
                'token'   => csrf_hash()
            ]);
        }
    }

    private function resolverEncabezadoVoucherSAP($conn, array $refund, int $bplIdSAP, string $userName): array
    {
        $folioActual    = trim((string) $refund['U_Folio']);
        $area           = trim((string) $refund['U_Area']);
        $sapTypeVoucher = ((int) ($refund['U_TypeVoucher'] ?? 1) === 2) ? 1 : 0;

        $folioClean = str_replace("'", "''", $folioActual);
        $sqlCheck   = "
            SELECT T0.\"Code\",
                   (SELECT COUNT(*) FROM \"@QSYS_GLO_VODE\" T1 WHERE T1.\"U_CodeVoucher\" = T0.\"Code\") AS \"CantLineas\"
            FROM \"@QSYS_GLO_VOUC\" T0
            WHERE T0.\"U_Folio\" = '{$folioClean}'
            LIMIT 1
        ";

        $rsCheck  = odbc_exec($conn, $sqlCheck);
        $rowCheck = $rsCheck ? odbc_fetch_array($rsCheck) : null;
        if ($rsCheck) {
            odbc_free_result($rsCheck);
        }

        // CASO 1: Ya existe en @QSYS_GLO_VOUC con 0 líneas en @QSYS_GLO_VODE -> Reutilizar su Code
        if ($rowCheck && !empty($rowCheck['Code']) && (int) ($rowCheck['CantLineas'] ?? 0) === 0) {
            $existingCode = trim($this->toUtf8($rowCheck['Code']));

            $patchPayload = [
                'U_Area'        => $area,
                'U_Employee'    => (string) $refund['U_Employee'],
                'U_Coments'     => mb_substr((string) ($refund['U_Coments'] ?? ''), 0, 64),
                'U_Date'        => date('Y-m-d', strtotime($refund['U_Date'])),
                'U_Total'       => round((float) $refund['total'], 2),
                'U_Status'      => (int) ($refund['U_Status'] ?? 1),
                'U_UserCode'    => $userName,
                'U_CodeMov'     => (string) ($refund['U_CodeMov'] ?? ''),
                'U_TypeVoucher' => $sapTypeVoucher,
                'U_Branch'      => (string) $bplIdSAP
            ];

            try {
                $this->requestUdtByKey('PATCH', 'U_QSYS_GLO_VOUC', $existingCode, $patchPayload);
            } catch (Throwable $ignorePatch) {
            }

            return [
                'code'       => $existingCode,
                'folio'      => $folioActual,
                'wasCreated' => false
            ];
        }

        // CASO 2: El U_Folio ya existe en SAP con líneas -> Calcular siguiente folio libre
        if ($rowCheck && !empty($rowCheck['Code']) && (int) ($rowCheck['CantLineas'] ?? 0) > 0) {
            if (!empty($refund['U_CodeMov'])) {
                throw new Exception("El comprobante de viáticos con folio '{$folioActual}' ya fue registrado previamente en SAP (Code: {$rowCheck['Code']}).");
            }
            $nextFreeNum = $this->getNextFolioByArea((int) $refund['idEmpresa'], $area, (int) $refund['U_TypeVoucher']);
            $folioActual = "CG_{$area}_{$nextFreeNum}";
        }

        // CASO 3: Insertar nuevo encabezado en @QSYS_GLO_VOUC
        $nextVoucherCode = $this->getNextUdtCodeODBC($conn, '@QSYS_GLO_VOUC');

        $voucherPayload = [
            'Code'          => (string) $nextVoucherCode,
            'Name'          => (string) $nextVoucherCode,
            'U_Folio'       => $folioActual,
            'U_Area'        => $area,
            'U_Employee'    => (string) $refund['U_Employee'],
            'U_Coments'     => mb_substr((string) ($refund['U_Coments'] ?? ''), 0, 64),
            'U_Date'        => date('Y-m-d', strtotime($refund['U_Date'])),
            'U_Total'       => round((float) $refund['total'], 2),
            'U_Status'      => (int) ($refund['U_Status'] ?? 1),
            'U_UserCode'    => $userName,
            'U_CodeMov'     => (string) ($refund['U_CodeMov'] ?? ''),
            'U_TypeVoucher' => $sapTypeVoucher,
            'U_Branch'      => (string) $bplIdSAP
        ];

        $resVouc  = $this->requestServiceLayer('POST', 'U_QSYS_GLO_VOUC', $voucherPayload);
        $realCode = (string) ($resVouc['Code'] ?? $nextVoucherCode);

        return [
            'code'       => $realCode,
            'folio'      => $folioActual,
            'wasCreated' => true
        ];
    }

    private function rollbackSapChanges(array $createdInSAP, int $idRefund): string
    {
        $acciones = [];

        if (!empty($createdInSAP['vodeCodes'])) {
            foreach ($createdInSAP['vodeCodes'] as $vodeCode) {
                try {
                    $this->requestUdtByKey('DELETE', 'U_QSYS_GLO_VODE', $vodeCode);
                    $acciones[] = "VODE {$vodeCode} eliminado";
                } catch (Throwable $ignore) {
                }
            }
        }

        if (!empty($createdInSAP['invoices'])) {
            foreach ($createdInSAP['invoices'] as $docEntry) {
                try {
                    $this->requestServiceLayer('POST', "PurchaseInvoices({$docEntry})/Cancel", []);
                    $acciones[] = "Factura {$docEntry} cancelada";
                } catch (Throwable $ignore) {
                }
            }
        }

        if (!empty($createdInSAP['journals'])) {
            foreach ($createdInSAP['journals'] as $jdtNum) {
                try {
                    $this->requestServiceLayer('POST', "JournalEntries({$jdtNum})/Cancel", []);
                    $acciones[] = "Póliza {$jdtNum} cancelada";
                } catch (Throwable $ignore) {
                }
            }
        }

        if (!empty($createdInSAP['voucherCode']) && !empty($createdInSAP['voucherWasCreated'])) {
            $voucCode = $createdInSAP['voucherCode'];
            try {
                $this->requestUdtByKey('DELETE', 'U_QSYS_GLO_VOUC', $voucCode);
                $acciones[] = "Encabezado VOUC {$voucCode} eliminado";
            } catch (Throwable $ignore) {
            }
        }

        if ($idRefund > 0) {
            try {
                $this->refundsModel->update($idRefund, [
                    'code'      => null,
                    'name'      => null,
                    'sapSynced' => 0
                ]);
                $this->refundsDetailsModel
                    ->where('idRefund', $idRefund)
                    ->set([
                        'code'          => null,
                        'name'          => null,
                        'U_CodeVoucher' => null,
                        'U_DocEntry'    => null,
                        'U_DocNum'      => null
                    ])
                    ->update();
            } catch (Throwable $ignore) {
            }
        }

        return implode(', ', $acciones);
    }

    // =========================================================================
    // 4. FLUJO DE AUTORIZACIÓN, NOTIFICACIÓN Y RECHAZO (100% SERVICE LAYER)
    // =========================================================================

    public function notifyVoucher()
    {
        return $this->changeVoucherStatusSAP(2, [1], 'notificado al área');
    }

    public function authorizeVoucher()
    {
        return $this->changeVoucherStatusSAP(4, [2], 'autorizado');
    }

    public function rejectVoucher()
    {
        return $this->changeVoucherStatusSAP(1, [2, 3], 'rechazado');
    }

    private function changeVoucherStatusSAP(int $newStatus, array $allowedCurrentStatuses, string $actionLabel)
    {
        $conn = null;
        try {
            $userName = user()->username;
            $input    = $this->getSafeInput();

            $code  = trim((string) ($input['code'] ?? ''));
            $folio = trim((string) ($input['folio'] ?? ''));

            if ($code === '') {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => 'Falta el parámetro "code"',
                    'token'   => csrf_hash()
                ]);
            }

            $conn      = $this->connectODBC();
            $codeClean = str_replace("'", "''", $code);
            $sqlCheck  = "SELECT \"U_Status\", \"U_Folio\" FROM \"@QSYS_GLO_VOUC\" WHERE \"Code\" = '{$codeClean}'";
            $rsCheck   = odbc_exec($conn, $sqlCheck);

            if (!$rsCheck || !($row = odbc_fetch_array($rsCheck))) {
                odbc_close($conn);
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'error'   => 'Comprobante no encontrado en SAP',
                    'token'   => csrf_hash()
                ]);
            }

            $currentStatus = (int) ($row['U_Status'] ?? 0);
            $folioActual   = $this->toUtf8($row['U_Folio'] ?? $folio);
            odbc_free_result($rsCheck);
            odbc_close($conn);
            $conn = null;

            if (!in_array($currentStatus, $allowedCurrentStatuses, true)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error'   => "Estatus actual ({$currentStatus}) inválido para realizar esta acción.",
                    'token'   => csrf_hash()
                ]);
            }

            $this->requestUdtByKey('PATCH', 'U_QSYS_GLO_VOUC', $codeClean, [
                'U_Status' => $newStatus
            ]);

            $this->refundsModel
                ->where('code', $code)
                ->set(['U_Status' => $newStatus])
                ->update();

            $this->log->save([
                'description' => "Usuario {$userName} cambió a {$actionLabel} (Status {$newStatus}) el comprobante {$folioActual} (Code: {$code})",
                'user'        => $userName,
                'module'      => 'REFUNDS_AUTH'
            ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => "Comprobante {$folioActual} {$actionLabel} correctamente",
                'code'    => $code,
                'folio'   => $folioActual,
                'status'  => $newStatus,
                'token'   => csrf_hash()
            ]);
        } catch (Throwable $e) {
            if ($conn) {
                @odbc_close($conn);
            }
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    // =========================================================================
    // 5. PAGOS DE REEMBOLSOS VÍA SERVICE LAYER (DoPayments y Pago a Empleado)
    // =========================================================================

    public function payRefundInvoicesSAP()
    {
        $conn = null;
        try {
            $userName = user()->username;
            $idRefund = (int) $this->request->getPost('idRefund');

            $refund = $this->refundsModel->find($idRefund);
            if (!$refund || empty($refund['code'])) {
                throw new Exception('El reembolso debe estar sincronizado con SAP antes de generar los pagos de sus facturas.');
            }

            $xmlLines = $this->refundsDetailsModel
                ->where('idRefund', $idRefund)
                ->where('U_Type', 'XML')
                ->where('U_Status', 'Abierto')
                ->findAll();

            if (empty($xmlLines)) {
                throw new Exception('No existen facturas XML con estatus Abierto por saldar en este reembolso.');
            }

            $conn = $this->connectODBC();

            $transferAccount = trim((string) ($refund['accountCredit'] ?? ''));
            if (empty($transferAccount) || $transferAccount === '0') {
                $transferAccount = $this->getConfigValueODBC($conn, 'GLO_ACCPYMNT');
                if (empty($transferAccount)) {
                    $transferAccount = $this->getConfigValueODBC($conn, 'GLO_DEU_REEM');
                }
            }

            if (empty($transferAccount)) {
                throw new Exception('No se encontró la cuenta puente para el pago de facturas (GLO_ACCPYMNT / GLO_DEU_REEM).');
            }

            $transferAccount = $this->resolveSapAccountCode($conn, $transferAccount, 'Cuenta Puente de Pagos');

            $bplId        = (int) ($refund['U_Branch'] ?? 1);
            $pagosCreados = 0;

            foreach ($xmlLines as $line) {
                $docEntryFactura = (int) $line['U_DocEntry'];
                if ($docEntryFactura <= 0) {
                    continue;
                }

                $paymentPayload = [
                    'DocType'           => 'rSupplier',
                    'DocDate'           => date('Y-m-d', strtotime($refund['U_Date'])),
                    'CardCode'          => $line['U_Provider'],
                    'TransferAccount'   => $transferAccount,
                    'TransferSum'       => (float) $line['U_Total'],
                    'TransferDate'      => date('Y-m-d', strtotime($refund['U_Date'])),
                    'BPLID'             => $bplId,
                    'U_GLO_PaymentType' => 'GLREM',
                    'U_GLO_CodeMov'     => $refund['U_Folio'],
                    'U_GLO_CostCenter'  => $refund['U_Area'],
                    'U_FZ_AuxiliarType' => '1',
                    'U_FZ_Auxiliar'     => (string) $refund['U_Employee'],
                    'PaymentInvoices'   => [
                        [
                            'LineNum'     => 0,
                            'DocEntry'    => $docEntryFactura,
                            'SumApplied'  => (float) $line['U_Total'],
                            'InvoiceType' => 'it_PurchaseInvoice'
                        ]
                    ]
                ];

                $resPago = $this->requestServiceLayer('POST', 'VendorPayments', $paymentPayload);

                if (!empty($line['code'])) {
                    $this->requestUdtByKey('PATCH', 'U_QSYS_GLO_VODE', $line['code'], [
                        'U_Status' => 'Cerrado'
                    ]);
                }

                $this->refundsDetailsModel->update($line['id'], [
                    'U_Status'           => 'Cerrado',
                    'sapPaymentDocEntry' => (int) ($resPago['DocEntry'] ?? 0)
                ]);

                $pagosCreados++;
            }

            odbc_close($conn);

            $this->log->save([
                'description' => "Se generaron {$pagosCreados} pagos de facturas para el reembolso {$refund['U_Folio']}",
                'user'        => $userName,
                'module'      => 'REFUNDS_PAYMENTS',
                'idMovModule' => $idRefund
            ]);

            return $this->response->setJSON([
                'error'   => false,
                'message' => "Proceso de pagos realizado con éxito ({$pagosCreados} facturas saldadas).",
                'token'   => csrf_hash()
            ]);
        } catch (Throwable $e) {
            if ($conn) {
                @odbc_close($conn);
            }
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function payRefundToEmployeeSAP()
    {
        $conn = null;
        try {
            $userName    = user()->username;
            $idRefund    = (int) $this->request->getPost('idRefund');
            $bankAccount = trim((string) $this->request->getPost('bankAccount'));

            $refund = $this->refundsModel->find($idRefund);
            if (!$refund) {
                throw new Exception('Reembolso no encontrado.');
            }

            if ((int) $refund['U_Status'] !== 4) {
                throw new Exception('El reembolso seleccionado no está autorizado (Se requiere estatus = 4).');
            }

            if (empty($bankAccount) || $bankAccount === '0') {
                throw new Exception('Seleccione la cuenta bancaria para emitir el pago.');
            }

            $conn = $this->connectODBC();

            $configLineAcc = ((int) $refund['U_TypeVoucher'] === 1) ? 'GLO_DEU_REEM_PAGO' : 'GLO_DEU_REEM';
            $lineAccount   = $this->getConfigValueODBC($conn, $configLineAcc);
            if (empty($lineAccount)) {
                $lineAccount = $refund['accountCredit'];
            }

            if (empty($lineAccount) || $lineAccount === '0') {
                throw new Exception('La cuenta contable del reembolso no tiene valor configurado.');
            }

            $bankAccount = $this->resolveSapAccountCode($conn, $bankAccount, 'Cuenta Bancaria');
            $lineAccount = $this->resolveSapAccountCode($conn, $lineAccount, 'Cuenta Contable del Reembolso');

            odbc_close($conn);
            $conn = null;

            $paymentType = ((int) $refund['U_TypeVoucher'] === 1) ? 'GLREM' : 'GLREG';

            $payload = [
                'DocType'           => 'rAccount',
                'DocDate'           => date('Y-m-d'),
                'CardName'          => $refund['empName'] ?: "Empleado {$refund['U_Employee']}",
                'TransferAccount'   => $bankAccount,
                'TransferSum'       => (float) $refund['U_Total'],
                'TransferDate'      => date('Y-m-d'),
                'BPLID'             => (int) $refund['U_Branch'],
                'U_GLO_PaymentType' => $paymentType,
                'U_GLO_CodeMov'     => $refund['U_Folio'],
                'U_GLO_CostCenter'  => $refund['U_Area'],
                'U_FZ_AuxiliarType' => '1',
                'U_FZ_Auxiliar'     => (string) $refund['U_Employee'],
                'PaymentAccounts'   => [
                    [
                        'AccountCode' => $lineAccount,
                        'SumPaid'     => (float) $refund['U_Total'],
                        'Decription'  => "Pago de reembolso {$refund['U_Folio']}"
                    ]
                ]
            ];

            $sapPayment = $this->requestServiceLayer('POST', 'VendorPayments', $payload);
            $docEntry   = (int) ($sapPayment['DocEntry'] ?? 0);

            $this->refundsModel->update($idRefund, [
                'sapPaymentDocEntry' => $docEntry,
                'U_Status'           => 5
            ]);

            if (!empty($refund['code'])) {
                $this->requestUdtByKey('PATCH', 'U_QSYS_GLO_VOUC', $refund['code'], [
                    'U_Status' => 5
                ]);
            }

            return $this->response->setJSON([
                'error'    => false,
                'message'  => "Pago al empleado generado correctamente en SAP (DocNum: " . ($sapPayment['DocNum'] ?? $docEntry) . ").",
                'docEntry' => $docEntry,
                'token'    => csrf_hash()
            ]);
        } catch (Throwable $e) {
            if ($conn) {
                @odbc_close($conn);
            }
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    // =========================================================================
    // 6. PREPARACIÓN Y VALIDACIÓN EN SECO DE FACTURAS XML Y PÓLIZAS DE NOTAS
    // =========================================================================

    private function prepararYValidarLineaXml($conn, string $attachPath, array $refund, array $line, int $lineNum, int $bplIdSAP, string $userName): array
    {
        $uuid = trim((string) ($line['UUID'] ?? ''));
        if (empty($uuid)) {
            throw new Exception("La línea #{$lineNum} es de tipo XML pero no tiene un UUID asociado.");
        }

        $datosXML = $this->xml->where('uuidTimbre', $uuid)->first();
        if (!$datosXML || empty($datosXML['archivoXML'])) {
            throw new Exception("No se encontró el archivo XML para el UUID '{$uuid}' en la línea #{$lineNum}.");
        }

        $xmlContent  = $datosXML['archivoXML'];
        $xmlLimpio   = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($xmlContent);
        $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xmlLimpio);
        $emisor      = $comprobante->searchNode('cfdi:Emisor');
        $rfcEmisor   = trim((string) ($emisor['Rfc'] ?? $datosXML['rfcEmisor'] ?? ''));
        $folioXML    = trim((string) ($comprobante['Folio'] ?? substr($uuid, -6)));
        if ($folioXML === '' || $folioXML === '0') {
            $folioXML = substr($uuid, -6);
        }

        // 1. Validar duplicidad de UUID en OPCH vía ODBC
        $uuidClean     = str_replace("'", "''", strtoupper($uuid));
        $sqlExisteUUID = "
            SELECT \"DocEntry\", \"DocNum\"
            FROM OPCH
            WHERE \"CANCELED\" = 'N'
              AND (UPPER(TRIM(\"U_FolioFiscal\")) = '{$uuidClean}' OR UPPER(TRIM(\"U_UDF_UUID\")) = '{$uuidClean}')
            LIMIT 1
        ";
        $rsExiste = odbc_exec($conn, $sqlExisteUUID);
        if ($rsExiste && ($rowExiste = odbc_fetch_array($rsExiste))) {
            odbc_free_result($rsExiste);
            throw new Exception("El CFDI '{$uuid}' (Línea #{$lineNum}) ya fue registrado en SAP (DocNum: {$rowExiste['DocNum']}).");
        }
        if ($rsExiste) {
            odbc_free_result($rsExiste);
        }

        // 2. Buscar CardCode del proveedor por RFC en OCRD
        $rfcClean = str_replace("'", "''", strtoupper($rfcEmisor));
        $sqlSocio = "
            SELECT \"CardCode\"
            FROM OCRD
            WHERE UPPER(TRIM(\"LicTradNum\")) = '{$rfcClean}'
              AND \"CardType\" = 'S' AND \"validFor\" = 'Y' AND \"frozenFor\" = 'N'
            ORDER BY \"CardCode\" ASC LIMIT 1
        ";
        $rsSocio  = odbc_exec($conn, $sqlSocio);
        $rowSocio = $rsSocio ? odbc_fetch_array($rsSocio) : null;
        if ($rsSocio) {
            odbc_free_result($rsSocio);
        }

        if (!$rowSocio || empty($rowSocio['CardCode'])) {
            throw new Exception("El proveedor con RFC '{$rfcEmisor}' (Línea #{$lineNum}) no existe o está inactivo en SAP (OCRD).");
        }
        $cardCodeSAP = trim($this->toUtf8($rowSocio['CardCode']));

        // 3. Obtener los conceptos multilínea del XML
        $rawOriginalList = !empty($refund['list']) ? json_decode($refund['list'], true) : [];
        $conceptLines    = $rawOriginalList[$lineNum - 1]['conceptLines'] ?? [];

        if (empty($conceptLines)) {
            $conceptLines = [[
                'itemCode'    => $line['codeProduct'] ?? $line['itemCode'] ?? '',
                'description' => $line['description'] ?? $line['U_Coments'] ?? '',
                'cant'        => (float) ($line['cant'] ?? 1),
                'price'       => (float) ($line['price'] ?? $line['U_Subtotal'] ?? 0),
                'porcentTax'  => (float) ($line['porcentTax'] ?? 16),
                'ieps'        => (float) ($line['U_IEPS'] ?? 0),
                'adminOper'   => $line['adminOper'] ?? 'A',
                'project'     => $line['project'] ?? '',
                'whsCode'     => $line['whsCode'] ?? '',
                'account'     => $line['account'] ?? '',
                'dim1'        => $line['dim1'] ?? $refund['U_Area'],
                'dim2'        => $line['dim2'] ?? '',
                'dim3'        => $line['dim3'] ?? '',
                'dim4'        => $line['dim4'] ?? '',
                'dim5'        => $line['dim5'] ?? '',
            ]];
        }

        $documentLines    = [];
        $iepsTotalFactura = 0.0;

        foreach ($conceptLines as $cIdx => $cLine) {
            $numCon   = $cIdx + 1;
            $itemCode = trim((string) ($cLine['itemCode'] ?? ''));
            if (empty($itemCode) || $itemCode === '0') {
                throw new Exception("El concepto #{$numCon} del CFDI '{$uuid}' no tiene un Artículo SAP (ItemCode) asignado.");
            }

            $porcentTax = (float) ($cLine['porcentTax'] ?? 0);
            if ($porcentTax > 0 && $porcentTax < 1) {
                $porcentTax = round($porcentTax * 100, 2);
            }
            $taxCode = $this->resolveSapTaxCode($conn, $porcentTax, $itemCode, $cardCodeSAP);

            $dim1 = !empty($cLine['dim1']) && $cLine['dim1'] !== '0' ? $cLine['dim1'] : $refund['U_Area'];

            $whsCodeValido = $this->resolveSapWarehouse(
                $conn,
                trim((string) ($cLine['whsCode'] ?? '')),
                (string) $dim1,
                $itemCode,
                $bplIdSAP
            );

            $adminOper = !empty($cLine['adminOper']) ? strtoupper(trim((string) $cLine['adminOper'])) : 'A';

            $docLine = [
                'ItemCode'        => $itemCode,
                'ItemDescription' => mb_substr((string) ($cLine['description'] ?? ''), 0, 100),
                'Quantity'        => (float) ($cLine['cant'] ?? 1),
                'UnitPrice'       => (float) ($cLine['price'] ?? 0),
                'TaxCode'         => $taxCode,
                'WarehouseCode'   => $whsCodeValido,
                'U_CO_AdmOper'    => $adminOper,
            ];

            if (!empty($cLine['project']) && $cLine['project'] !== '0') {
                $docLine['ProjectCode'] = $cLine['project'];
            }
            if (!empty($cLine['account']) && $cLine['account'] !== '0') {
                $docLine['AccountCode'] = $this->resolveSapAccountCode($conn, (string) $cLine['account'], "Cuenta del concepto #{$numCon}");
            }
            if (!empty($dim1)) {
                $docLine['CostingCode'] = $dim1;
            }
            if (!empty($cLine['dim2']) && $cLine['dim2'] !== '0') {
                $docLine['CostingCode2'] = $cLine['dim2'];
            }
            if (!empty($cLine['dim3']) && $cLine['dim3'] !== '0') {
                $docLine['CostingCode3'] = $cLine['dim3'];
            }
            if (!empty($cLine['dim4']) && $cLine['dim4'] !== '0') {
                $docLine['CostingCode4'] = $cLine['dim4'];
            }
            if (!empty($cLine['dim5']) && $cLine['dim5'] !== '0') {
                $docLine['CostingCode5'] = $cLine['dim5'];
            }

            $documentLines[]   = $docLine;
            $iepsTotalFactura += (float) ($cLine['ieps'] ?? 0);
        }

        if ($iepsTotalFactura > 0) {
            $itemIeps = $this->getConfigValueODBC($conn, 'GLO_ITEM_IEPS');
            if (!empty($itemIeps)) {
                $taxCodeZero = $this->resolveSapTaxCode($conn, 0.0, $itemIeps, $cardCodeSAP);
                $whsIeps     = $this->resolveSapWarehouse($conn, '', (string) $refund['U_Area'], $itemIeps, $bplIdSAP);

                $documentLines[] = [
                    'ItemCode'        => $itemIeps,
                    'ItemDescription' => 'IEPS',
                    'Quantity'        => 1,
                    'UnitPrice'       => round($iepsTotalFactura, 2),
                    'TaxCode'         => $taxCodeZero,
                    'WarehouseCode'   => $whsIeps,
                    'CostingCode'     => $refund['U_Area'],
                    'U_CO_AdmOper'    => 'A'
                ];
            }
        }

        $pdfContent = $this->xmlController->generarPDF($uuid, true);
        if (empty($pdfContent)) {
            throw new Exception("No fue posible generar el PDF para el CFDI '{$uuid}'.");
        }

        $docDate    = date('Y-m-d', strtotime($line['U_Date'] ?: $refund['U_Date']));
        $docDueDate = date('Y-m-d', strtotime($docDate . ' +1 month'));

        $payload = [
            'CardCode'                => $cardCodeSAP,
            'DocDate'                 => $docDate,
            'DocDueDate'              => $docDueDate,
            'TaxDate'                 => $docDate,
            'NumAtCard'               => $folioXML,
            'Comments'                => mb_substr((string) ($line['U_Coments'] ?: "Reembolso {$refund['U_Folio']}"), 0, 254),
            'BPL_IDAssignedToInvoice' => $bplIdSAP,
            'U_Refund'                => 'Y',
            'U_FolioAsigXML'          => substr($uuid, -6),
            'U_FolioFiscal'           => $uuid,
            'U_UDF_UUID'              => $uuid,
            'U_GLO_ObjTUG'            => 'frmReceipts',
            'U_MQ_OrigenFol'          => $refund['U_Folio'],
            'U_MQ_OrigenFol_Det'      => (string) $lineNum,
            'DocumentLines'           => $documentLines
        ];

        return [
            'uuid'       => $uuid,
            'cardCode'   => $cardCodeSAP,
            'xmlContent' => $xmlContent,
            'pdfContent' => $pdfContent,
            'payload'    => $payload
        ];
    }

    private function prepararYValidarNotasPoliza($conn, string $attachPath, array $refund, array $notas, int $bplIdSAP, string $accountCreditDefault): array
    {
        $journalLines    = [];
        $docDate         = date('Y-m-d', strtotime($refund['U_Date']));
        $rawOriginalList = !empty($refund['list']) ? json_decode($refund['list'], true) : [];
        $isRefundType    = ((int) ($refund['U_TypeVoucher'] ?? 1) === 1);

        if (empty($accountCreditDefault) || $accountCreditDefault === '0') {
            $configNoteKey        = $isRefundType ? 'GLO_DEU_REEM' : 'GLO_ACC_NOTES';
            $accountCreditDefault = $this->getConfigValueODBC($conn, $configNoteKey);
        }

        $renglonesNotas = [];
        foreach ($notas as $nRow) {
            $idxOriginal = ((int) ($nRow['U_Line'] ?? 1)) - 1;
            $subLines    = $rawOriginalList[$idxOriginal]['noteLines'] ?? [];
            if (!empty($subLines)) {
                foreach ($subLines as $sub) {
                    $renglonesNotas[] = $sub;
                }
            } else {
                $renglonesNotas[] = $nRow;
            }
        }

        $totalNotas = 0.0;
        $refHeader  = (!$isRefundType && !empty($refund['U_CodeMov'])) ? $refund['U_CodeMov'] : $refund['U_Folio'];

        foreach ($renglonesNotas as $nota) {
            $importe        = round((float) ($nota['U_Total'] ?? $nota['amount'] ?? 0), 2);
            $rawCuentaCargo = trim((string) ($nota['account'] ?? ''));
            $rawCuentaAbono = trim((string) (!empty($nota['affectable']) && $nota['affectable'] !== '0' ? $nota['affectable'] : $accountCreditDefault));
            $folioNota      = trim((string) ($nota['docFolio'] ?? ''));
            $proveedor      = trim((string) ($nota['U_Provider'] ?? $nota['provider'] ?? ''));
            $comentarios    = trim((string) ($nota['U_Coments'] ?? $nota['comments'] ?? ''));
            $auxEmpleado    = trim((string) ($nota['auxEmployee'] ?? $refund['U_Employee'] ?? ''));
            $codeMovNota    = trim((string) ($nota['codeMovLine'] ?? $refHeader));

            if (empty($rawCuentaCargo) || $rawCuentaCargo === '0' || empty($rawCuentaAbono) || $rawCuentaAbono === '0') {
                throw new Exception("La nota con folio '{$folioNota}' requiere Cuenta de Cargo y Cuenta Acreedora (o configurar GLO_DEU_REEM / GLO_ACC_NOTES en @QSYS_CONFIG).");
            }

            $cuentaCargo = $this->resolveSapAccountCode($conn, $rawCuentaCargo, "Cuenta de Cargo (Nota {$folioNota})");
            $cuentaAbono = $this->resolveSapAccountCode($conn, $rawCuentaAbono, "Cuenta Acreedora (Nota {$folioNota})");

            $rutaRemotaNota = '';
            $pdfRelative    = trim((string) ($nota['pdfFile'] ?? ''));
            if (!empty($pdfRelative)) {
                $localFullPath = WRITEPATH . ltrim($pdfRelative, '/\\');
                if (file_exists($localFullPath)) {
                    $rutaRemotaNota = $this->subirArchivoNotaSAP($attachPath, $localFullPath, basename($localFullPath));
                }
            }

            $debitLine = [
                'AccountCode'    => $cuentaCargo,
                'Debit'          => $importe,
                'Credit'         => 0.0,
                'Reference2'     => mb_substr($folioNota, 0, 100),
                'LineMemo'       => mb_substr($proveedor, 0, 50),
                'BPLID'          => $bplIdSAP,
                'U_GLO_Coments'  => mb_substr($comentarios, 0, 254),
                'U_GLO_File'     => $rutaRemotaNota,
                'U_GLO_TypeAux'  => !empty($auxEmpleado) ? '2' : '1',
                'U_GLO_Auxiliar' => $auxEmpleado,
                'U_GLO_CodeMov'  => $codeMovNota
            ];

            if (!empty($nota['project']) && $nota['project'] !== '0') {
                $debitLine['ProjectCode'] = $nota['project'];
            }
            if (!empty($nota['dim1']) && $nota['dim1'] !== '0') {
                $debitLine['CostingCode'] = $nota['dim1'];
            }
            if (!empty($nota['dim2']) && $nota['dim2'] !== '0') {
                $debitLine['CostingCode2'] = $nota['dim2'];
            }
            if (!empty($nota['dim3']) && $nota['dim3'] !== '0') {
                $debitLine['CostingCode3'] = $nota['dim3'];
            }
            if (!empty($nota['dim4']) && $nota['dim4'] !== '0') {
                $debitLine['CostingCode4'] = $nota['dim4'];
            }
            if (!empty($nota['dim5']) && $nota['dim5'] !== '0') {
                $debitLine['CostingCode5'] = $nota['dim5'];
            }

            $creditLine = [
                'AccountCode'    => $cuentaAbono,
                'Debit'          => 0.0,
                'Credit'         => $importe,
                'LineMemo'       => mb_substr("Reembolso {$refund['U_Folio']} - {$folioNota}", 0, 50),
                'BPLID'          => $bplIdSAP,
                'U_GLO_TypeAux'  => '1',
                'U_GLO_Auxiliar' => (string) $refund['U_Employee'],
                'U_GLO_CodeMov'  => $isRefundType ? $refund['U_Folio'] : $codeMovNota
            ];

            if (!empty($rutaRemotaNota)) {
                $creditLine['U_GLO_File'] = $rutaRemotaNota;
            }
            if (!empty($nota['dim1']) && $nota['dim1'] !== '0') {
                $creditLine['CostingCode'] = $nota['dim1'];
            }
            if (!empty($nota['dim2']) && $nota['dim2'] !== '0') {
                $creditLine['CostingCode2'] = $nota['dim2'];
            }
            if (!empty($nota['dim3']) && $nota['dim3'] !== '0') {
                $creditLine['CostingCode3'] = $nota['dim3'];
            }
            if (!empty($nota['dim4']) && $nota['dim4'] !== '0') {
                $creditLine['CostingCode4'] = $nota['dim4'];
            }

            $journalLines[] = $debitLine;
            $journalLines[] = $creditLine;
            $totalNotas    += $importe;
        }

        return [
            'totalNotas' => $totalNotas,
            'payload'    => [
                'TransactionCode'   => 'S/CF',
                'ReferenceDate'     => $docDate,
                'TaxDate'           => $docDate,
                'VatDate'           => $docDate,
                'DueDate'           => $docDate,
                'Memo'              => mb_substr('CND ' . $refund['U_Area'], 0, 50),
                'Reference'         => $refHeader,
                'JournalEntryLines' => $journalLines
            ]
        ];
    }

    private function resolveSapAccountCode($conn, string $accountInput, string $labelContext = 'Cuenta'): string
    {
        $accClean = str_replace("'", "''", trim($accountInput));
        $sql      = "
            SELECT \"AcctCode\", \"AcctName\", \"FormatCode\", \"Postable\", \"LocManTran\"
            FROM OACT
            WHERE \"AcctCode\" = '{$accClean}' OR \"FormatCode\" = '{$accClean}'
            LIMIT 1
        ";

        $rs  = odbc_exec($conn, $sql);
        $row = $rs ? odbc_fetch_array($rs) : null;
        if ($rs) {
            odbc_free_result($rs);
        }

        if (!$row || empty($row['AcctCode'])) {
            throw new Exception("La {$labelContext} '{$accountInput}' no existe en el plan de cuentas de SAP (OACT).");
        }

        $acctCode   = trim($this->toUtf8($row['AcctCode']));
        $acctName   = trim($this->toUtf8($row['AcctName']));
        $postable   = strtoupper(trim($this->toUtf8($row['Postable'] ?? 'N')));
        $locManTran = strtoupper(trim($this->toUtf8($row['LocManTran'] ?? 'N')));

        if ($postable !== 'Y') {
            throw new Exception("La {$labelContext} '{$accountInput}' ({$acctName}) es una cuenta de título y no es afectable en SAP (Postable = N).");
        }

        if ($locManTran === 'Y') {
            throw new Exception("La {$labelContext} '{$accountInput}' ({$acctName}) es una Cuenta Asociada (Control Account / LocManTran = Y) en SAP y no puede afectarse directamente en una póliza de diario. Seleccione una cuenta contable operativa/puente normal (LocManTran = N).");
        }

        return $acctCode;
    }

    private function resolveSapWarehouse($conn, string $requestedWhs, string $areaCode, string $itemCode, int $bplIdSAP): string
    {
        if ($requestedWhs !== '' && $requestedWhs !== '0') {
            $whsClean = str_replace("'", "''", $requestedWhs);
            $sqlCheck = "SELECT \"WhsCode\", \"BPLid\" FROM OWHS WHERE \"WhsCode\" = '{$whsClean}' AND \"Locked\" = 'N' LIMIT 1";
            $rsCheck  = @odbc_exec($conn, $sqlCheck);
            if ($rsCheck) {
                $rowCheck = odbc_fetch_array($rsCheck);
                odbc_free_result($rsCheck);
                if ($rowCheck) {
                    if ((int) ($rowCheck['BPLid'] ?? 0) === $bplIdSAP) {
                        return trim($this->toUtf8($rowCheck['WhsCode']));
                    }
                    throw new Exception("El almacén seleccionado '{$requestedWhs}' pertenece a otra sucursal en SAP (BPLId: {$rowCheck['BPLid']}) y no a la sucursal del documento (BPLId: {$bplIdSAP}).");
                }
            }
        }

        if ($areaCode !== '' && $areaCode !== '0') {
            $areaClean = str_replace("'", "''", $areaCode);
            $sqlArea   = "
                SELECT T0.\"U_GLO_Whouse\" AS \"WhsCode\"
                FROM OPRC T0
                INNER JOIN OWHS T1 ON T0.\"U_GLO_Whouse\" = T1.\"WhsCode\"
                WHERE T0.\"PrcCode\" = '{$areaClean}'
                  AND T1.\"BPLid\" = {$bplIdSAP}
                  AND T1.\"Locked\" = 'N'
                LIMIT 1
            ";
            $rsArea = @odbc_exec($conn, $sqlArea);
            if ($rsArea) {
                $rowArea = odbc_fetch_array($rsArea);
                odbc_free_result($rsArea);
                if ($rowArea && !empty($rowArea['WhsCode'])) {
                    return trim($this->toUtf8($rowArea['WhsCode']));
                }
            }
        }

        $itemClean = str_replace("'", "''", $itemCode);
        $sqlItem   = "SELECT \"InvntItem\", \"DfltWH\" FROM OITM WHERE \"ItemCode\" = '{$itemClean}' LIMIT 1";
        $rsItem    = @odbc_exec($conn, $sqlItem);
        if ($rsItem) {
            $rowItem = odbc_fetch_array($rsItem);
            odbc_free_result($rsItem);

            if ($rowItem) {
                $dfltWh = trim($this->toUtf8($rowItem['DfltWH'] ?? ''));
                if ($dfltWh !== '') {
                    $dfltClean = str_replace("'", "''", $dfltWh);
                    $rsDflt    = @odbc_exec($conn, "SELECT \"WhsCode\" FROM OWHS WHERE \"WhsCode\" = '{$dfltClean}' AND \"BPLid\" = {$bplIdSAP} AND \"Locked\" = 'N' LIMIT 1");
                    if ($rsDflt) {
                        $rowDflt = odbc_fetch_array($rsDflt);
                        odbc_free_result($rsDflt);
                        if ($rowDflt && !empty($rowDflt['WhsCode'])) {
                            return trim($this->toUtf8($rowDflt['WhsCode']));
                        }
                    }
                }

                if (($rowItem['InvntItem'] ?? 'N') === 'Y') {
                    $rsDrop = @odbc_exec($conn, "SELECT \"WhsCode\" FROM OWHS WHERE \"BPLid\" = {$bplIdSAP} AND \"DropShip\" = 'Y' AND \"Locked\" = 'N' ORDER BY \"WhsCode\" ASC LIMIT 1");
                    if ($rsDrop) {
                        $rowDrop = odbc_fetch_array($rsDrop);
                        odbc_free_result($rsDrop);
                        if ($rowDrop && !empty($rowDrop['WhsCode'])) {
                            return trim($this->toUtf8($rowDrop['WhsCode']));
                        }
                    }
                }
            }
        }

        $sqlObpl = "SELECT \"DflWhs\" FROM OBPL WHERE \"BPLId\" = {$bplIdSAP} LIMIT 1";
        $rsObpl  = @odbc_exec($conn, $sqlObpl);
        if ($rsObpl) {
            $rowObpl = odbc_fetch_array($rsObpl);
            odbc_free_result($rsObpl);
            if ($rowObpl && !empty($rowObpl['DflWhs'])) {
                return trim($this->toUtf8($rowObpl['DflWhs']));
            }
        }

        $sqlAnyWhs = "SELECT \"WhsCode\" FROM OWHS WHERE \"BPLid\" = {$bplIdSAP} AND \"Locked\" = 'N' ORDER BY \"WhsCode\" ASC LIMIT 1";
        $rsAnyWhs  = @odbc_exec($conn, $sqlAnyWhs);
        if ($rsAnyWhs) {
            $rowAnyWhs = odbc_fetch_array($rsAnyWhs);
            odbc_free_result($rsAnyWhs);
            if ($rowAnyWhs && !empty($rowAnyWhs['WhsCode'])) {
                return trim($this->toUtf8($rowAnyWhs['WhsCode']));
            }
        }

        throw new Exception("No se encontró ningún almacén activo en SAP (OWHS) asignado a la sucursal BPLId {$bplIdSAP}.");
    }

    // =========================================================================
    // 7. BÓVEDA XML, SUBIDA DE PDF DE NOTAS Y LECTURA DE DETALLES
    // =========================================================================

    public function xmlSinAsignar($tipo = 'I')
    {
        $auth = service('authentication');
        if (!$auth->check()) {
            return $this->response->setJSON([
                'draw'            => 0,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => []
            ]);
        }

        $tipo       = strtoupper(trim($tipo));
        $idUser     = user()->id;
        $empresas   = $this->empresa->mdlEmpresasPorUsuario($idUser);
        $empresasID = count($empresas) === 0 ? ['0'] : array_column($empresas, 'id');

        $params = [
            'draw'    => $this->request->getVar('draw'),
            'start'   => $this->request->getVar('start'),
            'length'  => $this->request->getVar('length'),
            'order'   => $this->request->getVar('order'),
            'columns' => $this->request->getVar('columns'),
            'search'  => $this->request->getVar('search'),
        ];

        $datos = $this->xml->mdlXMLSinAsignar($empresasID, $tipo, $params);

        return $this->response->setJSON([
            'draw'            => (int) ($params['draw'] ?? 0),
            'recordsTotal'    => $datos['recordsTotal'] ?? 0,
            'recordsFiltered' => $datos['recordsFiltered'] ?? 0,
            'data'            => $datos['data'] ?? [],
            'token'           => csrf_hash()
        ]);
    }

    public function uploadNoteFileAjax()
    {
        try {
            $file = $this->request->getFile('notePdfFile');
            if (!$file || !$file->isValid()) {
                throw new Exception('No se recibió un archivo válido.');
            }

            $ext = strtolower($file->getClientExtension());
            if (!in_array($ext, ['pdf', 'xml', 'jpg', 'jpeg', 'png'], true)) {
                throw new Exception('Solo se permiten archivos PDF, XML o imágenes (JPG/PNG).');
            }

            $uploadDir = WRITEPATH . 'uploads/refunds_notes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $newName = 'nota_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $file->move($uploadDir, $newName);

            return $this->response->setJSON([
                'error'        => false,
                'fileName'     => $newName,
                'originalName' => $file->getClientName(),
                'relativePath' => 'uploads/refunds_notes/' . $newName,
                'token'        => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function getXmlDataAjax()
    {
        $conn = null;
        try {
            $uuid     = trim((string) $this->request->getPost('uuid'));
            $datosXML = $this->xml->where('uuidTimbre', $uuid)->first();

            if (!$datosXML || empty($datosXML['archivoXML'])) {
                throw new Exception("No se encontró el CFDI con UUID '{$uuid}' en la bóveda de XML.");
            }

            $xmlLimpio   = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($datosXML['archivoXML']);
            $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xmlLimpio);
            $emisor      = $comprobante->searchNode('cfdi:Emisor');

            $rfcEmisor    = trim((string) ($emisor['Rfc'] ?? $datosXML['rfcEmisor'] ?? ''));
            $nombreEmisor = trim((string) ($emisor['Nombre'] ?? $datosXML['nombreEmisor'] ?? ''));
            $folio        = trim((string) ($comprobante['Folio'] ?? substr($uuid, -6)));
            $subTotal     = (float) ($comprobante['SubTotal'] ?? 0);
            $descuento    = (float) ($comprobante['Descuento'] ?? 0);
            $total        = (float) ($comprobante['Total'] ?? $datosXML['total'] ?? 0);

            $ivaGlobal     = 0.0;
            $iepsGlobal    = 0.0;
            $retIVAGlobal  = 0.0;
            $retISRGlobal  = 0.0;
            $conceptLines  = [];
            $descripciones = [];

            $conceptos = $comprobante->searchNodes('cfdi:Conceptos', 'cfdi:Concepto');
            foreach ($conceptos as $idx => $concepto) {
                $cant          = (float) ($concepto['Cantidad'] ?? 1);
                $valorUnitario = (float) ($concepto['ValorUnitario'] ?? 0);
                $importe       = (float) ($concepto['Importe'] ?? ($cant * $valorUnitario));
                $descConcepto  = (float) ($concepto['Descuento'] ?? 0);
                $descripcion   = (string) ($concepto['Descripcion'] ?? '');
                $claveProdServ = (string) ($concepto['ClaveProdServ'] ?? '');
                $noIdent       = (string) ($concepto['NoIdentificacion'] ?? '');

                $descripciones[] = $descripcion;

                $ivaLinea        = 0.0;
                $iepsLinea       = 0.0;
                $retIVALinea     = 0.0;
                $retISRLinea     = 0.0;
                $porcentTaxLinea = 0.0;

                $traslados = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Traslados', 'cfdi:Traslado');
                foreach ($traslados as $t) {
                    $imp    = (string) ($t['Impuesto'] ?? '');
                    $impVal = (float) ($t['Importe'] ?? 0);
                    $tasa   = (float) ($t['TasaOCuota'] ?? 0);
                    if ($imp === '002') {
                        $ivaLinea += $impVal;
                        $porcentTaxLinea = round($tasa * 100, 2);
                    } elseif ($imp === '003') {
                        $iepsLinea += $impVal;
                    }
                }

                $retenciones = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Retenciones', 'cfdi:Retencion');
                foreach ($retenciones as $r) {
                    $imp    = (string) ($r['Impuesto'] ?? '');
                    $impVal = (float) ($r['Importe'] ?? 0);
                    if ($imp === '002') {
                        $retIVALinea += $impVal;
                    } elseif ($imp === '001') {
                        $retISRLinea += $impVal;
                    }
                }

                $ivaGlobal    += $ivaLinea;
                $iepsGlobal   += $iepsLinea;
                $retIVAGlobal += $retIVALinea;
                $retISRGlobal += $retISRLinea;

                $subLinea   = $importe - $descConcepto;
                $totalLinea = $subLinea + $ivaLinea + $iepsLinea - $retIVALinea - $retISRLinea;

                $conceptLines[] = [
                    'lineNum'       => $idx + 1,
                    'claveProdServ' => $claveProdServ,
                    'noIdent'       => $noIdent,
                    'description'   => mb_substr($descripcion, 0, 250),
                    'cant'          => $cant,
                    'price'         => round($valorUnitario, 4),
                    'discount'      => round($descConcepto, 2),
                    'subTotal'      => round($subLinea, 2),
                    'porcentTax'    => $porcentTaxLinea,
                    'iva'           => round($ivaLinea, 2),
                    'ieps'          => round($iepsLinea, 2),
                    'retIVA'        => round($retIVALinea, 2),
                    'retISR'        => round($retISRLinea, 2),
                    'total'         => round($totalLinea, 2),
                    'itemCode'      => '',
                    'adminOper'     => 'A',
                    'whsCode'       => '',
                    'account'       => '',
                    'project'       => '',
                    'dim1'          => '',
                    'dim2'          => '',
                    'dim3'          => '',
                    'dim4'          => '',
                    'dim5'          => ''
                ];
            }

            $cardCode = '';
            try {
                $conn     = $this->connectODBC();
                $rfcClean = str_replace("'", "''", strtoupper($rfcEmisor));
                $sql      = "SELECT \"CardCode\" FROM OCRD WHERE UPPER(TRIM(\"LicTradNum\")) = '{$rfcClean}' AND \"CardType\" = 'S' LIMIT 1";
                $rs       = @odbc_exec($conn, $sql);
                if ($rs && ($row = odbc_fetch_array($rs))) {
                    $cardCode = trim($this->toUtf8($row['CardCode']));
                    odbc_free_result($rs);
                }
                odbc_close($conn);
                $conn = null;
            } catch (Throwable $e) {
            }

            return $this->response->setJSON([
                'error'                => false,
                'token'                => csrf_hash(),
                'uuid'                 => $uuid,
                'folio'                => $folio ?: substr($uuid, -6),
                'fecha'                => !empty($datosXML['fecha']) ? date('Y-m-d', strtotime($datosXML['fecha'])) : date('Y-m-d'),
                'rfcEmisor'            => $rfcEmisor,
                'nombreEmisor'         => $nombreEmisor,
                'cardCode'             => $cardCode,
                'subTotal'             => round($subTotal - $descuento, 2),
                'iva'                  => round($ivaGlobal, 2),
                'ieps'                 => round($iepsGlobal, 2),
                'retIVA'               => round($retIVAGlobal, 2),
                'retISR'               => round($retISRGlobal, 2),
                'total'                => round($total, 2),
                'descripcionConceptos' => mb_substr(implode(' | ', $descripciones), 0, 250),
                'conceptLines'         => $conceptLines
            ]);
        } catch (Throwable $e) {
            if ($conn) {
                @odbc_close($conn);
            }
            return $this->response->setJSON([
                'error'   => true,
                'message' => $e->getMessage(),
                'token'   => csrf_hash()
            ]);
        }
    }

    public function showVouchersWithoutAuth(
        string $search,
        int $start,
        int $length,
        string $orderField,
        string $orderDir,
        array $dataConect
    ): array {
        try {
            $conn = odbc_connect(
                $dataConect['nameODBC'],
                $dataConect['userODBC'],
                $dataConect['passwordODBC']
            );
            if (!$conn) {
                throw new Exception('Error conexión ODBC: ' . odbc_errormsg());
            }

            odbc_exec($conn, 'SET SCHEMA "' . $dataConect['companyDB'] . '"');

            $tableName = '"' . $dataConect['companyDB'] . '"."@QSYS_GLO_VOUC"';
            $where     = '"U_Status" = 2';

            if (!empty($search)) {
                $escaped = str_replace("'", "''", $search);
                $where .= " AND (
                    \"U_Folio\" LIKE '%{$escaped}%'
                    OR \"U_Area\" LIKE '%{$escaped}%'
                    OR CAST(\"U_Employee\" AS VARCHAR) LIKE '%{$escaped}%'
                    OR \"U_Coments\" LIKE '%{$escaped}%'
                    OR \"U_UserCode\" LIKE '%{$escaped}%'
                    OR \"U_CodeMov\" LIKE '%{$escaped}%'
                )";
            }

            $allowedOrder = ['Code', 'U_Folio', 'U_Area', 'U_Employee', 'U_Date', 'U_Total', 'U_Status', 'U_UserCode', 'U_CodeMov', 'U_TypeVoucher', 'U_Branch'];
            if (!in_array($orderField, $allowedOrder, true)) {
                $orderField = 'Code';
            }
            $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

            $sqlCount = "SELECT COUNT(*) AS total FROM {$tableName} WHERE {$where}";
            $rsCount  = odbc_exec($conn, $sqlCount);
            if (!$rsCount) {
                throw new Exception('Error COUNT: ' . odbc_errormsg($conn));
            }
            $totalRow     = odbc_fetch_array($rsCount);
            $recordsTotal = (int) ($totalRow['total'] ?? 0);
            odbc_free_result($rsCount);

            $sqlPaged = "
                SELECT * FROM (
                    SELECT
                        \"Code\", \"U_Folio\", \"U_Area\", \"U_Employee\", \"U_Coments\",
                        \"U_Date\", \"U_Total\", \"U_Status\", \"U_UserCode\", \"U_CodeMov\",
                        \"U_TypeVoucher\", \"U_Branch\",
                        ROW_NUMBER() OVER (ORDER BY \"{$orderField}\" {$orderDir}) AS rn
                    FROM {$tableName}
                    WHERE {$where}
                ) AS t
                WHERE rn > {$start} AND rn <= " . ($start + $length) . "
                ORDER BY rn
            ";

            $rsData = odbc_exec($conn, $sqlPaged);
            if (!$rsData) {
                throw new Exception('Error DATA: ' . odbc_errormsg($conn));
            }

            $data = [];
            while ($row = odbc_fetch_array($rsData)) {
                unset($row['rn']);
                $row    = $this->utf8ize($row);
                $data[] = [
                    'Code'          => $row['Code'],
                    'U_Folio'       => $row['U_Folio'],
                    'U_Area'        => $row['U_Area'],
                    'U_Employee'    => $row['U_Employee'],
                    'U_Coments'     => $row['U_Coments'],
                    'U_Date'        => $row['U_Date'],
                    'U_Total'       => round((float) $row['U_Total'], 2),
                    'U_Status'      => $row['U_Status'],
                    'U_UserCode'    => $row['U_UserCode'],
                    'U_CodeMov'     => $row['U_CodeMov'],
                    'U_TypeVoucher' => $row['U_TypeVoucher'],
                    'U_Branch'      => $row['U_Branch'],
                ];
            }

            odbc_free_result($rsData);
            odbc_close($conn);

            return [
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsTotal,
                'data'            => $data,
            ];
        } catch (Throwable $e) {
            log_message('error', 'Error en showVouchersWithoutAuth: ' . $e->getMessage());
            return [
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => true,
                'message'         => $e->getMessage(),
            ];
        }
    }

    public function showVoucherDetails()
    {
        $input = $this->getSafeInput();

        $draw             = (int) ($input['draw'] ?? 0);
        $start            = (int) ($input['start'] ?? 0);
        $length           = (int) ($input['length'] ?? 10);
        $searchValue      = (string) ($input['search']['value'] ?? ($input['search'] ?? ''));
        $orderColumnIndex = (int) ($input['order'][0]['column'] ?? 1);
        $orderDir         = strtolower($input['order'][0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $codeVoucher      = (int) ($input['code'] ?? 0);
        $idRefund         = (int) ($input['idRefund'] ?? 0);

        if ($codeVoucher <= 0 && $idRefund > 0) {
            $localLines = $this->refundsDetailsModel
                ->where('idRefund', $idRefund)
                ->where('deleted_at', null)
                ->orderBy('id', 'ASC')
                ->findAll();

            $out = [];
            foreach ($localLines as $i => $ln) {
                $out[] = [
                    'No'          => $i + 1,
                    'Linea'       => $ln['U_Line'] ?? ($i + 1),
                    'NA'          => $ln['U_NA'] ?? 'N',
                    'Tipo'        => $ln['U_Type'] ?? '',
                    'Proveedor'   => $ln['U_Provider'] ?? '',
                    'Subtotal'    => round((float) ($ln['U_Subtotal'] ?? 0), 2),
                    'IVA'         => round((float) ($ln['U_IVA'] ?? 0), 2),
                    'Total'       => round((float) ($ln['U_Total'] ?? 0), 2),
                    'Comentarios' => $ln['U_Coments'] ?? '',
                ];
            }

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => count($out),
                'recordsFiltered' => count($out),
                'data'            => $out,
                'token'           => csrf_hash()
            ]);
        }

        if ($codeVoucher <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Se requiere el parámetro "code" o "idRefund"',
                'token'           => csrf_hash()
            ]);
        }

        try {
            $conn       = $this->connectODBC();
            $sqlDetails = "
                SELECT \"U_Line\", \"U_NA\", \"U_Type\", \"U_Provider\", \"U_Subtotal\", \"U_IVA\", \"U_Total\", \"U_Coments\"
                FROM \"@QSYS_GLO_VODE\"
                WHERE \"U_CodeVoucher\" = '{$codeVoucher}'
                ORDER BY CAST(\"U_Line\" AS INTEGER) ASC
            ";

            $stmt = odbc_exec($conn, $sqlDetails);
            if (!$stmt) {
                throw new Exception('Error ejecutando query ODBC: ' . odbc_errormsg($conn));
            }

            $lines = [];
            while ($row = odbc_fetch_array($stmt)) {
                $lines[] = $this->utf8ize($row);
            }
            odbc_free_result($stmt);
            odbc_close($conn);

            $filtered = $lines;
            if ($searchValue !== '') {
                $s        = mb_strtolower($searchValue);
                $filtered = array_values(array_filter($lines, function ($ln) use ($s) {
                    return strpos(mb_strtolower((string) ($ln['U_Provider'] ?? '')), $s) !== false
                        || strpos(mb_strtolower((string) ($ln['U_Type'] ?? '')), $s) !== false
                        || strpos(mb_strtolower((string) ($ln['U_Coments'] ?? '')), $s) !== false;
                }));
            }

            $recordsTotal    = count($lines);
            $recordsFiltered = count($filtered);

            $columnsMap = [
                0 => 'U_Line',
                1 => 'U_NA',
                2 => 'U_Type',
                3 => 'U_Provider',
                4 => 'U_Subtotal',
                5 => 'U_IVA',
                6 => 'U_Total'
            ];
            $orderField = $columnsMap[$orderColumnIndex] ?? 'U_Line';

            usort($filtered, function ($a, $b) use ($orderField, $orderDir) {
                $va  = $a[$orderField] ?? '';
                $vb  = $b[$orderField] ?? '';
                $cmp = (is_numeric($va) && is_numeric($vb)) ? ($va <=> $vb) : strcasecmp((string) $va, (string) $vb);
                return ($orderDir === 'desc') ? -$cmp : $cmp;
            });

            $paged = ($length > 0) ? array_slice($filtered, $start, $length) : $filtered;
            $out   = [];
            $idx   = $start + 1;

            foreach ($paged as $line) {
                $out[] = [
                    'No'          => $idx++,
                    'Linea'       => $line['U_Line'] ?? '',
                    'NA'          => $line['U_NA'] ?? '',
                    'Tipo'        => $line['U_Type'] ?? '',
                    'Proveedor'   => $line['U_Provider'] ?? '',
                    'Subtotal'    => isset($line['U_Subtotal']) ? round((float) $line['U_Subtotal'], 2) : 0,
                    'IVA'         => isset($line['U_IVA']) ? round((float) $line['U_IVA'], 2) : 0,
                    'Total'       => isset($line['U_Total']) ? round((float) $line['U_Total'], 2) : 0,
                    'Comentarios' => $line['U_Coments'] ?? '',
                ];
            }

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $out,
                'token'           => csrf_hash()
            ]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage(),
                'token'           => csrf_hash()
            ]);
        }
    }

    // =========================================================================
    // 8. BUSCADORES AJAX SAP VÍA ODBC (SOLO LECTURA)
    // =========================================================================

    public function getEmployeesAjax()
    {
        try {
            $search = $this->request->getPost('searchTerm') ?? $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = " WHERE \"Active\" = 'Y' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    CAST(\"empID\" AS VARCHAR) LIKE '%{$searchClean}%'
                    OR UPPER(\"firstName\") LIKE UPPER('%{$searchClean}%')
                    OR UPPER(\"lastName\") LIKE UPPER('%{$searchClean}%')
                ) ";
            }

            $sql = "
                SELECT \"empID\", \"firstName\", \"middleName\", \"lastName\"
                FROM OHEM
                {$where}
                ORDER BY \"empID\" ASC
                LIMIT 30
            ";

            $rs   = odbc_exec($conn, $sql);
            $data = [['id' => '0', 'text' => '0 - Seleccione Empleado']];

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $empId    = $this->toUtf8($row['empID']);
                    $fullName = trim($this->toUtf8($row['firstName']) . ' ' . $this->toUtf8($row['middleName']) . ' ' . $this->toUtf8($row['lastName']));
                    $data[]   = [
                        'id'      => $empId,
                        'text'    => "{$empId} - {$fullName}",
                        'empName' => $fullName
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['token' => csrf_hash(), 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'data' => [], 'error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function getAccountsAjax()
    {
        try {
            $search = $this->request->getPost('searchTerm') ?? $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = " WHERE \"Postable\" = 'Y' AND \"FrozenFor\" = 'N' AND \"LocManTran\" = 'N' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    UPPER(\"AcctCode\") LIKE UPPER('%{$searchClean}%')
                    OR UPPER(\"AcctName\") LIKE UPPER('%{$searchClean}%')
                    OR UPPER(\"FormatCode\") LIKE UPPER('%{$searchClean}%')
                ) ";
            }

            $sql = "
                SELECT \"AcctCode\", \"AcctName\", \"FormatCode\"
                FROM OACT
                {$where}
                ORDER BY \"AcctCode\" ASC
                LIMIT 30
            ";

            $rs   = odbc_exec($conn, $sql);
            $data = [['id' => '0', 'text' => '0 - Automática (@QSYS_CONFIG) / Seleccione Cuenta']];

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code   = $this->toUtf8($row['AcctCode']);
                    $name   = $this->toUtf8($row['AcctName']);
                    $data[] = [
                        'id'       => $code,
                        'text'     => "{$code} - {$name}",
                        'acctCode' => $code,
                        'acctName' => $name
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['token' => csrf_hash(), 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'data' => [], 'error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function getDimensionAjax($dimCode = 1)
    {
        try {
            $dimCode = (int) $dimCode;
            $search  = $this->request->getPost('searchTerm') ?? $this->request->getGet('searchTerm') ?? '';
            $conn    = $this->connectODBC();

            $where = " WHERE \"Locked\" = 'N' AND \"Active\" = 'Y' AND \"DimCode\" = {$dimCode} ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    UPPER(\"PrcCode\") LIKE UPPER('%{$searchClean}%')
                    OR UPPER(\"PrcName\") LIKE UPPER('%{$searchClean}%')
                ) ";
            }

            $sql = "
                SELECT \"PrcCode\", \"PrcName\"
                FROM OPRC
                {$where}
                ORDER BY \"PrcCode\" ASC
                LIMIT 30
            ";

            $rs   = odbc_exec($conn, $sql);
            $data = [['id' => '0', 'text' => "0 - Seleccione Dimensión {$dimCode}"]];

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code   = $this->toUtf8($row['PrcCode']);
                    $name   = $this->toUtf8($row['PrcName']);
                    $data[] = [
                        'id'      => $code,
                        'text'    => "{$code} - {$name}",
                        'prcCode' => $code,
                        'prcName' => $name
                    ];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['token' => csrf_hash(), 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'data' => [], 'error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function getItemCodeAjax()
    {
        try {
            $search = $this->request->getPost('searchTerm') ?? $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = " WHERE \"PrchseItem\" = 'Y' AND \"frozenFor\" = 'N' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (
                    UPPER(\"ItemCode\") LIKE UPPER('%{$searchClean}%')
                    OR UPPER(\"ItemName\") LIKE UPPER('%{$searchClean}%')
                ) ";
            }

            $sql = "SELECT \"ItemCode\", \"ItemName\" FROM OITM {$where} ORDER BY \"ItemCode\" ASC LIMIT 30";
            $rs  = odbc_exec($conn, $sql);

            $data = [['id' => '0', 'text' => '0 - Seleccione Artículo']];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code   = $this->toUtf8($row['ItemCode']);
                    $name   = $this->toUtf8($row['ItemName']);
                    $data[] = ['id' => $code, 'text' => "{$code} - {$name}", 'itemCode' => $code, 'itemName' => $name];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['token' => csrf_hash(), 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'data' => [], 'error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function getPrjCodeAjax()
    {
        try {
            $search = $this->request->getPost('searchTerm') ?? $this->request->getGet('searchTerm') ?? '';
            $conn   = $this->connectODBC();

            $where = " WHERE \"Active\" = 'Y' ";
            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (UPPER(\"PrjCode\") LIKE UPPER('%{$searchClean}%') OR UPPER(\"PrjName\") LIKE UPPER('%{$searchClean}%')) ";
            }

            $sql = "SELECT \"PrjCode\", \"PrjName\" FROM OPRJ {$where} ORDER BY \"PrjCode\" ASC LIMIT 30";
            $rs  = odbc_exec($conn, $sql);

            $data = [['id' => '0', 'text' => '0 - Seleccione Proyecto']];
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code   = $this->toUtf8($row['PrjCode']);
                    $name   = $this->toUtf8($row['PrjName']);
                    $data[] = ['id' => $code, 'text' => "{$code} - {$name}", 'prjCode' => $code, 'prjName' => $name];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['token' => csrf_hash(), 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'data' => [], 'error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function getWarehousesAjax()
    {
        try {
            $search     = $this->request->getPost('searchTerm') ?? $this->request->getGet('searchTerm') ?? '';
            $idEmpresa  = (int) ($this->request->getPost('idEmpresa') ?? 0);
            $idSucursal = (int) ($this->request->getPost('idSucursal') ?? 0);

            $bplIdSAP = 0;
            if ($idEmpresa > 0 && $idSucursal > 0) {
                $enlace = $this->linkSapBranchOffice
                    ->where('idEmpresa', $idEmpresa)
                    ->where('idBranchOffice', $idSucursal)
                    ->where('deleted_at', null)
                    ->first();
                if ($enlace && !empty($enlace['idBranchOfficeSAP'])) {
                    $bplIdSAP = (int) $enlace['idBranchOfficeSAP'];
                }
            }

            $conn  = $this->connectODBC();
            $where = " WHERE \"Locked\" = 'N' ";

            if ($bplIdSAP > 0) {
                $where .= " AND \"BPLid\" = {$bplIdSAP} ";
            }

            if (!empty($search)) {
                $searchClean = str_replace("'", "''", trim($search));
                $where .= " AND (UPPER(\"WhsCode\") LIKE UPPER('%{$searchClean}%') OR UPPER(\"WhsName\") LIKE UPPER('%{$searchClean}%')) ";
            }

            $sql  = "SELECT \"WhsCode\", \"WhsName\", \"BPLid\" FROM OWHS {$where} ORDER BY \"WhsCode\" ASC LIMIT 30";
            $rs   = odbc_exec($conn, $sql);
            $data = [['id' => '', 'text' => 'Automático de la Sucursal / Área']];

            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $code   = $this->toUtf8($row['WhsCode']);
                    $name   = $this->toUtf8($row['WhsName']);
                    $data[] = ['id' => $code, 'text' => "{$code} - {$name}"];
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);

            return $this->response->setJSON(['token' => csrf_hash(), 'data' => $data]);
        } catch (Throwable $e) {
            return $this->response->setJSON(['token' => csrf_hash(), 'data' => [], 'error' => true, 'message' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // 9. CLIENTE SERVICE LAYER (POST / PATCH / DELETE / GET) Y UTILIDADES SMB
    // =========================================================================

    private function requestServiceLayer(string $method, string $endpoint, array $payload = []): array
    {
        $dataConect = $this->serviceLayerModel->first();
        if (!$dataConect) {
            throw new Exception('No se encontró configuración de Service Layer.');
        }

        $baseUrl     = rtrim($dataConect['urlServiceLayer'] ?? $dataConect['url'] ?? '', '/');
        $companyDB   = $dataConect['companyDB'] ?? '';
        $userSAP     = $dataConect['userSAP'] ?? $dataConect['user'] ?? $dataConect['username'] ?? '';
        $passwordSAP = $dataConect['passwordSAP'] ?? $dataConect['password'] ?? '';
        $port        = $dataConect['portServiceLayer'] ?? $dataConect['port'] ?? '';

        if (empty($baseUrl)) {
            throw new Exception('La URL de SAP Service Layer no está configurada.');
        }

        if (!empty($port) && strpos($baseUrl, ':' . $port) === false && strpos($baseUrl, '/b1s/') === false) {
            $baseUrl .= ':' . $port;
        }
        if (strpos($baseUrl, '/b1s/v') === false) {
            $baseUrl .= '/b1s/v1';
        }

        $loginPayload = json_encode([
            'CompanyDB' => $companyDB,
            'UserName'  => $userSAP,
            'Password'  => $passwordSAP
        ]);

        $chLogin = curl_init("{$baseUrl}/Login");
        curl_setopt_array($chLogin, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $loginPayload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 30
        ]);

        $loginResult = curl_exec($chLogin);
        if ($loginResult === false) {
            $err = curl_error($chLogin);
            curl_close($chLogin);
            throw new Exception("Error de conexión con Service Layer (Login): {$err}");
        }

        $httpCodeLogin = curl_getinfo($chLogin, CURLINFO_HTTP_CODE);
        $headerSize    = curl_getinfo($chLogin, CURLINFO_HEADER_SIZE);
        $headersLogin  = substr($loginResult, 0, $headerSize);
        $bodyLogin     = substr($loginResult, $headerSize);
        curl_close($chLogin);

        if ($httpCodeLogin < 200 || $httpCodeLogin >= 300) {
            $decodedLoginErr = json_decode($bodyLogin, true);
            $msg             = $decodedLoginErr['error']['message']['value'] ?? $bodyLogin;
            throw new Exception("Error de autenticación en Service Layer ({$httpCodeLogin}): {$msg}");
        }

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headersLogin, $matches);
        $cookies = implode('; ', $matches[1] ?? []);

        $method = strtoupper($method);
        $chDoc  = curl_init("{$baseUrl}/" . ltrim($endpoint, '/'));

        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                "Cookie: {$cookies}"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 60
        ];

        if (!empty($payload) && in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            $curlOpts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($chDoc, $curlOpts);
        $docResult   = curl_exec($chDoc);
        $httpCodeDoc = curl_getinfo($chDoc, CURLINFO_HTTP_CODE);

        if ($docResult === false) {
            $err = curl_error($chDoc);
            curl_close($chDoc);
            throw new Exception("Error al enviar petición a Service Layer: {$err}");
        }
        curl_close($chDoc);

        $chLogout = curl_init("{$baseUrl}/Logout");
        curl_setopt_array($chLogout, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ["Cookie: {$cookies}"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10
        ]);
        curl_exec($chLogout);
        curl_close($chLogout);

        $decodedDoc = json_decode($docResult, true);

        if ($httpCodeDoc < 200 || $httpCodeDoc >= 300) {
            $sapErrorMsg = $decodedDoc['error']['message']['value'] ?? $docResult;
            throw new Exception("SAP Service Layer rechazó la operación ({$httpCodeDoc}): {$sapErrorMsg}");
        }

        return is_array($decodedDoc) ? $decodedDoc : [];
    }

    private function getNextFolioByArea(int $idEmpresa, string $area, int $typeVoucher): int
    {
        $maxLocal  = 0;
        $localRows = $this->refundsModel
            ->select('U_Folio')
            ->where('idEmpresa', $idEmpresa)
            ->where('U_Area', $area)
            ->findAll();

        foreach ($localRows as $r) {
            $f = trim((string) ($r['U_Folio'] ?? ''));
            if (preg_match('/_(\d+)$/', $f, $m)) {
                $num = (int) $m[1];
                if ($num > $maxLocal) {
                    $maxLocal = $num;
                }
            }
        }

        $maxSAP = 0;
        try {
            $conn      = $this->connectODBC();
            $areaClean = str_replace("'", "''", $area);
            $sql       = "SELECT \"U_Folio\" FROM \"@QSYS_GLO_VOUC\" WHERE \"U_Area\" = '{$areaClean}'";
            $rs        = @odbc_exec($conn, $sql);
            if ($rs) {
                while ($row = odbc_fetch_array($rs)) {
                    $f = trim($this->toUtf8($row['U_Folio'] ?? ''));
                    if (preg_match('/_(\d+)$/', $f, $m)) {
                        $num = (int) $m[1];
                        if ($num > $maxSAP) {
                            $maxSAP = $num;
                        }
                    }
                }
                odbc_free_result($rs);
            }
            odbc_close($conn);
        } catch (Throwable $e) {
        }

        return max($maxLocal, $maxSAP) + 1;
    }

    private function getNextUdtCodeODBC($conn, string $udtName): int
    {
        $sql = "SELECT COALESCE(MAX(CAST(\"Code\" AS INTEGER)), 0) + 1 AS \"NextCode\" FROM \"{$udtName}\"";
        $rs  = odbc_exec($conn, $sql);
        if (!$rs) {
            throw new Exception("Error al obtener consecutivo de {$udtName}: " . odbc_errormsg($conn));
        }
        $row      = odbc_fetch_array($rs);
        $nextCode = (int) ($row['NextCode'] ?? 1);
        odbc_free_result($rs);

        return $nextCode;
    }

    private function getConfigValueODBC($conn, string $configCode): string
    {
        $codeClean = str_replace("'", "''", $configCode);
        $sql       = "SELECT \"U_Value\" FROM \"@QSYS_CONFIG\" WHERE \"Code\" = '{$codeClean}' OR \"Name\" = '{$codeClean}' LIMIT 1";
        $rs        = @odbc_exec($conn, $sql);
        if ($rs) {
            $row = odbc_fetch_array($rs);
            odbc_free_result($rs);
            if ($row && !empty($row['U_Value'])) {
                return trim($this->toUtf8($row['U_Value']));
            }
        }
        return '';
    }

    private function getAttachPathODBC($conn): string
    {
        $rsOadp = odbc_exec($conn, 'SELECT "AttachPath" FROM OADP LIMIT 1');
        if (!$rsOadp) {
            throw new Exception('Error al consultar ruta de anexos (OADP.AttachPath): ' . odbc_errormsg($conn));
        }
        $rowOadp = odbc_fetch_array($rsOadp);
        odbc_free_result($rsOadp);

        $attachPath = trim($this->toUtf8($rowOadp['AttachPath'] ?? ''));
        if (empty($attachPath)) {
            throw new Exception('SAP Business One no tiene configurada la ruta de anexos en OADP.AttachPath.');
        }

        return $attachPath;
    }

    public function subirAnexoSMB(
        string $host,
        string $recursoCompartido,
        string $subDirectorio,
        string $usuario,
        string $password,
        string $archivoLocal,
        string $nombreArchivoRemoto
    ): array {
        $unc      = escapeshellarg("//{$host}/{$recursoCompartido}");
        $userPass = escapeshellarg("{$usuario}%{$password}");

        $comandos = [];
        if ($subDirectorio !== '') {
            $subDirClean = str_replace('/', '\\', trim($subDirectorio, '/\\'));
            $comandos[]  = "cd \"{$subDirClean}\"";
        }
        $comandos[] = "put \"{$archivoLocal}\" \"{$nombreArchivoRemoto}\"";

        $comandoSMB = escapeshellarg(implode('; ', $comandos));
        $cmd        = "smbclient {$unc} -U {$userPass} -m SMB3 -c {$comandoSMB} 2>&1";
        $output     = [];
        $returnVar  = 0;
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            $detalleError = implode(" | ", $output);
            return [
                'ok'    => false,
                'error' => "No se pudo subir a la carpeta compartida (//{$host}/{$recursoCompartido}): {$detalleError}"
            ];
        }

        return ['ok' => true];
    }

    private function eliminarAnexoSMB(
        string $host,
        string $recursoCompartido,
        string $subDirectorio,
        string $usuario,
        string $password,
        string $nombreArchivoRemoto
    ): void {
        $unc      = escapeshellarg("//{$host}/{$recursoCompartido}");
        $userPass = escapeshellarg("{$usuario}%{$password}");

        $comandos = [];
        if ($subDirectorio !== '') {
            $subDirClean = str_replace('/', '\\', trim($subDirectorio, '/\\'));
            $comandos[]  = "cd \"{$subDirClean}\"";
        }
        $comandos[] = "del \"{$nombreArchivoRemoto}\"";

        $comandoSMB = escapeshellarg(implode('; ', $comandos));
        $cmd        = "smbclient {$unc} -U {$userPass} -m SMB3 -c {$comandoSMB} 2>&1";
        @exec($cmd);
    }

    private function subirAnexosSAP(string $attachPath, string $uuidTimbre, string $xmlContent, string $pdfContent): array
    {
        $dataConect = $this->serviceLayerModel->first();
        if (!$dataConect) {
            throw new Exception('No se encontró configuración en SapservicelayerModel.');
        }

        $userSMB = trim((string) ($dataConect['userFTPAnexos'] ?? ''));
        $passSMB = (string) ($dataConect['passFTPAnexos'] ?? '');

        if (empty($userSMB)) {
            throw new Exception('No está configurado el usuario de anexos (userFTPAnexos) en los parámetros de SAP Service Layer.');
        }

        $nombreXML       = "{$uuidTimbre}.xml";
        $nombrePDF       = "{$uuidTimbre}.pdf";
        $separator       = (strpos($attachPath, '\\') !== false) ? '\\' : '/';
        $attachPathClean = rtrim($attachPath, "\\/ ") . $separator;

        $rutaCompletaXML = $attachPathClean . $nombreXML;
        $rutaCompletaPDF = $attachPathClean . $nombrePDF;

        $normalizedPath    = trim(str_replace('\\', '/', $attachPath), '/');
        $partes            = array_values(array_filter(explode('/', $normalizedPath), 'strlen'));
        $smbHost           = '';
        $recursoCompartido = '';
        $subDirs           = [];

        if (!empty($partes)) {
            if (strpos($partes[0], ':') === false) {
                $smbHost           = $partes[0];
                $recursoCompartido = $partes[1] ?? '';
                $subDirs           = array_slice($partes, 2);
            } else {
                $recursoCompartido = $partes[1] ?? '';
                $subDirs           = array_slice($partes, 2);
            }
        }

        if (empty($smbHost)) {
            $slUrl   = $dataConect['urlServiceLayer'] ?? $dataConect['url'] ?? '';
            $smbHost = parse_url($slUrl, PHP_URL_HOST) ?: $slUrl;
        }

        if (empty($smbHost) || empty($recursoCompartido)) {
            throw new Exception("No se pudo determinar el servidor o recurso compartido desde OADP.AttachPath ({$attachPath}).");
        }

        $subDirectorio = !empty($subDirs) ? implode('/', $subDirs) : '';
        $tmpXML        = tempnam(sys_get_temp_dir(), 'sap_xml_');
        $tmpPDF        = tempnam(sys_get_temp_dir(), 'sap_pdf_');

        try {
            file_put_contents($tmpXML, $xmlContent);
            file_put_contents($tmpPDF, $pdfContent);

            $resXML = $this->subirAnexoSMB($smbHost, $recursoCompartido, $subDirectorio, $userSMB, $passSMB, $tmpXML, $nombreXML);
            if (!$resXML['ok']) {
                throw new Exception("Error al subir XML ({$nombreXML}): " . $resXML['error']);
            }

            $resPDF = $this->subirAnexoSMB($smbHost, $recursoCompartido, $subDirectorio, $userSMB, $passSMB, $tmpPDF, $nombrePDF);
            if (!$resPDF['ok']) {
                $this->eliminarAnexoSMB($smbHost, $recursoCompartido, $subDirectorio, $userSMB, $passSMB, $nombreXML);
                throw new Exception("Error al subir PDF ({$nombrePDF}): " . $resPDF['error']);
            }
        } finally {
            if ($tmpXML && file_exists($tmpXML)) {
                @unlink($tmpXML);
            }
            if ($tmpPDF && file_exists($tmpPDF)) {
                @unlink($tmpPDF);
            }
        }

        return [
            'rutaXML' => $rutaCompletaXML,
            'rutaPDF' => $rutaCompletaPDF
        ];
    }

    private function subirArchivoNotaSAP(string $attachPath, string $localFilePath, string $nombreRemoto): string
    {
        $dataConect = $this->serviceLayerModel->first();
        $userSMB    = trim((string) ($dataConect['userFTPAnexos'] ?? ''));
        $passSMB    = (string) ($dataConect['passFTPAnexos'] ?? '');

        if (empty($userSMB)) {
            throw new Exception('No está configurado userFTPAnexos en los parámetros de SAP Service Layer.');
        }

        $separator       = (strpos($attachPath, '\\') !== false) ? '\\' : '/';
        $attachPathClean = rtrim($attachPath, "\\/ ") . $separator;
        $rutaCompleta    = $attachPathClean . $nombreRemoto;

        $normalizedPath    = trim(str_replace('\\', '/', $attachPath), '/');
        $partes            = array_values(array_filter(explode('/', $normalizedPath), 'strlen'));
        $smbHost           = '';
        $recursoCompartido = '';
        $subDirs           = [];

        if (!empty($partes)) {
            if (strpos($partes[0], ':') === false) {
                $smbHost           = $partes[0];
                $recursoCompartido = $partes[1] ?? '';
                $subDirs           = array_slice($partes, 2);
            } else {
                $recursoCompartido = $partes[1] ?? '';
                $subDirs           = array_slice($partes, 2);
            }
        }

        if (empty($smbHost)) {
            $slUrl   = $dataConect['urlServiceLayer'] ?? $dataConect['url'] ?? '';
            $smbHost = parse_url($slUrl, PHP_URL_HOST) ?: $slUrl;
        }

        $subDirectorio = !empty($subDirs) ? implode('/', $subDirs) : '';

        $res = $this->subirAnexoSMB($smbHost, $recursoCompartido, $subDirectorio, $userSMB, $passSMB, $localFilePath, $nombreRemoto);
        if (!$res['ok']) {
            throw new Exception("Error al subir anexo de nota ({$nombreRemoto}): " . $res['error']);
        }

        return $rutaCompleta;
    }

    private function resolveSapTaxCode($conn, float $porcentTax, string $itemCode, string $cardCode): string
    {
        $rateFormatted = number_format($porcentTax, 2, '.', '');
        $itemClean     = str_replace("'", "''", $itemCode);
        $cardClean     = str_replace("'", "''", $cardCode);

        $sqlItemTax = "
            SELECT T0.\"TaxCodeAP\" AS \"Code\"
            FROM OITM T0
            INNER JOIN OSTC T1 ON T0.\"TaxCodeAP\" = T1.\"Code\"
            WHERE T0.\"ItemCode\" = '{$itemClean}'
              AND T1.\"ValidForAP\" = 'Y' AND T1.\"Lock\" = 'N'
              AND ROUND(T1.\"Rate\", 2) = {$rateFormatted}
            LIMIT 1
        ";
        $rsItem = @odbc_exec($conn, $sqlItemTax);
        if ($rsItem) {
            $rowItem = odbc_fetch_array($rsItem);
            odbc_free_result($rsItem);
            if ($rowItem && !empty($rowItem['Code'])) {
                return trim($this->toUtf8($rowItem['Code']));
            }
        }

        $sqlBpTax = "
            SELECT T0.\"TaxCode\" AS \"Code\"
            FROM CRD1 T0
            INNER JOIN OSTC T1 ON T0.\"TaxCode\" = T1.\"Code\"
            WHERE T0.\"CardCode\" = '{$cardClean}'
              AND T1.\"ValidForAP\" = 'Y' AND T1.\"Lock\" = 'N'
              AND ROUND(T1.\"Rate\", 2) = {$rateFormatted}
            LIMIT 1
        ";
        $rsBp = @odbc_exec($conn, $sqlBpTax);
        if ($rsBp) {
            $rowBp = odbc_fetch_array($rsBp);
            odbc_free_result($rsBp);
            if ($rowBp && !empty($rowBp['Code'])) {
                return trim($this->toUtf8($rowBp['Code']));
            }
        }

        $sqlOstc = "
            SELECT \"Code\"
            FROM OSTC
            WHERE \"ValidForAP\" = 'Y' AND \"Lock\" = 'N'
              AND ROUND(\"Rate\", 2) = {$rateFormatted}
            ORDER BY \"Code\" ASC LIMIT 1
        ";
        $rsOstc = odbc_exec($conn, $sqlOstc);
        if ($rsOstc) {
            $rowOstc = odbc_fetch_array($rsOstc);
            odbc_free_result($rsOstc);
            if ($rowOstc && !empty($rowOstc['Code'])) {
                return trim($this->toUtf8($rowOstc['Code']));
            }
        }

        throw new Exception("No se encontró un código de impuesto en SAP (OSTC) para la tasa {$rateFormatted}%.");
    }

    private function connectODBC()
    {
        $dataConect = $this->serviceLayerModel->first();
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

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        }
        return $mixed;
    }
}