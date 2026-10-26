<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class Link_sap_branchofficeModel extends Model {

    protected $table = 'link_sap_branchoffice';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['id', 'idEmpresa', 'idBranchOffice', 'idBranchOfficeSAP', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    // Reglas de validación para evitar registros duplicados
    protected $validationRules = [
        'idEmpresa' => 'required|numeric',
        'idBranchOffice' => 'required|is_unique[link_sap_branchoffice.idBranchOffice,id,{id}]',
        'idBranchOfficeSAP' => 'required|is_unique[link_sap_branchoffice.idBranchOfficeSAP,id,{id}]',
    ];
    protected $validationMessages = [
        'idBranchOffice' => [
            'required' => 'La sucursal es obligatoria.',
            'is_unique' => 'La sucursal ya se encuentra vinculada a un registro existente.'
        ],
        'idBranchOfficeSAP' => [
            'required' => 'La sucursal de SAP es obligatoria.',
            'is_unique' => 'La sucursal SAP ya está asignada a otro registro.'
        ]
    ];
    protected $skipValidation = false;

    public function mdlGetLink_sap_branchoffice(array $idEmpresas) {
        return $this->db->table('link_sap_branchoffice a')
                        ->join('empresas b', 'a.idEmpresa = b.id', 'left')
                        ->join('branchoffices c', 'a.idBranchOffice = c.id', 'left')
                        ->select("
            a.id, 
            a.idEmpresa, 
            a.idBranchOffice, 
            a.idBranchOfficeSAP, 
            a.created_at, 
            a.updated_at, 
            a.deleted_at, 
            b.nombre AS nombreEmpresa, 
            c.name AS nombreBranchOffice
        ")
                        ->whereIn('a.idEmpresa', $idEmpresas);
    }
}
