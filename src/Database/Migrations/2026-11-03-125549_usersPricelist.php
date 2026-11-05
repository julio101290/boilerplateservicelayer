<?php

namespace julio101290\boilerplateservicelayer\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserPricelistTable extends Migration
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
            'iduser' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
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
        $this->forge->addKey(['idEmpresa', 'iduser'], false, false, 'idx_empresa_user');
        $this->forge->addKey('idPriceList', false, false, 'idx_pricelist');

        $attributes = [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ];

        $this->forge->createTable('user_pricelist', true, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('user_pricelist', true);
    }
}