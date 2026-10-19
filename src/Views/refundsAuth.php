<?php
/**
 * Vista: Autorización y Desautorización de Comprobantes (Refunds/Vouchers)
 */
?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesAuthRefunds/modalShowVoucherDetails') ?>

<div class="card card-default card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="tabs-vouchers" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-pending-tab" data-toggle="tab" href="#tab-pending" role="tab" aria-controls="tab-pending" aria-selected="true">
                    <i class="fas fa-clock text-warning mr-1"></i> Pendientes de Autorización
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-authorized-tab" data-toggle="tab" href="#tab-authorized" role="tab" aria-controls="tab-authorized" aria-selected="false">
                    <i class="fas fa-check-double text-success mr-1"></i> Autorizados
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="tabs-vouchers-content">
            <!-- PESTAÑA: PENDIENTES -->
            <div class="tab-pane active" id="tab-pending" role="tabpanel" aria-labelledby="tab-pending-tab">
                <div class="table-responsive">
                    <table id="tableAuthVouchers" class="table table-striped table-hover va-middle w-100">
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Folio</th>
                                <th>Área</th>
                                <th>Empleado</th>
                                <th>Comentarios</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Tipo Comprobante</th>
                                <th>Sucursal</th>
                                <th>Usuario Creación</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- PESTAÑA: AUTORIZADOS -->
            <div class="tab-pane" id="tab-authorized" role="tabpanel" aria-labelledby="tab-authorized-tab">
                <div class="table-responsive">
                    <table id="tableAuthorizedVouchers" class="table table-striped table-hover va-middle w-100">
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Folio</th>
                                <th>Mov. Posterior</th>
                                <th>Área</th>
                                <th>Empleado</th>
                                <th>Comentarios</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Tipo Comprobante</th>
                                <th>Sucursal</th>
                                <th>Usuario Creación</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    // Formato de moneda
    function fmtMoney(val) {
        if (val === null || typeof val === 'undefined' || val === '') return '';
        var n = parseFloat(val);
        if (isNaN(n)) return '';
        try {
            return new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(n);
        } catch (e) {
            return n.toFixed(2);
        }
    }

    // Ajustar columnas de la tabla activa al cambiar de pestaña
    $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
    });

    // 1. DataTable: Pendientes (U_Status = 2)
    var tableAuthVouchers = $('#tableAuthVouchers').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/servicelayer/refundsauth') ?>',
            method: 'GET',
            dataType: "json",
            dataSrc: function (json) {
                return json.data || [];
            }
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '180px' },
            { targets: [6], className: 'text-right' }
        ],
        columns: [
            {
                data: function (row) {
                    var code = row.Code ?? row.code ?? '';
                    var folio = row.U_Folio ?? row.folio ?? '';
                    var total = row.U_Total ?? row.total ?? 0;

                    var eCode = String(code).replace(/"/g, '&quot;');
                    var eFolio = String(folio).replace(/"/g, '&quot;');

                    return `
                    <div class="btn-group" role="group">
                        <button class="btn btn-success btnAuthorizeVoucher btn-sm"
                                data-code="${eCode}"
                                data-folio="${eFolio}"
                                data-total="${total}"
                                title="Autorizar comprobante">
                            <i class="fas fa-check-circle"></i> Autorizar
                        </button>
                        <button class="btn btn-info btnViewVoucherDetails btn-sm ml-1"
                                data-code="${eCode}"
                                data-folio="${eFolio}"
                                title="Ver líneas del comprobante">
                            <i class="fas fa-list-ul"></i> Detalle
                        </button>
                    </div>`;
                }
            },
            { data: 'U_Folio', name: 'U_Folio' },
            { data: 'U_Area', name: 'U_Area' },
            { data: 'U_Employee', name: 'U_Employee' },
            { data: 'U_Coments', name: 'U_Coments' },
            { data: 'U_Date', name: 'U_Date' },
            {
                data: function (row) { return fmtMoney(row.U_Total); },
                name: 'U_Total'
            },
            { data: 'U_TypeVoucher', name: 'U_TypeVoucher' },
            { data: 'U_Branch', name: 'U_Branch' },
            { data: 'U_UserCode', name: 'U_UserCode' }
        ],
        language: {
            processing: "Cargando...",
            emptyTable: "No hay comprobantes pendientes de autorización"
        }
    });

    // 2. DataTable: Autorizados (U_Status = 4)
    var tableAuthorizedVouchers = $('#tableAuthorizedVouchers').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[1, 'desc']],
        ajax: {
            url: '<?= base_url('admin/servicelayer/refundsauth/getAuthorized') ?>',
            method: 'GET',
            dataType: "json",
            dataSrc: function (json) {
                return json.data || [];
            }
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '200px' },
            { targets: [7], className: 'text-right' }
        ],
        columns: [
            {
                data: function (row) {
                    var code = row.Code ?? row.code ?? '';
                    var folio = String(row.U_Folio ?? '').trim();
                    var total = row.U_Total ?? row.total ?? 0;
                    var codeMov = String(row.U_CodeMov ?? '').trim();

                    // Es movimiento posterior real si hasMovements viene en true
                    // o si codeMov existe, no es '0' y NO es igual al folio del propio comprobante
                    var hasMov = row.hasMovements === true || (codeMov !== '' && codeMov !== '0' && codeMov !== folio);

                    var eCode = String(code).replace(/"/g, '&quot;');
                    var eFolio = String(folio).replace(/"/g, '&quot;');

                    var btnDesautorizar = '';
                    if (hasMov) {
                        btnDesautorizar = `
                            <button class="btn btn-secondary btn-sm" disabled title="No se puede desautorizar: tiene movimiento posterior (${codeMov})">
                                <i class="fas fa-lock"></i> Bloqueado
                            </button>`;
                    } else {
                        btnDesautorizar = `
                            <button class="btn btn-warning btnUnauthorizeVoucher btn-sm"
                                    data-code="${eCode}"
                                    data-folio="${eFolio}"
                                    data-total="${total}"
                                    title="Desautorizar y regresar a pendientes">
                                <i class="fas fa-undo"></i> Desautorizar
                            </button>`;
                    }

                    return `
                    <div class="btn-group" role="group">
                        ${btnDesautorizar}
                        <button class="btn btn-info btnViewVoucherDetails btn-sm ml-1"
                                data-code="${eCode}"
                                data-folio="${eFolio}"
                                title="Ver líneas del comprobante">
                            <i class="fas fa-list-ul"></i> Detalle
                        </button>
                    </div>`;
                }
            },
            { data: 'U_Folio', name: 'U_Folio' },
            {
                data: function (row) {
                    var codeMov = String(row.U_CodeMov ?? '').trim();
                    var folio = String(row.U_Folio ?? '').trim();

                    if (codeMov !== '' && codeMov !== '0' && codeMov !== folio) {
                        return `<span class="badge badge-primary"><i class="fas fa-receipt mr-1"></i>${codeMov}</span>`;
                    }
                    return `<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Sin movimiento</span>`;
                },
                name: 'U_CodeMov'
            },
            { data: 'U_Area', name: 'U_Area' },
            { data: 'U_Employee', name: 'U_Employee' },
            { data: 'U_Coments', name: 'U_Coments' },
            { data: 'U_Date', name: 'U_Date' },
            {
                data: function (row) { return fmtMoney(row.U_Total); },
                name: 'U_Total'
            },
            { data: 'U_TypeVoucher', name: 'U_TypeVoucher' },
            { data: 'U_Branch', name: 'U_Branch' },
            { data: 'U_UserCode', name: 'U_UserCode' }
        ],
        language: {
            processing: "Cargando...",
            emptyTable: "No hay comprobantes autorizados"
        }
    });

    // Acción: Autorizar comprobante (2 -> 4)
    $('#tableAuthVouchers tbody').on('click', '.btnAuthorizeVoucher', function () {
        const $btn =$(this);
        const code = $btn.data('code');
        const folio = $btn.data('folio');
        const total = $btn.data('total');

        Swal.fire({
            title: '¿Autorizar este comprobante?',
            html: `
                <p><strong>Folio:</strong> ${folio}</p>
                <p><strong>Total:</strong> ${fmtMoney(total)}</p>
                <p class="text-info">El comprobante pasará al estatus "Autorizado" (4).</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, autorizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '<?= base_url("admin/servicelayer/refundsauth/authorizeVoucher") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ code: code, folio: folio }),
                    success: function (resp) {
                        if (resp && resp.success) {
                            tableAuthVouchers.ajax.reload(null, false);
                            tableAuthorizedVouchers.ajax.reload(null, false);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: resp.message || 'Comprobante autorizado',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            Swal.fire('Error', (resp && resp.error) ? resp.error : 'Error en la autorización', 'error');
                        }
                    },
                    error: function (xhr) {
                        var err = xhr.responseJSON ? xhr.responseJSON.error : 'Error de comunicación';
                        Swal.fire('Error', err, 'error');
                    }
                });
            }
        });
    });

    // Acción: Desautorizar comprobante (4 -> 2)
    $('#tableAuthorizedVouchers tbody').on('click', '.btnUnauthorizeVoucher', function () {
        const $btn =$(this);
        const code = $btn.data('code');
        const folio = $btn.data('folio');
        const total = $btn.data('total');

        Swal.fire({
            title: '¿Desautorizar este comprobante?',
            html: `
                <p><strong>Folio:</strong> ${folio}</p>
                <p><strong>Total:</strong> ${fmtMoney(total)}</p>
                <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> El comprobante regresará al estatus "Pendiente" (2).</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desautorizar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '<?= base_url("admin/servicelayer/refundsauth/unauthorizeVoucher") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ code: code, folio: folio }),
                    success: function (resp) {
                        if (resp && resp.success) {
                            tableAuthVouchers.ajax.reload(null, false);
                            tableAuthorizedVouchers.ajax.reload(null, false);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: resp.message || 'Comprobante desautorizado',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            Swal.fire('Atención', (resp && resp.error) ? resp.error : 'No se pudo desautorizar', 'warning');
                        }
                    },
                    error: function (xhr) {
                        var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error al procesar la desautorización';
                        Swal.fire('Error', err, 'error');
                    }
                });
            }
        });
    });

    // Acción: Ver detalle de líneas (delegado para ambas tablas)
    $(document).on('click', '.btnViewVoucherDetails', function () {
        const code = $(this).data('code');
        const folio = $(this).data('folio');

        $('#modalShowVoucherDetails').modal('show');
        $('#modalVoucherDetailsLabel').text('Detalle del comprobante: ' + folio);
        $('#modalVoucherDetailsBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-pulse fa-2x text-info"></i><p class="mt-2">Cargando líneas...</p></div>');

        $.ajax({
            url: '<?= base_url("admin/servicelayer/refundsauth/showVoucherDetails") ?>',
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({ code: code }),
            success: function (resp) {
                if (resp && resp.data && resp.data.length > 0) {
                    var html = '<div class="table-responsive">';
                    html += '<table class="table table-sm table-bordered table-striped">';
                    html += '<thead class="thead-light"><tr>';
                    html += '<th>#</th><th>Línea</th><th>NA</th><th>Tipo</th><th>Proveedor</th>';
                    html += '<th>Subtotal</th><th>IVA</th><th>Total</th><th>Comentarios</th>';
                    html += '</tr></thead><tbody>';

                    $.each(resp.data, function(i, item) {
                        html += '<tr>';
                        html += '<td class="text-center">' + (item.No ?? (i+1)) + '</td>';
                        html += '<td>' + (item.Linea ?? '') + '</td>';
                        html += '<td>' + (item.NA ?? '') + '</td>';
                        html += '<td>' + (item.Tipo ?? '') + '</td>';
                        html += '<td>' + (item.Proveedor ?? '') + '</td>';
                        html += '<td class="text-right">' + fmtMoney(item.Subtotal) + '</td>';
                        html += '<td class="text-right">' + fmtMoney(item.IVA) + '</td>';
                        html += '<td class="text-right font-weight-bold">' + fmtMoney(item.Total) + '</td>';
                        html += '<td>' + (item.Comentarios ?? '') + '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                    $('#modalVoucherDetailsBody').html(html);
                } else {
                    $('#modalVoucherDetailsBody').html('<div class="alert alert-info">No se encontraron líneas para este comprobante.</div>');
                }
            },
            error: function () {
                $('#modalVoucherDetailsBody').html('<div class="alert alert-danger">Error al cargar las líneas del comprobante.</div>');
            }
        });
    });
</script>
<?= $this->endSection() ?>