<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<!-- Modal para editar Activo Fijo -->
<div class="modal fade" id="modalFixedAsset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFixedAssetLabel">Editar Activo Fijo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formFixedAsset">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ItemCode">Código</label>
                                <input type="text" class="form-control" name="ItemCode" id="ItemCode" readonly>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="ItemName">Descripción</label>
                                <input type="text" class="form-control" name="ItemName" id="ItemName" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="U_BPId">Sucursal</label>
                                <select class="form-control" name="U_BPId" id="U_BPId" style="width: 100%;">
                                    <option value="">-- Sin sucursal (Vacío) --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="U_TipoEquipo">Tipo de Equipo</label>
                                <select class="form-control" name="U_TipoEquipo" id="U_TipoEquipo" style="width: 100%;">
                                    <option value="">-- Sin tipo de equipo (Vacío) --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary" id="btnSaveFixedAsset"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title">Catálogo de Activos Fijos (SAP)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableFixedAssets" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="60">Acciones</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Sucursal</th>
                        <th>Tipo de Equipo</th>
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
        var tipoEquipoCache = [];

        // Inicializar Select2 dentro del modal
        $('#U_BPId, #U_TipoEquipo').select2({
            dropdownParent: $('#modalFixedAsset'),
            width: '100%'
        });

        // Cargar catálogo de sucursales (con opción para dejar vacío)
        function loadBranchesCatalog() {
            return $.ajax({
                url: '<?= base_url('admin/servicelayer/fixedassets/getBranchesAjax') ?>',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    branchesCache = data || [];
                    var $select = $('#U_BPId');
                    $select.empty().append(new Option('-- Sin sucursal (Vacío) --', ''));

                    $.each(branchesCache, function (i, item) {
                        $select.append(new Option(item.BPLId + ' - ' + item.BPLName, item.BPLId));
                    });
                },
                error: function () {
                    $('#U_BPId').empty().append(new Option('Error cargando sucursales', ''));
                }
            });
        }

        // Cargar catálogo @TIPOEQUIPO
        function loadTipoEquipoCatalog() {
            return $.ajax({
                url: '<?= base_url('admin/servicelayer/fixedassets/getTipoEquipoAjax') ?>',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    tipoEquipoCache = data || [];
                    var $select = $('#U_TipoEquipo');
                    $select.empty().append(new Option('-- Sin tipo de equipo (Vacío) --', ''));

                    $.each(tipoEquipoCache, function (i, item) {
                        $select.append(new Option(item.Code + ' - ' + item.Name, item.Code));
                    });
                },
                error: function () {
                    $('#U_TipoEquipo').empty().append(new Option('Error cargando tipos de equipo', ''));
                }
            });
        }

        loadBranchesCatalog();
        loadTipoEquipoCatalog();

        // DataTable de Activos Fijos
        var tableFixedAssets = $('#tableFixedAssets').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            autoWidth: false,
            order: [[1, 'asc']],
            ajax: {
                url: '<?= base_url('admin/servicelayer/fixedassets') ?>',
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
                        var itemCode = row.ItemCode || '';
                        return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm btnEditAsset" data-itemcode="${itemCode}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>`;
                    }
                },
                {data: 'ItemCode'},
                {data: 'ItemName'},
                {
                    data: 'BPLName',
                    defaultContent: '',
                    render: function (data, type, row) {
                        if (!row.U_BPId) return '<span class="badge badge-secondary">Sin asignar</span>';
                        return row.BPLName ? (row.U_BPId + ' - ' + row.BPLName) : row.U_BPId;
                    }
                },
                {
                    data: 'TipoEquipoName',
                    defaultContent: '',
                    render: function (data, type, row) {
                        if (!row.U_TipoEquipo) return '<span class="badge badge-secondary">Sin asignar</span>';
                        return row.TipoEquipoName || row.U_TipoEquipo;
                    }
                }
            ],
            language: {
                processing: "Cargando..."
            }
        });

        // Abrir modal de edición
        $('#tableFixedAssets tbody').on('click', '.btnEditAsset', function () {
            var itemCode = $(this).attr('data-itemcode');
            if (!itemCode) {
                Swal.fire('Error', 'Código de activo no válido', 'error');
                return;
            }

            $.when(
                branchesCache.length === 0 ? loadBranchesCatalog() : true,
                tipoEquipoCache.length === 0 ? loadTipoEquipoCatalog() : true
            ).done(function () {
                $.ajax({
                    url: '<?= base_url('admin/servicelayer/fixedassets/getAsset') ?>',
                    method: 'POST',
                    data: {ItemCode: itemCode},
                    dataType: 'json',
                    success: function (resp) {
                        if (resp.ItemCode) {
                            $('#ItemCode').val(resp.ItemCode);
                            $('#ItemName').val(resp.ItemName || '');

                            var currentBranch = resp.U_BPId !== null && resp.U_BPId !== undefined ? String(resp.U_BPId) : '';
                            $('#U_BPId').val(currentBranch).trigger('change');

                            var currentTipo = resp.U_TipoEquipo !== null && resp.U_TipoEquipo !== undefined ? String(resp.U_TipoEquipo) : '';
                            $('#U_TipoEquipo').val(currentTipo).trigger('change');

                            $('#modalFixedAsset').modal('show');
                        } else {
                            Swal.fire('Error', resp.message || 'No se pudo obtener el activo fijo', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error de comunicación al cargar el activo fijo', 'error');
                    }
                });
            });
        });

        // Guardar cambios
        $('#btnSaveFixedAsset').on('click', function () {
            var data = {
                ItemCode: $('#ItemCode').val(),
                U_BPId: $('#U_BPId').val(),
                U_TipoEquipo: $('#U_TipoEquipo').val()
            };

            if (!data.ItemCode) {
                Swal.fire('Atención', 'No se encontró el código del activo fijo', 'warning');
                return;
            }

            $('#btnSaveFixedAsset').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('admin/servicelayer/fixedassets/save') ?>',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function (resp) {
                    $('#btnSaveFixedAsset').prop('disabled', false);
                    if (resp.status === 200) {
                        $('#modalFixedAsset').modal('hide');
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: resp.message || 'Actualizado correctamente',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        tableFixedAssets.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Error al guardar en SAP', 'error');
                    }
                },
                error: function (xhr) {
                    $('#btnSaveFixedAsset').prop('disabled', false);
                    var msg = xhr.responseJSON?.message || 'Error de red';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        $('#modalFixedAsset').draggable({
            handle: '.modal-header'
        });
    });
</script>
<?= $this->endSection() ?>