<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<!-- Modal para agregar/editar artículo -->
<div class="modal fade" id="modalMaterial" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMaterialLabel"><?= lang('material.modal_title') ?? 'Artículo' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formMaterial">
                    <input type="hidden" name="isNew" id="isNew" value="1">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ItemCode"><?= lang('material.fields.ItemCode') ?? 'Código de Artículo' ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ItemCode" id="ItemCode" required placeholder="Ej. ART-001">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="ItemName"><?= lang('material.fields.ItemName') ?? 'Descripción' ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ItemName" id="ItemName" required placeholder="Descripción del material">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="BuyUnitMsr"><?= lang('material.fields.BuyUnitMsr') ?? 'Unidad de Compra' ?></label>
                                <input type="text" class="form-control" name="BuyUnitMsr" id="BuyUnitMsr" placeholder="Ej. PZA, KG, MTR">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="PrchseItem"><?= lang('material.fields.PrchseItem') ?? '¿Artículo de Compra?' ?></label>
                                <select class="form-control" name="PrchseItem" id="PrchseItem">
                                    <option value="Y" selected><?= lang('material.yes') ?? 'Sí' ?></option>
                                    <option value="N"><?= lang('material.no') ?? 'No' ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="validFor"><?= lang('material.fields.active') ?? 'Activo' ?></label>
                                <select class="form-control" name="validFor" id="validFor">
                                    <option value="Y" selected><?= lang('material.active_yes') ?? 'Sí' ?></option>
                                    <option value="N"><?= lang('material.active_no') ?? 'No' ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="InvntItem"><?= lang('material.fields.InvntItem') ?? '¿Artículo de Inventario?' ?></label>
                                <select class="form-control" name="InvntItem" id="InvntItem">
                                    <option value="Y" selected><?= lang('material.yes') ?? 'Sí' ?></option>
                                    <option value="N"><?= lang('material.no') ?? 'No' ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="SellItem"><?= lang('material.fields.SellItem') ?? '¿Artículo de Venta?' ?></label>
                                <select class="form-control" name="SellItem" id="SellItem">
                                    <option value="Y"><?= lang('material.yes') ?? 'Sí' ?></option>
                                    <option value="N" selected><?= lang('material.no') ?? 'No' ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('boilerplate.global.close') ?? 'Cerrar' ?></button>
                <button type="button" class="btn btn-primary" id="btnSaveMaterial"><?= lang('boilerplate.global.save') ?? 'Guardar' ?></button>
            </div>
        </div>
    </div>
</div>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title"><?= lang('material.list_title') ?? 'Catálogo de Artículos de Compra' ?></h3>
        <div class="card-tools">
            <button class="btn btn-success btn-sm" id="btnNewMaterial">
                <i class="fas fa-plus"></i> <?= lang('material.btn_new') ?? 'Nuevo Artículo' ?>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableMaterials" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="80"><?= lang('material.fields.actions') ?? 'Acciones' ?></th>
                        <th><?= lang('material.fields.ItemCode') ?? 'Código' ?></th>
                        <th><?= lang('material.fields.ItemName') ?? 'Descripción' ?></th>
                        <th><?= lang('material.fields.BuyUnitMsr') ?? 'U. Medida Compra' ?></th>
                        <th><?= lang('material.fields.PrchseItem') ?? 'Compra' ?></th>
                        <th><?= lang('material.fields.validFor') ?? 'Activo' ?></th>
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
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, width: '100px' }
            ],
            columns: [
                {
                    data: null,
                    defaultContent: '',
                    render: function (data, type, row) {
                        var itemCode = encodeURIComponent(row.ItemCode || '');
                        return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm btnEditMaterial" data-itemcode="${itemCode}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm btnDeleteMaterial" data-itemcode="${itemCode}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
                    }
                },
                { data: 'ItemCode' },
                { data: 'ItemName' },
                { 
                    data: 'BuyUnitMsr',
                    defaultContent: ''
                },
                {
                    data: 'PrchseItem',
                    render: function (data) {
                        return data === 'Y' 
                            ? '<span class="badge badge-success">Sí</span>' 
                            : '<span class="badge badge-secondary">No</span>';
                    }
                },
                {
                    data: 'validFor',
                    render: function (data) {
                        return data === 'Y' 
                            ? '<span class="badge badge-primary">Activo</span>' 
                            : '<span class="badge badge-danger">Inactivo</span>';
                    }
                }
            ],
            language: {
                processing: "Cargando..."
            }
        });

        // Abrir modal Nuevo Artículo
        $('#btnNewMaterial').on('click', function () {
            $('#formMaterial')[0].reset();
            $('#isNew').val(1);
            $('#ItemCode').prop('readonly', false);
            $('#validFor').val('Y');
            $('#PrchseItem').val('Y');
            $('#InvntItem').val('Y');
            $('#SellItem').val('N');
            $('#modalMaterialLabel').text('<?= lang('material.new_title') ?? 'Nuevo Artículo' ?>');
            $('#modalMaterial').modal('show');
        });

        // Abrir modal Editar Artículo
        $('#tableMaterials tbody').on('click', '.btnEditMaterial', function () {
            var itemCode = $(this).attr('data-itemcode');
            if (!itemCode) {
                Swal.fire('Error', 'Código de artículo no válido', 'error');
                return;
            }

            $.ajax({
                url: '<?= base_url('admin/servicelayer/materials/getMaterial') ?>/' + itemCode,
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.ItemCode) {
                        $('#isNew').val(0);
                        $('#ItemCode').val(resp.ItemCode).prop('readonly', true);
                        $('#ItemName').val(resp.ItemName || '');
                        $('#BuyUnitMsr').val(resp.BuyUnitMsr || '');
                        $('#PrchseItem').val(resp.PrchseItem || 'Y');
                        $('#InvntItem').val(resp.InvntItem || 'Y');
                        $('#SellItem').val(resp.SellItem || 'N');
                        $('#validFor').val(resp.validFor || 'Y');

                        $('#modalMaterialLabel').text('<?= lang('material.edit_title') ?? 'Editar Artículo' ?>: ' + resp.ItemCode);
                        $('#modalMaterial').modal('show');
                    } else {
                        Swal.fire('Error', 'No se pudo obtener el artículo', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error al cargar los datos del artículo', 'error');
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
                Swal.fire('Atención', 'Debes capturar el código de artículo', 'warning');
                return;
            }

            if (!data.ItemName || data.ItemName.trim() === '') {
                Swal.fire('Atención', 'Debes capturar la descripción del artículo', 'warning');
                return;
            }

            $.ajax({
                url: '<?= base_url('admin/servicelayer/materials/save') ?>',
                method: 'POST',
                data: data,
                dataType: 'json',
                success: function (resp) {
                    if (resp.status === 200 || resp.status === 201) {
                        $('#modalMaterial').modal('hide');
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: resp.message || 'Artículo guardado correctamente',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        tableMaterials.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Error al guardar el artículo', 'error');
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON?.message || 'Error de comunicación con el servidor';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // Eliminar / Cancelar Artículo
        $('#tableMaterials tbody').on('click', '.btnDeleteMaterial', function () {
            var itemCode = $(this).attr('data-itemcode');
            Swal.fire({
                title: '¿Eliminar artículo?',
                text: 'Código: ' + decodeURIComponent(itemCode),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('admin/servicelayer/materials/delete') ?>/' + itemCode,
                        method: 'DELETE',
                        dataType: 'json',
                        success: function (resp) {
                            if (resp.status === 200) {
                                Swal.fire('Eliminado', resp.message || 'Artículo eliminado', 'success');
                                tableMaterials.ajax.reload(null, false);
                            } else {
                                Swal.fire('Error', resp.message || 'Error al eliminar', 'error');
                            }
                        },
                        error: function () {
                            Swal.fire('Error', 'No se pudo eliminar el artículo', 'error');
                        }
                    });
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