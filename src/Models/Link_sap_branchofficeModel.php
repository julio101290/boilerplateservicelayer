<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class Link_sap_branchofficeModel extends Model
{
    protected $table            = 'link_sap_branchoffice';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['id', 'idEmpresa', 'idBranchOffice', 'idBranchOfficeSAP', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    public function mdlGetLink_sap_branchoffice(array $idEmpresas)
    {
        return $this->db->table('link_sap_branchoffice a')
            ->join('empresas b', 'a.idEmpresa = b.id')
            ->select("a.id, a.idEmpresa, a.idBranchOffice, a.idBranchOfficeSAP, a.created_at, a.updated_at, a.deleted_at, b.nombre AS nombreEmpresa")
            ->whereIn('a.idEmpresa', $idEmpresas);
    }
}