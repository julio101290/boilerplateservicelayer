<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class RefundsModel extends Model
{
    protected $table            = 'refunds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $beforeInsert     = ['normalizeNulls'];
    protected $beforeUpdate     = ['normalizeNulls'];

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
        'empName',
        'U_Coments',
        'U_Date',
        'U_Total',
        'U_Status',
        'U_UserCode',
        'U_CodeMov',
        'U_TypeVoucher',
        'U_Branch',
        'accountCredit',
        'copyComments',
        'sapPaymentDocEntry',
        'sapSynced',
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
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'idEmpresa'           => 'permit_empty|integer',
        'idSucursal'          => 'required|integer',
        'idUser'              => 'permit_empty|integer',
        'idSupplier'          => 'permit_empty|integer',
        'code'                => 'permit_empty|string|max_length[64]',
        'name'                => 'permit_empty|string|max_length[128]',
        'U_Folio'             => 'permit_empty|string|max_length[64]',
        'U_Area'              => 'permit_empty|string|max_length[64]',
        'U_Employee'          => 'permit_empty|string|max_length[64]',
        'empName'             => 'permit_empty|string|max_length[150]',
        'U_Coments'           => 'permit_empty|string|max_length[254]',
        'U_Date'              => 'permit_empty|valid_date[Y-m-d]',
        'U_Total'             => 'permit_empty|decimal',
        'U_Status'            => 'permit_empty|integer',
        'U_UserCode'          => 'permit_empty|string|max_length[64]',
        'U_CodeMov'           => 'permit_empty|string|max_length[64]',
        'U_TypeVoucher'       => 'permit_empty|integer',
        'U_Branch'            => 'permit_empty|string|max_length[64]',
        'accountCredit'       => 'permit_empty|string|max_length[64]',
        'copyComments'        => 'permit_empty|string|max_length[1]',
        'sapPaymentDocEntry'  => 'permit_empty|integer',
        'sapSynced'           => 'permit_empty|integer',
        'list'                => 'permit_empty|string',
        'taxes'               => 'permit_empty|decimal',
        'subTotal'            => 'permit_empty|decimal',
        'total'               => 'permit_empty|decimal',
        'balance'             => 'permit_empty|decimal',
        'date'                => 'permit_empty|valid_date[Y-m-d]',
        'dateVen'             => 'permit_empty|valid_date[Y-m-d]',
        'quoteTo'             => 'permit_empty|string|max_length[512]',
        'delivaryTime'        => 'permit_empty|string|max_length[512]',
        'generalObservations' => 'permit_empty|string|max_length[512]',
        'UUID'                => 'permit_empty|string|max_length[36]',
        'IVARetenido'         => 'permit_empty|decimal',
        'ISRRetenido'         => 'permit_empty|decimal',
        'tasaCero'            => 'permit_empty|decimal',
    ];

    protected $validationMessages = [
        'idSucursal' => [
            'required' => 'El campo idSucursal es obligatorio.',
            'integer'  => 'El campo idSucursal debe ser un número entero.',
        ],
        'UUID' => [
            'max_length' => 'El UUID no debe exceder los 36 caracteres.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Obtiene un reembolso por UUID validando las empresas asignadas al usuario
     */
    public function mdlGetRefundUUID(string $uuid, array $empresasID)
    {
        return $this->db->table('refunds a')
            ->select('a.*, b.nombre AS nombreEmpresa')
            ->join('empresas b', 'a.idEmpresa = b.id', 'left')
            ->whereIn('a.idEmpresa', $empresasID)
            ->where('a.UUID', $uuid)
            ->where('a.deleted_at', null)
            ->get()
            ->getRowArray();
    }

    protected function cleanData(array $data): array
    {
        $allowed = $this->allowedFields;

        $nullableFields = [
            'idEmpresa', 'idUser', 'idSupplier', 'code', 'name',
            'U_Folio', 'U_Area', 'U_Employee', 'empName', 'U_Coments', 'U_Date',
            'U_Total', 'U_Status', 'U_UserCode', 'U_CodeMov', 'U_TypeVoucher',
            'U_Branch', 'accountCredit', 'copyComments', 'sapPaymentDocEntry',
            'list', 'taxes', 'subTotal', 'total', 'balance',
            'date', 'dateVen', 'quoteTo', 'delivaryTime',
            'generalObservations', 'UUID', 'tasaCero',
        ];

        $decimalFields = [
            'U_Total', 'subTotal', 'taxes', 'IVARetenido', 'ISRRetenido', 'total', 'balance', 'tasaCero'
        ];

        $cleaned = [];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            if (in_array($key, $nullableFields, true) && ($value === '' || strtolower((string) $value) === 'null')) {
                $cleaned[$key] = null;
                continue;
            }

            if (in_array($key, $decimalFields, true)) {
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
            $decimalNotNull = ['IVARetenido', 'ISRRetenido', 'U_Total', 'total', 'subTotal', 'taxes'];

            foreach ($data['data'] as $key => $value) {
                if (in_array($key, $decimalNotNull, true) && ($value === '' || $value === null || strtolower(trim((string) $value)) === 'null')) {
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