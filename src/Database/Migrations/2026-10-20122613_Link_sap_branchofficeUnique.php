<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueIndexesToLinkSapBranchoffice extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE `link_sap_branchoffice` 
            ADD UNIQUE INDEX `idx_unique_branchoffice` (`idBranchOffice`),
            ADD UNIQUE INDEX `idx_unique_branchoffice_sap` (`idBranchOfficeSAP`);'
        );
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `link_sap_branchoffice` 
            DROP INDEX `idx_unique_branchoffice`,
            DROP INDEX `idx_unique_branchoffice_sap`;'
        );
    }
}