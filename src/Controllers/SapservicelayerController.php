<?php 

 namespace julio101290\boilerplateservicelayer\Controllers;

 use App\Controllers\BaseController;
 use julio101290\boilerplateservicelayer\Models\SapservicelayerModel;
 use julio101290\boilerplatelog\Models\LogModel;
 use CodeIgniter\API\ResponseTrait;
 use julio101290\boilerplatecompanies\Models\EmpresasModel;

 class SapservicelayerController extends BaseController {
     use ResponseTrait;
     protected $log;
     protected $sapservicelayer;
     public function __construct() {
         $this->sapservicelayer = new SapservicelayerModel();
         $this->log = new LogModel();
         $this->empresa = new EmpresasModel();
         helper('menu');
         helper('utilerias');
     }
     public function index() {



        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == "0") {

            $empresasID[0] = "0";
        } else {

            $empresasID = array_column($titulos["empresas"], "id");
        }




         if ($this->request->isAJAX()) {
             // Parámetros de DataTables
    $draw = (int) ($this->request->getPost('draw') ?? 0);
    $start = (int) ($this->request->getPost('start') ?? 0);
    $length = (int) ($this->request->getPost('length') ?? 10);
    $searchValue = $this->request->getPost('search')['value'] ?? '';
    $order = $this->request->getPost('order') ?? [];
    $columns = $this->request->getPost('columns') ?? [];

    // Obtén el QueryBuilder base desde tu modelo
    $builder = $this->sapservicelayer->mdlGetSapservicelayer($empresasID);

    // Construir allowed columns dinámicamente desde el array columns enviado por DataTables
    // Se espera que cada columna tenga al menos: data (nombre lógico), searchable, orderable
    // Regla de mapeo por conveniencia:
    //  - si columns[i]['data'] contiene un punto (ej "a.id" o "b.nombre"), se usa tal cual (tras validar)
    //  - si es "nombreEmpresa" lo mapeamos a "b.nombre"
    //  - si es cualquier otro nombre simple lo mapeamos a "a.{nombre}"
    $allowedColumns = [];        // mapa: nombre_logico => columna_db (ej 'description' => 'a.description')
    $searchableColumns = [];     // lista de columnas DB que permiten búsqueda
    $orderableColumns = [];      // lista de columnas DB que permiten orden

    foreach ($columns as $col) {
        $dataName = $col['data'] ?? null;
        if (! $dataName) continue;

        // sanitizar nombre lógico (permitir letras, números, guion bajo y punto)
        if (! preg_match('/^[A-Za-z0-9_\.]+$/', $dataName)) continue;

        // determinar columna real en DB
        if (strpos($dataName, '.') !== false) {
            // si viene con prefijo tipo a.id o b.nombre, usar tal cual
            $dbCol = $dataName;
        } elseif ($dataName === 'nombreEmpresa' || strtolower($dataName) === 'nombreempresa') {
            $dbCol = 'b.nombre';
        } else {
            // por defecto mapear al alias de la tabla principal 'a'
            $dbCol = 'a.' . $dataName;
        }

        // guardar en map
        $allowedColumns[$dataName] = $dbCol;

        // revisar flags searchable/orderable (pueden venir como 'true'/'false' o booleanos)
        $isSearchable = false;
        $isOrderable = false;
        if (isset($col['searchable'])) {
            $isSearchable = filter_var($col['searchable'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($col['orderable'])) {
            $isOrderable = filter_var($col['orderable'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($isSearchable) $searchableColumns[$dataName] = $dbCol;
        if ($isOrderable) $orderableColumns[$dataName] = $dbCol;
    }

    // Helper: aplicar búsqueda global usando las columnas marcadas como searchable
    $applySearch = function($b, $term) use ($searchableColumns) {
        if ($term === '' || $term === null) return;
        if (empty($searchableColumns)) return;

        $b->groupStart();
        $first = true;
        foreach ($searchableColumns as $dbCol) {
            // usar like para cada columna searchable
            if ($first) {
                $b->like($dbCol, $term);
                $first = false;
            } else {
                $b->orLike($dbCol, $term);
            }
        }
        $b->groupEnd();
    };

    // 1) recordsTotal (sin búsqueda) -> clonar builder
    $totalQuery = clone $builder;
    $recordsTotal = (int) $totalQuery->countAllResults(false);

    // 2) builder con búsqueda para conteo filtrado y obtención de datos
    $filteredBuilder = clone $builder;
    $applySearch($filteredBuilder, $searchValue);
    $recordsFiltered = (int) $filteredBuilder->countAllResults(false);

    // 3) ordenar (usar solo columnas marcadas como orderable)
    if (! empty($order) && is_array($order)) {
        foreach ($order as $o) {
            $colIndex = (int) $o['column'];
            $dir = strtolower($o['dir']) === 'desc' ? 'DESC' : 'ASC';

            // proteger índices fuera de rango
            if (! isset($columns[$colIndex])) continue;

            $colData = $columns[$colIndex]['data'] ?? null;
            if (! $colData) continue;

            // Verificar que la columna está permitida para orden
            if (! isset($orderableColumns[$colData])) continue;

            $dbCol = $orderableColumns[$colData];
            $filteredBuilder->orderBy($dbCol . ' ' . $dir);
        }
    } else {
        // orden por defecto si no hay órdenes: id desc (si existe)
        if (in_array('a.id', $allowedColumns, true) || array_search('a.id', $allowedColumns, true) !== false) {
            $filteredBuilder->orderBy('a.id DESC');
        }
    }

    // 4) paginar y obtener rows
    if ($length != -1) {
        $filteredBuilder->limit($length, $start);
    }

    $rows = $filteredBuilder->get()->getResultArray();

    // 5) preparar respuesta
    $response = [
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $rows,
    ];

    return $this->response->setJSON($response);
         }
         $titulos["title"] = lang('sapservicelayer.title');
         $titulos["subtitle"] = lang('sapservicelayer.subtitle');
         return view('sapservicelayer', $titulos);
     }
     /**
      * Read Sapservicelayer
      */
     public function getSapservicelayer() {
        
        helper('auth');

        $idUser = user()->id;
        $titulos["empresas"] = $this->empresa->mdlEmpresasPorUsuario($idUser);

        if (count($titulos["empresas"]) == "0") {

            $empresasID[0] = "0";
        } else {

            $empresasID = array_column($titulos["empresas"], "id");
        }
        
        
        $idSapservicelayer = $this->request->getPost("idSapservicelayer");
         $datosSapservicelayer = $this->sapservicelayer->whereIn('idEmpresa',$empresasID)
         ->where("id",$idSapservicelayer)->first();
         echo json_encode($datosSapservicelayer);
     
     
        }
     /**
      * Save or update Sapservicelayer
      */
     public function save() {
         helper('auth');
         $userName = user()->username;
         $idUser = user()->id;
         $datos = $this->request->getPost();
         if ($datos["idSapservicelayer"] == 0) {
             try {
                 if ($this->sapservicelayer->save($datos) === false) {
                     $errores = $this->sapservicelayer->errors();
                     foreach ($errores as $field => $error) {
                         echo $error . " ";
                     }
                     return;
                 }
                 $dateLog["description"] = lang("vehicles.logDescription") . json_encode($datos);
                 $dateLog["user"] = $userName;
                 $this->log->save($dateLog);
                 echo "Guardado Correctamente";
             } catch (\PHPUnit\Framework\Exception $ex) {
                 echo "Error al guardar " . $ex->getMessage();
             }
         } else {
             if ($this->sapservicelayer->update($datos["idSapservicelayer"], $datos) == false) {
                 $errores = $this->sapservicelayer->errors();
                 foreach ($errores as $field => $error) {
                     echo $error . " ";
                 }
                 return;
             } else {
                 $dateLog["description"] = lang("sapservicelayer.logUpdated") . json_encode($datos);
                 $dateLog["user"] = $userName;
                 $this->log->save($dateLog);
                 echo "Actualizado Correctamente";
                 return;
             }
         }
         return;
     }
     /**
      * Delete Sapservicelayer
      * @param type $id
      * @return type
      */
     public function delete($id) {
         $infoSapservicelayer = $this->sapservicelayer->find($id);
         helper('auth');
         $userName = user()->username;
         if (!$found = $this->sapservicelayer->delete($id)) {
             return $this->failNotFound(lang('sapservicelayer.msg.msg_get_fail'));
         }
         $this->sapservicelayer->purgeDeleted();
         $logData["description"] = lang("sapservicelayer.logDeleted") . json_encode($infoSapservicelayer);
         $logData["user"] = $userName;
         $this->log->save($logData);
         return $this->respondDeleted($found, lang('sapservicelayer.msg_delete'));
     }
 }
        