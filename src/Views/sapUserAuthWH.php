<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<!-- Modal para agregar/editar Autorizaciones de Almacén -->
<div class="modal fade" id="modalAuthWH" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAuthWHLabel">Autorización de Almacén</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAuthWH">
                    <input type="hidden" name="isNew" id="isNew" value="1">
                    <input type="hidden" name="CodeHidden" id="CodeHidden" value="">

                    <!-- Cabecera: Almacén (@AUTORIZACOMPRA) -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header py-2">
                            <h6 class="card-title font-weight-bold mb-0">
                                <i class="fas fa-warehouse mr-1"></i> Datos del Almacén
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Code (Select2 en nuevo, Readonly en edición) -->
                                <div class="col-md-5">
                                    <div class="form-group mb-0" id="groupSelectCode">
                                        <label for="Code">Almacén SAP (OWHS) <span class="text-danger">*</span></label>
                                        <select class="form-control" id="Code" name="Code" style="width: 100%;" required>
                                            <option value="">Seleccione un almacén...</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-0" id="groupInputCode" style="display: none;">
                                        <label for="CodeDisplay">Código de Almacén</label>
                                        <input type="text" class="form-control font-weight-bold" id="CodeDisplay" readonly>
                                    </div>
                                </div>

                                <!-- Name -->
                                <div class="col-md-7">
                                    <div class="form-group mb-0">
                                        <label for="Name">Nombre del Almacén <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="Name" id="Name" required placeholder="Nombre descriptivo del almacén">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle: Usuarios Autorizados (@AUTORIZACOMPRADET) -->
                    <div class="card card-outline card-secondary">
                        <div class="card-header py-2 bg-light">
                            <h6 class="card-title font-weight-bold mb-0">
                                <i class="fas fa-users-cog mr-1"></i> Asignación de Usuarios y Derechos
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Barra rápida para agregar usuario -->
                            <div class="row align-items-end mb-3 bg-light p-2 border rounded">
                                <div class="col-md-4">
                                    <label for="selectNewUser" class="small font-weight-bold">Usuario SAP (OUSR)</label>
                                    <select class="form-control" id="selectNewUser" style="width: 100%;">
                                        <option value="">Buscar usuario...</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="checkNewSolComp" checked>
                                        <label class="custom-control-label small font-weight-bold" for="checkNewSolComp">Sol. Compra</label>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="checkNewPedido" checked>
                                        <label class="custom-control-label small font-weight-bold" for="checkNewPedido">Pedido</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="inputNewFolioUser" class="small font-weight-bold">Folio / Serie Usuario</label>
                                    <input type="text" class="form-control form-control-sm text-uppercase" id="inputNewFolioUser" placeholder="Ej. MZGTEPZA">
                                </div>
                                <div class="col-md-1 text-right">
                                    <button type="button" class="btn btn-primary btn-sm btn-block" id="btnAddUserRow" title="Agregar Usuario a la lista">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla editable de detalle -->
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-bordered table-striped table-hover mb-0" id="tableAuthDetails">
                                    <thead class="thead-dark text-center">
                                        <tr>
                                            <th style="width: 100px;">ID Usuario</th>
                                            <th>Nombre / Código SAP</th>
                                            <th style="width: 130px;">Sol. Compra</th>
                                            <th style="width: 130px;">Pedido</th>
                                            <th style="width: 180px;">Folio Usuario</th>
                                            <th style="width: 60px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyAuthDetails">
                                        <tr class="empty-row text-center text-muted">
                                            <td colspan="6">Sin usuarios configurados para este almacén</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?= lang('boilerplate.global.close') ?? 'Cerrar' ?>
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveAuthWH">
                    <i class="fas fa-save mr-1"></i> <?= lang('boilerplate.global.save') ?? 'Guardar' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Card principal con listado DataTables -->
<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title"><?= $box_title ?? 'Listado de Autorizaciones por Almacén' ?></h3>
        <div class="card-tools">
            <button class="btn btn-success btn-sm" id="btnNewAuthWH">
                <i class="fas fa-plus mr-1"></i> Nueva Autorización
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableAuthWH" class="table table-striped table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th width="80" class="text-center">Acciones</th>
                        <th width="120">Cód. Almacén</th>
                        <th>Nombre de Almacén</th>
                        <th width="100" class="text-center">DocEntry</th>
                        <th width="120" class="text-center">Usuarios Asignados</th>
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
        var baseControllerUrl = '<?= base_url('admin/servicelayer/sapuserauthwh') ?>';

        // 1. Select2 para selección de Almacenes (OWHS)
        $('#Code').select2({
            dropdownParent: $('#modalAuthWH'),
            placeholder: 'Seleccione un almacén SAP...',
            allowClear: true,
            ajax: {
                url: baseControllerUrl + '/getWarehousesAjax',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {searchTerm: params.term || ''};
                },
                processResults: function (data) {
                    return {results: data.data || []};
                }
            }
        }).on('select2:select', function (e) {
            var selectedData = e.params.data;
            $('#Name').val(selectedData.name || '');
        });

        // 2. Select2 para buscar usuarios SAP (OUSR)
        $('#selectNewUser').select2({
            dropdownParent: $('#modalAuthWH'),
            placeholder: 'Buscar usuario SAP...',
            allowClear: true,
            ajax: {
                url: baseControllerUrl + '/getSapUsersAjax',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {searchTerm: params.term || ''};
                },
                processResults: function (data) {
                    return {results: data.data || []};
                }
            }
        }).on('select2:select', function (e) {
            // Se llena en automático al seleccionar el usuario
            var selectedData = e.params.data;
            $('#inputNewFolioUser').val(selectedData.folioUser || '');
        }).on('select2:clear', function () {
            // Se limpia si deseleccionan el usuario
            $('#inputNewFolioUser').val('');
        });

        // 3. DataTable Principal de Almacenes Autorizados
        var tableAuthWH = $('#tableAuthWH').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            order: [[1, 'asc']],
            ajax: {
                url: baseControllerUrl,
                method: 'GET',
                dataType: 'json',
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columnDefs: [
                {targets: 0, orderable: false, searchable: false, className: 'text-center'},
                {targets: [3, 4], className: 'text-center'}
            ],
            columns: [
                {
                    data: null,
                    render: function (data, type, row) {
                        var code = encodeURIComponent(row.Code || '');
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-warning btn-sm btnEditAuthWH" data-code="${code}" title="Editar Autorización">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button hidden class="btn btn-danger btn-sm btnDeleteAuthWH" data-code="${code}" title="Eliminar Registro">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                    }
                },
                {data: 'Code', className: 'font-weight-bold'},
                {data: 'Name'},
                {data: 'DocEntry'},
                {
                    data: 'UsersCount',
                    render: function (data) {
                        return `<span class="badge badge-info p-2">${data} usuario(s)</span>`;
                    }
                }
            ],
            language: {
                processing: "Cargando registros...",
                emptyTable: "No se encontraron autorizaciones de almacén configuradas"
            }
        });

        // 4. Agregar línea de usuario a la tabla temporal
        $('#btnAddUserRow').on('click', function () {
            var userData = $('#selectNewUser').select2('data')[0];
            if (!userData || !userData.id) {
                Swal.fire('Atención', 'Seleccione un usuario SAP válido para agregar.', 'warning');
                return;
            }

            var userId = userData.id;
            var userName = userData.userName || userData.text;
            var solComp = $('#checkNewSolComp').is(':checked');
            var pedido = $('#checkNewPedido').is(':checked');
            var folioUser = $('#inputNewFolioUser').val().trim().toUpperCase();

            // Evitar duplicados por ID de usuario
            var exists = false;
            $('#tbodyAuthDetails tr').each(function () {
                if ($(this).data('userid') == userId) {
                    exists = true;
                    return false;
                }
            });

            if (exists) {
                Swal.fire('Atención', 'El usuario seleccionado ya se encuentra en la lista.', 'warning');
                return;
            }

            appendUserRow({
                U_USERID: userId,
                U_UserName: userName,
                U_SolComp: solComp ? 'Y' : 'N',
                U_Pedido: pedido ? 'Y' : 'N',
                U_FolioUser: folioUser
            });

            // Limpiar inputs de captura rápida
            $('#selectNewUser').val(null).trigger('change');
            $('#inputNewFolioUser').val('');
            $('#checkNewSolComp').prop('checked', true);
            $('#checkNewPedido').prop('checked', true);
        });

        // 5. Renderizar fila en el cuerpo de la tabla
        function appendUserRow(item) {
            $('#tbodyAuthDetails tr.empty-row').remove();

            var tr = $(`
                <tr data-userid="${item.U_USERID}">
                    <td class="text-center font-weight-bold col-userid">${item.U_USERID}</td>
                    <td class="col-username">${item.U_UserName}</td>
                    <td class="text-center">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input check-solcomp" id="swSol_${item.U_USERID}" ${item.U_SolComp === 'Y' ? 'checked' : ''}>
                            <label class="custom-control-label" for="swSol_${item.U_USERID}"></label>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input check-pedido" id="swPed_${item.U_USERID}" ${item.U_Pedido === 'Y' ? 'checked' : ''}>
                            <label class="custom-control-label" for="swPed_${item.U_USERID}"></label>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm text-uppercase input-foliouser" value="${item.U_FolioUser || ''}" placeholder="Folio">
                    </td>
                    <td class="text-center">
                        <button hidden type="button" class="btn btn-outline-danger btn-sm btnRemoveRow" title="Quitar usuario">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `);

            $('#tbodyAuthDetails').append(tr);
        }

        // Quitar fila de la tabla
        $('#tbodyAuthDetails').on('click', '.btnRemoveRow', function () {
            $(this).closest('tr').remove();
            if ($('#tbodyAuthDetails tr').length === 0) {
                $('#tbodyAuthDetails').append(`
                    <tr class="empty-row text-center text-muted">
                        <td colspan="6">Sin usuarios configurados para este almacén</td>
                    </tr>
                `);
            }
        });

        // 6. Abrir modal: Nuevo Registro
        $('#btnNewAuthWH').on('click', function () {
            $('#formAuthWH')[0].reset();
            $('#isNew').val(1);
            $('#CodeHidden').val('');

            $('#groupSelectCode').show();
            $('#groupInputCode').hide();
            $('#Code').val(null).trigger('change');
            $('#selectNewUser').val(null).trigger('change');

            $('#tbodyAuthDetails').html(`
                <tr class="empty-row text-center text-muted">
                    <td colspan="6">Sin usuarios configurados para este almacén</td>
                </tr>
            `);

            $('#modalAuthWHLabel').text('Nueva Autorización de Almacén');
            $('#modalAuthWH').modal('show');
        });

        // 7. Abrir modal: Editar Registro
        $('#tableAuthWH tbody').on('click', '.btnEditAuthWH', function () {
            var code = $(this).data('code');
            if (!code)
                return;

            $.ajax({
                url: baseControllerUrl + '/getAuthWH/' + code,
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.error || !resp.header) {
                        Swal.fire('Error', resp.message || 'No se pudo obtener el detalle del almacén', 'error');
                        return;
                    }

                    $('#formAuthWH')[0].reset();
                    $('#isNew').val(0);
                    $('#CodeHidden').val(resp.header.Code);
                    $('#CodeDisplay').val(resp.header.Code);
                    $('#Name').val(resp.header.Name);

                    $('#groupSelectCode').hide();
                    $('#groupInputCode').show();

                    $('#tbodyAuthDetails').empty();
                    if (resp.details && resp.details.length > 0) {
                        resp.details.forEach(function (line) {
                            appendUserRow(line);
                        });
                    } else {
                        $('#tbodyAuthDetails').append(`
                            <tr class="empty-row text-center text-muted">
                                <td colspan="6">Sin usuarios configurados para este almacén</td>
                            </tr>
                        `);
                    }

                    $('#modalAuthWHLabel').text('Editar Autorizaciones: ' + resp.header.Code + ' - ' + resp.header.Name);
                    $('#modalAuthWH').modal('show');
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        });

        // 8. Guardar Autorización (POST)
        $('#btnSaveAuthWH').on('click', function () {
            var isNew = $('#isNew').val() === '1';
            var code = isNew ? $('#Code').val() : $('#CodeHidden').val();
            var name = $('#Name').val().trim();

            if (!code || code.trim() === '') {
                Swal.fire('Atención', 'Debe seleccionar un almacén válido.', 'warning');
                return;
            }

            if (!name) {
                Swal.fire('Atención', 'El nombre del almacén es obligatorio.', 'warning');
                return;
            }

            // Recolectar filas de detalle
            var details = [];
            $('#tbodyAuthDetails tr').each(function () {
                var $tr = $(this);
                if ($tr.hasClass('empty-row'))
                    return;

                details.push({
                    U_USERID: $tr.data('userid'),
                    U_UserName: $tr.find('.col-username').text().trim(),
                    U_SolComp: $tr.find('.check-solcomp').is(':checked') ? 'Y' : 'N',
                    U_Pedido: $tr.find('.check-pedido').is(':checked') ? 'Y' : 'N',
                    U_FolioUser: $tr.find('.input-foliouser').val().trim()
                });
            });

            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            $.ajax({
                url: baseControllerUrl + '/save',
                method: 'POST',
                data: {
                    isNew: isNew ? 1 : 0,
                    Code: code,
                    Name: name,
                    details: JSON.stringify(details)
                },
                dataType: 'json',
                success: function (resp) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <?= lang('boilerplate.global.save') ?? 'Guardar' ?>');

                    if (resp.status === 200) {
                        $('#modalAuthWH').modal('hide');
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: resp.message || 'Configuración guardada correctamente',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        tableAuthWH.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Ocurrió un error al guardar', 'error');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <?= lang('boilerplate.global.save') ?? 'Guardar' ?>');
                    var msg = xhr.responseJSON?.message || 'Error interno del servidor';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // 9. Eliminar Registro
        $('#tableAuthWH tbody').on('click', '.btnDeleteAuthWH', function () {
            var code = $(this).data('code');
            if (!code)
                return;

            Swal.fire({
                title: '¿Eliminar autorización?',
                text: 'Se eliminará la configuración del almacén ' + decodeURIComponent(code) + ' y sus derechos asignados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: baseControllerUrl + '/delete/' + code,
                        method: 'POST',
                        dataType: 'json',
                        success: function (resp) {
                            if (resp.status === 200) {
                                Swal.fire('Eliminado', resp.message, 'success');
                                tableAuthWH.ajax.reload(null, false);
                            } else {
                                Swal.fire('Error', resp.message || 'No se pudo eliminar', 'error');
                            }
                        },
                        error: function (xhr) {
                            var msg = xhr.responseJSON?.message || 'Error al procesar la eliminación';
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });

        // 10. Arrastrar modal
        $('#modalAuthWH').draggable({
            handle: '.modal-header'
        });
    });
</script>
<?= $this->endSection() ?>