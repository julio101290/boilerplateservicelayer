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
                <span class="badge badge-secondary p-2">
                    <i class="fas fa-tachometer-alt"></i> Salidas OIGE / IGE1
                </span>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">

        <!-- Card de Filtros AJAX -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-header py-2">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-filter mr-1"></i> Búsqueda de Salida y Activo
                </h3>
            </div>
            <div class="card-body py-3">
                <form id="formSearchOdometro" autocomplete="off">
                    <div class="row align-items-end">
                        
                        <!-- Cambiado a Input de Texto -->
                        <div class="col-md-5 form-group mb-2">
                            <label for="inputDocNum" class="font-weight-bold mb-1">
                                Folio de Salida (OIGE) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="inputDocNum" name="DocNum" placeholder="Escriba el folio de salida..." required>
                        </div>

                        <!-- Cambiado a Input de Texto -->
                        <div class="col-md-4 form-group mb-2">
                            <label for="inputOcrCode" class="font-weight-bold mb-1">
                                Activo / Centro de Costo (OPRC)
                            </label>
                            <input type="text" class="form-control" id="inputOcrCode" name="OcrCode" placeholder="Escriba código del activo (Opcional)...">
                        </div>

                        <div class="col-md-3 form-group mb-2">
                            <button type="submit" class="btn btn-primary mr-1" id="btnBuscar">
                                <i class="fas fa-search"></i> Cargar Líneas
                            </button>
                            <button type="button" class="btn btn-default" id="btnLimpiar">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla DataTables conectada a AJAX -->
        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-list-ol mr-1"></i> Líneas del Documento
                </h3>
                <div class="card-tools" id="headerInfoDoc" style="display: none;">
                    <span class="badge badge-info px-2 py-1 mr-2" id="infoDocEntry">DocEntry: -</span>
                    <span class="badge badge-light border px-2 py-1 mr-2" id="infoDocDate">Fecha: -</span>
                </div>
            </div>
            <div class="card-body">
                <div id="commentsDocWrapper" class="alert alert-light border py-2 px-3 mb-3" style="display: none;">
                    <strong>Comentarios SAP:</strong> <span id="infoComments"></span>
                </div>

                <div class="table-responsive">
                    <table id="tableOdometroLines" class="table table-striped table-bordered table-hover w-100">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">Acción</th>
                                <th style="width: 60px;" class="text-center">Línea</th>
                                <th style="width: 130px;">Artículo</th>
                                <th>Descripción</th>
                                <th style="width: 80px;" class="text-right">Cantidad</th>
                                <th style="width: 80px;" class="text-center">Almacén</th>
                                <th style="width: 120px;" class="text-center">Activo / CC</th>
                                <th style="width: 120px;" class="text-right">Odómetro</th>
                                <th style="width: 120px;" class="text-right">Horómetro</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal para Edición -->
<div class="modal fade" id="modalEditOdometro" tabindex="-1" role="dialog" aria-labelledby="modalEditOdometroTitle" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold" id="modalEditOdometroTitle">Modificar Lecturas</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUpdateOdometro" autocomplete="off">
                <input type="hidden" name="DocEntry" id="modalDocEntry" value="0">
                <input type="hidden" name="LineNum" id="modalLineNum" value="-1">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-muted d-block">Artículo:</small>
                                <span class="font-weight-bold" id="modalItemDesc">-</span>
                                <hr class="my-1">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Activo / CC (OcrCode):</small>
                                        <span class="badge badge-info" id="modalOcrCode">-</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Línea SAP:</small>
                                        <span class="font-weight-bold" id="modalLineNumText">#0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Odómetro -->
                        <div class="col-md-6 form-group">
                            <label for="inputOdometro" class="font-weight-bold">
                                Odómetro <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-road"></i></span>
                                </div>
                                <input type="number" step="0.01" class="form-control form-control-lg font-weight-bold text-primary" id="inputOdometro" name="U_Odometro" placeholder="0.00" required>
                            </div>
                            <small class="text-muted">Campo <code>U_Odometro</code></small>
                        </div>

                        <!-- Horómetro -->
                        <div class="col-md-6 form-group">
                            <label for="inputHorometro" class="font-weight-bold">
                                Horómetro <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-stopwatch"></i></span>
                                </div>
                                <input type="number" step="0.01" class="form-control form-control-lg font-weight-bold text-success" id="inputHorometro" name="U_Horometro" placeholder="0.00" required>
                            </div>
                            <small class="text-muted">Campo <code>U_Horometro</code></small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSaveOdometro">
                        <i class="fas fa-save"></i> Guardar en SAP
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
    const urls = {
        searchLines: '<?= base_url('admin/servicelayer/sapodometro/searchLines') ?>',
        update: '<?= base_url('admin/servicelayer/sapodometro/update') ?>'
    };

    let currentDocLines = [];

    // Silenciar alertas nativas de DataTables por precaución
    $.fn.dataTable.ext.errMode = 'none';

    // DataTable nativo por AJAX
    const tableOdometroLines = $('#tableOdometroLines').DataTable({
        processing: true,
        responsive: true,
        autoWidth: false,
        deferLoading: 0, // IMPORTANTISIMO: Evita que dispare el AJAX al cargar la página
        ajax: {
            url: urls.searchLines,
            type: 'GET',
            data: function (d) {
                // Lee el valor directamente desde los inputs de texto
                d.DocNum = $('#inputDocNum').val();
                d.OcrCode = $('#inputOcrCode').val();
            },
            dataFilter: function(data) {
                // Intercepta la respuesta de PHP y cambia la palabra 'error' por 'hasError'
                let json = JSON.parse(data);
                if (json.error !== undefined) {
                    json.hasError = json.error;
                    delete json.error;
                }
                return JSON.stringify(json);
            },
            dataSrc: function (res) {
                if (res.hasError) {
                    Toast.fire({ icon: 'warning', title: res.message || 'No se encontraron resultados' });
                    $('#headerInfoDoc').hide();
                    $('#commentsDocWrapper').hide();
                    currentDocLines = [];
                    return [];
                }

                if (res.header) {
                    $('#infoDocEntry').text(`DocEntry: #${res.header.DocEntry}`);
                    $('#infoDocDate').text(`Fecha: ${res.header.DocDate.substring(0, 10)}`);
                    $('#headerInfoDoc').show();

                    if (res.header.Comments) {
                        $('#infoComments').text(res.header.Comments);
                        $('#commentsDocWrapper').show();
                    } else {
                        $('#commentsDocWrapper').hide();
                    }
                }

                currentDocLines = res.data || [];
                return res.data || [];
            }
        },
        order: [[1, 'asc']],
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center align-middle',
                render: function (data, type, row) {
                    return `
                        <button type="button" class="btn btn-warning btn-sm btn-edit-odometro" data-line="${row.LineNum}" title="Modificar Odómetro/Horómetro">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
            },
            { data: 'LineNum', className: 'text-center align-middle font-weight-bold' },
            { data: 'ItemCode', className: 'align-middle font-weight-bold' },
            { data: 'Dscription', className: 'align-middle' },
            {
                data: 'Quantity',
                className: 'text-right align-middle',
                render: function (val) {
                    return parseFloat(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            },
            { data: 'WhsCode', className: 'text-center align-middle' },
            {
                data: 'OcrCode',
                className: 'text-center align-middle',
                render: function (val) {
                    return val ? `<span class="badge badge-secondary px-2 py-1 font-weight-normal">${val}</span>` : '<span class="text-muted">-</span>';
                }
            },
            {
                data: 'U_Odometro',
                className: 'text-right align-middle font-weight-bold',
                render: function (val) {
                    const num = parseFloat(val || 0);
                    return `<span class="badge badge-primary px-2 py-1" style="font-size: 0.90rem;">${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
                }
            },
            {
                data: 'U_Horometro',
                className: 'text-right align-middle font-weight-bold',
                render: function (val) {
                    const num = parseFloat(val || 0);
                    return `<span class="badge badge-success px-2 py-1" style="font-size: 0.90rem;">${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
                }
            }
        ]
    });

    // Submit de búsqueda (Ahora el ÚNICO lugar donde se dispara el AJAX de DataTables)
    $('#formSearchOdometro').on('submit', function (e) {
        e.preventDefault();
        const docNum = $('#inputDocNum').val();

        if ($.trim(docNum) === '') {
            Toast.fire({ icon: 'warning', title: 'Ingrese un folio de salida' });
            $('#inputDocNum').focus();
            return;
        }

        // Esto recarga el datatable haciendo la petición GET
        tableOdometroLines.ajax.reload();
    });

    // Limpiar pantalla e inputs
    $('#btnLimpiar').on('click', function () {
        $('#inputDocNum').val('');
        $('#inputOcrCode').val('');
        $('#headerInfoDoc').hide();
        $('#commentsDocWrapper').hide();
        currentDocLines = [];
        tableOdometroLines.clear().draw();
    });

    // Abrir Modal de Edición
    $(document).on('click', '.btn-edit-odometro', function () {
        const lineNum = parseInt($(this).data('line'));
        const rowData = currentDocLines.find(l => l.LineNum === lineNum);

        if (!rowData) {
            Toast.fire({ icon: 'error', title: 'No se encontraron los datos de la línea seleccionada' });
            return;
        }

        $('#modalDocEntry').val(rowData.DocEntry);
        $('#modalLineNum').val(rowData.LineNum);
        $('#modalLineNumText').text(`#${rowData.LineNum}`);
        $('#modalItemDesc').text(`${rowData.ItemCode} - ${rowData.Dscription}`);
        $('#modalOcrCode').text(rowData.OcrCode || 'N/A');
        $('#inputOdometro').val(rowData.U_Odometro);
        $('#inputHorometro').val(rowData.U_Horometro);

        $('#modalEditOdometroTitle').text(`Modificar Lecturas - Línea #${rowData.LineNum}`);
        $('#modalEditOdometro').modal('show');

        setTimeout(() => $('#inputOdometro').focus().select(), 400);
    });

    // Guardar vía PATCH en Service Layer
    $('#formUpdateOdometro').on('submit', function (e) {
        e.preventDefault();

        const docEntry = $('#modalDocEntry').val();
        const lineNum = $('#modalLineNum').val();
        const odometro = $('#inputOdometro').val();
        const horometro = $('#inputHorometro').val();

        if (odometro === '' || isNaN(odometro) || horometro === '' || isNaN(horometro)) {
            Toast.fire({ icon: 'warning', title: 'Ingrese valores numéricos válidos' });
            return;
        }

        const $btn =$('#btnSaveOdometro');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: urls.update,
            type: 'POST',
            data: {
                DocEntry: docEntry,
                LineNum: lineNum,
                U_Odometro: odometro,
                U_Horometro: horometro
            },
            dataType: 'json'
        }).done(function (res) {
            if (res.status === 200) {
                $('#modalEditOdometro').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: res.message || 'Valores modificados con éxito en SAP',
                    timer: 1800,
                    showConfirmButton: false
                });

                tableOdometroLines.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message || 'No fue posible actualizar en Service Layer', 'error');
            }
        }).fail(function (xhr) {
            const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error inesperado al contactar con el servidor';
            Swal.fire('Error', msg, 'error');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar en SAP');
        });
    });

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