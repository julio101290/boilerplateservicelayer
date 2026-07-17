<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class RefundsModel extends Model {

    protected $table = 'refunds';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $beforeInsert = ['normalizeNulls'];
    protected $beforeUpdate = ['normalizeNulls'];
    protected $allowedFields = [
        'id',
        'idEmpresa',
        'idSucursal',
        'idUser',
        'idSupplier',
        'code',
        'name',
        'U_Folio',
        'U_Area',
        'U_Employee',
        'U_Comments',
        'U_Date',
        'U_Status',
        'U_User_Code',
        'U_CodeMov',
        'U_TypeVoucher',
        'list',
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
        'IVARetenido',
        'ISRRetenido',
        'tasaCero',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'idEmpresa' => 'permit_empty|integer',
        'idSucursal' => 'required|integer',
        'idUser' => 'permit_empty|integer',
        'idSupplier' => 'permit_empty|integer',
        'code' => 'permit_empty|integer',
        'name' => 'permit_empty|string|max_length[128]',
        'U_Folio' => 'permit_empty|string|max_length[36]',
        'U_Area' => 'permit_empty|string|max_length[36]',
        'U_Employee' => 'permit_empty|string|max_length[36]',
        'U_Comments' => 'permit_empty|string|max_length[36]',
        'U_Date' => 'permit_empty|valid_date[Y-m-d]',
        'U_Status' => 'permit_empty|string|max_length[8]',
        'U_User_Code' => 'permit_empty|string|max_length[36]',
        'U_CodeMov' => 'permit_empty|string|max_length[16]',
        'U_TypeVoucher' => 'permit_empty|string|max_length[16]',
        'list' => 'permit_empty|string',
        'taxes' => 'permit_empty|decimal',
        'subTotal' => 'permit_empty|decimal',
        'total' => 'permit_empty|decimal',
        'balance' => 'permit_empty|decimal',
        'date' => 'permit_empty|valid_date[Y-m-d]',
        'dateVen' => 'permit_empty|valid_date[Y-m-d]',
        'quoteTo' => 'permit_empty|string|max_length[512]',
        'delivaryTime' => 'permit_empty|string|max_length[512]',
        'generalObservations' => 'permit_empty|string|max_length[512]',
        'UUID' => 'permit_empty|string|max_length[36]',
        'IVARetenido' => 'required|decimal',
        'ISRRetenido' => 'required|decimal',
        'tasaCero' => 'permit_empty|decimal',
    ];
    protected $validationMessages = [
        'idSucursal' => [
            'required' => 'El campo idSucursal es obligatorio.',
            'integer' => 'El campo idSucursal debe ser un número entero.',
        ],
        'IVARetenido' => [
            'required' => 'El campo IVARetenido es obligatorio.',
            'decimal' => 'El campo IVARetenido debe ser un número decimal.',
        ],
        'ISRRetenido' => [
            'required' => 'El campo ISRRetenido es obligatorio.',
            'decimal' => 'El campo ISRRetenido debe ser un número decimal.',
        ],
        'UUID' => [
            'max_length' => 'El UUID no debe exceder los 36 caracteres.',
        ],
    ];
    protected $skipValidation = false;

    // ... (aquí dejo intactos mdlGetSells, mdlGetSellsFilters, mdlCarteraVencida,
    //      mdlGetSellUUID, mdlVentasPorProductos, mdlVentasPorProductosAgrupado,
    //      mdlIVARetenidoTotales, mdlISRRetenidoTotales, ya que consultan la tabla
    //      "sells" directamente con query builder crudo, no dependen de $allowedFields
    //      de este modelo)

    protected function cleanData(array $data): array {
        $allowed = $this->allowedFields;

        // Campos que aceptan null según la migración (todo excepto idSucursal, IVARetenido, ISRRetenido)
        $nullableFields = [
            'idEmpresa', 'idUser', 'idSupplier', 'code', 'name',
            'U_Folio', 'U_Area', 'U_Employee', 'U_Comments', 'U_Date',
            'U_Status', 'U_User_Code', 'U_CodeMov', 'U_TypeVoucher',
            'list', 'taxes', 'subTotal', 'total', 'balance',
            'date', 'dateVen', 'quoteTo', 'delivaryTime',
            'generalObservations', 'UUID', 'tasaCero',
        ];

        $cleaned = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed)) {
                // Ignorar campos no permitidos
                continue;
            }

            // Normalizar null
            if (in_array($key, $nullableFields) && ($value === '' || strtolower((string) $value) === 'null')) {
                $cleaned[$key] = null;
                continue;
            }

            // Para decimales, convertir string numérico a float
            if (in_array($key, ['subTotal', 'taxes', 'IVARetenido', 'ISRRetenido', 'total', 'balance', 'tasaCero'])) {
                $cleaned[$key] = is_numeric($value) ? (float) $value : 0.0;
                continue;
            }

            // En general asignar valor tal cual
            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    protected function normalizeNulls(array $data): array {
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $key => $value) {
                // Si es string "null", o string vacío, poner null real
                if (is_string($value) && (trim($value) === '' || strtolower(trim($value)) === 'null')) {
                    $data['data'][$key] = null;
                }
            }
        }

        return $data;
    }
}