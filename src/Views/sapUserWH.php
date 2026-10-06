<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?= esc($title) ?></h1>
                <small class="text-muted"><?= esc($subtitle) ?></small>
            </div>
            <div class="col-sm-6 text-right">
                <button type="button" class="btn btn-primary" id="btnNuevoUsuarioAlmacen">
                    <i class="fas fa-plus"></i> Nueva Asignación
                </button>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><?= esc($box_title) ?></h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tableUserWH" class="table table-striped table-bordered table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 70px;">Acciones</th>
                                <th style="width: 90px;">DocEntry</th>
                                <th style="width: 90px;">DocNum</th>
                                <th style="width: 100px;">ID Empleado</th>
                                <th>Empleado</th>
                                <th style="width: 140px;">Fecha Creación</th>
                                <th style="width: 110px;" class="text-center">Almacenes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registro / Edición -->
<div class="modal fade" id="modalUserWH" tabindex="-1" role="dialog" aria-labelledby="modalUserWHTitle" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold" id="modalUserWHTitle">Asignación de Almacenes</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUserWH" autocomplete="off">
                <input type="hidden" name="isNew" id="isNew" value="1">
                <input type="hidden" name="DocEntry" id="DocEntry" value="0">
                <input type="hidden" name="U_Empleado" id="U_Empleado" value="">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="selectEmpID" class="font-weight-bold">Empleado (OHEM) <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="selectEmpID" name="U_empID" style="width: 100%;" required>
                                <option value="">Buscar por nombre o ID...</option>
                            </select>
                        </div>
                    </div>

                    <hr class="mt-2 mb-3">

                    <label class="font-weight-bold mb-2">Agregar Almacenes Permitidos</label>
                    <div class="row align-items-end mb-3">
                        <div class="col-md-9 form-group mb-0">
                            <label for="selectWarehouse" class="small text-muted mb-1">Catálogo de Almacenes (OWHS)</label>
                            <select class="form-control select2" id="selectWarehouse" style="width: 100%;">
                                <option value="">Seleccione o busque un almacén...</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-0">
                            <button type="button" class="btn btn-outline-success btn-block" id="btnAddWarehouse">
                                <i class="fas fa-plus-circle"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-striped table-hover mb-0" id="tableDetalleAlmacenes">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 140px;">Código Almacén</th>
                                    <th>Nombre de Almacén</th>
                                    <th style="width: 60px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDetalleAlmacenes">
                                <tr id="rowEmpty">
                                    <td colspan="3" class="text-center text-muted py-3">No hay almacenes agregados a la lista.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSaveUserWH">
                        <i class="fas fa-save"></i> Guardar Asignación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
$(document).ready(function () {
    let warehousesSelected = [];

    const urls = {
        datatable: '<?= base_url('admin/servicelayer/sapuserwh') ?>',
        getRecord: '<?= base_url('admin/servicelayer/sapuserwh/getUserWH') ?>',
        save: '<?= base_url('admin/servicelayer/sapuserwh/save') ?>',
        delete: '<?= base_url('admin/servicelayer/sapuserwh/delete') ?>',
        employeesAjax: '<?= base_url('admin/servicelayer/sapuserwh/getEmployeesAjax') ?>',
        warehousesAjax: '<?= base_url('admin/servicelayer/sapuserwh/getWarehousesAjax') ?>'
    };

    // 1. DataTables Servidor
    const tableUserWH = $('#tableUserWH').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: urls.datatable,
            type: 'GET'
        },
        order: [[1, 'desc']],
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center align-middle',
                render: function (data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="${row.DocEntry}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button hidden type="button" class="btn btn-danger btn-sm btn-delete" data-id="${row.DocEntry}" data-nombre="${row.U_Empleado}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            },
            { data: 'DocEntry', className: 'align-middle' },
            { data: 'DocNum', className: 'align-middle' },
            { data: 'U_empID', className: 'align-middle' },
            { data: 'U_Empleado', className: 'align-middle font-weight-bold' },
            { data: 'CreateDate', className: 'align-middle' },
            {
                data: 'WarehousesCount',
                className: 'text-center align-middle',
                render: function (data) {
                    return `<span class="badge badge-info px-2 py-1">${data}</span>`;
                }
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        }
    });

    // 2. Select2 Empleados
    $('#selectEmpID').select2({
        dropdownParent: $('#modalUserWH'),
        placeholder: 'Buscar empleado por ID o nombre...',
        allowClear: true,
        ajax: {
            url: urls.employeesAjax,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { searchTerm: params.term || '' };
            },
            processResults: function (response) {
                return { results: response.data || [] };
            },
            cache: true
        }
    }).on('select2:select', function (e) {
        const item = e.params.data;
        $('#U_Empleado').val(item.fullName || item.text);
    }).on('select2:clear', function () {
        $('#U_Empleado').val('');
    });

    // 3. Select2 Almacenes
    $('#selectWarehouse').select2({
        dropdownParent: $('#modalUserWH'),
        placeholder: 'Buscar código o nombre de almacén...',
        allowClear: true,
        ajax: {
            url: urls.warehousesAjax,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { searchTerm: params.term || '' };
            },
            processResults: function (response) {
                return { results: response.data || [] };
            },
            cache: true
        }
    });

    // Render del grid de almacenes asignados
    function renderWarehousesTable() {
        const $tbody =$('#tbodyDetalleAlmacenes');
        $tbody.empty();

        if (warehousesSelected.length === 0) {
            $tbody.append(`
                <tr id="rowEmpty">
                    <td colspan="3" class="text-center text-muted py-3">No hay almacenes agregados a la lista.</td>
                </tr>
            `);
            return;
        }

        warehousesSelected.forEach((item, index) => {
            $tbody.append(`
                <tr data-index="${index}">
                    <td class="font-weight-bold align-middle">${item.U_WhsCode}</td>
                    <td class="align-middle">${item.U_WhsName}</td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-outline-danger btn-xs btn-remove-whs" data-index="${index}" title="Quitar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Agregar almacén a la tabla
    $('#btnAddWarehouse').on('click', function () {
        const selected = $('#selectWarehouse').select2('data')[0];
        if (!selected || !selected.id) {
            Toast.fire({ icon: 'warning', title: 'Seleccione un almacén del catálogo' });
            return;
        }

        const whsCode = (selected.whsCode || selected.id).toString().trim();
        const whsName = (selected.whsName || selected.name || selected.text || '').replace(whsCode + ' - ', '').trim();

        const exists = warehousesSelected.some(w => w.U_WhsCode.toUpperCase() === whsCode.toUpperCase());
        if (exists) {
            Toast.fire({ icon: 'warning', title: `El almacén ${whsCode} ya se encuentra agregado` });
            return;
        }

        warehousesSelected.push({
            U_WhsCode: whsCode,
            U_WhsName: whsName
        });

        $('#selectWarehouse').val(null).trigger('change');
        renderWarehousesTable();
    });

    // Quitar almacén de la lista
    $(document).on('click', '.btn-remove-whs', function () {
        const index = $(this).data('index');
        warehousesSelected.splice(index, 1);
        renderWarehousesTable();
    });

    // Resetear formulario
    function resetForm() {
        $('#formUserWH')[0].reset();
        $('#isNew').val('1');
        $('#DocEntry').val('0');
        $('#U_Empleado').val('');
        $('#selectEmpID').val(null).trigger('change').prop('disabled', false);
        $('#selectWarehouse').val(null).trigger('change');
        warehousesSelected = [];
        renderWarehousesTable();
        $('#modalUserWHTitle').text('Nueva Asignación de Almacenes');
    }

    // Abrir modal Nuevo
    $('#btnNuevoUsuarioAlmacen').on('click', function () {
        resetForm();
        $('#modalUserWH').modal('show');
    });

    // Abrir modal Edición
    $(document).on('click', '.btn-edit', function () {
        const docEntry = $(this).data('id');
        resetForm();

        Swal.fire({
            title: 'Cargando datos...',
            didOpen: () => Swal.showLoading(),
            allowOutsideClick: false
        });

        $.ajax({
            url: `${urls.getRecord}/${docEntry}`,
            type: 'GET',
            dataType: 'json'
        }).done(function (res) {
            Swal.close();
            if (res.error) {
                Swal.fire('Error', res.message || 'No fue posible obtener los datos', 'error');
                return;
            }

            const header = res.header;
            $('#isNew').val('0');
            $('#DocEntry').val(header.DocEntry);
            $('#U_Empleado').val(header.U_Empleado);
            $('#modalUserWHTitle').text(`Editar Asignación - DocEntry #${header.DocEntry}`);

            // Cargar empleado en Select2
            const optEmp = new Option(`${header.U_empID} - ${header.U_Empleado}`, header.U_empID, true, true);
            $('#selectEmpID').append(optEmp).trigger('change').prop('disabled', true);

            // Mapear líneas de detalle
            warehousesSelected = (res.details || []).map(d => ({
                U_WhsCode: d.U_WhsCode,
                U_WhsName: d.U_WhsName
            }));

            renderWarehousesTable();
            $('#modalUserWH').modal('show');
        }).fail(function (xhr) {
            Swal.close();
            Swal.fire('Error', 'Fallo de comunicación al cargar la asignación', 'error');
        });
    });

    // Guardar (POST)
    $('#formUserWH').on('submit', function (e) {
        e.preventDefault();

        const empId = $('#selectEmpID').val();
        const empName = $('#U_Empleado').val();

        if (!empId) {
            Swal.fire('Atención', 'Debe seleccionar un empleado de SAP', 'warning');
            return;
        }

        if (warehousesSelected.length === 0) {
            Swal.fire('Atención', 'Debe agregar al menos un almacén a la lista', 'warning');
            return;
        }

        const payload = {
            isNew: $('#isNew').val(),
            DocEntry: $('#DocEntry').val(),
            U_empID: empId,
            U_Empleado: empName,
            details: JSON.stringify(warehousesSelected)
        };

        const $btn =$('#btnSaveUserWH');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: urls.save,
            type: 'POST',
            data: payload,
            dataType: 'json'
        }).done(function (res) {
            if (res.status === 200) {
                $('#modalUserWH').modal('hide');
                tableUserWH.ajax.reload(null, false);
                Swal.fire('Éxito', res.message, 'success');
            } else {
                Swal.fire('Error', res.message || 'No fue posible guardar en SAP', 'error');
            }
        }).fail(function (xhr) {
            const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error inesperado en el servidor';
            Swal.fire('Error', msg, 'error');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Asignación');
        });
    });

    // Eliminar registro
    $(document).on('click', '.btn-delete', function () {
        const docEntry = $(this).data('id');
        const empleado = $(this).data('nombre');

        Swal.fire({
            title: '¿Eliminar registro?',
            text: `Se eliminarán los almacenes autorizados para ${empleado} (DocEntry: ${docEntry}). Esta acción no se puede revertir en SAP.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Eliminando en SAP...',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: `${urls.delete}/${docEntry}`,
                    type: 'POST',
                    dataType: 'json'
                }).done(function (res) {
                    if (res.status === 200) {
                        tableUserWH.ajax.reload(null, false);
                        Swal.fire('Eliminado', res.message, 'success');
                    } else {
                        Swal.fire('Error', res.message || 'No se pudo eliminar el registro', 'error');
                    }
                }).fail(function (xhr) {
                    const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error al procesar eliminación';
                    Swal.fire('Error', msg, 'error');
                });
            }
        });
    });

    // Configuración base de notificaciones Toast
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
});
</script>
<?= $this->endSection() ?>