<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Link_sap_branchoffice extends Migration {

    public function up() {
        // Link_sap_branchoffice
        $this->forge->addField([
            'id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idEmpresa' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'idBranchOffice' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'idBranchOfficeSAP' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('link_sap_branchoffice', true);
    }

    public function down() {
        $this->forge->dropTable('link_sap_branchoffice', true);
    }
}
