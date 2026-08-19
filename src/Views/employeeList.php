<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<!-- Modal para agregar/editar empleado -->
<div class="modal fade" id="modalEmployee" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEmployeeLabel"><?= lang('employee.modal_title') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEmployee">
                    <input type="hidden" name="empID" id="empID" value="0">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName"><?= lang('employee.fields.firstName') ?></label>
                                <input type="text" class="form-control" name="firstName" id="firstName" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lastName"><?= lang('employee.fields.lastName') ?></label>
                                <input type="text" class="form-control" name="lastName" id="lastName" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="middleName"><?= lang('employee.fields.middleName') ?></label>
                                <input type="text" class="form-control" name="middleName" id="middleName">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="Code"><?= lang('employee.fields.Code') ?></label>
                                <input type="text" class="form-control" name="Code" id="Code">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="Active"><?= lang('employee.fields.active') ?></label>
                                <select class="form-control" name="Active" id="Active">
                                    <option value="Y"><?= lang('employee.active_yes') ?></option>
                                    <option value="N"><?= lang('employee.active_no') ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Gestión de roles -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title"><?= lang('employee.roles_title') ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-md-8">
                                                <select class="form-control select2-roles" id="selectRole" style="width:100%;"></select>
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-primary btn-block" id="btnAddRole">
                                                    <i class="fas fa-plus"></i> <?= lang('employee.btn_add_role') ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered" id="tableRoles">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('employee.fields.role_code') ?></th>
                                                        <th><?= lang('employee.fields.role_name') ?></th>
                                                        <th><?= lang('employee.fields.position') ?></th>
                                                        <th width="80"><?= lang('employee.fields.actions') ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyRoles">
                                                    <tr><td colspan="4" class="text-center"><?= lang('employee.no_roles') ?></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary" id="btnSaveEmployee"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title"><?= lang('employee.list_title') ?></h3>
        <div class="card-tools">
            <button class="btn btn-success btn-sm" id="btnNewEmployee">
                <i class="fas fa-plus"></i> <?= lang('employee.btn_new') ?>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableEmployees" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= lang('employee.fields.actions') ?></th>
                        <th><?= lang('employee.fields.empID') ?></th>
                        <th><?= lang('employee.fields.firstName') ?></th>
                        <th><?= lang('employee.fields.lastName') ?></th>
                        <th><?= lang('employee.fields.middleName') ?></th>
                        <th><?= lang('employee.fields.dept') ?></th>
                        <th><?= lang('employee.fields.active') ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    $(function () {
        var tableEmployees = $('#tableEmployees').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            order: [[1, 'asc']],
            ajax: {
                url: '<?= base_url('admin/servicelayer/employees') ?>',
                method: 'GET',
                dataType: 'json',
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columnDefs: [
                {targets: 0, orderable: false, searchable: false, width: '150px'}
            ],
            columns: [
                {
                    data: null,
                    defaultContent: '',
                    render: function (data, type, row) {
                        var empID = row.empID || '';
                        return `
            <div class="btn-group" role="group">
                <button class="btn btn-warning btn-sm btnEditEmployee" data-empID="${empID}" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>

            </div>`;
                    }
                },
                {data: 'empID'},
                {data: 'firstName'},
                {data: 'lastName'},
                {data: 'middleName'},
                {data: 'Dept'},
                {
                    data: 'Active',
                    render: function (data) {
                        return data === 'Y' ? 'Sí' : (data === 'N' ? 'No' : data);
                    }
                }
            ],
            language: {
                processing: "Cargando..."
            }
        });

        // Nuevo
        $('#btnNewEmployee').on('click', function () {
            $('#formEmployee')[0].reset();
            $('#empID').val(0);
            $('#Active').val('Y');
            $('#modalEmployeeLabel').text('<?= lang('employee.new_title') ?>');
            $('#modalEmployee').modal('show');
        });

        // Editar
        $('#tableEmployees tbody').on('click', '.btnEditEmployee', function () {
            var empID = $(this).attr('data-empid');
            if (!empID) {
                Swal.fire('Error', 'ID de empleado no válido', 'error');
                return;
            }
            $.ajax({
                url: '<?= base_url('admin/servicelayer/employees/getEmployee') ?>/' + empID,
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.empID) {
                        $('#empID').val(resp.empID);
                        $('#firstName').val(resp.firstName || '');
                        $('#lastName').val(resp.lastName || '');
                        $('#middleName').val(resp.middleName || '');
                        $('#middleName').val(resp.middleName || '');
                        $('#Code').val(resp.Code || '');
                        $('#Active').val(resp.Active || 'Y');
                        $('#modalEmployeeLabel').text('<?= lang('employee.edit_title') ?>');
                        $('#modalEmployee').modal('show');
                    } else {
                        Swal.fire('Error', 'No se pudo obtener el empleado', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error al cargar los datos', 'error');
                }
            });
        });

        // Guardar
        $('#btnSaveEmployee').on('click', function () {
            var formData = $('#formEmployee').serializeArray();
            var data = {};
            $.each(formData, function (i, field) {
                data[field.name] = field.value;
            });

            if (!data.empID || data.empID.trim() === '' || parseInt(data.empID) <= 0) {
                Swal.fire('Atención', 'Debes capturar el código de empleado', 'warning');
                return;
            }

            $.ajax({
                url: '<?= base_url('admin/servicelayer/employees/save') ?>',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function (resp) {
                    if (resp.status === 200 || resp.status === 201) {
                        $('#modalEmployee').modal('hide');
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: resp.message || 'Operación exitosa',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        tableEmployees.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Error al guardar', 'error');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || 'Error de red';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
        // Eliminar
        $('#tableEmployees tbody').on('click', '.btnDeleteEmployee', function () {
            var empID = $(this).data('empID');
            Swal.fire({
                title: '¿Eliminar empleado?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('admin/servicelayer/employees/delete') ?>/' + empID,
                        method: 'DELETE',
                        dataType: 'json',
                        success: function (resp) {
                            if (resp.status === 200) {
                                Swal.fire('Eliminado', resp.message, 'success');
                                tableEmployees.ajax.reload(null, false);
                            } else {
                                Swal.fire('Error', resp.message || 'Error al eliminar', 'error');
                            }
                        },
                        error: function () {
                            Swal.fire('Error', 'No se pudo eliminar', 'error');
                        }
                    });
                }
            });
        });

        // Draggable
        $('#modalEmployee').draggable({
            handle: '.modal-header'
        });
    });


    // ===== GESTIÓN DE ROLES =====
    var currentEmpID = 0;

// Inicializar select2 para roles
    $('#selectRole').select2({
        dropdownParent: $('#modalEmployee'),
        ajax: {
            url: '<?= base_url('admin/servicelayer/employees/getRolesAjaxSelect2') ?>',
            dataType: 'json',
            method: 'POST',
            delay: 250,
            data: function (params) {
                return {searchTerm: params.term || ''};
            },
            processResults: function (data) {
                return {results: data.results || []};
            },
            cache: true
        },
        placeholder: '<?= lang('employee.select_role') ?>',
        allowClear: true,
        language: {
            noResults: function () {
                return '<?= lang('employee.no_results') ?>';
            }
        }
    });

// Función para cargar roles del empleado actual
    function loadEmployeeRoles(empID) {
        currentEmpID = empID;
        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/getEmployeeRoles') ?>/' + empID,
            method: 'GET',
            dataType: 'json',
            success: function (resp) {
                var tbody = $('#tbodyRoles');
                tbody.empty();
                if (resp.length === 0) {
                    tbody.append('<tr><td colspan="4" class="text-center"><?= lang('employee.no_roles') ?></td></tr>');
                    return;
                }
                $.each(resp, function (i, role) {
                    var tr = '<tr>' +
                            '<td>' + (role.roleID || '') + '</td>' +
                            '<td>' + (role.RoleName || '') + '</td>' +
                            '<td>' + (role.Position || '') + '</td>' +
                            '<td><button class="btn btn-danger btn-sm btnRemoveRole" data-rolecode="' + role.roleID + '"><i class="fas fa-trash"></i></button></td>' +
                            '</tr>';
                    tbody.append(tr);
                });
            },
            error: function () {
                Swal.fire('Error', 'No se pudieron cargar los roles', 'error');
            }
        });
    }

// Al abrir el modal de edición, cargar roles
    $('#modalEmployee').on('show.bs.modal', function (e) {
        var empID = $('#empID').val();
        if (empID > 0) {
            loadEmployeeRoles(empID);
            $('#selectRole').val(null).trigger('change');
        } else {
            $('#tbodyRoles').html('<tr><td colspan="4" class="text-center"><?= lang('employee.save_first') ?></td></tr>');
        }
    });

// Agregar rol
    $('#btnAddRole').on('click', function () {
        var empID = $('#empID').val();
        if (empID == 0) {
            Swal.fire('Error', 'Primero guarda el empleado', 'warning');
            return;
        }
        var roleCode = $('#selectRole').val();
        if (!roleCode) {
            Swal.fire('Error', 'Selecciona un rol', 'warning');
            return;
        }

        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/addEmployeeRole') ?>',
            method: 'POST',
            data: {empID: empID, RoleCode: roleCode},
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 200) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: resp.message,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    loadEmployeeRoles(empID);
                    $('#selectRole').val(null).trigger('change');
                } else {
                    Swal.fire('Error', resp.message || 'Error al asignar rol', 'error');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'Error de red';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

// Eliminar rol (evento delegado)
    $(document).off('click', '#tbodyRoles .btnRemoveRole').on('click', '#tbodyRoles .btnRemoveRole', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var empID = $('#empID').val();
        var roleCode = $(this).data('rolecode');

        if (!empID || !roleCode) {
            Swal.fire('Error', 'Faltan datos para eliminar el rol', 'error');
            return;
        }

        Swal.fire({
            title: '¿Eliminar rol?',
            text: 'Rol: ' + roleCode,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: '<?= base_url('admin/servicelayer/employees/removeEmployeeRole') ?>/' + empID + '/' + encodeURIComponent(roleCode),
                method: 'DELETE',
                dataType: 'json',
                success: function (resp) {
                    if (resp.status === 200) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: resp.message || 'Rol eliminado',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        loadEmployeeRoles(empID);
                    } else {
                        Swal.fire('Error', resp.message || 'Error al eliminar', 'error');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || 'No se pudo eliminar el rol';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });

    // Editar rol (reemplazar)
    $('#tbodyRoles').on('click', '.btnEditRole', function () {
        var empID = $('#empID').val();
        var oldRoleID = $(this).data('roleid');
        // Mostrar un modal o select con roles disponibles
        // (o usar un select2 inline)
        var newRoleID = prompt('Nuevo ID de rol:', oldRoleID);
        if (newRoleID && newRoleID != oldRoleID) {
            $.ajax({
                url: '<?= base_url('admin/servicelayer/employees/updateEmployeeRole') ?>',
                method: 'POST',
                data: {empID: empID, oldRoleID: oldRoleID, newRoleID: newRoleID},
                dataType: 'json',
                success: function (resp) {
                    if (resp.status === 200) {
                        Swal.fire('Actualizado', resp.message, 'success');
                        loadEmployeeRoles(empID);
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo actualizar el rol', 'error');
                }
            });
        }
    });

</script>
<?= $this->endSection() ?>