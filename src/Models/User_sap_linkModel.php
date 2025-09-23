<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class User_sap_linkModel extends Model {

    protected $table = 'user_sap_link';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['id', 'idEmpresa', 'iduser', 'sapuser', 'nameusersap', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    public function mdlGetUser_sap_link(array $idEmpresas) {
        return $this->db->table('user_sap_link a')
                        ->join('empresas b', 'a.idEmpresa = b.id')
                        ->join('users c', 'a.iduser = c.id')
                        ->select("a.id,c.username, a.idEmpresa, a.iduser, a.sapuser,nameusersap, a.created_at, a.updated_at, a.deleted_at, b.nombre AS nombreEmpresa")
                        ->whereIn('a.idEmpresa', $idEmpresas);
    }

    public function mdlGetUsers($search) {

        return $this->db->table('users a, usuariosempresa b')
                        ->select('a.id,a.username,a.firstname,a.lastname')
                        ->where('b.idEmpresa', 1)
                        ->where('b.idUsuario', 'a.id', FALSE)
                        ->groupStart()
                        ->like('a.id', '', $search)
                        ->orLike('a.username', '', $search)
                        ->orLike('a.firstname', '', $search)
                        ->orLike('a.lastname', '', $search)
                        ->groupEnd()
                        ->get();
    }
}
