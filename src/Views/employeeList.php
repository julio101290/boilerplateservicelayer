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
                                <label for="firstName"><?= lang('employee.fields.firstName') ?> *</label>
                                <input type="text" class="form-control" name="firstName" id="firstName" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lastName"><?= lang('employee.fields.lastName') ?> *</label>
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="ExtEmpNo">No. Empleado Externo *</label>
                                <input type="text" class="form-control" name="ExtEmpNo" id="ExtEmpNo" required placeholder="Ej. EMP-001">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Code">Código SAP (Auto)</label>
                                <input type="text" class="form-control" name="Code" id="Code" readonly placeholder="Automático por SAP">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="BPLId">Sucursal Principal *</label>
                                <select class="form-control" name="BPLId" id="BPLId" style="width: 100%;" required>
                                    <option value="">Cargando sucursales...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Active"><?= lang('employee.fields.active') ?></label>
                                <select class="form-control" name="Active" id="Active">
                                    <option value="Y"><?= lang('employee.active_yes') ?></option>
                                    <option value="N"><?= lang('employee.active_no') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de Sucursales Asignadas -->
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="card card-secondary card-outline">
                                <div class="card-header">
                                    <h5 class="card-title">Sucursales Asignadas al Empleado</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-md-8">
                                            <select class="form-control" id="selectBranchAssign" style="width:100%;"></select>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-secondary btn-block" id="btnAddBranch">
                                                <i class="fas fa-plus"></i> Asignar Sucursal
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="tableBranchesAssign">
                                            <thead>
                                                <tr>
                                                    <th width="100">ID Sucursal</th>
                                                    <th>Nombre Sucursal</th>
                                                    <th width="80"><?= lang('employee.fields.actions') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbodyBranchesAssign">
                                                <tr><td colspan="3" class="text-center">Sin sucursales asignadas</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gestión de roles -->
                    <div class="row mt-2">
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
                                            <button type="button" class="btn btn-primary btn-block" id="btnAddRole">
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
                        <th width="60"><?= lang('employee.fields.actions') ?></th>
                        <th><?= lang('employee.fields.empID') ?></th>
                        <th>No. Externo</th>
                        <th>Sucursal</th>
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
        var branchesCache = [];

        // Carga de catálogo de sucursales con promesa para sincronizar la edición
        function loadBranchesCatalog() {
            return $.ajax({
                url: '<?= base_url('admin/servicelayer/employees/getBranchesAjax') ?>',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    branchesCache = data || [];
                    var $select =$('#BPLId');
                    var $selectAssign =$('#selectBranchAssign');

                    $select.empty().append(new Option('Seleccione sucursal...', ''));$selectAssign.empty().append(new Option('Seleccione sucursal para asignar...', ''));

                    $.each(branchesCache, function (i, item) {
                        $select.append(new Option(item.BPLName, item.BPLId));$selectAssign.append(new Option(item.BPLId + ' - ' + item.BPLName, item.BPLId));
                    });
                },
                error: function () {
                    $('#BPLId').empty().append(new Option('Error cargando sucursales', ''));
                }
            });
        }

        // Cargar catálogo al inicio
        loadBranchesCatalog();

        // Datatable de Empleados
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
                {targets: 0, orderable: false, searchable: false, width: '60px'}
            ],
            columns: [
                {
                    data: null,
                    defaultContent: '',
                    render: function (data, type, row) {
                        var empID = row.empID || '';
                        return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm btnEditEmployee" data-empid="${empID}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>`;
                    }
                },
                {data: 'empID'},
                {data: 'ExtEmpNo'},
                {data: 'BPLName', defaultContent: ''},
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

        // Nuevo Empleado
        $('#btnNewEmployee').on('click', function () {
            $('#formEmployee')[0].reset();
            $('#empID').val(0);
            $('#ExtEmpNo').val('');
            $('#Code').val('');
            $('#BPLId').val('').trigger('change');
            $('#Active').val('Y');
            $('#tbodyRoles').html('<tr><td colspan="4" class="text-center"><?= lang('employee.save_first') ?></td></tr>');
            $('#tbodyBranchesAssign').html('<tr><td colspan="3" class="text-center">Guarde el empleado primero</td></tr>');
            $('#modalEmployeeLabel').text('<?= lang('employee.new_title') ?>');
            $('#modalEmployee').modal('show');
        });

        // Editar Empleado
        $('#tableEmployees tbody').on('click', '.btnEditEmployee', function () {
            var empID = $(this).attr('data-empid');
            if (!empID) {
                Swal.fire('Error', 'ID de empleado no válido', 'error');
                return;
            }

            // Asegurar que las sucursales estén cargadas antes de asignar el valor
            $.when(branchesCache.length === 0 ? loadBranchesCatalog() : true).done(function () {
                $.ajax({
                    url: '<?= base_url('admin/servicelayer/employees/getEmployee') ?>/' + empID,
                    method: 'GET',
                    dataType: 'json',
                    success: function (resp) {
                        if (resp.empID) {
                            $('#empID').val(resp.empID);
                            $('#ExtEmpNo').val(resp.ExtEmpNo || '');
                            $('#Code').val(resp.Code || '');
                            $('#firstName').val(resp.firstName || '');
                            $('#lastName').val(resp.lastName || '');
                            $('#middleName').val(resp.middleName || '');
                            $('#Active').val(resp.Active || 'Y');

                            // Asignar sucursal seleccionada
                            var currentBranch = resp.BPLId !== null && resp.BPLId !== undefined && resp.BPLId !== '' ? resp.BPLId : '';
                            $('#BPLId').val(currentBranch).trigger('change');

                            loadEmployeeRoles(resp.empID);
                            loadEmployeeBranches(resp.empID);

                            $('#modalEmployeeLabel').text('<?= lang('employee.edit_title') ?>');
                            $('#modalEmployee').modal('show');
                        } else {
                            Swal.fire('Error', resp.message || 'No se pudo obtener el empleado', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error de comunicación al cargar empleado', 'error');
                    }
                });
            });
        });

        // Guardar Empleado
        $('#btnSaveEmployee').on('click', function () {
            var formData = $('#formEmployee').serializeArray();
            var data = {};
            $.each(formData, function (i, field) {
                data[field.name] = field.value;
            });

            if (!data.ExtEmpNo || data.ExtEmpNo.trim() === '') {
                Swal.fire('Atención', 'Debes capturar el número de empleado externo', 'warning');
                return;
            }

            if (!data.firstName || data.firstName.trim() === '' || !data.lastName || data.lastName.trim() === '') {
                Swal.fire('Atención', 'Nombre y Apellido son obligatorios', 'warning');
                return;
            }

            if (!data.BPLId || data.BPLId === '') {
                Swal.fire('Atención', 'Debes seleccionar una sucursal principal', 'warning');
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

        $('#modalEmployee').draggable({
            handle: '.modal-header'
        });
    });

    // ===== GESTIÓN DE ROLES =====
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
        allowClear: true
    });

    function loadEmployeeRoles(empID) {
        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/getEmployeeRoles') ?>/' + empID,
            method: 'GET',
            dataType: 'json',
            success: function (resp) {
                var tbody = $('#tbodyRoles');
                tbody.empty();
                if (!resp || resp.length === 0) {
                    tbody.append('<tr><td colspan="4" class="text-center"><?= lang('employee.no_roles') ?></td></tr>');
                    return;
                }
                $.each(resp, function (i, role) {
                    var roleID = role.roleID !== undefined ? role.roleID : role.RoleID;
                    var tr = '<tr>' +
                            '<td>' + roleID + '</td>' +
                            '<td>' + (role.RoleName || role.name || '') + '</td>' +
                            '<td>' + (role.Description || role.descriptio || '') + '</td>' +
                            '<td><button type="button" class="btn btn-danger btn-sm btnRemoveRole" data-rolecode="' + roleID + '"><i class="fas fa-trash"></i></button></td>' +
                            '</tr>';
                    tbody.append(tr);
                });
            }
        });
    }

    // Agregar rol (permite -2)
    $('#btnAddRole').on('click', function () {
        var empID = $('#empID').val();
        if (empID == 0) {
            Swal.fire('Error', 'Primero guarda el empleado', 'warning');
            return;
        }

        var roleCode = $('#selectRole').val();
        if (roleCode === null || roleCode === undefined || roleCode === '') {
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
                    loadEmployeeRoles(empID);
                    $('#selectRole').val(null).trigger('change');
                } else {
                    Swal.fire('Error', resp.message || 'Error al asignar rol', 'error');
                }
            }
        });
    });

    // Eliminar rol
    $(document).off('click', '#tbodyRoles .btnRemoveRole').on('click', '#tbodyRoles .btnRemoveRole', function (e) {
        e.preventDefault();
        var empID = $('#empID').val();
        var roleCode = $(this).attr('data-rolecode');

        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/removeEmployeeRole') ?>/' + empID + '/' + encodeURIComponent(roleCode),
            method: 'DELETE',
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 200) {
                    loadEmployeeRoles(empID);
                } else {
                    Swal.fire('Error', resp.message || 'Error al eliminar', 'error');
                }
            }
        });
    });

    // ===== GESTIÓN DE MÚLTIPLES SUCURSALES =====
    function loadEmployeeBranches(empID) {
        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/getEmployeeBranches') ?>/' + empID,
            method: 'GET',
            dataType: 'json',
            success: function (resp) {
                var tbody = $('#tbodyBranchesAssign');
                tbody.empty();
                if (!resp || resp.length === 0) {
                    tbody.append('<tr><td colspan="3" class="text-center">Sin sucursales asignadas</td></tr>');
                    return;
                }
                $.each(resp, function (i, b) {
                    var tr = '<tr>' +
                            '<td>' + b.BPLId + '</td>' +
                            '<td>' + b.BPLName + '</td>' +
                            '<td><button type="button" class="btn btn-danger btn-sm btnRemoveBranch" data-bplid="' + b.BPLId + '"><i class="fas fa-trash"></i></button></td>' +
                            '</tr>';
                    tbody.append(tr);
                });
            }
        });
    }

    // Asignar sucursal secundaria
    $('#btnAddBranch').on('click', function () {
        var empID = $('#empID').val();
        if (empID == 0) {
            Swal.fire('Error', 'Primero guarda el empleado', 'warning');
            return;
        }

        var bplId = $('#selectBranchAssign').val();
        if (!bplId) {
            Swal.fire('Error', 'Selecciona una sucursal para asignar', 'warning');
            return;
        }

        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/addEmployeeBranch') ?>',
            method: 'POST',
            data: {empID: empID, BPLId: bplId},
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 200) {
                    loadEmployeeBranches(empID);
                    $('#selectBranchAssign').val('').trigger('change');
                } else {
                    Swal.fire('Error', resp.message || 'Error al asignar sucursal', 'error');
                }
            }
        });
    });

    // Remover sucursal asignada
    $(document).off('click', '#tbodyBranchesAssign .btnRemoveBranch').on('click', '#tbodyBranchesAssign .btnRemoveBranch', function (e) {
        e.preventDefault();
        var empID = $('#empID').val();
        var bplId = $(this).attr('data-bplid');

        $.ajax({
            url: '<?= base_url('admin/servicelayer/employees/removeEmployeeBranch') ?>/' + empID + '/' + bplId,
            method: 'DELETE',
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 200) {
                    loadEmployeeBranches(empID);
                } else {
                    Swal.fire('Error', resp.message || 'Error al eliminar', 'error');
                }
            }
        });
    });
</script>
<?= $this->endSection() ?>