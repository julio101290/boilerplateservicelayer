<?php

namespace julio101290\boilerplatesells\Models;

use CodeIgniter\Model;

class RefundsDetailsModel extends Model {

    protected $table = 'refundsdetails';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $beforeInsert = ['normalizeNulls'];
    protected $beforeUpdate = ['normalizeNulls'];
    protected $allowedFields = [
        'id',
        'idRefund',
        'code',
        'name',
        'U_NA',
        'U_Line',
        'U_Date',
        'U_Type',
        'U_CodeVoucher',
        'U_DocEntry',
        'U_DocNum',
        'U_Provider',
        'U_Status',
        'U_Coments',
        'U_Subtotal',
        'U_IVA',
        'description',
        'claveProductoSAT',
        'claveUnidadSAT',
        'codeProduct',
        'cant',
        'price',
        'porcentTax',
        'tax',
        'porcentIVARetenido',
        'IVARetenido',
        'porcentISRRetenido',
        'ISRRetenido',
        'neto',
        'unidad',
        'tasaCero',
        'importeExento',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'idRefund' => 'permit_empty|integer',
        'code' => 'permit_empty|integer',
        'name' => 'permit_empty|string|max_length[64]',
        'U_NA' => 'permit_empty|string|max_length[4]',
        'U_Line' => 'permit_empty|string|max_length[4]',
        'U_Date' => 'permit_empty|valid_date[Y-m-d]',
        'U_Type' => 'permit_empty|string|max_length[4]',
        'U_CodeVoucher' => 'permit_empty|string|max_length[4]',
        'U_DocEntry' => 'permit_empty|string|max_length[4]',
        'U_DocNum' => 'permit_empty|string|max_length[16]',
        'U_Provider' => 'permit_empty|string|max_length[16]',
        'U_Status' => 'permit_empty|string|max_length[16]',
        'U_Coments' => 'permit_empty|string|max_length[1024]',
        'U_Subtotal' => 'permit_empty|decimal',
        'U_IVA' => 'permit_empty|decimal',
        'description' => 'permit_empty|string|max_length[512]',
        'claveProductoSAT' => 'required|string|max_length[64]',
        'claveUnidadSAT' => 'required|string|max_length[64]',
        'codeProduct' => 'permit_empty|string|max_length[32]',
        'cant' => 'permit_empty|decimal',
        'price' => 'permit_empty|decimal',
        'porcentTax' => 'permit_empty|decimal',
        'tax' => 'permit_empty|decimal',
        'porcentIVARetenido' => 'permit_empty|decimal',
        'IVARetenido' => 'permit_empty|decimal',
        'porcentISRRetenido' => 'permit_empty|decimal',
        'ISRRetenido' => 'permit_empty|decimal',
        'neto' => 'permit_empty|decimal',
        'unidad' => 'required|string|max_length[64]',
        'tasaCero' => 'permit_empty|string|max_length[16]',
        'importeExento' => 'permit_empty|decimal',
    ];
    protected $validationMessages = [
        'claveProductoSAT' => [
            'required' => 'El campo claveProductoSAT es obligatorio.',
        ],
        'claveUnidadSAT' => [
            'required' => 'El campo claveUnidadSAT es obligatorio.',
        ],
        'unidad' => [
            'required' => 'El campo unidad es obligatorio.',
        ],
    ];
    protected $skipValidation = false;

    protected function cleanData(array $data): array {
        $allowed = $this->allowedFields;

        // Campos que aceptan null según la migración
        $nullableFields = [
            'idRefund', 'code', 'name', 'U_NA', 'U_Line', 'U_Date', 'U_Type',
            'U_CodeVoucher', 'U_DocEntry', 'U_DocNum', 'U_Provider', 'U_Status',
            'U_Coments', 'U_Subtotal', 'U_IVA', 'description', 'codeProduct',
            'cant', 'price', 'porcentTax', 'tax', 'porcentIVARetenido',
            'IVARetenido', 'porcentISRRetenido', 'ISRRetenido', 'neto',
            'tasaCero', 'importeExento',
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
            if (in_array($key, ['U_Subtotal', 'U_IVA', 'cant', 'price', 'porcentTax', 'tax', 'porcentIVARetenido', 'IVARetenido', 'porcentISRRetenido', 'ISRRetenido', 'neto', 'importeExento'])) {
                $cleaned[$key] = is_numeric($value) ? (float) $value : 0.0;
                continue;
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    protected function normalizeNulls(array $data): array {
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $key => $value) {
                if (is_string($value) && (trim($value) === '' || strtolower(trim($value)) === 'null')) {
                    $data['data'][$key] = null;
                }
            }
        }

        return $data;
    }
}