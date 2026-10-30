<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Refunds extends Migration {

    public function up() {
        // Tabla de encabezado: Equivalente a @QSYS_GLO_VOUC (QSYS_Vouchers)
        $this->forge->addField([
            'id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idEmpresa' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'idSucursal' => ['type' => 'bigint', 'constraint' => 20, 'null' => false],
            'idUser' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'idSupplier' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            // Campos nativos de tabla de usuario SAP (@QSYS_GLO_VOUC)
            'code' => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // RowCode en SAP
            'name' => ['type' => 'varchar', 'constraint' => 128, 'null' => true],
            'U_Folio' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_Area' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_Employee' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'empName' => ['type' => 'varchar', 'constraint' => 150, 'null' => true], // Para mostrar nombre sin consultar SAP cada vez
            'U_Coments' => ['type' => 'varchar', 'constraint' => 254, 'null' => true], // En SAP es U_Coments
            'U_Date' => ['type' => 'date', 'null' => true],
            'U_Total' => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_Status' => ['type' => 'int', 'constraint' => 11, 'default' => 1, 'null' => true], // 1:Pendiente, 2:Pendiente Area, 4:Autorizado
            'U_UserCode' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_CodeMov' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_TypeVoucher' => ['type' => 'int', 'constraint' => 11, 'default' => 1, 'null' => true], // 1:Refund, 2:Voucher/Viatico
            'U_Branch' => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // BPLId de SAP
            // Controles adicionales de frmReceipts y control de sincronización con Service Layer
            'accountCredit' => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // txtAcc / txtCredit en frmReceipts
            'copyComments' => ['type' => 'char', 'constraint' => 1, 'default' => 'N', 'null' => true], // chk_Copy
            'sapPaymentDocEntry' => ['type' => 'int', 'constraint' => 11, 'null' => true], // DocEntry del pago al empleado (OVPM)
            'sapSynced' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0, 'null' => false],
            // Campos estándar de tu boilerplate de compras/facturas
            'list' => ['type' => 'text', 'null' => true],
            'taxes' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'subTotal' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'total' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'balance' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'date' => ['type' => 'date', 'null' => true],
            'dateVen' => ['type' => 'date', 'null' => true],
            'quoteTo' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
            'delivaryTime' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
            'generalObservations' => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
            'UUID' => ['type' => 'varchar', 'constraint' => 36, 'null' => true],
            'IVARetenido' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => false],
            'ISRRetenido' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => false],
            'tasaCero' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('U_Folio');
        $this->forge->addKey('code');
        $this->forge->createTable('refunds', true);
    }

    public function down() {
        $this->forge->dropTable('refunds', true);
    }
}
