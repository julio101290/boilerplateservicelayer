[![Latest Stable Version](https://poser.okvpn.org/julio101290/boilerplateservicelayer/v/stable)](https://packagist.org/packages/julio101290/boilerplateservicelayer) 
[![Total Downloads](https://poser.okvpn.org/julio101290/boilerplateservicelayer/downloads)](https://packagist.org/packages/julio101290/boilerplateservicelayer) 
[![Latest Unstable Version](https://poser.okvpn.org/julio101290/boilerplateservicelayer/v/unstable)](https://packagist.org/packages/julio101290/boilerplateservicelayer) 
[![License](https://poser.okvpn.org/julio101290/boilerplateservicelayer/license)](https://packagist.org/packages/julio101290/boilerplateservicelayer)

![thumbnail](https://github.com/user-attachments/assets/97c1d071-6f6c-44fe-89f2-bd2eb76c7310)


## CodeIgniter 4 Boilerplate Service Layer CFDI V4.0
**CodeIgniter4 Boilerplate Service Layer** provides a CRUD MVC for managing *SAP Service Layer* connections per company.  
It includes description, URL, port, credentials, and company database fields.  

This module integrates with other boilerplates (Companies, BranchOffice, Log) to centralize Service Layer configuration.

---

## Requirements
* PhpCfdi\SatCatalogos  
* julio101290/boilerplatelog  
* julio101290/boilerplatecompanies  
* julio101290/boilerplatebranchoffice  

---

## Installation

### Run composer commands

```bash
composer require phpcfdi/sat-catalogos
composer require julio101290/boilerplatelog
composer require julio101290/boilerplatecompanies
composer require julio101290/boilerplatebranchoffice
composer require julio101290/boilerplateservicelayer
```

### Run migrations and seeders

```bash
php spark boilerplatecompanies:installcompaniescrud
php spark boilerplatelog:installlog
php spark boilerplatebranchoffice:installbranchoffice
php spark boilerplateservicelayer:installservicelayer
```

---

### BaseController.php Configuration

Add the SAT Catalogs Factory and configure global variables with SQLite DSN:

```php
<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
// ADD
use PhpCfdi\SatCatalogos\Factory;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = [];
    public $catalogosSAT;
    public $unidadesSAT;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        date_default_timezone_set("America/Mazatlan");

        // ADD
        $dsn = "sqlite:".ROOTPATH."writable/database/catalogossat.db";
        $factory = new Factory();
        $satCatalogos = $factory->catalogosFromDsn($dsn);
        $this->catalogosSAT = $satCatalogos;
    }
}
```

---

### SAT Catalog Database
* Download and uncompress the file:  
  https://github.com/phpcfdi/resources-sat-catalogs/releases/latest/download/catalogs.db.bz2  
* Place it in:  
  `writable/database/catalogossat.db`

---

### Service Layer Menu Example
![image](https://github.com/user-attachments/assets/ae27afee-fe2d-4f28-9556-bde49f305105)

---

# Ready to Use

![image](https://github.com/user-attachments/assets/45bfe8be-8b4d-49bc-a1a9-8119beacb480)

---

## Usage
Explore the code in **routes**, **controllers**, and **views** to understand how it works.  

Finally... Happy Coding!

---

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more details about recent changes.

---

## Contributing
Contributions are welcome and greatly appreciated.

---

## License
This package is free software distributed under the terms of the [MIT license](LICENSE.md).