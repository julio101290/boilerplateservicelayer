<?php

namespace julio101290\boilerplateservicelayer\Database\Seeds;

use CodeIgniter\Config\Services;
use CodeIgniter\Database\Seeder;
use Myth\Auth\Entities\User;
use Myth\Auth\Models\UserModel;

/**
 * Class BoilerplateSeeder.
 */
class BoilerplateServiceLayer extends Seeder {

    /**
     * @var Authorize
     */
    protected $authorize;

    /**
     * @var Db
     */
    protected $db;

    /**
     * @var Users
     */
    protected $users;

    public function __construct() {
        $this->authorize = Services::authorization();
        $this->db = \Config\Database::connect();
        $this->users = new UserModel();
    }

    public function run() {


        // Permission
        $this->authorize->createPermission('servicelayer-permission', 'Permissions for service layer configuration');
        $this->authorize->createPermission('user_sap_link-permission', 'Permiso para la lista de user_sap_link');
        $this->authorize->createPermission('reqauth-permission', 'Permisos para autorizar requisiciones del SAP');
        $this->authorize->createPermission('poauth-permission', 'Permiso Para Autorizar');

        // Assign Permission to user
        $this->authorize->addPermissionToUser('servicelayer-permission', 1);
        $this->authorize->addPermissionToUser('user_sap_link-permission', 1);
        $this->authorize->addPermissionToUser('reqauth-permission', 1);
        $this->authorize->addPermissionToUser('poauth-permission', 1);
    }

    public function down() {
        //
    }
}
