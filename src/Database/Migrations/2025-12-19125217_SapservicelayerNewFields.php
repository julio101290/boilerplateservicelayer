<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOdbcFieldsToSapServiceLayer extends Migration
{
    public function up()
    {
        // Definimos los nuevos campos
        $fields = [
            'nameODBC' => ['type' => 'varchar', 'constraint' => 128, 'null' => true],
            'userODBC' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'passwordODBC' => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
        ];

        // Agregamos los campos a la tabla sapservicelayer
        $this->forge->addColumn('sapservicelayer', $fields);
    }

    public function down()
    {
        // Eliminamos los campos en caso de rollback
        $this->forge->dropColumn('sapservicelayer', 'nameODBC');
        $this->forge->dropColumn('sapservicelayer', 'userODBC');
        $this->forge->dropColumn('sapservicelayer', 'passwordODBC');
    }
}
