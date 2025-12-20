<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class SapservicelayerModel extends Model {

    protected $table = 'sapservicelayer';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'id'
        , 'idEmpresa'
        , 'description'
        , 'url'
        , 'port'
        , 'nameODBC'
        , 'userODBC'
        , 'passwordODBC'
        , 'updated_at'
        , 'deleted_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'idEmpresa' => 'required|integer|greater_than[0]',
    ];
    protected $validationMessages = [
        'idEmpresa' => [
            'required' => 'El campo Empresa es obligatorio.',
            'integer' => 'El campo Empresa debe ser un número válido.',
            'greater_than' => 'El campo Empresa debe ser mayor a 0.'
        ],
    ];
    protected $skipValidation = false;

    public function mdlGetSapservicelayer($idEmpresas) {
        $result = $this->db->table('sapservicelayer a, empresas b')
                ->select('a.id'
                        . ',a.idEmpresa'
                        . ',a.description'
                        . ',a.url'
                        . ',a.port'
                        . ',a.companyDB'
                        . ',a.password'
                        . ',a.username'
                        . ',a.created_at'
                        . ',a.updated_at'
                        . ',a.deleted_at'
                        . ', b.nombre as nombreEmpresa')
                ->where('a.idEmpresa', 'b.id', false)
                ->whereIn('a.idEmpresa', $idEmpresas);

        return $result;
    }
}
