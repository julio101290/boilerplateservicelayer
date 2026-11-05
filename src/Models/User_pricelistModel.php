<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class User_pricelistModel extends Model
{
    protected $table            = 'user_pricelist';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'id',
        'idEmpresa',
        'iduser',
        'idPriceList',
        'namePriceList',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Consulta principal para el DataTable (Sin join a pricelists)
     */
    public function mdlGetUser_pricelist(array $empresasID)
    {
        return $this->db->table('user_pricelist a')
            ->select('
                a.id,
                a.idEmpresa,
                a.iduser,
                a.idPriceList,
                a.namePriceList,
                a.created_at,
                a.updated_at,
                a.deleted_at,
                b.nombre AS nombreEmpresa,
                CONCAT(u.username, " - ", COALESCE(u.firstname, ""), " ", COALESCE(u.lastname, "")) AS username
            ')
            ->join('empresas b', 'a.idEmpresa = b.id', 'left')
            ->join('users u', 'a.iduser = u.id', 'left')
            ->whereIn('a.idEmpresa', $empresasID)
            ->where('a.deleted_at', null);
    }

    /**
     * Búsqueda de usuarios por empresa en MariaDB para Select2
     */
    public function mdlGetUsers(string $searchTerm, int $idEmpresa)
    {
        $builder = $this->db->table('users a')
            ->select('a.id, a.username, a.firstname, a.lastname')
            ->join('usuariosempresa b', 'a.id = b.idUsuario', 'inner')
            ->where('b.idEmpresa', $idEmpresa);

        if (!empty($searchTerm)) {
            $builder->groupStart()
                ->like('a.username', $searchTerm)
                ->orLike('a.firstname', $searchTerm)
                ->orLike('a.lastname', $searchTerm)
                ->orLike('a.id', $searchTerm)
                ->groupEnd();
        }

        return $builder->limit(20)->get();
    }
}