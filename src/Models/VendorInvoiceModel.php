<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class VendorInvoiceModel extends Model
{
    protected $table            = 'vendor_invoice';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $beforeInsert = ['normalizeNulls'];
    protected $beforeUpdate = ['normalizeNulls'];

    protected $allowedFields = [
        'id',
        'idEmpresa',
        'idSucursal',
        'idCustumer',
        'folio',
        'idUser',
        'listProducts',
        'taxes',
        'subTotal',
        'total',
        'balance',
        'date',
        'dateVen',
        'quoteTo',
        'delivaryTime',
        'generalObservations',
        'UUID',
        'idQuote',
        'IVARetenido',
        'ISRRetenido',
        'tipoComprobanteRD',
        'folioComprobanteRD',
        'RFCReceptor',
        'usoCFDI',
        'metodoPago',
        'formaPago',
        'razonSocialReceptor',
        'codigoPostalReceptor',
        'regimenFiscalReceptor',
        'idVehiculo',
        'idChofer',
        'tipoVehiculo',
        'idArqueoCaja',
        'esFacturaGlobal',
        'periodicidad',
        'mes',
        'anio',
        'tasaCero',
        'tipoDocumentoRelacionado',
        'UUIDRelacion',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'idEmpresa'               => 'required|integer',
        'idSucursal'              => 'required|integer',
        'idCustumer'              => 'permit_empty|integer',
        'folio'                   => 'permit_empty|integer',
        'idUser'                  => 'permit_empty|integer',
        'listProducts'            => 'permit_empty|string',
        'taxes'                   => 'permit_empty|decimal',
        'subTotal'                => 'permit_empty|decimal',
        'total'                   => 'permit_empty|decimal',
        'balance'                 => 'permit_empty|decimal',
        'date'                    => 'permit_empty|valid_date[Y-m-d]',
        'dateVen'                 => 'permit_empty|valid_date[Y-m-d]',
        'quoteTo'                 => 'permit_empty|string|max_length[512]',
        'delivaryTime'            => 'permit_empty|string|max_length[512]',
        'generalObservations'     => 'permit_empty|string|max_length[512]',
        'UUID'                    => 'required|string|max_length[36]',
        'idQuote'                 => 'permit_empty|integer',
        'IVARetenido'             => 'permit_empty|decimal',
        'ISRRetenido'             => 'permit_empty|decimal',
        'tipoComprobanteRD'       => 'permit_empty|string|max_length[4]',
        'folioComprobanteRD'      => 'permit_empty|integer',
        'RFCReceptor'             => 'permit_empty|string|max_length[16]',
        'usoCFDI'                 => 'permit_empty|string|max_length[32]',
        'metodoPago'              => 'permit_empty|string|max_length[32]',
        'formaPago'               => 'permit_empty|string|max_length[32]',
        'razonSocialReceptor'     => 'permit_empty|string|max_length[1024]',
        'codigoPostalReceptor'    => 'permit_empty|string|max_length[5]',
        'regimenFiscalReceptor'   => 'permit_empty|string|max_length[32]',
        'idVehiculo'              => 'permit_empty|integer',
        'idChofer'                => 'permit_empty|integer',
        'tipoVehiculo'            => 'permit_empty|string|max_length[64]',
        'idArqueoCaja'            => 'permit_empty|integer',
        'esFacturaGlobal'         => 'permit_empty|string|in_list[on,off]',
        'periodicidad'            => 'permit_empty|string|max_length[8]',
        'mes'                     => 'permit_empty|string|max_length[8]',
        'anio'                    => 'permit_empty|string|max_length[4]',
        'tasaCero'                => 'permit_empty|decimal',
        'tipoDocumentoRelacionado'=> 'permit_empty|string|max_length[5]',
        'UUIDRelacion'            => 'permit_empty|string|max_length[40]',
    ];

    protected $validationMessages = [
        'idEmpresa' => [
            'required' => 'El campo idEmpresa es obligatorio.',
            'integer'  => 'El campo idEmpresa debe ser un número entero.',
        ],
        'idSucursal' => [
            'required' => 'El campo idSucursal es obligatorio.',
            'integer'  => 'El campo idSucursal debe ser un número entero.',
        ],
        'UUID' => [
            'required' => 'El UUID es obligatorio.',
        ],
        'esFacturaGlobal' => [
            'in_list' => 'El valor de esFacturaGlobal debe ser "on" o "off".',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Obtener facturas con paginación y filtros DataTables
     */
    public function mdlGetVendorInvoice($empresas, $params = [])
    {
        $dbDriver = $this->db->getPlatform();
        $nameExpression = $dbDriver === 'Postgre'
            ? "(b.firstname || ' ' || b.lastname) AS \"nameCustumer\""
            : "CONCAT(b.firstname, ' ', b.lastname) AS nameCustumer";

        $builder = $this->db->table('vendor_invoice a')
            ->select("
                a.UUID,
                a.id,
                {$nameExpression},
                a.idCustumer,
                b.razonSocial,
                a.folio,
                a.date,
                b.email AS correoCliente,
                a.dateVen,
                a.total,
                a.taxes,
                a.subTotal,
                a.balance,
                a.delivaryTime,
                a.generalObservations,
                a.idQuote,
                a.IVARetenido,
                a.ISRRetenido,
                a.tipoComprobanteRD,
                a.folioComprobanteRD,
                a.idSucursal,
                a.RFCReceptor,
                a.usoCFDI,
                a.metodoPago,
                a.formaPago,
                a.razonSocialReceptor,
                a.codigoPostalReceptor,
                a.regimenFiscalReceptor,
                a.idVehiculo,
                a.idChofer,
                a.tipoVehiculo,
                a.idArqueoCaja,
                a.tasaCero,
                a.esFacturaGlobal,
                a.periodicidad,
                a.mes,
                a.anio,
                a.tipoDocumentoRelacionado,
                a.UUIDRelacion,
                a.created_at,
                a.updated_at,
                a.deleted_at
            ")
            ->join('custumers b', 'a.idCustumer = b.id', 'left')
            ->join('empresas c', 'a.idEmpresa = c.id', 'left')
            ->whereIn('a.idEmpresa', $empresas);

        if (!empty($params['columns'])) {
            foreach ($params['columns'] as $col) {
                if (!empty($col['search']['value'])) {
                    $builder->like($col['data'], $col['search']['value']);
                }
            }
        }

        if (!empty($params['order'])) {
            foreach ($params['order'] as $ord) {
                $colIndex = $ord['column'];
                $dir      = $ord['dir'] ?? 'asc';
                $colName  = $params['columns'][$colIndex]['data'];
                $builder->orderBy($colName, $dir);
            }
        }

        if (isset($params['length']) && $params['length'] != -1) {
            $builder->limit($params['length'], $params['start']);
        }

        $data = $builder->get()->getResultArray();

        $total = $this->db->table('vendor_invoice a')
            ->join('custumers b', 'a.idCustumer = b.id', 'left')
            ->join('empresas c', 'a.idEmpresa = c.id', 'left')
            ->whereIn('a.idEmpresa', $empresas)
            ->countAllResults();

        return [
            'data'            => $data,
            'recordsTotal'    => $total,
            'recordsFiltered' => count($data),
        ];
    }

    /**
     * Búsqueda por filtros avanzados y rango de fechas
     */
    public function mdlGetVendorInvoiceFilters(
        $empresas, $from, $to, $allInvoices,
        $empresa = 0, $sucursal = 0, $cliente = 0,
        $params = []
    ) {
        $dbDriver = $this->db->getPlatform();
        $nameExpression = $dbDriver === 'Postgre'
            ? "(b.firstname || ' ' || b.lastname) AS \"nameCustumer\""
            : "CONCAT(b.firstname, ' ', b.lastname) AS nameCustumer";

        $builder = $this->db->table('vendor_invoice a')
            ->select("
                a.UUID,
                a.id,
                {$nameExpression},
                a.idCustumer,
                b.razonSocial,
                a.folio,
                a.date,
                b.email AS correoCliente,
                a.dateVen,
                a.total,
                a.taxes,
                a.subTotal,
                a.balance,
                a.delivaryTime,
                a.generalObservations,
                a.idQuote,
                a.IVARetenido,
                a.ISRRetenido,
                a.tipoComprobanteRD,
                a.folioComprobanteRD,
                a.idSucursal,
                a.RFCReceptor,
                a.usoCFDI,
                a.metodoPago,
                a.formaPago,
                a.razonSocialReceptor,
                a.codigoPostalReceptor,
                a.regimenFiscalReceptor,
                a.idVehiculo,
                a.idChofer,
                a.tipoVehiculo,
                a.idArqueoCaja,
                a.tasaCero,
                a.esFacturaGlobal,
                a.periodicidad,
                a.mes,
                a.anio,
                a.tipoDocumentoRelacionado,
                a.UUIDRelacion,
                a.created_at,
                a.updated_at,
                a.deleted_at
            ")
            ->join('custumers b', 'a.idCustumer = b.id', 'left')
            ->join('empresas c', 'a.idEmpresa = c.id', 'left')
            ->where('a.date >=', $from . ' 00:00:00')
            ->where('a.date <=', $to . ' 23:59:59')
            ->whereIn('a.idEmpresa', $empresas);

        if ($allInvoices !== 'true') {
            $builder->where('a.balance >', 0);
        }

        if ($empresa != '0') {
            $builder->where('a.idEmpresa', $empresa);
        }

        if ($sucursal != '0') {
            $builder->where('a.idSucursal', $sucursal);
        }

        if ($cliente != '0') {
            $builder->where('a.idCustumer', $cliente);
        }

        $search = $params['search']['value'] ?? '';
        if (!empty($search)) {
            $builder->groupStart();
            foreach ($params['columns'] as $col) {
                if (!empty($col['data'])) {
                    $builder->orLike($col['data'], $search);
                }
            }
            $builder->groupEnd();
        }

        if (!empty($params['columns'])) {
            foreach ($params['columns'] as $col) {
                if (!empty($col['search']['value'])) {
                    $builder->like($col['data'], $col['search']['value']);
                }
            }
        }

        if (!empty($params['order'])) {
            foreach ($params['order'] as $ord) {
                $colIndex = $ord['column'];
                $dir      = $ord['dir'] ?? 'asc';
                $colName  = $params['columns'][$colIndex]['data'];
                $builder->orderBy($colName, $dir);
            }
        }

        if (isset($params['length']) && $params['length'] != -1) {
            $builder->limit($params['length'], $params['start']);
        }

        $data = $builder->get()->getResultArray();

        $totalBuilder = $this->db->table('vendor_invoice a')
            ->join('custumers b', 'a.idCustumer = b.id', 'left')
            ->join('empresas c', 'a.idEmpresa = c.id', 'left')
            ->where('a.date >=', $from . ' 00:00:00')
            ->where('a.date <=', $to . ' 23:59:59')
            ->whereIn('a.idEmpresa', $empresas);

        if ($allInvoices !== 'true') {
            $totalBuilder->where('a.balance >', 0);
        }

        if ($empresa != '0') {
            $totalBuilder->where('a.idEmpresa', $empresa);
        }

        if ($sucursal != '0') {
            $totalBuilder->where('a.idSucursal', $sucursal);
        }

        if ($cliente != '0') {
            $totalBuilder->where('a.idCustumer', $cliente);
        }

        $total = $totalBuilder->countAllResults();

        return [
            'data'            => $data,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
        ];
    }

    /**
     * Saldos pendientes / Cartera vencida
     */
    public function mdlCarteraVencida($empresas, $sucursales)
    {
        $builder = $this->db->table('vendor_invoice a')
            ->select('b.id, b.razonSocial, SUM(a.balance) AS deuda')
            ->join('custumers b', 'a.idCustumer = b.id')
            ->where('a.balance >', 1)
            ->whereIn('a.idEmpresa', $empresas)
            ->whereIn('a.idSucursal', $sucursales)
            ->groupBy(['b.id', 'b.razonSocial'])
            ->orderBy('deuda', 'DESC')
            ->limit(10);

        return $builder->get()->getResultArray();
    }

    /**
     * Obtener comprobante por UUID
     */
    public function mdlGetVendorInvoiceUUID($uuid, $empresas)
    {
        $dbDriver = $this->db->getPlatform();
        $nameExpression = $dbDriver === 'Postgre'
            ? "(b.firstname || ' ' || b.lastname) AS \"nameCustumer\""
            : "CONCAT(b.firstname, ' ', b.lastname) AS nameCustumer";

        return $this->db->table('vendor_invoice a')
            ->select("
                a.idCustumer,
                a.folio,
                a.quoteTo,
                a.UUID,
                a.idUser,
                a.id,
                {$nameExpression},
                a.idEmpresa,
                c.nombre AS nombreEmpresa,
                a.listProducts,
                a.date,
                a.dateVen,
                a.total,
                a.taxes,
                a.IVARetenido,
                a.ISRRetenido,
                a.subTotal,
                a.tipoComprobanteRD,
                a.folioComprobanteRD,
                a.idQuote,
                a.delivaryTime,
                a.generalObservations,
                a.RFCReceptor,
                a.usoCFDI,
                a.metodoPago,
                a.formaPago,
                a.razonSocialReceptor,
                a.codigoPostalReceptor,
                a.regimenFiscalReceptor,
                a.idSucursal,
                a.idVehiculo,
                a.idChofer,
                a.tipoVehiculo,
                a.idArqueoCaja,
                a.tasaCero,
                a.esFacturaGlobal,
                a.periodicidad,
                a.mes,
                a.anio,
                a.tipoDocumentoRelacionado,
                a.UUIDRelacion,
                a.created_at,
                a.updated_at,
                a.deleted_at
            ")
            ->join('custumers b', 'a.idCustumer = b.id', 'left')
            ->join('empresas c', 'a.idEmpresa = c.id', 'left')
            ->where('a.UUID', $uuid)
            ->whereIn('a.idEmpresa', $empresas)
            ->get()
            ->getRowArray();
    }

    /**
     * Compras/facturas filtradas por productos y detalles
     */
    public function mdlComprasPorProductos(
        $idEmpresa = 0,
        $idSucursal = 0,
        $idProducto = 0,
        $from = null,
        $to = null,
        $idEmpresas = null,
        $idSucursales = null,
        $idCliente = 0
    ) {
        $result = $this->db->table('vendor_invoice a, vendor_invoicedetails b, empresas c, branchoffices d, custumers e')
            ->select('
                a.id,
                a.idEmpresa,
                a.idSucursal,
                c.nombre as nombreEmpresa,
                d.name as nombreSucursal,
                e.firstname as nombreCliente,
                e.lastname as apellidoCliente,
                e.razonSocial as razonSocialCliente,
                a.folio,
                a.date,
                b.idProduct,
                b.description,
                b.codeProduct,
                b.cant,
                b.price,
                b.porcentTax,
                b.tax as impuestoProducto,
                b.total as totalProducto,
                a.esFacturaGlobal,
                a.periodicidad,
                a.mes,
                a.anio,
                a.tipoDocumentoRelacionado,
                a.UUIDRelacion,
                a.taxes,
                a.subTotal,
                b.neto
            ')
            ->where('a.id', 'b.idVendorInvoice', false)
            ->where('a.idEmpresa', 'c.id', false)
            ->where('a.idSucursal', 'd.id', false)
            ->groupStart()
                ->where('\'0\'', $idEmpresa, true)
                ->orWhere('a.idEmpresa', $idEmpresa)
            ->groupEnd()
            ->groupStart()
                ->where('\'0\'', $idSucursal, true)
                ->orWhere('a.idSucursal', $idSucursal)
            ->groupEnd()
            ->groupStart()
                ->where('\'0\'', $idCliente, true)
                ->orWhere('a.idCustumer', $idCliente)
            ->groupEnd()
            ->groupStart()
                ->where('\'0\'', $idProducto, true)
                ->orWhere('b.idProduct', $idProducto)
            ->groupEnd()
            ->where('a.date >=', $from . ' 00:00:00')
            ->where('a.date <=', $to . ' 23:59:59')
            ->whereIn('a.idEmpresa', $idEmpresas)
            ->whereIn('a.idSucursal', $idSucursales)
            ->where('a.idCustumer', 'e.id', false);

        return $result;
    }

    /**
     * Compras agrupadas por producto
     */
    public function mdlComprasPorProductosAgrupado(
        $idEmpresa = 0,
        $idSucursal = 0,
        $idProducto = 0,
        $from = null,
        $to = null,
        $idEmpresas = null,
        $idSucursales = null
    ) {
        $builder = $this->db->table('vendor_invoice a')
            ->select('b.idProduct, SUM(b.cant) as cant, e.description')
            ->join('vendor_invoicedetails b', 'a.id = b.idVendorInvoice')
            ->join('products e', 'b.idProduct = e.id')
            ->join('empresas c', 'a.idEmpresa = c.id')
            ->join('branchoffices d', 'a.idSucursal = d.id');

        if (!empty($idEmpresa) && $idEmpresa != '0') {
            $builder->where('a.idEmpresa', $idEmpresa);
        }

        if (!empty($idSucursal) && $idSucursal != '0') {
            $builder->where('a.idSucursal', $idSucursal);
        }

        if (!empty($idProducto) && $idProducto != '0') {
            $builder->where('b.idProduct', $idProducto);
        }

        if (!empty($from)) {
            $builder->where('a.date >=', $from . ' 00:00:00');
        }

        if (!empty($to)) {
            $builder->where('a.date <=', $to . ' 23:59:59');
        }

        if (!empty($idEmpresas) && is_array($idEmpresas)) {
            $builder->whereIn('a.idEmpresa', $idEmpresas);
        }

        if (!empty($idSucursales) && is_array($idSucursales)) {
            $builder->whereIn('a.idSucursal', $idSucursales);
        }

        return $builder
            ->groupBy('b.idProduct, e.description')
            ->orderBy('cant', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }

    public function mdlIVARetenidoTotales($id)
    {
        return $this->db->table('vendor_invoicedetails')
            ->select('porcentIVARetenido, sum(IVARetenido) as importeTotal')
            ->where('porcentIVARetenido >', 0)
            ->where('idVendorInvoice', $id)
            ->groupBy('porcentIVARetenido')
            ->get()
            ->getResultArray();
    }

    public function mdlISRRetenidoTotales($id)
    {
        return $this->db->table('vendor_invoicedetails')
            ->select('porcentISRRetenido, sum(ISRRetenido) as importeTotal')
            ->where('porcentISRRetenido >', 0)
            ->where('idVendorInvoice', $id)
            ->groupBy('porcentISRRetenido')
            ->get()
            ->getResultArray();
    }

    protected function cleanData(array $data): array
    {
        $allowed = $this->allowedFields;

        $nullableFields = [
            'metodoPago', 'usoCFDI', 'formaPago', 'regimenFiscalReceptor', 'anio',
            'observacionesPago', 'tipoDocumentoRelacionado', 'UUIDRelacion',
            'idVehiculo', 'idChofer', 'tipoVehiculo',
        ];

        $cleaned = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed)) {
                continue;
            }

            if (in_array($key, $nullableFields) && ($value === '' || strtolower($value) === 'null')) {
                $cleaned[$key] = null;
                continue;
            }

            if ($key === 'esFacturaGlobal') {
                $cleaned[$key] = ($value === 'on') ? 'on' : 'off';
                continue;
            }

            if (in_array($key, ['subTotal', 'taxes', 'IVARetenido', 'ISRRetenido', 'total', 'balance', 'tasaCero'])) {
                $cleaned[$key] = is_numeric($value) ? (float) $value : 0.0;
                continue;
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    protected function normalizeNulls(array $data): array
    {
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $key => $value) {
                if (is_string($value) && (trim($value) === '' || strtolower(trim($value)) === 'null')) {
                    $data['data'][$key] = null;
                }
            }

            $defaults = [
                'tipoComprobanteRD'     => 'I',
                'usoCFDI'               => 'G03',
                'formaPago'             => '99',
                'metodoPago'            => 'PUE',
                'regimenFiscalReceptor' => '601',
            ];

            foreach ($defaults as $key => $defaultValue) {
                if (!isset($data['data'][$key]) || $data['data'][$key] === null) {
                    $data['data'][$key] = $defaultValue;
                }
            }
        }

        return $data;
    }
}