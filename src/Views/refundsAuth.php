<?php
/**
 * Vista: Autorización de Comprobantes (Refunds/Vouchers)
 * Adaptado de la vista de autorización de pedidos de compra.
 */
?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesAuthRefunds/modalShowVoucherDetails') ?>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title">Comprobantes pendientes de autorización</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableAuthVouchers" class="table table-striped table-hover va-middle">
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
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    // Helper formato moneda
    function fmtMoney(val) {
        if (val === null || typeof val === 'undefined' || val === '')
            return '';
        var n = parseFloat(val);
        if (isNaN(n))
            return '';
        try {
            return new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(n);
        } catch (e) {
            return n.toFixed(2);
        }
    }

    // Inicializar DataTable para comprobantes pendientes (U_Status = 2)
    var tableAuthVouchers = $('#tableAuthVouchers').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/servicelayer/refundsauth') ?>',
            method: 'GET',
            dataType: "json",
            dataSrc: function (json) {
                console.log('AJAX response (Vouchers):', json);
                return json.data || [];
            }
        },
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                width: '220px'
            },
            {targets: [6], className: 'text-right'}
        ],
        columns: [
            {
                data: function (row) {
                    var code = row.Code ?? row.code ?? (row._raw && (row._raw.Code ?? '')) ?? '';
                    var folio = row.U_Folio ?? row.folio ?? (row._raw && (row._raw.U_Folio ?? '')) ?? '';
                    var total = row.U_Total ?? row.total ?? (row._raw && (row._raw.U_Total ?? 0)) ?? 0;

                    var eCode = String(code).replace(/"/g, '&quot;');
                    var eFolio = String(folio).replace(/"/g, '&quot;');

                    return `
                    <div class="btn-group" role="group" aria-label="Acciones">
                        <button class="btn btn-success btnAuthorizeVoucher btn-sm"
                                data-code="${eCode}"
                                data-folio="${eFolio}"
                                data-total="${total}">
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
                data: function (row) {
                    var v = row.U_Total ?? (row._raw && row._raw.U_Total) ?? 0;
                    return fmtMoney(v);
                },
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

    // Autorizar comprobante
    $('#tableAuthVouchers tbody').on('click', '.btnAuthorizeVoucher', function (e) {
        const $btn = $(this);
        const code = $btn.data('code');
        const folio = $btn.data('folio');
        const total = $btn.data('total');

        Swal.fire({
            title: '¿Autorizar este comprobante?',
            html: `
                <p><strong>Código:</strong> ${code}</p>
                <p><strong>Folio:</strong> ${folio}</p>
                <p><strong>Total:</strong> ${fmtMoney(total)}</p>
                <p class="text-warning">Una vez autorizado, el comprobante pasará a estado "Autorizado" (4) y no podrá ser modificado.</p>
            `,
            icon: 'warning',
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
                            $btn.removeClass('btn-success').addClass('btn-secondary').attr('disabled', true);
                            $btn.html('<i class="fas fa-check"></i> Autorizado');
                            $btn.closest('tr').find('.btnViewVoucherDetails').attr('disabled', true);
                            tableAuthVouchers.ajax.reload(null, false);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: resp.message || 'Comprobante autorizado',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            var msg = (resp && resp.error) ? resp.error : 'Error en la autorización';
                            Swal.fire('Error', msg, 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        console.error('AJAX authorizeVoucher error', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudo autorizar el comprobante (error de comunicación).', 'error');
                    }
                });
            }
        });
    });

    // Ver detalle (líneas) del comprobante - CORREGIDO
    $('#tableAuthVouchers tbody').on('click', '.btnViewVoucherDetails', function () {
        const $btn = $(this);
        const code = $btn.data('code');
        const folio = $btn.data('folio');

        // Mostrar modal y poner título
        $('#modalShowVoucherDetails').modal('show');
        $('#modalVoucherDetailsLabel').text('Detalle del comprobante: ' + folio);
        // Limpiar contenido anterior y mostrar spinner
        $('#modalVoucherDetailsBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-pulse fa-2x text-info"></i><p class="mt-2">Cargando líneas...</p></div>');

        $.ajax({
            url: '<?= base_url("admin/servicelayer/refundsauth/showVoucherDetails") ?>',
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({ code: code }),
            success: function (resp) {
                console.log('Respuesta detalle:', resp);
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
            error: function (xhr, status, err) {
                console.error('AJAX showVoucherDetails error', status, err, xhr.responseText);
                $('#modalVoucherDetailsBody').html('<div class="alert alert-danger">Error al cargar las líneas del comprobante.</div>');
            }
        });
    });
</script>
<?= $this->endSection() ?>