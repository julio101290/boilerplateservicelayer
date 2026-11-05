<?php

namespace julio101290\boilerplateservicelayer\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotesSapTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'idEmpresa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'idSucursal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'bplIdSAP' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'whsCode' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'idPriceList' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'namePriceList' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'idVendedor' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'nombreVendedor' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'codigo' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'cardCode' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => false,
            ],
            'cardName' => [
                'type'       => 'VARCHAR',
                'constraint' => 250,
                'null'       => true,
            ],
            'fecha' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'fechaVencimiento' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'cotizarA' => [
                'type'       => 'VARCHAR',
                'constraint' => 250,
                'null'       => true,
            ],
            'observaciones' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'plazoEntrega' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'codigoPostal' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'telefono' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'metodoPago' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'formaPago' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'usoCFDI' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'porcIva' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 16.00,
            ],
            'neto' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,2',
                'default'    => 0.00,
            ],
            'impuesto' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,2',
                'default'    => 0.00,
            ],
            'total' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,2',
                'default'    => 0.00,
            ],
            'productos' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'default'    => 'PEN',
            ],
            'folioFacturaStatus' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => true,
            ],
            'sapDocEntry' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'sapDocNum' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'sapSynced' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'UUID' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['idEmpresa', 'idSucursal'], false, false, 'idx_emp_suc');
        $this->forge->addKey('UUID', false, true, 'idx_uuid_quote');
        $this->forge->addKey('idVendedor', false, false, 'idx_vendedor');

        $attributes = [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ];

        $this->forge->createTable('quotes_sap', true, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('quotes_sap', true);
    }
}