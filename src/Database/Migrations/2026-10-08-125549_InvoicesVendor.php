<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class VendorInvoice extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'idEmpresa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'idSucursal' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
            ],
            'idCustumer' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'folio' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'idUser' => [
                'type'       => 'BIGINT',
                'constraint' => 11,
                'null'       => true,
            ],
            'listProducts' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'taxes' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
                'null'       => true,
            ],
            'subTotal' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
                'null'       => true,
            ],
            'total' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
                'null'       => true,
            ],
            'balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
                'null'       => true,
            ],
            'date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'dateVen' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'quoteTo' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
            ],
            'delivaryTime' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
            ],
            'generalObservations' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
            ],
            'UUID' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'idQuote' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'IVARetenido' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
            ],
            'ISRRetenido' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
            ],
            'tipoComprobanteRD' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
            ],
            'folioComprobanteRD' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
            ],
            'RFCReceptor' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => true,
            ],
            'usoCFDI' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'metodoPago' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'formaPago' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'razonSocialReceptor' => [
                'type'       => 'VARCHAR',
                'constraint' => 1024,
                'null'       => true,
            ],
            'codigoPostalReceptor' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            'regimenFiscalReceptor' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'idVehiculo' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'idChofer' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'tipoVehiculo' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'idArqueoCaja' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'esFacturaGlobal' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'null'       => true,
            ],
            'periodicidad' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'null'       => true,
            ],
            'mes' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
                'null'       => true,
            ],
            'anio' => [
                'type'       => 'VARCHAR',
                'constraint' => 4,
                'null'       => true,
            ],
            'tasaCero' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,0',
                'null'       => true,
            ],
            'tipoDocumentoRelacionado' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            'UUIDRelacion' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
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
        $this->forge->createTable('vendor_invoice', true, [
            'ENGINE'        => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'       => 'utf8mb4_general_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('vendor_invoice', true);
    }
}