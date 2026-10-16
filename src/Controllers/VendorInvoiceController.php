<?php

namespace julio101290\boilerplateservicelayer\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use julio101290\boilerplatelog\Models\LogModel;
use julio101290\boilerplateproducts\Models\ProductsModel;
use julio101290\boilerplatequotes\Models\QuotesModel;
use julio101290\boilerplateservicelayer\Models\VendorInvoiceModel;
use julio101290\boilerplateservicelayer\Models\VendorInvoiceDetailsModel;
use julio101290\boilerplatestorages\Models\StoragesModel;
use julio101290\boilerplatecompanies\Models\EmpresasModel;
use julio101290\boilerplatecustumers\Models\CustumersModel;
use julio101290\boilerplatesells\Models\PaymentsModel;
use julio101290\boilerplatecomprobanterd\Models\Comprobantes_rdModel;
use julio101290\boilerplatevehicles\Models\VehiculosModel;
use julio101290\boilerplatedrivers\Models\ChoferesModel;
use julio101290\boilerplatevehicles\Models\TipovehiculoModel;
use julio101290\boilerplatebranchoffice\Models\BranchofficesModel;
use julio101290\boilerplatecashtonnage\Models\ArqueoCajaModel;
use julio101290\boilerplateinventory\Models\SaldosModel;
use julio101290\boilerplatesells\Models\EnlacexmlModel;
use julio101290\boilerplateCFDI\Models\XmlModel;
use julio101290\boilerplateCFDI\Controllers\XmlController;
use julio101290\boilerplatesuppliers\Models\ProveedoresModel;

class VendorInvoiceController extends BaseController {

    use ResponseTrait;

    protected $log;
    protected $vendorInvoice;
    protected $vendorInvoiceDetail;
    protected $storages;
    protected $sucursales;
    protected $empresa;
    protected $user;
    protected $custumer;
    protected $payments;
    protected $products;
    protected $quotes;
    protected $comprobantesRD;
    protected $vehiculos;
    protected $choferes;
    protected $tiposVehiculo;
    protected $arqueoCaja;
    protected $saldos;
    protected $xmlEnlace;
    protected $enlaceXML;
    protected $xml;
    protected $xmlController;
    protected $suppliers;
    public $catalogosSAT;

    public function __construct() {
        $this->log = new LogModel();
        $this->vendorInvoice = new VendorInvoiceModel();
        $this->vendorInvoiceDetail = new VendorInvoiceDetailsModel();
        $this->empresa = new EmpresasModel();
        $this->user = new UserModel();
        $this->custumer = new CustumersModel();
        $this->payments = new PaymentsModel();
        $this->products = new ProductsModel();
        $this->quotes = new QuotesModel();
        $this->storages = new StoragesModel();
        $this->comprobantesRD = new Comprobantes_rdModel();
        $this->vehiculos = new VehiculosModel();
        $this->choferes = new ChoferesModel();
        $this->tiposVehiculo = new TipovehiculoModel();
        $this->sucursales = new BranchofficesModel();
        $this->arqueoCaja = new ArqueoCajaModel();
        $this->saldos = new SaldosModel();
        $this->xmlEnlace = new EnlacexmlModel();
        $this->enlaceXML = new EnlacexmlModel();
        $this->xml = new XmlModel();
        $this->xmlController = new XmlController();
        $this->suppliers = new ProveedoresModel();

        if (class_exists('\julio101290\boilerplateCFDI\Libraries\CatalogosSAT')) {
            $this->catalogosSAT = new \julio101290\boilerplateCFDI\Libraries\CatalogosSAT();
        }

        helper(['menu', 'utilerias', 'auth']);
    }

    public function index() {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        if ($this->request->isAJAX()) {
            $params = [
                'draw' => $this->request->getGet('draw'),
                'start' => $this->request->getGet('start'),
                'length' => $this->request->getGet('length'),
                'order' => $this->request->getGet('order'),
                'columns' => $this->request->getGet('columns'),
            ];

            $datos = $this->vendorInvoice->mdlGetVendorInvoice($empresasID, $params);

            return $this->response->setJSON([
                        'draw' => intval($params['draw']),
                        'recordsTotal' => $datos['recordsTotal'],
                        'recordsFiltered' => $datos['recordsFiltered'],
                        'data' => $datos['data'],
            ]);
        }

        $tiposVehiculo = $this->tiposVehiculo->mdlGetTipovehiculoArray($empresasID);

        $titulos["tiposVehiculo"] = $tiposVehiculo;
        $titulos["listaTitle"] = "Administración de Facturas de Proveedor";
        $titulos["listaSubtitle"] = "Muestra la lista de facturas de proveedores / compras";

        return view('julio101290\boilerplateservicelayer\Views\vendorInvoice', $titulos);
    }

    public function vendorInvoiceFilters($desdeFecha, $hastaFecha, $todas, $empresa, $sucursal, $cliente) {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        if ($this->request->isAJAX()) {
            $params = [
                'draw' => $this->request->getGet('draw'),
                'start' => $this->request->getGet('start'),
                'length' => $this->request->getGet('length'),
                'order' => $this->request->getGet('order'),
                'columns' => $this->request->getGet('columns'),
                'search' => $this->request->getGet('search'),
            ];

            $datos = $this->vendorInvoice->mdlGetVendorInvoiceFilters(
                    $empresasID, $desdeFecha, $hastaFecha, $todas,
                    $empresa, $sucursal, $cliente,
                    $params
            );

            return $this->response->setJSON([
                        'draw' => intval($params['draw']),
                        'recordsTotal' => $datos['recordsTotal'],
                        'recordsFiltered' => $datos['recordsFiltered'],
                        'data' => $datos['data'],
            ]);
        }
    }

    public function vendorInvoiceListFilters($desdeFecha, $hastaFecha, $todas, $empresa, $sucursal, $cliente) {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        if ($this->request->isAJAX()) {
            $params = [
                'draw' => $this->request->getGet('draw'),
                'start' => $this->request->getGet('start'),
                'length' => $this->request->getGet('length'),
                'order' => $this->request->getGet('order'),
                'columns' => $this->request->getGet('columns'),
                'search' => $this->request->getGet('search'),
            ];

            $datos = $this->vendorInvoice->mdlGetVendorInvoiceFilters(
                    $empresasID, $desdeFecha, $hastaFecha, $todas,
                    $empresa, $sucursal, $cliente,
                    $params
            );

            return $this->response->setJSON([
                        'draw' => intval($params['draw']),
                        'recordsTotal' => $datos['recordsTotal'],
                        'recordsFiltered' => $datos['recordsFiltered'],
                        'data' => $datos['data'],
            ]);
        }

        $titulos["desdeFecha"] = $desdeFecha;
        $titulos["hastaFecha"] = $hastaFecha;
        $titulos["todas"] = $todas;
        $titulos["empresa"] = $empresa;
        $titulos["sucursal"] = $sucursal;
        $titulos["cliente"] = $cliente;

        return view('julio101290\boilerplateservicelayer\Views\vendorInvoice', $titulos);
    }

    public function vendorInvoiceReport(
            $idEmpresa = 0, $idSucursal = 0, $idProducto = 0,
            $from = null, $to = null, $cliente = 0
    ) {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        $sucursales = $this->sucursales->mdlSucursalesPorUsuario($idUser);

        if (count($sucursales) == 0) {
            $sucursalesID[0] = "0";
        } else {
            $sucursalesID = array_column($sucursales, "id");
        }

        if ($this->request->isAJAX()) {
            $draw = intval($this->request->getVar('draw'));
            $start = intval($this->request->getVar('start'));
            $length = intval($this->request->getVar('length'));
            $searchValue = $this->request->getVar('search')['value'] ?? '';
            $order = $this->request->getVar('order');
            $columns = $this->request->getVar('columns');

            $queryBuilder = $this->vendorInvoice->mdlComprasPorProductos(
                    $idEmpresa, $idSucursal, $idProducto,
                    $from, $to,
                    $empresasID, $sucursalesID,
                    $cliente
            );

            $recordsTotal = $queryBuilder->countAllResults(false);

            if (!empty($searchValue)) {
                $queryBuilder->groupStart();
                foreach ($columns as $col) {
                    if (isset($col['searchable']) && $col['searchable'] == 'true') {
                        $field = $col['data'];
                        $queryBuilder->orLike($field, $searchValue);
                    }
                }
                $queryBuilder->groupEnd();
            }

            $recordsFiltered = $queryBuilder->countAllResults(false);

            if (!empty($order)) {
                foreach ($order as $ord) {
                    $colIdx = intval($ord['column']);
                    $dir = $ord['dir'] === 'asc' ? 'ASC' : 'DESC';

                    if (isset($columns[$colIdx]) && $columns[$colIdx]['orderable'] == 'true') {
                        $orderColumn = $columns[$colIdx]['data'];
                        $queryBuilder->orderBy($orderColumn, $dir);
                    }
                }
            }

            if ($length != -1) {
                $queryBuilder->limit($length, $start);
            }

            $data = $queryBuilder->get()->getResultArray();

            $response = [
                "draw" => $draw,
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data
            ];

            return $this->response->setJSON($response);
        }
    }

    public function generaPDFDesdeFactura($uuidFactura) {
        $datosFactura = $this->vendorInvoice->select("*")->where("UUID", $uuidFactura)->first();

        $enlaceXML = $this->enlaceXML->select("*")
                ->where("idDocumento", $datosFactura["id"])
                ->where("tipo", "com")
                ->first();

        if (!$enlaceXML) {
            return "No hay XML asociado a esta factura";
        }

        $archivo = $this->xmlController->generarPDF($enlaceXML["uuidXML"], true);

        echo $archivo;
        $this->response->setHeader("Content-Type", "application/pdf");
    }

    public function newVendorInvoice() {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $idUser = user()->id;

        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        $authorize = service('authorization');
        $permisoAgregarArticulo = $authorize->hasPermission('capturaarticulodesdeventa', $idUser);

        $fechaActual = fechaMySQLADateHTML5(fechaHoraActual());
        $idMax = "0";

        $titulos["idMax"] = $idMax;
        $titulos["idSell"] = $idMax;
        $titulos["folio"] = "0";
        $titulos["fecha"] = $fechaActual;
        $titulos["userName"] = $userName;
        $titulos["idUser"] = $idUser;
        $titulos["contact"] = "";
        $titulos["idQuote"] = "0";
        $titulos["codeCustumer"] = "";
        $titulos["observations"] = "";
        $titulos["taxes"] = "0.00";
        $titulos["IVARetenido"] = "0.00";
        $titulos["ISRRetenido"] = "0.00";
        $titulos["subTotal"] = "0.00";
        $titulos["total"] = "0.00";

        if ($this->catalogosSAT) {
            $titulos["formaPago"] = $this->catalogosSAT->formasDePago40()->searchByField("texto", "%%", 99999);
            $titulos["usoCFDI"] = $this->catalogosSAT->usosCfdi40()->searchByField("texto", "%%", 99999);
            $titulos["metodoPago"] = $this->catalogosSAT->metodosDePago40()->searchByField("texto", "%%", 99999);
            $titulos["regimenFiscal"] = $this->catalogosSAT->regimenesFiscales40()->searchByField("texto", "%%", 99999);
        } else {
            $titulos["formaPago"] = [];
            $titulos["usoCFDI"] = [];
            $titulos["metodoPago"] = [];
            $titulos["regimenFiscal"] = [];
        }

        $titulos["RFCReceptor"] = "";
        $titulos["regimenFiscalReceptor"] = "";
        $titulos["usoCFDIReceptor"] = "";
        $titulos["metodoPagoReceptor"] = "";
        $titulos["formaPagoReceptor"] = "";
        $titulos["razonSocialReceptor"] = "";
        $titulos["codigoPostalReceptor"] = "";
        $titulos["permisoAgregarArticulo"] = $permisoAgregarArticulo;
        $titulos["folioComprobanteRD"] = "0";
        $titulos["uuid"] = generaUUID();
        $titulos["uuidRelacion"] = "";
        $titulos["tiposVehiculo"] = $this->tiposVehiculo->mdlGetTipovehiculoArray($empresasID);
        $titulos["title"] = "Nueva Factura de Proveedor";
        $titulos["subtitle"] = "Captura de Compras / Facturas Recibidas";
        $titulos["totalExento"] = "0";

        return view('julio101290\boilerplateservicelayer\Views\newVendorInvoice', $titulos);
    }

    public function reportVendorInvoiceProducts() {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        $titulos["title"] = "Reporte de Compras por Productos";
        $titulos["subtitle"] = "Muestra el acumulado de compras por producto";

        return view('julio101290\boilerplateservicelayer\Views\reportVendorInvoiceProducts', $titulos);
    }

    public function getXMLEnlazados($uuidFactura) {
        try {
            $datosFactura = $this->vendorInvoice->select("*")->where("UUID", $uuidFactura)->first();

            if (isset($datosFactura)) {
                $request = service('request');
                $db = \Config\Database::connect();

                $columns = ['a.id', 'a.idDocumento', 'a.uuidXML', 'a.tipo', 'a.importe', 'c.status', 'c.archivoXML', 'a.created_at', 'a.updated_at', 'a.deleted_at'];

                $builder = $db->table('enlacexml a');
                $builder->join('xml c', 'c.uuidTimbre = a.uuidXML');
                $builder->where('a.idDocumento', $datosFactura["id"]);
                $builder->where('a.tipo', "com");

                $total = $builder->countAllResults(false);

                $searchValue = $request->getPost('search')['value'] ?? '';
                if ($searchValue) {
                    $builder->groupStart();
                    foreach ($columns as $col) {
                        $builder->orLike($col, $searchValue);
                    }
                    $builder->groupEnd();
                }

                $filtered = $builder->countAllResults(false);

                $orderColumnIndex = $request->getPost('order')[0]['column'] ?? 0;
                $orderColumn = $columns[$orderColumnIndex] ?? 'a.id';
                $orderDir = $request->getPost('order')[0]['dir'] ?? 'asc';
                $builder->orderBy($orderColumn, $orderDir);

                $length = $request->getPost('length') ?? 10;
                $start = $request->getPost('start') ?? 0;
                $builder->limit($length, $start);

                $query = $builder->get();
                $data = $query->getResultArray();

                return $this->response->setJSON([
                            'draw' => intval($request->getPost('draw')),
                            'recordsTotal' => $total,
                            'recordsFiltered' => $filtered,
                            'data' => $data
                ]);
            } else {
                $datos = $this->enlaceXML
                        ->select('id,idDocumento,uuidXML,tipo,importe')
                        ->where('idDocumento', 0)
                        ->findAll();

                return $this->response->setJSON([
                            'data' => $datos
                ]);
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getLastCode() {
        $idEmpresa = $this->request->getPost("idEmpresa");
        $idSucursal = $this->request->getPost("idSucursal");

        $result = $this->vendorInvoice->selectMax("folio")
                ->where("idEmpresa", $idEmpresa)
                ->where("idSucursal", $idSucursal)
                ->first();

        if ($result["folio"] == null) {
            $result["folio"] = 1;
        } else {
            $result["folio"] = $result["folio"] + 1;
        }

        echo json_encode($result);
    }

    public function getLastCodeInterno($idEmpresa, $idSucursal) {
        $result = $this->vendorInvoice->selectMax("folio")
                ->where("idEmpresa", $idEmpresa)
                ->where("idSucursal", $idSucursal)
                ->first();

        if ($result["folio"] == null) {
            $result["folio"] = 1;
        } else {
            $result["folio"] = $result["folio"] + 1;
        }

        return $result["folio"];
    }

    public function editVendorInvoice($uuid) {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $idUser = user()->id;

        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        $authorize = service('authorization');
        $permisoAgregarArticulo = $authorize->hasPermission('capturaarticulodesdeventa', $idUser);

        $invoice = $this->vendorInvoice->mdlGetVendorInvoiceUUID($uuid, $empresasID);

        $listProducts = json_decode($invoice["listProducts"], true);

        $titulos["idSell"] = $invoice["id"];
        $titulos["folio"] = $invoice["folio"];
        $titulos["idCustumer"] = $invoice["idCustumer"];
        $titulos["nameCustumer"] = $invoice["nameCustumer"];
        $titulos["idEmpresa"] = $invoice["idEmpresa"];
        $titulos["nombreEmpresa"] = $invoice["nombreEmpresa"];
        $titulos["idUser"] = $idUser;
        $titulos["userName"] = $userName;
        $titulos["listProducts"] = $listProducts;
        $titulos["taxes"] = number_format($invoice["taxes"], 2, ".");
        $titulos["IVARetenido"] = number_format($invoice["IVARetenido"], 2, ".");
        $titulos["ISRRetenido"] = number_format($invoice["ISRRetenido"], 2, ".");
        $titulos["subTotal"] = number_format($invoice["subTotal"], 2, ".");
        $titulos["total"] = number_format($invoice["total"], 2, ".");
        $titulos["fecha"] = $invoice["date"];
        $titulos["dateVen"] = $invoice["dateVen"];
        $titulos["quoteTo"] = $invoice["quoteTo"];
        $titulos["observations"] = $invoice["generalObservations"];
        $titulos["uuid"] = $invoice["UUID"];
        $titulos["idQuote"] = $invoice["idQuote"];

        if ($this->catalogosSAT) {
            $titulos["formaPago"] = $this->catalogosSAT->formasDePago40()->searchByField("texto", "%%", 99999);
            $titulos["usoCFDI"] = $this->catalogosSAT->usosCfdi40()->searchByField("texto", "%%", 99999);
            $titulos["metodoPago"] = $this->catalogosSAT->metodosDePago40()->searchByField("texto", "%%", 99999);
            $titulos["regimenFiscal"] = $this->catalogosSAT->regimenesFiscales40()->searchByField("texto", "%%", 99999);
        } else {
            $titulos["formaPago"] = [];
            $titulos["usoCFDI"] = [];
            $titulos["metodoPago"] = [];
            $titulos["regimenFiscal"] = [];
        }

        $titulos["RFCReceptor"] = $invoice["RFCReceptor"];
        $titulos["regimenFiscalReceptor"] = $invoice["regimenFiscalReceptor"];
        $titulos["usoCFDIReceptor"] = $invoice["usoCFDI"];
        $titulos["metodoPagoReceptor"] = $invoice["metodoPago"];
        $titulos["formaPagoReceptor"] = $invoice["formaPago"];
        $titulos["razonSocialReceptor"] = $invoice["razonSocialReceptor"];
        $titulos["codigoPostalReceptor"] = $invoice["codigoPostalReceptor"];
        $titulos["permisoAgregarArticulo"] = $permisoAgregarArticulo;
        $titulos["totalExento"] = $invoice["tasaCero"];
        $titulos["idVehiculo"] = $invoice["idVehiculo"];
        $titulos["uuidRelacion"] = $invoice["UUIDRelacion"];

        $datosVehiculo = $this->vehiculos->select("*")->where("id", $invoice["idVehiculo"])->first();

        if (isset($datosVehiculo["descripcion"])) {
            $titulos["vehiculoNombre"] = $invoice["tipoVehiculo"] . " " . $datosVehiculo["placas"] . " " . $datosVehiculo["descripcion"];
        } else {
            $titulos["vehiculoNombre"] = "Seleccione Vehiculo";
        }

        $titulos["idChofer"] = $invoice["idChofer"];
        $datosChofer = $this->choferes->select("*")->where("id", $invoice["idChofer"])->first();

        if (isset($datosChofer["nombre"])) {
            $titulos["choferNombre"] = $datosChofer["nombre"] . " " . $datosChofer["Apellido"];
        } else {
            $titulos["choferNombre"] = "Seleccione Chofer";
        }

        $titulos["tipoVehiculo"] = $invoice["tipoVehiculo"];
        $titulos["tiposVehiculo"] = $this->tiposVehiculo->mdlGetTipovehiculoArray($empresasID);
        $titulos["idSucursal"] = $invoice["idSucursal"];

        $sucursal = $this->sucursales->select("*")->where("id", $titulos["idSucursal"])->first();
        $titulos["nombreSucursal"] = $sucursal ? ($sucursal["key"] . " " . $sucursal["name"]) : "";

        if (isset($invoice["tipoComprobanteRD"]) && is_numeric($invoice["tipoComprobanteRD"]) && $invoice["tipoComprobanteRD"] > 0) {
            $comprobante = $this->comprobantesRD->find($invoice["tipoComprobanteRD"]);
            $titulos["folioComprobanteRD"] = $invoice["folioComprobanteRD"];
            $titulos["tipoComprobanteRDID"] = $comprobante["id"];
            $titulos["tipoComprobanteRDNombre"] = $comprobante["nombre"];
            $titulos["tipoComprobanteRDPrefijo"] = $comprobante["prefijo"];
        } else {
            $titulos["folioComprobanteRD"] = "0";
            $titulos["tipoComprobanteRDID"] = "0";
            $titulos["tipoComprobanteRDNombre"] = "0";
            $titulos["tipoComprobanteRDPrefijo"] = "0";
        }

        $titulos["title"] = "Editar Factura de Proveedor";
        $titulos["subtitle"] = "Edición de Compras / Factura Recibida";

        return view('julio101290\boilerplateservicelayer\Views\newVendorInvoice', $titulos);
    }

    public function fromXMLVendorInvoice($uuid) {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $idUser = user()->id;

        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        $authorize = service('authorization');
        $permisoAgregarArticulo = $authorize->hasPermission('capturaarticulodesdeventa', $idUser);

        /*
         * Search UUID in table if exists not continue
         */

        // 1. Validar si ya existe una factura de proveedor registrada con este UUID
        $existeFactura = $this->vendorInvoice
                ->where('UUID', $uuid)
                ->countAllResults();

        if ($existeFactura > 0) {
            return redirect()->to(base_url('admin/vendorInvoice'))
                            ->with('error', 'El CFDI con UUID ' . $uuid . ' ya fue registrado previamente.');
        }


        $invoice = $this->xml->select("*")->where("uuidTimbre", $uuid)->get()->getResultArray();

        $invoice = $invoice[0];

        /*
         * EXTRACT DATA FROM CFDI XML
         */

        $xml = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($invoice["archivoXML"]);

        // create the main node structure
        $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xml);

        // create the CfdiData object, it contains all the required information
        $cfdiData = (new \PhpCfdi\CfdiToPdf\CfdiDataBuilder())
                ->build($comprobante);

        $emisor = $cfdiData->emisor();
        $receptor = $cfdiData->receptor();

        // VALIDAMOS STATUS EN EL SAT

        /*
          try {

          $client = new SoapConsumerClient();
          $consumer = new Consumer($client);

          $cfdiStatus = $consumer->execute($cfdiData->qrUrl());

          if ($cfdiStatus->document()->isActive()) {
          $datosXML["status"] = 'vigente';
          } else {
          $datosXML["status"] = 'cancelado';
          }
          } catch (Exception $ex) {

          }

         */

        $tfd = $cfdiData->timbreFiscalDigital();

        $totalImpuestosTrasladados = $comprobante->searchAttribute('cfdi:Impuestos', 'TotalImpuestosTrasladados');
        $totalImpuestosRetenidos = $comprobante->searchAttribute('cfdi:Impuestos', 'TotalImpuestosRetenidos');

        //CHECK IF EXISTS VENDOR IN CATALOG
        // 1. Verificar si el proveedor ya existe para esa empresa
        // 1. Verificar existencia
        $countProveedores = $this->suppliers
                ->where('taxID', $invoice['rfcEmisor'])
                ->where('idEmpresa', $invoice['idEmpresa'])
                ->countAllResults();

        if ($countProveedores === 0) {
            $nuevoProveedor = [
                'idEmpresa' => (int) $invoice['idEmpresa'],
                'firstname' => $invoice['nombreEmisor'],
                'lastname' => '',
                'codigoPostal' => $comprobante["LugarExpedicion"],
                'razonSocial' => $invoice['nombreEmisor'],
                'taxID' => $invoice['rfcEmisor'],
                'usoCFDI' => $invoice['usoCFDI'] ?? null,
            ];

            // Intentar insertar
            if ($this->suppliers->insert($nuevoProveedor) === false) {
                // Opción A: Errores por reglas de validación del Modelo
                $erroresValidacion = $this->suppliers->errors();

                // Opción B: Error a nivel SQL / Driver de base de datos
                $dbError = $this->suppliers->db->error();

                $mensajeError = !empty($erroresValidacion) ? implode(', ', $erroresValidacion) : ($dbError['message'] ?? 'Error desconocido al guardar el proveedor');

                // Manejo según tu flujo: lanzar excepción o retornar respuesta JSON
                // log_message('error', 'Error al insertar proveedor: ' . $mensajeError);
                // Si estás en un controlador API / AJAX:
                /*
                  return $this->response->setJSON([
                  'status'  => 'error',
                  'message' => 'No se pudo crear el proveedor: ' . $mensajeError
                  ])->setStatusCode(400);
                 */

                // O lanzar excepción para detener el proceso:
                throw new \RuntimeException('Error al crear el proveedor: ' . $mensajeError);
            }

            $idProveedor = $this->suppliers->getInsertID();
        } else {
            $proveedorExistente = $this->suppliers
                    ->where('taxID', $invoice['rfcEmisor'])
                    ->where('idEmpresa', $invoice['idEmpresa'])
                    ->first();

            $idProveedor = $proveedorExistente['id'];
        }


        // VERIFICAR QUE NO EXISTA
        //   $listProducts = json_decode($invoice["listProducts"], true);

        $listProducts = [];
        $conceptos = $comprobante->searchNodes('cfdi:Conceptos', 'cfdi:Concepto');
        foreach ($conceptos as $concepto) {
            // 1. Valores base del concepto
            $cantidad = (float) ($concepto['Cantidad'] ?? 1);
            $valorUnitario = (float) ($concepto['ValorUnitario'] ?? 0);
            $importe = (float) ($concepto['Importe'] ?? ($cantidad * $valorUnitario));

            // 2. Inicialización de impuestos
            $porcentTax = 0.0;
            $tax = 0.0;
            $porcentIVARetenido = 0.0;
            $ivaRetenido = 0.0;
            $porcentISRRetenido = 0.0;
            $isrRetenido = 0.0;

            // 3. Extraer Traslados (IVA trasladado: impuesto = '002')
            $traslados = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Traslados', 'cfdi:Traslado');
            foreach ($traslados as $traslado) {
                if (($traslado['Impuesto'] ?? '') === '002') {
                    $tasaCuota = (float) ($traslado['TasaOCuota'] ?? 0);
                    $porcentTax = $tasaCuota * 100;
                    $tax += (float) ($traslado['Importe'] ?? ($importe * $tasaCuota));
                }
            }

            // 4. Extraer Retenciones (IVA: '002', ISR: '001')
            $retenciones = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Retenciones', 'cfdi:Retencion');
            foreach ($retenciones as $retencion) {
                $impuestoTipo = $retencion['Impuesto'] ?? '';
                $tasaCuota = (float) ($retencion['TasaOCuota'] ?? 0);
                $impRetenido = (float) ($retencion['Importe'] ?? 0);

                if ($impuestoTipo === '002') { // IVA Retenido
                    $porcentIVARetenido = $tasaCuota * 100;
                    $ivaRetenido += $impRetenido;
                } elseif ($impuestoTipo === '001') { // ISR Retenido
                    $porcentISRRetenido = $tasaCuota * 100;
                    $isrRetenido += $impRetenido;
                }
            }

            // 5. Cálculo del importe neto (Subtotal + Traslados - Retenciones)
            $neto = $importe + $tax - $ivaRetenido - $isrRetenido;

            // 6. Armar estructura esperada por tu base de datos
            $listProducts[] = [
                'idProduct' => '0', // 0 o null si es compra/gasto libre sin catálogo previo
                'description' => (string) $concepto['Descripcion'],
                'codeProduct' => (string) ($concepto['NoIdentificacion'] ?? ''),
                'cant' => (string) $cantidad,
                'price' => number_format($valorUnitario, 2, '.', ''),
                'porcentTax' => (string) $porcentTax,
                'tax' => number_format($tax, 2, '.', ''),
                'porcentIVARetenido' => number_format($porcentIVARetenido, 4, '.', ''),
                'porcentISRRetenido' => number_format($porcentISRRetenido, 4, '.', ''),
                'IVARetenido' => number_format($ivaRetenido, 2, '.', ''),
                'ISRRetenido' => number_format($isrRetenido, 2, '.', ''),
                'claveProductoSAT' => (string) ($concepto['ClaveProdServ'] ?? '0'),
                'claveUnidadSAT' => (string) ($concepto['ClaveUnidad'] ?? '0'),
                'neto' => number_format($neto, 2, '.', ''),
                'unidad' => (string) ($concepto['Unidad'] ?? ''),
                'total' => number_format($importe, 2, '.', ''),
            ];
        }


        /*
         * Taxes
         */
        // 1. Extraer totales directos con valor por defecto 0.00 si no existen
        $totalTraslados = (float) ($comprobante->searchAttribute('cfdi:Impuestos', 'TotalImpuestosTrasladados') ?? 0);
        $totalRetenciones = (float) ($comprobante->searchAttribute('cfdi:Impuestos', 'TotalImpuestosRetenidos') ?? 0);

// 2. Desglosar retenciones entre IVA (002) e ISR (001)
        $ivaRetenido = 0.0;
        $isrRetenido = 0.0;

// Buscar el nodo cfdi:Impuestos en la raíz y sus retenciones hijas
        $nodoImpuestos = $comprobante->searchNode('cfdi:Impuestos');

        if ($nodoImpuestos !== null) {
            $retencionesGlobales = $nodoImpuestos->searchNodes('cfdi:Retenciones', 'cfdi:Retencion');
            foreach ($retencionesGlobales as $retencion) {
                $impuestoTipo = $retencion['Impuesto'] ?? '';
                $importe = (float) ($retencion['Importe'] ?? 0);

                if ($impuestoTipo === '002') {
                    $ivaRetenido += $importe;
                } elseif ($impuestoTipo === '001') {
                    $isrRetenido += $importe;
                }
            }
        }

// 3. Fallback de seguridad: si no hubo desglose pero sí un total global de retenciones
// (o si el comprobante no traía cfdi:Impuestos global, sumarlo desde $listProducts)
        if ($totalRetenciones > 0 && ($ivaRetenido + $isrRetenido) === 0.0) {
            // Si ya procesaste $listProducts previamente:
            if (!empty($listProducts)) {
                $ivaRetenido = array_sum(array_column($listProducts, 'IVARetenido'));
                $isrRetenido = array_sum(array_column($listProducts, 'ISRRetenido'));
            } else {
                $ivaRetenido = $totalRetenciones;
            }
        }

        $titulos["idSell"] = 0;
        $titulos["folio"] = $invoice["folio"];
        $titulos["idCustumer"] = $idProveedor;
        $titulos["nameCustumer"] = $invoice["nombreEmisor"];
        $titulos["idEmpresa"] = $invoice["idEmpresa"];
        $titulos["nombreEmpresa"] = $invoice["nombreReceptor"];
        $titulos["idUser"] = $idUser;
        $titulos["userName"] = $userName;
        $titulos["listProducts"] = $listProducts;
        $titulos['taxes'] = $totalTraslados;
        $titulos['IVARetenido'] = $ivaRetenido;
        $titulos['ISRRetenido'] = $isrRetenido;
        $titulos["subTotal"] = number_format((float) ($comprobante["SubTotal"] ?? 0), 2, ".", "");
        $titulos["total"] = number_format($invoice["total"], 2, ".");
        $titulos["fecha"] = fechaMySQLADateHTML5($invoice["fecha"]);
        $titulos["dateVen"] = fechaMySQLADateHTML5($invoice["fecha"]);
        $titulos["quoteTo"] = 0;
        $titulos["observations"] = "";
        $titulos["uuid"] = $invoice["uuidTimbre"];
        $titulos["idQuote"] = "";

        if ($this->catalogosSAT) {
            $titulos["formaPago"] = $this->catalogosSAT->formasDePago40()->searchByField("texto", "%%", 99999);
            $titulos["usoCFDI"] = $this->catalogosSAT->usosCfdi40()->searchByField("texto", "%%", 99999);
            $titulos["metodoPago"] = $this->catalogosSAT->metodosDePago40()->searchByField("texto", "%%", 99999);
            $titulos["regimenFiscal"] = $this->catalogosSAT->regimenesFiscales40()->searchByField("texto", "%%", 99999);
        } else {
            $titulos["formaPago"] = [];
            $titulos["usoCFDI"] = [];
            $titulos["metodoPago"] = [];
            $titulos["regimenFiscal"] = [];
        }

        $emisor = $cfdiData->emisor();
        $receptor = $cfdiData->receptor();

        $totalExento = 0.0;
        $totalTasaCero = 0.0;

// 1. Revisar los traslados en los conceptos (o en cfdi:Impuestos)
        foreach ($conceptos as $concepto) {
            $traslados = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Traslados', 'cfdi:Traslado');

            foreach ($traslados as $traslado) {
                $tipoFactor = $traslado['TipoFactor'] ?? '';
                $tasaCuota = (float) ($traslado['TasaOCuota'] ?? 0);
                $base = (float) ($traslado['Base'] ?? 0);

                if ($tipoFactor === 'Exento') {
                    $totalExento += $base;
                } elseif ($tipoFactor === 'Tasa' && $tasaCuota === 0.0) {
                    $totalTasaCero += $base;
                }
            }
        }

// 2. Guardar en $invoice
        $invoice['totalExento'] = $totalExento;
        $invoice['tasaCero'] = $totalTasaCero;

        $titulos["RFCReceptor"] = $receptor['Rfc'];
        $titulos["regimenFiscalReceptor"] = $receptor['RegimenFiscalReceptor'];
        $titulos["usoCFDIReceptor"] = $receptor['UsoCFDI'];
        $titulos["metodoPagoReceptor"] = $comprobante['MetodoPago'];
        $titulos["formaPagoReceptor"] = $comprobante["FormaPago"];
        $titulos["razonSocialReceptor"] = $receptor['Nombre'];
        $titulos["codigoPostalReceptor"] = $receptor["DomicilioFiscalReceptor"];
        $titulos["permisoAgregarArticulo"] = $permisoAgregarArticulo;
        $titulos['totalExento'] = number_format((float) ($invoice['totalExento'] ?? 0), 2, '.', '');
        $titulos['tasaCero'] = number_format((float) ($invoice['tasaCero'] ?? 0), 2, '.', '');
        $titulos["idVehiculo"] = "";
        $titulos["uuidRelacion"] = "";

        //$datosVehiculo = $this->vehiculos->select("*")->where("id", $invoice["idVehiculo"])->first();

        if (isset($datosVehiculo["descripcion"])) {
            $titulos["vehiculoNombre"] = $invoice["tipoVehiculo"] . " " . $datosVehiculo["placas"] . " " . $datosVehiculo["descripcion"];
        } else {
            $titulos["vehiculoNombre"] = "Seleccione Vehiculo";
        }

        $titulos["idChofer"] = 0;
        $datosChofer = $this->choferes->select("*")->where("id", $titulos["idChofer"])->first();

        if (isset($datosChofer["nombre"])) {
            $titulos["choferNombre"] = $datosChofer["nombre"] . " " . $datosChofer["Apellido"];
        } else {
            $titulos["choferNombre"] = "Seleccione Chofer";
        }

        $titulos["tipoVehiculo"] = 0;
        $titulos["tiposVehiculo"] = $this->tiposVehiculo->mdlGetTipovehiculoArray($empresasID);
        $titulos["idSucursal"] = 0;

        $sucursal = $this->sucursales->select("*")->where("id", $titulos["idSucursal"])->first();
        $titulos["nombreSucursal"] = $sucursal ? ($sucursal["key"] . " " . $sucursal["name"]) : "";

        if (isset($invoice["tipoComprobanteRD"]) && is_numeric($invoice["tipoComprobanteRD"]) && $invoice["tipoComprobanteRD"] > 0) {
            $comprobante = $this->comprobantesRD->find($invoice["tipoComprobanteRD"]);
            $titulos["folioComprobanteRD"] = $invoice["folioComprobanteRD"];
            $titulos["tipoComprobanteRDID"] = $comprobante["id"];
            $titulos["tipoComprobanteRDNombre"] = $comprobante["nombre"];
            $titulos["tipoComprobanteRDPrefijo"] = $comprobante["prefijo"];
        } else {
            $titulos["folioComprobanteRD"] = "0";
            $titulos["tipoComprobanteRDID"] = "0";
            $titulos["tipoComprobanteRDNombre"] = "0";
            $titulos["tipoComprobanteRDPrefijo"] = "0";
        }

        $titulos["title"] = "Factura de Proveedor Desde XML";
        $titulos["subtitle"] = "Edición de Compras / Factura Recibida";

        return view('julio101290\boilerplateservicelayer\Views\newVendorInvoice', $titulos);
    }

    public function save() {
        $auth = service('authentication');
        if (!$auth->check()) {
            $this->session->set('redirect_url', current_url());
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $datos = $this->request->getPost();

        $this->vendorInvoice->db->transBegin();

        $existsInvoice = $this->vendorInvoice->where("UUID", $datos["UUID"])->countAllResults();
        $listProducts = json_decode($datos["listProducts"], true);
        $datosSucursal = $this->sucursales->find($datos["idSucursal"]);

        $datos["idArqueoCaja"] = 0;

        if (isset($datosSucursal["arqueoCaja"]) && $datosSucursal["arqueoCaja"] == "on") {
            $datosArqueoCaja = $this->arqueoCaja->mdlObtenerIdArqueo($datos["idEmpresa"], $datos["idSucursal"], $datos["date"]);

            if (!isset($datosArqueoCaja["id"])) {
                $this->vendorInvoice->db->transRollback();
                echo "No hay habilitado arqueo de caja";
                return;
            } else {
                $datos["idArqueoCaja"] = $datosArqueoCaja["id"];
            }
        }

        if ($existsInvoice == 0) {
            $ultimoFolio = $this->getLastCodeInterno($datos["idEmpresa"], $datos["idSucursal"]);
            $empresa = $this->empresa->find($datos["idEmpresa"]);

            if (!empty($datos["tipoComprobanteRD"])) {
                $comprobante = $this->comprobantesRD->find($datos["tipoComprobanteRD"]);
            }

            if (isset($empresa["facturacionRD"]) && $empresa["facturacionRD"] == "on") {
                if (empty($datos["tipoComprobanteRD"])) {
                    $this->vendorInvoice->db->transRollback();
                    echo "No se selecciono tipo comprobante";
                    return;
                }

                if (empty($datos["folioComprobanteRD"])) {
                    $this->vendorInvoice->db->transRollback();
                    echo "No hay folio Comprobante";
                    return;
                }

                if ($datos["folioComprobanteRD"] > $comprobante["folioFinal"]) {
                    $this->vendorInvoice->db->transRollback();
                    echo "Se agotaron los folio son hasta $comprobante[folioFinal] y van en $datos[folioComprobanteRD]";
                    return;
                }

                if ($datos["folioComprobanteRD"] < $comprobante["folioInicial"]) {
                    $this->vendorInvoice->db->transRollback();
                    echo "Folio fuera de rango $comprobante[folioInicial] y van en $datos[folioComprobanteRD]";
                    return;
                }

                if ($datos["date"] < $comprobante["desdeFecha"]) {
                    $this->vendorInvoice->db->transRollback();
                    echo "fecha fuera de rango limite inferior $comprobante[desdeFecha] fecha factura $datos[date]";
                    return;
                }

                if ($datos["date"] > $comprobante["hastaFecha"]) {
                    $this->vendorInvoice->db->transRollback();
                    echo "fecha fuera de rango, limite superior $comprobante[desdeFecha] fecha factura $datos[date]";
                    return;
                }
            }

            $datos["folio"] = $ultimoFolio;
            $datos["balance"] = $datos["total"] - (($datos["importPayment"] ?? 0) - ($datos["importBack"] ?? 0));

            try {
                $datos1 = array_intersect_key($datos, array_flip($this->vendorInvoice->allowedFields));
                $datos1["tipoComprobanteRD"] = $datos["tipoComprobanteRD"] ?? "";

                if ($this->vendorInvoice->insert($datos1) === false) {
                    $errores = $this->vendorInvoice->errors();
                    $listErrors = "";
                    foreach ($errores as $field => $error) {
                        $listErrors .= $error . " ";
                    }
                    echo $listErrors;
                    $this->vendorInvoice->db->transRollback();
                    return;
                }

                $idInvoiceInserted = $this->vendorInvoice->getInsertID();

                foreach ($listProducts as $key => $value) {
                    $datosDetalle["idSell"] = $idInvoiceInserted;
                    $datosDetalle["idProduct"] = $value["idProduct"];
                    $datosDetalle["description"] = $value["description"];
                    $datosDetalle["unidad"] = $value["unidad"] ?? 'Pieza';
                    $datosDetalle["codeProduct"] = $value["codeProduct"];
                    $datosDetalle["cant"] = $value["cant"];
                    $datosDetalle["price"] = $value["price"];
                    $datosDetalle["porcentTax"] = $value["porcentTax"];
                    $datosDetalle["porcentIVARetenido"] = $value["porcentIVARetenido"] ?? 0;
                    $datosDetalle["porcentISRRetenido"] = $value["porcentISRRetenido"] ?? 0;
                    $datosDetalle["IVARetenido"] = $value["IVARetenido"] ?? 0;
                    $datosDetalle["ISRRetenido"] = $value["ISRRetenido"] ?? 0;
                    $datosDetalle["claveProductoSAT"] = $value["claveProductoSAT"] ?? '';
                    $datosDetalle["claveUnidadSAT"] = $value["claveUnidadSAT"] ?? '';
                    $datosDetalle["lote"] = $value["lote"] ?? '';
                    $datosDetalle["idAlmacen"] = $value["idAlmacen"] ?? 1;
                    $datosDetalle["tax"] = $value["tax"];
                    $datosDetalle["total"] = $value["total"];
                    $datosDetalle["importeExento"] = $value["importeExento"] ?? 0;
                    $datosDetalle["neto"] = $value["neto"];
                    $datosDetalle["predial"] = $value["predial"] ?? '';

                    $product = $this->products->find($datosDetalle["idProduct"]);

                    // Entrada de compras: suma a inventario riguroso
                    if (isset($product["inventarioRiguroso"]) && $product["inventarioRiguroso"] == "on") {
                        $datosSaldo["idEmpresa"] = $datos["idEmpresa"];
                        $datosSaldo["idAlmacen"] = $datosDetalle["idAlmacen"];
                        $datosSaldo["idProducto"] = $datosDetalle["idProduct"];
                        $datosSaldo["lote"] = $datosDetalle["lote"];

                        $datosNuevosSaldo = $this->saldos->select("*")->where($datosSaldo)->first();

                        if ($datosNuevosSaldo) {
                            $datosNuevosSaldo["cantidad"] = $datosNuevosSaldo["cantidad"] + $datosDetalle["cant"];
                            if ($this->saldos->update($datosNuevosSaldo["id"], $datosNuevosSaldo) === false) {
                                echo "error al actualizar el saldo $datosDetalle[idProduct]";
                                $this->vendorInvoice->db->transRollback();
                                return;
                            }
                        } else {
                            $nuevoRegistroSaldo = array_merge($datosSaldo, ['cantidad' => $datosDetalle["cant"]]);
                            if ($this->saldos->save($nuevoRegistroSaldo) === false) {
                                echo "error al registrar nuevo saldo de lote $datosDetalle[idProduct]";
                                $this->vendorInvoice->db->transRollback();
                                return;
                            }
                        }
                    }

                    if ($this->vendorInvoiceDetail->save($datosDetalle) === false) {
                        echo "error al insertar el producto $datosDetalle[idProduct]";
                        $this->vendorInvoiceDetail->db->transRollback();
                        return;
                    } else {
                        // Entrada de compras: incrementa stock general
                        if (isset($product["validateStock"]) && $product["validateStock"] == "on") {
                            $newStock = $product["stock"] + $datosDetalle["cant"];
                            $updateDataStock["stock"] = $newStock;
                            if ($this->products->update($datosDetalle["idProduct"], $updateDataStock) === false) {
                                echo "error al actualizar el stock del producto $datosDetalle[idProduct]";
                                $this->vendorInvoiceDetail->db->transRollback();
                                return;
                            }
                        }
                    }
                }

                if (isset($datos["idQuote"]) && $datos["idQuote"] > 0) {
                    $newSellQuote["idSell"] = $idInvoiceInserted;
                    $this->quotes->update($datos["idQuote"], $newSellQuote);
                }

                if (isset($datos["importPayment"]) && $datos["importPayment"] > 0) {
                    $dataPayment["idSell"] = $idInvoiceInserted;
                    $dataPayment["importPayment"] = $datos["importPayment"];
                    $dataPayment["importBack"] = $datos["importBack"] ?? 0;
                    $dataPayment["datePayment"] = $datos["datePayment"] ?? date('Y-m-d');
                    $dataPayment["metodPayment"] = $datos["metodoPago"] ?? 'PUE';
                    $dataPayment["observaciones"] = $datos["observacionesPago"] ?? '';

                    try {
                        if ($this->payments->save($dataPayment) === false) {
                            echo "error al insertar el pago";
                            $this->vendorInvoiceDetail->db->transRollback();
                            return;
                        }
                    } catch (\Exception $e) {
                        $this->vendorInvoiceDetail->db->transRollback();
                        echo $e->getMessage();
                        return;
                    }
                }

                if (isset($empresa["facturacionRD"]) && $empresa["facturacionRD"] == "on" && !empty($datos["tipoComprobanteRD"])) {
                    $comprobante = $this->comprobantesRD->find($datos["tipoComprobanteRD"]);
                    $folio = $comprobante["folioActual"] + 1;
                    $datosComprobante["folioActual"] = $folio;
                    $this->comprobantesRD->update($datos["tipoComprobanteRD"], $datosComprobante);
                }

                $datosBitacora["description"] = "Se guardo la factura de proveedor con los siguientes datos: " . json_encode($datos);
                $datosBitacora["user"] = $userName;
                $datosBitacora["module"] = "VENDOR_INVOICE";
                $datosBitacora["idMovModule"] = $idInvoiceInserted;

                $this->log->save($datosBitacora);

                $this->vendorInvoiceDetail->db->transCommit();
                echo "Guardado Correctamente";
            } catch (\Exception $ex) {
                $this->vendorInvoice->db->transRollback();
                echo "Error al guardar " . $ex->getMessage();
            }
        } else {
            $backInvoice = $this->vendorInvoice->where("UUID", $datos["UUID"])->first();

            if ($this->xmlEnlace->select("*")->where("idDocumento", $backInvoice["id"])->where("tipo", "com")->countAllResults() > 0) {
                $this->vendorInvoice->db->transRollback();
                return $this->failNotFound('La Factura no se puede modificar porque ya tiene timbre enlazado');
            }

            if ($this->payments->select("*")->where("idSell", $backInvoice["id"])->countAllResults() > 0) {
                $this->vendorInvoice->db->transRollback();
                return $this->failNotFound('La Factura no se puede modificar porque ya tiene pagos enlazados, favor de eliminar los pagos primero');
            }

            $listProductsBack = json_decode($backInvoice["listProducts"], true);

            $datos["folio"] = $backInvoice["folio"];
            $datos["balance"] = $datos["total"];

            if ($this->vendorInvoice->update($backInvoice["id"], $datos) == false) {
                $errores = $this->vendorInvoice->errors();
                $listError = "";
                foreach ($errores as $field => $error) {
                    $listError .= $error . " ";
                }
                echo $listError;
                $this->vendorInvoice->db->transRollback();
                return;
            } else {
                // Reversión de existencias añadidas originalmente por la compra
                foreach ($listProductsBack as $key => $value) {
                    $product = $this->products->find($value["idProduct"]);

                    if (isset($product["validateStock"]) && $product["validateStock"] == "on") {
                        $newStock = $product["stock"] - $value["cant"];
                        $updateDataStock["stock"] = $newStock;
                        if ($this->products->update($value["idProduct"], $updateDataStock) === false) {
                            echo "error al revertir el stock del producto $value[idProduct]";
                            $this->vendorInvoiceDetail->db->transRollback();
                            return;
                        }
                    }

                    if (isset($product["inventarioRiguroso"]) && $product["inventarioRiguroso"] == "on") {
                        $datosSaldo["idEmpresa"] = $backInvoice["idEmpresa"];
                        $datosSaldo["idAlmacen"] = $value["idAlmacen"];
                        $datosSaldo["idProducto"] = $value["idProduct"];
                        $datosSaldo["lote"] = $value["lote"];

                        $datosNuevosSaldo = $this->saldos->select("*")->where($datosSaldo)->first();
                        if ($datosNuevosSaldo) {
                            $datosNuevosSaldo["cantidad"] = $datosNuevosSaldo["cantidad"] - $value["cant"];
                            if ($this->saldos->update($datosNuevosSaldo["id"], $datosNuevosSaldo) === false) {
                                echo "error al revertir el saldo $value[idProduct]";
                                $this->vendorInvoice->db->transRollback();
                                return;
                            }
                        }
                    }
                }

                $this->vendorInvoiceDetail->select("*")->where("idSell", $backInvoice["id"])->delete();
                $this->vendorInvoiceDetail->purgeDeleted();

                foreach ($listProducts as $key => $value) {
                    $datosDetalle["idSell"] = $backInvoice["id"];
                    $datosDetalle["idProduct"] = $value["idProduct"];
                    $datosDetalle["description"] = $value["description"];
                    $datosDetalle["unidad"] = $value["unidad"] ?? 'Pieza';
                    $datosDetalle["codeProduct"] = $value["codeProduct"];
                    $datosDetalle["cant"] = $value["cant"];
                    $datosDetalle["price"] = $value["price"];
                    $datosDetalle["porcentTax"] = $value["porcentTax"];
                    $datosDetalle["porcentIVARetenido"] = $value["porcentIVARetenido"] ?? 0;
                    $datosDetalle["porcentISRRetenido"] = $value["porcentISRRetenido"] ?? 0;
                    $datosDetalle["IVARetenido"] = $value["IVARetenido"] ?? 0;
                    $datosDetalle["ISRRetenido"] = $value["ISRRetenido"] ?? 0;
                    $datosDetalle["claveProductoSAT"] = $value["claveProductoSAT"] ?? '';
                    $datosDetalle["claveUnidadSAT"] = $value["claveUnidadSAT"] ?? '';
                    $datosDetalle["lote"] = $value["lote"] ?? '';
                    $datosDetalle["idAlmacen"] = $value["idAlmacen"] ?? 1;
                    $datosDetalle["tax"] = $value["tax"];
                    $datosDetalle["total"] = $value["total"];
                    $datosDetalle["neto"] = $value["neto"];

                    if ($this->vendorInvoiceDetail->save($datosDetalle) === false) {
                        $errores = $this->vendorInvoiceDetail->errors();
                        $listError = "";
                        foreach ($errores as $field => $error) {
                            $listError .= $error . " ";
                        }
                        echo "error al insertar el producto $datosDetalle[idProduct] $listError";
                        $this->vendorInvoice->db->transRollback();
                        return;
                    } else {
                        $product = $this->products->find($value["idProduct"]);

                        if (isset($product["validateStock"]) && $product["validateStock"] == "on") {
                            $newStock = $product["stock"] + $datosDetalle["cant"];
                            $updateDataStock["stock"] = $newStock;
                            if ($this->products->update($datosDetalle["idProduct"], $updateDataStock) === false) {
                                echo "error al actualizar el stock del producto $datosDetalle[idProduct]";
                                $this->vendorInvoiceDetail->db->transRollback();
                                return;
                            }
                        }

                        if (isset($product["inventarioRiguroso"]) && $product["inventarioRiguroso"] == "on") {
                            $datosSaldo["idEmpresa"] = $datos["idEmpresa"];
                            $datosSaldo["idAlmacen"] = $datosDetalle["idAlmacen"];
                            $datosSaldo["idProducto"] = $datosDetalle["idProduct"];
                            $datosSaldo["lote"] = $datosDetalle["lote"];

                            $datosNuevosSaldo = $this->saldos->select("*")->where($datosSaldo)->first();
                            if ($datosNuevosSaldo) {
                                $datosNuevosSaldo["cantidad"] = $datosNuevosSaldo["cantidad"] + $datosDetalle["cant"];
                                if ($this->saldos->update($datosNuevosSaldo["id"], $datosNuevosSaldo) === false) {
                                    echo "error al actualizar el saldo $value[idProduct]";
                                    $this->vendorInvoice->db->transRollback();
                                    return;
                                }
                            } else {
                                $nuevoRegistroSaldo = array_merge($datosSaldo, ['cantidad' => $datosDetalle["cant"]]);
                                $this->saldos->save($nuevoRegistroSaldo);
                            }
                        }
                    }
                }

                $datosBitacora["description"] = "Se actualizó la factura: " . json_encode($datos) . " Datos anteriores: " . json_encode($backInvoice);
                $datosBitacora["user"] = $userName;
                $datosBitacora["module"] = "VENDOR_INVOICE";
                $datosBitacora["idMovModule"] = $backInvoice["id"];
                $this->log->save($datosBitacora);

                echo "Actualizado Correctamente";
                $this->vendorInvoice->db->transCommit();
                return;
            }
        }
    }

    public function delete($id) {
        $auth = service('authentication');
        if (!$auth->check()) {
            return redirect()->route('admin');
        }

        $userName = user()->username;
        $idUser = user()->id;

        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        if ($this->vendorInvoice->select("*")->whereIn("idEmpresa", $empresasID)->where("id", $id)->countAllResults() == 0) {
            return $this->failNotFound('Acceso Prohibido');
        }

        $this->vendorInvoice->db->transBegin();

        $infoInvoice = $this->vendorInvoice->find($id);

        if ($this->xmlEnlace->select("*")->where("idDocumento", $infoInvoice["id"])->where("tipo", "com")->countAllResults() > 0) {
            $this->vendorInvoice->db->transRollback();
            return $this->failNotFound('La Factura no se puede eliminar porque ya tiene timbre enlazado');
        }

        if ($this->payments->select("*")->where("idSell", $infoInvoice["id"])->countAllResults() > 0) {
            $this->vendorInvoice->db->transRollback();
            return $this->failNotFound('La Factura no se puede eliminar porque ya tiene pagos enlazados');
        }

        if (!$found = $this->vendorInvoice->delete($id)) {
            $this->vendorInvoice->db->transRollback();
            return $this->failNotFound('Error al eliminar');
        }

        if ($this->vendorInvoiceDetail->select("*")->where("idSell", $id)->delete() === false) {
            $this->vendorInvoiceDetail->db->transRollback();
            return $this->failNotFound('Error al eliminar el detalle');
        }

        $this->vendorInvoiceDetail->purgeDeleted();
        $this->vendorInvoice->purgeDeleted();

        $listProducts = json_decode($infoInvoice["listProducts"], true);

        // Al eliminar factura de compra, se descuentan las existencias añadidas
        if (is_array($listProducts)) {
            foreach ($listProducts as $key => $value) {
                $product = $this->products->find($value["idProduct"]);

                if (isset($product["validateStock"]) && $product["validateStock"] == "on") {
                    $stock = $product["stock"] - $value["cant"];
                    $newStock["stock"] = $stock;

                    if ($this->products->update($value["idProduct"], $newStock) === false) {
                        $this->vendorInvoice->db->transRollback();
                        return $this->failNotFound('Error al actualizar el Stock');
                    }
                }

                if (isset($product["inventarioRiguroso"]) && $product["inventarioRiguroso"] == "on") {
                    $datosSaldo["idEmpresa"] = $infoInvoice["idEmpresa"];
                    $datosSaldo["idAlmacen"] = $value["idAlmacen"];
                    $datosSaldo["idProducto"] = $value["idProduct"];
                    $datosSaldo["lote"] = $value["lote"];

                    $datosNuevosSaldo = $this->saldos->select("*")->where($datosSaldo)->first();
                    if ($datosNuevosSaldo) {
                        $datosNuevosSaldo["cantidad"] = $datosNuevosSaldo["cantidad"] - $value["cant"];
                        if ($this->saldos->update($datosNuevosSaldo["id"], $datosNuevosSaldo) === false) {
                            $this->vendorInvoice->db->transRollback();
                            return $this->failNotFound("Error al actualizar el saldo $value[idProduct]");
                        }
                    }
                }
            }
        }

        $datosBitacora["description"] = 'Se eliminó la factura de proveedor: ' . json_encode($infoInvoice);
        $datosBitacora["user"] = $userName;
        $datosBitacora["module"] = "VENDOR_INVOICE";
        $datosBitacora["idMovModule"] = $id;

        $this->log->save($datosBitacora);

        $this->vendorInvoice->db->transCommit();
        return $this->respondDeleted($found, 'Eliminado Correctamente');
    }

    public function descargaAcuseCancelacion($uuid) {
        $datosXML = $this->xml->select("*")->where("uuidTimbre", $uuid)->find();

        $this->response->setHeader("Content-Type", "text/xml");
        echo $datosXML[0]["acuseCancelacion"];
    }

    public function enlazaFactura() {
        $auth = service('authentication');
        if (!$auth->check()) {
            $this->session->set('redirect_url', current_url());
            echo "No se ha iniciado Session";
            return;
        }

        $userName = user()->username;
        $request = service('request');
        $postData = $request->getPost();

        $factura = $this->vendorInvoice->select("*")->where("UUID", $postData["uuidFactura"])->first();
        $xml = $this->xml->select("*")->where("uuidTimbre", $postData["uuidTimbre"])->first();

        $datos["idDocumento"] = $factura["id"];
        $datos["uuidXML"] = $postData["uuidTimbre"];
        $datos["tipo"] = "com";
        $datos["importe"] = $xml["total"] ?? 0;

        if ($this->enlaceXML->save($datos) === false) {
            $errores = $this->enlaceXML->errors();
            $listErrors = "";
            foreach ($errores as $field => $error) {
                $listErrors .= $error . " ";
            }
            echo $listErrors;
            return;
        }

        $datosBitacora["description"] = "Se enlazó el XML $postData[uuidTimbre] con la factura $postData[uuidFactura]: " . json_encode($datos);
        $datosBitacora["user"] = $userName;

        $this->log->save($datosBitacora);

        echo "Guardado Correctamente";
    }

    public function xmlSinAsignar($tipo) {
        $userName = user()->username;
        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == 0) {
            $empresasID[0] = "0";
        } else {
            $empresasID = array_column($titulos["empresas"], "id");
        }

        if ($this->request->isAJAX()) {
            $params = [
                'draw' => $this->request->getGet('draw'),
                'start' => $this->request->getGet('start'),
                'length' => $this->request->getGet('length'),
                'order' => $this->request->getGet('order'),
                'columns' => $this->request->getGet('columns'),
                'search' => $this->request->getGet('search'),
            ];

            $datos = $this->xml->mdlXMLSinAsignar($empresasID, $tipo, $params);

            return $this->response->setJSON([
                        'draw' => intval($params['draw']),
                        'recordsTotal' => $datos['recordsTotal'],
                        'recordsFiltered' => $datos['recordsFiltered'],
                        'data' => $datos['data'],
            ]);
        }
    }

    public function report($uuid, $isMail = 0) {
        $dataInvoice = $this->vendorInvoice->where("UUID", $uuid)->first();
        $listProducts = json_decode($dataInvoice["listProducts"], true);
        $user = $this->user->where("id", $dataInvoice["idUser"])->first();
        $custumer = $this->custumer->where("id", $dataInvoice["idCustumer"])->where("deleted_at", null)->first();
        $datosEmpresa = $this->empresa->select("*")->where("id", $dataInvoice["idEmpresa"])->first();
        $datosEmpresaObj = $this->empresa->select("*")->where("id", $dataInvoice["idEmpresa"])->asObject()->first();

        $pdf = new \julio101290\boilerplatesells\Libraries\PDFLayoutSells(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->nombreDocumento = "FACTURA DE PROVEEDOR";
        $pdf->direccion = $datosEmpresaObj->direccion ?? '';

        if (empty($datosEmpresaObj->logo)) {
            $pdf->logo = ROOTPATH . "public/images/logo/default.png";
        } else {
            $pdf->logo = ROOTPATH . "public/images/logo/" . $datosEmpresaObj->logo;
        }

        $pdf->folio = str_pad($dataInvoice["folio"], 5, "0", STR_PAD_LEFT);
        $pdf->nombreEmpresa = $datosEmpresa["nombre"] ?? '';
        $pdf->usuario = "";
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($user["username"] ?? 'Admin');
        $pdf->SetTitle('CI4JCPOS - Factura de Proveedor');
        $pdf->SetSubject('Factura de Proveedor');
        $pdf->SetKeywords('CI4JCPOS, PDF, Compras, Facturas');

        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();
        $pdf->SetY(45);

        $proveedorTexto = ($custumer["razonSocial"] ?? ($custumer["firstname"] . " " . $custumer["lastname"])) ?? 'N/A';
        $emailProveedor = $custumer["email"] ?? 'N/A';

        $bloque2 = <<<EOF
        <table style="font-size:10px; padding:0px 10px;">
            <tr>
                <td style="width: 50%; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white;">Proveedor:</td>
                <td style="width: 50%; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white;">Observaciones:</td>
            </tr>
            <tr>
                <td>
                    <b>Razón Social:</b> $proveedorTexto<br>
                    <b>Email:</b> $emailProveedor<br>
                </td>
                <td>
                    $dataInvoice[generalObservations]
                </td>
            </tr>
            <tr>
                <td style="width: 25%; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white;">Registrado por:</td>
                <td style="width: 25%; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white;">Fecha:</td>
                <td style="width: 25%; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white;">Vencimiento:</td>
                <td style="width: 25%; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white;">Folio:</td>
            </tr>
            <tr>
                <td>$user[username]</td>
                <td>$dataInvoice[date]</td>
                <td>$dataInvoice[dateVen]</td>
                <td>$dataInvoice[folio]</td>
            </tr>
        </table>
EOF;

        $pdf->writeHTML($bloque2, false, false, false, false, '');

        $bloque3 = <<<EOF
        <table style="font-size:10px; padding:5px 10px;">
            <tr>
                <td style="width: 100px; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white; text-align:center">Código</td>
                <td style="width: 200px; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white; text-align:center">Descripción</td>
                <td style="width: 60px; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white; text-align:center">Cant</td>
                <td style="width: 80px; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white; text-align:center">Precio</td>
                <td style="width: 100px; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white; text-align:center">Subtotal</td>
                <td style="width: 100px; background-color:#2c3e50; padding: 4px; font-weight:bold; color:white; text-align:center">Total</td>
            </tr>
        </table>
EOF;
        $pdf->writeHTML($bloque3, false, false, false, false, '');

        $contador = 0;
        foreach ($listProducts as $key => $value) {
            $clase = ($contador % 2 == 0) ? 'style="background-color:#ecf0f1; padding: 3px 4px;"' : 'style="background-color:white; padding: 3px 4px;"';

            $precio = number_format($value["price"], 2, ".");
            $subTotal = number_format($value["total"], 2, ".");
            $total = number_format($value["neto"], 2, ".");

            $bloque4 = <<<EOF
            <table style="font-size:10px; padding:5px 10px;">
                <tr>
                    <td $clase width:100px; text-align:center">$value[codeProduct]</td>
                    <td $clase width:200px; text-align:left">$value[description]</td>
                    <td $clase width:60px; text-align:center">$value[cant]</td>
                    <td $clase width:80px; text-align:right">$precio</td>
                    <td $clase width:100px; text-align:right">$subTotal</td>
                    <td $clase width:100px; text-align:right">$total</td>
                </tr>
            </table>
EOF;
            $contador++;
            $pdf->writeHTML($bloque4, false, false, false, false, '');
        }

        $subTotalF = number_format($dataInvoice["subTotal"], 2, ".");
        $taxesF = number_format($dataInvoice["taxes"], 2, ".");
        $totalF = number_format($dataInvoice["total"], 2, ".");

        $bloque5 = <<<EOF
        <table style="font-size:10px; padding:5px 10px;">
            <tr>
                <td style="width:440px; text-align:right">Subtotal:</td>
                <td style="width:100px; text-align:right">$subTotalF</td>
            </tr>
            <tr>
                <td style="width:440px; text-align:right">Impuestos:</td>
                <td style="width:100px; text-align:right">$taxesF</td>
            </tr>
            <tr>
                <td style="width:440px; font-weight:bold; text-align:right">Total:</td>
                <td style="width:100px; font-weight:bold; text-align:right">$totalF</td>
            </tr>
        </table>
        <br>
        <div style="font-size:8.5pt; text-align:left;"><b>UUID:</b> $dataInvoice[UUID]</div>
EOF;
        $pdf->writeHTML($bloque5, false, false, false, false, 'R');

        if ($isMail == 0) {
            ob_end_clean();
            $this->response->setHeader("Content-Type", "application/pdf");
            $pdf->Output('facturaProveedor.pdf', 'I');
        } else {
            return $pdf->Output('facturaProveedor.pdf', 'S');
        }
    }
}
