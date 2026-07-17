<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class refunds extends Migration {

    public function up() {
        // refunds
        $this->forge->addField([
                'id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'idEmpresa' => ['type' => 'int', 'constraint' => 11, 'null' => true],
                'idSucursal' => ['type' => 'bigint', 'constraint' => 20, 'null' => false],
                'idUser' => ['type' => 'int', 'constraint' => 11, 'null' => true],
                'idSupplier' => ['type' => 'int', 'constraint' => 11, 'null' => true],
                'code' => ['type' => 'int', 'constraint' => 11, 'null' => true],
                'name' => ['type' => 'varchar', 'constraint' => 128, 'null' => true],
                'U_Folio' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
                'U_Area' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
                'U_Employee' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
                'U_Comments' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
                'U_Date' => ['type' => 'date', 'null' => true],
                'U_Status' => ['type' => 'varchar', 'constraint' => 8, 'null' => true],
                'U_User_Code' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
                'U_CodeMov' => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
                'U_TypeVoucher' => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
                'list' => ['type' => 'text', 'null' => true],
                'taxes' => ['type' => 'decimal', 'constraint' => "18,4", 'null' => true],
                'subTotal' => ['type' => 'decimal', 'constraint' => "18,4", 'null' => true],
                'total' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
                'balance' => ['type' => 'decimal', 'constraint' => "18,4", 'null' => true],
                'date' => ['type' => 'date', 'null' => true],
                'dateVen' => ['type' => 'date', 'null' => true],
                'quoteTo' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
                'delivaryTime' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
                'generalObservations' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
                'UUID' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
                'IVARetenido' => ['type' => 'decimal', 'constraint' => 18, 'null' => false],
                'ISRRetenido' => ['type' => 'decimal', 'constraint' => 18, 'null' => false],
                'tasaCero' => ['type' => 'decimal', 'constraint' => 18, 'null' => true],
                'created_at' => ['type' => 'datetime', 'null' => true],
                'updated_at' => ['type' => 'datetime', 'null' => true],
                'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('refunds', true);
    }

    public function down() {
        $this->forge->dropTable('refunds', true);
    }
}
