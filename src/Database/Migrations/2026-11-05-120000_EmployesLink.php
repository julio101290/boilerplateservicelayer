<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Employee_sap_link extends Migration {

    public function up() {
        // Employee_sap_link
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idEmpresa'       => ['type' => 'bigint', 'constraint' => 20, 'null' => false],
            'iduser'          => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'sapemployee'     => ['type' => 'varchar', 'constraint' => 32, 'null' => true],
            'nameemployeesap' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'datetime', 'null' => true],
            'updated_at'      => ['type' => 'datetime', 'null' => true],
            'deleted_at'      => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('employee_sap_link', true);
    }

    public function down() {
        $this->forge->dropTable('employee_sap_link', true);
    }
}