<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class RefundsDetailsModel extends Model
{
    protected $table            = 'refundsdetails';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $beforeInsert     = ['normalizeNulls'];
    protected $beforeUpdate     = ['normalizeNulls'];

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
        'U_RetIVA',
        'U_ISR',
        'U_IEPS',
        'U_Total',
        'U_Coment',
        'U_UserCode',
        'docFolio',
        'UUID',
        'accountType',
        'account',
        'affectable',
        'auxEmployee',
        'auxAffectable',
        'codeMovLine',
        'project',
        'adminOper',
        'whsCode',
        'dim1',
        'dim2',
        'dim3',
        'dim4',
        'dim5',
        'xmlFile',
        'pdfFile',
        'sapPaymentDocEntry',
        'description',
        'claveProductoSAT',
        'claveUnidadSAT',
        'codeProduct',
        'unidad',
        'cant',
        'price',
        'porcentTax',
        'tax',
        'porcentIVARetenido',
        'IVARetenido',
        'porcentISRRetenido',
        'ISRRetenido',
        'neto',
        'tasaCero',
        'importeExento',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'idRefund'           => 'permit_empty|integer',
        'code'               => 'permit_empty|string|max_length[64]',
        'name'               => 'permit_empty|string|max_length[64]',
        'U_NA'               => 'permit_empty|string|max_length[4]',
        'U_Line'             => 'permit_empty|string|max_length[64]',
        'U_Date'             => 'permit_empty|valid_date[Y-m-d]',
        'U_Type'             => 'permit_empty|string|max_length[64]',
        'U_CodeVoucher'      => 'permit_empty|string|max_length[64]',
        'U_DocEntry'         => 'permit_empty|string|max_length[64]',
        'U_DocNum'           => 'permit_empty|string|max_length[64]',
        'U_Provider'         => 'permit_empty|string|max_length[128]',
        'U_Status'           => 'permit_empty|string|max_length[64]',
        'U_Coments'          => 'permit_empty|string|max_length[254]',
        'U_Subtotal'         => 'permit_empty|decimal',
        'U_IVA'              => 'permit_empty|decimal',
        'U_RetIVA'           => 'permit_empty|decimal',
        'U_ISR'              => 'permit_empty|decimal',
        'U_IEPS'             => 'permit_empty|decimal',
        'U_Total'            => 'permit_empty|decimal',
        'U_Coment'           => 'permit_empty|string|max_length[254]',
        'U_UserCode'         => 'permit_empty|string|max_length[64]',
        'docFolio'           => 'permit_empty|string|max_length[64]',
        'UUID'               => 'permit_empty|string|max_length[64]',
        'accountType'        => 'permit_empty|string|max_length[32]',
        'account'            => 'permit_empty|string|max_length[64]',
        'affectable'         => 'permit_empty|string|max_length[64]',
        'auxEmployee'        => 'permit_empty|string|max_length[64]',
        'auxAffectable'      => 'permit_empty|string|max_length[64]',
        'codeMovLine'        => 'permit_empty|string|max_length[64]',
        'project'            => 'permit_empty|string|max_length[64]',
        'adminOper'          => 'permit_empty|string|max_length[1]',
        'whsCode'            => 'permit_empty|string|max_length[64]',
        'dim1'               => 'permit_empty|string|max_length[64]',
        'dim2'               => 'permit_empty|string|max_length[64]',
        'dim3'               => 'permit_empty|string|max_length[64]',
        'dim4'               => 'permit_empty|string|max_length[64]',
        'dim5'               => 'permit_empty|string|max_length[64]',
        'xmlFile'            => 'permit_empty|string|max_length[255]',
        'pdfFile'            => 'permit_empty|string|max_length[255]',
        'sapPaymentDocEntry' => 'permit_empty|integer',
        'description'        => 'permit_empty|string|max_length[512]',
        'claveProductoSAT'   => 'permit_empty|string|max_length[64]',
        'claveUnidadSAT'     => 'permit_empty|string|max_length[64]',
        'codeProduct'        => 'permit_empty|string|max_length[64]',
        'unidad'             => 'permit_empty|string|max_length[64]',
        'cant'               => 'permit_empty|decimal',
        'price'              => 'permit_empty|decimal',
        'porcentTax'         => 'permit_empty|decimal',
        'tax'                => 'permit_empty|decimal',
        'porcentIVARetenido' => 'permit_empty|decimal',
        'IVARetenido'        => 'permit_empty|decimal',
        'porcentISRRetenido' => 'permit_empty|decimal',
        'ISRRetenido'        => 'permit_empty|decimal',
        'neto'               => 'permit_empty|decimal',
        'tasaCero'           => 'permit_empty|string|max_length[16]',
        'importeExento'      => 'permit_empty|decimal',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    protected function cleanData(array $data): array
    {
        $allowed = $this->allowedFields;

        $decimalFields = [
            'U_Subtotal', 'U_IVA', 'U_RetIVA', 'U_ISR', 'U_IEPS', 'U_Total',
            'cant', 'price', 'porcentTax', 'tax', 'porcentIVARetenido',
            'IVARetenido', 'porcentISRRetenido', 'ISRRetenido', 'neto', 'importeExento'
        ];

        $cleaned = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            if (in_array($key, $decimalFields, true)) {
                $cleaned[$key] = is_numeric($value) ? (float) $value : 0.0;
                continue;
            }

            if ($value === '' || strtolower((string) $value) === 'null') {
                $cleaned[$key] = null;
                continue;
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    protected function normalizeNulls(array $data): array
    {
        if (isset($data['data']) && is_array($data['data'])) {
            $decimalFields = [
                'U_Subtotal', 'U_IVA', 'U_RetIVA', 'U_ISR', 'U_IEPS', 'U_Total',
                'cant', 'price', 'porcentTax', 'tax', 'porcentIVARetenido',
                'IVARetenido', 'porcentISRRetenido', 'ISRRetenido', 'neto', 'importeExento'
            ];

            foreach ($data['data'] as $key => $value) {
                if (in_array($key, $decimalFields, true) && ($value === '' || $value === null || strtolower(trim((string) $value)) === 'null')) {
                    $data['data'][$key] = 0.0;
                    continue;
                }

                if (is_string($value) && (trim($value) === '' || strtolower(trim($value)) === 'null')) {
                    $data['data'][$key] = null;
                }
            }
        }

        return $data;
    }
}