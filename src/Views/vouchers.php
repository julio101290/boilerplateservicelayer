<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<!-- Modal para editar estado de impresión del Vale -->
<div class="modal fade" id="modalVoucher" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalVoucherLabel">Cambiar Estado de Impresión del Vale</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formVoucher">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="Code">Code (ID Interno)</label>
                                <input type="text" class="form-control" name="Code" id="Code" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="U_VALE">N° de Vale</label>
                                <input type="text" class="form-control" id="U_VALE" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="U_DOCNUM">DocNum</label>
                                <input type="text" class="form-control" id="U_DOCNUM" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="U_DOCENTRY">DocEntry</label>
                                <input type="text" class="form-control" id="U_DOCENTRY" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="U_ITEMCODE">Artículo (ItemCode)</label>
                                <input type="text" class="form-control" id="U_ITEMCODE" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="U_CANT">Cantidad</label>
                                <input type="text" class="form-control" id="U_CANT" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="U_WHSCODE">Almacén</label>
                                <input type="text" class="form-control" id="U_WHSCODE" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="U_IMP" class="font-weight-bold text-primary">
                                    <i class="fas fa-print mr-1"></i> ¿Vale Impreso? (U_IMP) <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-control-lg" name="U_IMP" id="U_IMP" required>
                                    <option value="N">N - No Impreso (Disponible para imprimir)</option>
                                    <option value="Y">Y - Ya Impreso</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="U_DOCDATEIMP">Última Fecha de Impresión</label>
                                <input type="text" class="form-control" id="U_DOCDATEIMP" readonly>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnSaveVoucher">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ticket-alt mr-1"></i> Control de Impresión de Vales SAP</h3>
        <div class="card-tools">
            <button class="btn btn-outline-secondary btn-sm" id="btnClearFilters">
                <i class="fas fa-eraser mr-1"></i> Limpiar Filtros
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Barra de Filtros Específicos -->
        <div class="row mb-3">
            <!-- Filtro por Número de Vale -->
            <div class="col-md-2">
                <label for="filterVale"><i class="fas fa-hashtag mr-1"></i> N° Vale</label>
                <input type="text" id="filterVale" class="form-control" placeholder="Ej. 178">
            </div>

            <!-- Filtro por ItemCode -->
            <div class="col-md-3">
                <label for="filterItemCode"><i class="fas fa-box mr-1"></i> ItemCode</label>
                <input type="text" id="filterItemCode" class="form-control text-uppercase" placeholder="Ej. TAG0004">
            </div>

            <!-- Filtro por DocEntry -->
            <div class="col-md-2">
                <label for="filterDocEntry"><i class="fas fa-file-alt mr-1"></i> DocEntry</label>
                <input type="text" id="filterDocEntry" class="form-control" placeholder="Ej. 8736">
            </div>

            <!-- Filtro por Almacén -->
            <div class="col-md-3">
                <label for="filterWhsCode"><i class="fas fa-warehouse mr-1"></i> Almacén</label>
                <select id="filterWhsCode" class="form-control" style="width: 100%;">
                    <option value="">Todos los almacenes</option>
                </select>
            </div>

            <!-- Filtro por Estado U_IMP -->
            <div class="col-md-2">
                <label for="filterImp"><i class="fas fa-print mr-1"></i> Impreso (U_IMP)</label>
                <select id="filterImp" class="form-control">
                    <option value="">Todos</option>
                    <option value="N" selected>N - No Impresos</option>
                    <option value="Y">Y - Impresos</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tableVouchers" class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th width="100">Acciones</th>
                        <th>Code</th>
                        <th>N° Vale</th>
                        <th>DocNum</th>
                        <th>DocEntry</th>
                        <th>ItemCode</th>
                        <th>Cant.</th>
                        <th>Almacén</th>
                        <th>Impreso (U_IMP)</th>
                        <th>Cancelado</th>
                        <th>Fecha Doc.</th>
                        <th>Fecha Impresión</th>
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
        // 1. Select2 para filtro de Almacén (OWHS)
        $('#filterWhsCode').select2({
            placeholder: 'Todos los almacenes',
            allowClear: true,
            ajax: {
                url: '<?= base_url('admin/servicelayer/vouchers/getWarehousesAjax') ?>',
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

        // 2. Inicializar DataTable
        var tableVouchers = $('#tableVouchers').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            order: [[1, 'desc']],
            ajax: {
                url: '<?= base_url('admin/servicelayer/vouchers') ?>',
                method: 'GET',
                dataType: 'json',
                data: function (d) {
                    d.vale     = $('#filterVale').val();
                    d.itemCode = $('#filterItemCode').val();
                    d.docEntry = $('#filterDocEntry').val();
                    d.whsCode  = $('#filterWhsCode').val();
                    d.uImp     = $('#filterImp').val();
                },
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
                    render: function (data, type, row) {
                        var code = encodeURIComponent(row.Code || '');
                        var currentImp = (row.U_IMP === 'Y') ? 'Y' : 'N';
                        var nextImp = (currentImp === 'Y') ? 'N' : 'Y';
                        var toggleBtnClass = (currentImp === 'Y') ? 'btn-secondary' : 'btn-success';
                        var toggleTitle = (currentImp === 'Y')
                            ? 'Cambiar a N (No Impreso)'
                            : 'Cambiar a Y (Impreso)';

                        return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning btn-sm btnEditVoucher" data-code="${code}" title="Editar en Modal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn ${toggleBtnClass} btn-sm btnToggleImp" data-code="${row.Code}" data-vale="${row.U_VALE}" data-next="${nextImp}" title="${toggleTitle}">
                                <i class="fas fa-sync-alt"></i> ${nextImp}
                            </button>
                        </div>`;
                    }
                },
                { data: 'Code' },
                {
                    data: 'U_VALE',
                    render: function (data) {
                        return `<strong>${data || ''}</strong>`;
                    }
                },
                { data: 'U_DOCNUM' },
                { data: 'U_DOCENTRY' },
                { data: 'U_ITEMCODE' },
                { data: 'U_CANT' },
                {
                    data: 'U_WHSCODE',
                    render: function (data) {
                        return `<span class="badge badge-light border">${data || ''}</span>`;
                    }
                },
                {
                    data: 'U_IMP',
                    render: function (data) {
                        return data === 'Y'
                            ? '<span class="badge badge-success"><i class="fas fa-check mr-1"></i> Y (Impreso)</span>'
                            : '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> N (No Impreso)</span>';
                    }
                },
                {
                    data: 'U_CANCEL',
                    render: function (data) {
                        return data === 'Y'
                            ? '<span class="badge badge-danger">Cancelado</span>'
                            : '<span class="badge badge-secondary">Activo</span>';
                    }
                },
                { data: 'U_DOCDATE', defaultContent: '' },
                { data: 'U_DOCDATEIMP', defaultContent: '' }
            ],
            language: {
                processing: "Cargando vales..."
            }
        });

        // 3. Eventos de filtrado con debounce para inputs de texto
        var filterTimer = null;
        $('#filterVale, #filterItemCode, #filterDocEntry').on('keyup change', function () {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(function () {
                tableVouchers.ajax.reload();
            }, 350);
        });

        $('#filterWhsCode, #filterImp').on('change', function () {
            tableVouchers.ajax.reload();
        });

        // Limpiar todos los filtros
        $('#btnClearFilters').on('click', function () {
            $('#filterVale').val('');
            $('#filterItemCode').val('');
            $('#filterDocEntry').val('');
            $('#filterWhsCode').val(null).trigger('change.select2');
            $('#filterImp').val('N');
            tableVouchers.ajax.reload();
        });

        // 4. Abrir Modal para editar estado de impresión
        $('#tableVouchers tbody').on('click', '.btnEditVoucher', function () {
            var code = $(this).attr('data-code');
            if (!code) return;

            $.ajax({
                url: '<?= base_url('admin/servicelayer/vouchers/getVoucher') ?>/' + code,
                method: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.Code) {
                        $('#Code').val(resp.Code);
                        $('#U_VALE').val(resp.U_VALE || '');
                        $('#U_DOCNUM').val(resp.U_DOCNUM || '');
                        $('#U_DOCENTRY').val(resp.U_DOCENTRY || '');
                        $('#U_ITEMCODE').val(resp.U_ITEMCODE || '');
                        $('#U_CANT').val(resp.U_CANT || '');
                        $('#U_WHSCODE').val(resp.U_WHSCODE || '');
                        $('#U_IMP').val(resp.U_IMP === 'Y' ? 'Y' : 'N');
                        $('#U_DOCDATEIMP').val(resp.U_DOCDATEIMP || 'Sin registro');

                        $('#modalVoucherLabel').text('Editar Vale #' + (resp.U_VALE || resp.Code));
                        $('#modalVoucher').modal('show');
                    } else {
                        Swal.fire('Error', resp.message || 'No se encontró el vale', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                }
            });
        });

        // 5. Cambio rápido (1 clic con confirmación) desde la fila
        $('#tableVouchers tbody').on('click', '.btnToggleImp', function () {
            var code = $(this).attr('data-code');
            var vale = $(this).attr('data-vale');
            var nextImp = $(this).attr('data-next');
            var descEstado = (nextImp === 'Y') ? 'Y (Impreso)' : 'N (No Impreso)';

            Swal.fire({
                title: '¿Cambiar estado de impresión?',
                html: `El Vale <strong>#${vale}</strong> (Code: ${code}) pasará a <strong>${descEstado}</strong>.`,
                icon: 'question',
                type: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, actualizar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                // Compatible con versiones anteriores (result.value) y nuevas (result.isConfirmed) de SweetAlert2
                if (result.value || result.isConfirmed) {
                    actualizarEstadoVale(code, nextImp);
                }
            });
        });

        // 6. Guardar desde el Modal
        $('#btnSaveVoucher').on('click', function () {
            var code = $('#Code').val();
            var uImp = $('#U_IMP').val();

            if (!code) {
                Swal.fire('Atención', 'Código de vale inválido', 'warning');
                return;
            }

            var $btn = $(this);$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

            actualizarEstadoVale(code, uImp, function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Guardar Cambios');
                $('#modalVoucher').modal('hide');
            }, function () {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Guardar Cambios');
            });
        });

        // Función centralizada para guardar el cambio de U_IMP
        function actualizarEstadoVale(code, uImp, onSuccess, onError) {
            $.ajax({
                url: '<?= base_url('admin/servicelayer/vouchers/save') ?>',
                method: 'POST',
                data: {
                    Code: code,
                    U_IMP: uImp
                },
                dataType: 'json',
                success: function (resp) {
                    if (resp.status === 200) {
                        if (typeof onSuccess === 'function') onSuccess();
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            type: 'success',
                            title: resp.message || 'Estado actualizado',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        tableVouchers.ajax.reload(null, false);
                    } else {
                        if (typeof onError === 'function') onError();
                        Swal.fire('Error', resp.message || 'No se pudo actualizar', 'error');
                    }
                },
                error: function (xhr) {
                    if (typeof onError === 'function') onError();
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Error en el servidor (' + xhr.status + ')';
                    Swal.fire('Error', msg, 'error');
                }
            });
        }

        // Hacer el modal arrastrable
        $('#modalVoucher').draggable({
            handle: '.modal-header'
        });
    });
</script>
<?= $this->endSection() ?>