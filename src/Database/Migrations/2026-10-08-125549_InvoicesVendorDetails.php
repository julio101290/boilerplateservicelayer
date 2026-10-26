<?php

namespace julio101290\boilerplateservicelayer\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVarcharFieldsToVendorInvoiceDetails extends Migration {

    public function up() {
        $fields = [
            'itemCode' => [
                'type' => 'varchar',
                'constraint' => 64,
                'null' => true,
            ],
            'ocrCode' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
            ],
            'ocrCode2' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
            ],
        ];

        $this->forge->addColumn('vendor_invoice_details', $fields);
    }

    public function down() {
        $fields = [
            'itemCode',
            'ocrCode',
            'ocrCode2',
        ];

        $this->forge->dropColumn('vendor_invoice_details', $fields);
    }
}