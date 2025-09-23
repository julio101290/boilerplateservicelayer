<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNameusersapToUserSapLink extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_sap_link', [
            'nameusersap' => [
                'type'       => 'varchar',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'sapuser', // lo agrega después del campo sapuser
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_sap_link', 'nameusersap');
    }
}
