<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<!-- Modal para agregar/editar/clonar artículo -->
<div class="modal fade" id="modalMaterial" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalMaterialLabel"><?= lang('material.modal_title') ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formMaterial">
                    <input type="hidden" name="isNew" id="isNew" value="1">

                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle mr-1"></i> 
                        El artículo se registrará automáticamente como <strong>Artículo de Compra</strong> e <strong>Inventario</strong> (No venta).
                    </div>

                    <div class="row">
                        <!-- ItemCode -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ItemCode"><?= lang('material.fields.ItemCode') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="ItemCode" id="ItemCode" required placeholder="Ej. RMM o RMT">
                                <small class="form-text text-muted" id="itemCodeHelp">Ingresa el prefijo (ej. RMM); al salir se calculará el consecutivo.</small>
                            </div>
                        </div>

                        <!-- ItemName -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="ItemName"><?= lang('material.fields.ItemName') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ItemName" id="ItemName" required placeholder="Descripción del material">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- ItemType -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ItemType"><?= lang('material.fields.ItemType') ?></label>
                                <select class="form-control" name="ItemType" id="ItemType">
                                    <option value="itItems" selected>Artículos (itItems)</option>
                                    <option value="itLabor">Mano de Obra (itLabor)</option>
                                    <option value="itTravel">Viajes (itTravel)</option>
                                </select>
                            </div>
                        </div>

                        <!-- ItmsGrpCod (Catálogo OITB con Select2) -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="ItmsGrpCod"><?= lang('material.fields.ItmsGrpCod') ?> <span class="text-danger">*</span></label>
                                <select class="form-control" name="ItmsGrpCod" id="ItmsGrpCod" style="width: 100%;" required>
                                    <option value="">Seleccione un grupo...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- BuyUnitMsr (Catálogo OUOM con Select2) -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="BuyUnitMsr"><?= lang('material.fields.BuyUnitMsr') ?> <span class="text-danger">*</span></label>
                                <select class="form-control" name="BuyUnitMsr" id="BuyUnitMsr" style="width: 100%;" required>
                                    <option value="">Seleccione U. Medida...</option>
                                </select>
                            </div>
                        </div>

                        <!-- VATLiable -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="VATLiable"><?= lang('material.fields.VATLiable') ?></label>
                                <select class="form-control" name="VATLiable" id="VATLiable">
                                    <option value="Y" selected><?= lang('material.yes') ?></option>
                                    <option value="N"><?= lang('material.no') ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- validFor -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="validFor"><?= lang('material.fields.active') ?></label>
                                <select class="form-control" name="validFor" id="validFor">
                                    <option value="Y" selected><?= lang('material.active_yes') ?></option>
                                    <option value="N"><?= lang('material.active_no') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('boilerplate.global.close') ?? 'Cerrar' ?></button>
                <button type="button" class="btn btn-primary" id="btnSaveMaterial">
                    <i class="fas fa-save mr-1"></i> <?= lang('boilerplate.global.save') ?? 'Guardar' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title"><?= lang('material.list_title') ?></h3>
        <div class="card-tools">
            <button class="btn btn-success btn-sm" id="btnNewMaterial">
                <i class="fas fa-plus"></i> <?= lang('material.btn_new') ?>
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filtro por Grupos -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="filterGroup"><i class="fas fa-filter mr-1"></i> <?= lang('material.filter_group') ?></label>
                <select id="filterGroup" class="form-control" style="width: 100%;">
                    <option value=""><?= lang('material.all_groups') ?></option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tableMaterials" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="80"><?= lang('material.fields.actions') ?></th>
                        <th><?= lang('material.fields.ItemCode') ?></th>
                        <th><?= lang('material.fields.ItemName') ?></th>
                        <th><?= lang('material.fields.ItmsGrpNam') ?></th>
                        <th><?= lang('material.fields.BuyUnitMsr') ?></th>
                        <th><?= lang('material.fields.VATLiable') ?></th>
                        <th><?= lang('material.fields.active') ?></th>
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
        // 1. Select2 para el filtro de Grupos en el header
        $('#filterGroup').select2({
            placeholder: '<?= lang('material.all_groups') ?>',
            allowClear: true,
            ajax: {
                url: '<?= base_url('admin/servicelayer/materials/getItemGroupsAjax') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { searchTerm: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.data || [] };
                }
            }
        });

        $('#filterGroup').on('change', function () {
            tableMaterials.ajax.reload();
        });

        // 2. Select2 dentro del Modal: Grupo de Artículos (OITB)
        $('#ItmsGrpCod').select2({
            dropdownParent: $('#modalMaterial'),
            placeholder: 'Seleccione un grupo de artículos',
            allowClear: true,
            ajax: {
                url: '<?= base_url('admin/servicelayer/materials/getItemGroupsAjax') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { searchTerm: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.data || [] };
                }
            }
        });

        // 3. Select2 dentro del Modal: Unidad de Medida de Compra (OUOM)
        $('#BuyUnitMsr').select2({
            dropdownParent: $('#modalMaterial'),
            placeholder: 'Seleccione Unidad de Medida',
            allowClear: true,
            ajax: {
                url: '<?= base_url('admin/servicelayer/materials/getUnitsAjax') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { searchTerm: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.data || [] };
                }
            }
        });

        // DataTables
        var tableMaterials = $('#tableMaterials').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            order: [[1, 'asc']],
            ajax: {
                url: '<?= base_url('admin/servicelayer/materials') ?>',
                method: 'GET',
                dataType: 'json',
                data: function (d) {
                    d.groupCode = $('#filterGroup').val();
                },
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, width: '80px' }
            ],
            columns: [
                {
                    data: null,
                    render: function (data, type, row) {
                        var itemCode = encodeURIComponent(row.ItemCode || '');
                        return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm btnEditMaterial" data-itemcode="${itemCode}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-info btn-sm btnCloneMaterial" data-itemcode="${itemCode}" title="<?= lang('material.btn_clone') ?>">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>`;
                    }
                },
                { data: 'ItemCode' },
                { data: 'ItemName' },
                { data: 'ItmsGrpNam', defaultContent: '' },
                { data: 'BuyUnitMsr', defaultContent: '' },
                {
                    data: 'VATLiable',
                    render: function (data) {
                        return data === 'Y'
                            ? '<span class="badge badge-info"><?= lang('material.yes') ?></span>'
                            : '<span class="badge badge-secondary"><?= lang('material.no') ?></span>';
                    }
                },
                {
                    data: 'validFor',
                    render: function (data) {
                        return data === 'Y'
                            ? '<span class="badge badge-success"><?= lang('material.active_yes') ?></span>'
                            : '<span class="badge badge-danger"><?= lang('material.active_no') ?></span>';
                    }
                }
            ],
            language: {
                processing: "Cargando artículos..."
            }
        });

        // Consecutivo automático con 5 ceros al perder foco en ItemCode
        $('#ItemCode').on('blur', function () {
            if ($('#isNew').val() !== '1') return;

            var rawVal = $(this).val().trim().toUpperCase();$(this).val(rawVal);
            if (!rawVal) return;

            var cleanPrefix = rawVal.replace(/[0-9]+$/, '');
            if (!cleanPrefix) {
                cleanPrefix = rawVal;
            }

            $.ajax({
                url: '<?= base_url('admin/servicelayer/materials/getNextItemCode') ?>/' + encodeURIComponent(cleanPrefix),
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.status === 200 && resp.nextCode) {
                        $('#ItemCode').val(resp.nextCode);
                    }
                }
            });
        });

        // Abrir modal Nuevo Artículo
        $('#btnNewMaterial').on('click', function () {
            $('#formMaterial')[0].reset();
            $('#isNew').val(1);
            $('#ItemCode').prop('readonly', false);
            $('#itemCodeHelp').show();
            $('#ItmsGrpCod').val(null).trigger('change');
            $('#BuyUnitMsr').val(null).trigger('change');
            $('#ItemType').val('itItems');
            $('#VATLiable').val('Y');
            $('#validFor').val('Y');
            $('#modalMaterialLabel').text('<?= lang('material.new_title') ?>');
            $('#modalMaterial').modal('show');
        });

        // Abrir modal Editar Artículo
        $('#tableMaterials tbody').on('click', '.btnEditMaterial', function () {
            var itemCode = $(this).attr('data-itemcode');
            if (!itemCode) return;

            $.ajax({
                url: '<?= base_url('admin/servicelayer/materials/getMaterial') ?>/' + itemCode,
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.ItemCode) {
                        $('#isNew').val(0);
                        $('#ItemCode').val(resp.ItemCode).prop('readonly', true);
                        $('#itemCodeHelp').hide();
                        $('#ItemName').val(resp.ItemName || '');
                        $('#ItemType').val(resp.ItemType || 'itItems');
                        $('#VATLiable').val(resp.VATLiable || 'Y');
                        $('#validFor').val(resp.validFor || 'Y');

                        // Asignar Grupo en Select2
                        if (resp.ItmsGrpCod) {
                            var optGroup = new Option(resp.ItmsGrpNam || ('Grupo ' + resp.ItmsGrpCod), resp.ItmsGrpCod, true, true);
                            $('#ItmsGrpCod').empty().append(optGroup).trigger('change');
                        } else {
                            $('#ItmsGrpCod').val(null).trigger('change');
                        }

                        // Asignar Unidad de Medida en Select2
                        if (resp.BuyUnitMsr) {
                            var optUnit = new Option(resp.BuyUnitMsr, resp.BuyUnitMsr, true, true);
                            $('#BuyUnitMsr').empty().append(optUnit).trigger('change');
                        } else {
                            $('#BuyUnitMsr').val(null).trigger('change');
                        }

                        $('#modalMaterialLabel').text('<?= lang('material.edit_title') ?>: ' + resp.ItemCode);
                        $('#modalMaterial').modal('show');
                    } else {
                        Swal.fire('Error', resp.message || '<?= lang('material.messages.not_found') ?>', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', '<?= lang('material.messages.server_error') ?>', 'error');
                }
            });
        });

        // Abrir modal Clonar Artículo
        $('#tableMaterials tbody').on('click', '.btnCloneMaterial', function () {
            var itemCode = $(this).attr('data-itemcode');
            if (!itemCode) return;

            $.ajax({
                url: '<?= base_url('admin/servicelayer/materials/getMaterial') ?>/' + itemCode,
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.ItemCode) {
                        $('#formMaterial')[0].reset();
                        $('#isNew').val(1);
                        $('#ItemCode').prop('readonly', false);
                        $('#itemCodeHelp').show();

                        $('#ItemName').val(resp.ItemName || '');
                        $('#ItemType').val(resp.ItemType || 'itItems');
                        $('#VATLiable').val(resp.VATLiable || 'Y');
                        $('#validFor').val('Y');

                        if (resp.ItmsGrpCod) {
                            var optGroup = new Option(resp.ItmsGrpNam || ('Grupo ' + resp.ItmsGrpCod), resp.ItmsGrpCod, true, true);
                            $('#ItmsGrpCod').empty().append(optGroup).trigger('change');
                        } else {
                            $('#ItmsGrpCod').val(null).trigger('change');
                        }

                        if (resp.BuyUnitMsr) {
                            var optUnit = new Option(resp.BuyUnitMsr, resp.BuyUnitMsr, true, true);
                            $('#BuyUnitMsr').empty().append(optUnit).trigger('change');
                        } else {
                            $('#BuyUnitMsr').val(null).trigger('change');
                        }

                        $('#modalMaterialLabel').text('<?= lang('material.clone_title') ?> (' + resp.ItemCode + ')');
                        $('#modalMaterial').modal('show');

                        var cleanPrefix = resp.ItemCode.replace(/[0-9]+$/, '');
                        if (!cleanPrefix) {
                            cleanPrefix = resp.ItemCode;
                        }

                        $.ajax({
                            url: '<?= base_url('admin/servicelayer/materials/getNextItemCode') ?>/' + encodeURIComponent(cleanPrefix),
                            method: 'GET',
                            dataType: 'json',
                            success: function (nextResp) {
                                if (nextResp.status === 200 && nextResp.nextCode) {
                                    $('#ItemCode').val(nextResp.nextCode);
                                }
                            }
                        });

                    } else {
                        Swal.fire('Error', resp.message || '<?= lang('material.messages.not_found') ?>', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', '<?= lang('material.messages.server_error') ?>', 'error');
                }
            });
        });

        // Guardar Artículo
        $('#btnSaveMaterial').on('click', function () {
            var formData = $('#formMaterial').serializeArray();
            var data = {};
            $.each(formData, function (i, field) {
                data[field.name] = field.value;
            });

            if (!data.ItemCode || data.ItemCode.trim() === '') {
                Swal.fire('Atención', '<?= lang('material.messages.code_required') ?>', 'warning');
                return;
            }
            if (!data.ItemName || data.ItemName.trim() === '') {
                Swal.fire('Atención', '<?= lang('material.messages.name_required') ?>', 'warning');
                return;
            }
            if (!data.ItmsGrpCod) {
                Swal.fire('Atención', '<?= lang('material.messages.group_required') ?>', 'warning');
                return;
            }
            if (!data.BuyUnitMsr || data.BuyUnitMsr.trim() === '') {
                Swal.fire('Atención', '<?= lang('material.messages.unit_required') ?>', 'warning');
                return;
            }

            var $btn = $(this);$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            $.ajax({
                url: '<?= base_url('admin/servicelayer/materials/save') ?>',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function (resp) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <?= lang('boilerplate.global.save') ?? 'Guardar' ?>');
                    if (resp.status === 200 || resp.status === 201) {
                        $('#modalMaterial').modal('hide');
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: resp.message || '<?= lang('material.messages.saved') ?>',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        tableMaterials.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || '<?= lang('material.messages.save_error') ?>', 'error');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> <?= lang('boilerplate.global.save') ?? 'Guardar' ?>');
                    var msg = xhr.responseJSON?.message || '<?= lang('material.messages.server_error') ?>';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // Arrastrar modal
        $('#modalMaterial').draggable({
            handle: '.modal-header'
        });
    });
</script>
<?= $this->endSection() ?>