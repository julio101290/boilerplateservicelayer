<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOdbcFieldsToSapServiceLayer extends Migration
{
    public function up()
    {
        // Definimos los nuevos campos
        $fields = [
            'userFTPAnexos' => ['type' => 'varchar', 'constraint' => 128, 'null' => true],
            'passFTPAnexos' => ['type' => 'varchar', 'constraint' => 128, 'null' => true],
        ];

        // Agregamos los campos a la tabla sapservicelayer
        $this->forge->addColumn('sapservicelayer', $fields);
    }

    public function down()
    {
        // Eliminamos los campos en caso de rollback
        $this->forge->dropColumn('sapservicelayer', 'userFTPAnexos');
        $this->forge->dropColumn('sapservicelayer', 'passFTPAnexos');
    }
}
