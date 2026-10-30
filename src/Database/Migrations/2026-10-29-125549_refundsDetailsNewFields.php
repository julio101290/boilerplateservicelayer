<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductFieldsToRefundsDetails extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('codeProduct', 'refundsdetails')) {
            $fields['codeProduct'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'pdfFile',
            ];
        }

        if (!$this->db->fieldExists('claveProductoSAT', 'refundsdetails')) {
            $fields['claveProductoSAT'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'codeProduct',
            ];
        }

        if (!$this->db->fieldExists('claveUnidadSAT', 'refundsdetails')) {
            $fields['claveUnidadSAT'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'claveProductoSAT',
            ];
        }

        if (!$this->db->fieldExists('unidad', 'refundsdetails')) {
            $fields['unidad'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'claveUnidadSAT',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('refundsdetails', $fields);
        }
    }

    public function down()
    {
        $columnsToDrop = [];

        foreach (['codeProduct', 'claveProductoSAT', 'claveUnidadSAT', 'unidad'] as $col) {
            if ($this->db->fieldExists($col, 'refundsdetails')) {
                $columnsToDrop[] = $col;
            }
        }

        if (!empty($columnsToDrop)) {
            $this->forge->dropColumn('refundsdetails', $columnsToDrop);
        }
    }
}