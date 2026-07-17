<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class refundsdetails extends Migration {

    public function up() {
        // Refundsdetails
        $this->forge->addField([
            'id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idRefund' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'code' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'name' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_NA' => ['type' => 'varchar', 'constraint' => 4, 'null' => true],
            'U_Line' => ['type' => 'varchar', 'constraint' => 4, 'null' => true],
            'U_Date' => ['type' => 'date', 'null' => true],
            'U_NA' => ['type' => 'varchar', 'constraint' => 4, 'null' => true],
            'U_Type' => ['type' => 'varchar', 'constraint' => 4, 'null' => true],
            'U_CodeVoucher' => ['type' => 'varchar', 'constraint' => 4, 'null' => true],
            'U_DocEntry' => ['type' => 'varchar', 'constraint' => 4, 'null' => true],
            'U_DocNum' => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
            'U_Provider' => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
            'U_Status' => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
            'U_Coments' => ['type' => 'varchar', 'constraint' =>1024, 'null' => true],
            'U_Subtotal' => ['type' => 'decimal', 'constraint' => "18,2", 'null' => true],
            'U_IVA' => ['type' => 'decimal', 'constraint' => "18,2", 'null' => true],
            'description' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
            'cant' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'price' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'porcentTax' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'tax' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'porcentIVARetenido' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'IVARetenido' => ['type' => 'decimal', 'constraint' => "18,2", 'null' => true],
            'porcentISRRetenido' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'ISRRetenido' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'neto' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'tasaCero' => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
            'importeExento' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('refundsdetails', true);
    }

    public function down() {
        $this->forge->dropTable('refundsdetails', true);
    }
}
