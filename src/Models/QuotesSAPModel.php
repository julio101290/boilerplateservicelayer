<?php

namespace julio101290\boilerplateservicelayer\Models;

use CodeIgniter\Model;

class QuotesSAPModel extends Model
{
    protected $table            = 'quotes_sap';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'id',
        'idEmpresa',
        'idSucursal',
        'bplIdSAP',
        'whsCode',
        'idPriceList',
        'namePriceList',
        'idVendedor',
        'nombreVendedor',
        'codigo',
        'cardCode',
        'cardName',
        'fecha',
        'fechaVencimiento',
        'cotizarA',
        'observaciones',
        'plazoEntrega',
        'codigoPostal',
        'telefono',
        'metodoPago',
        'formaPago',
        'usoCFDI',
        'porcIva',
        'neto',
        'impuesto',
        'total',
        'productos',
        'status',
        'folioFacturaStatus',
        'sapDocEntry',
        'sapDocNum',
        'sapSynced',
        'UUID',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Calcula el siguiente folio consecutivo por empresa y sucursal
     * verificando si las columnas existen en la tabla
     */
    public function getNextFolio(int $idEmpresa, int $idSucursal): int
    {
        if (!$this->db->tableExists($this->table)) {
            return 1;
        }

        $builder = $this->selectMax('codigo', 'maxCodigo');

        if ($this->db->fieldExists('idEmpresa', $this->table)) {
            $builder->where('idEmpresa', $idEmpresa);
        } elseif ($this->db->fieldExists('companie', $this->table)) {
            $builder->where('companie', $idEmpresa);
        }

        if ($this->db->fieldExists('idSucursal', $this->table)) {
            $builder->where('idSucursal', $idSucursal);
        }

        $row = $builder->first();

        return ((int) ($row['maxCodigo'] ?? 0)) + 1;
    }
}