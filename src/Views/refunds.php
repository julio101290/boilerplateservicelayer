<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-money-check-alt mr-1"></i>
                    <?= esc($title ?? 'Reembolsos y Comprobación de Gastos') ?>
                </h3>
                <div class="card-tools ml-auto">
                    <a href="<?= base_url('admin/refunds/newRefund') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle"></i> Nuevo Reembolso / Comprobación
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="tableRefunds" class="table table-bordered table-striped table-hover table-sm text-nowrap w-100">
                        <thead class="bg-navy text-white text-center">
                            <tr>
                                <th>ID</th>
                                <th>Code SAP</th>
                                <th>Folio</th>
                                <th>Tipo</th>
                                <th>Área (C. Costo)</th>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th class="text-right">Total</th>
                                <th>Estatus</th>
                                <th>Sincronizado SAP</th>
                                <th>Usuario</th>
                                <th>Viático (CodeMov)</th>
                                <th style="width: 180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 1: VER DETALLE DE LÍNEAS DEL COMPROBANTE (@QSYS_GLO_VODE / MariaDB)
     ========================================================================= -->
<div class="modal fade" id="modalVoucherDetails" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white py-2">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul mr-1"></i> Detalle del Comprobante: <span id="lblModalFolio" class="font-weight-bold"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableModalDetails" class="table table-sm table-bordered table-striped w-100">
                        <thead class="bg-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Línea</th>
                                <th>N/A</th>
                                <th>Tipo</th>
                                <th>Proveedor / Establecimiento</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">IVA</th>
                                <th class="text-right">Total</th>
                                <th>Comentarios</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: PAGO EFECTUADO AL EMPLEADO EN SAP (OVPM - frmSearchRefunds.cs)
     ========================================================================= -->
<div class="modal fade" id="modalPayEmployee" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title">
                    <i class="fas fa-hand-holding-usd mr-1"></i> Generar Pago al Empleado en SAP (`OVPM`)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="payIdRefund" value="0">

                <div class="form-group">
                    <label>Folio del Reembolso</label>
                    <input type="text" id="payFolio" class="form-control font-weight-bold" readonly>
                </div>

                <div class="form-group">
                    <label>Empleado Beneficiario</label>
                    <input type="text" id="payEmployee" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label>Importe a Pagar</label>
                    <input type="text" id="payAmount" class="form-control font-weight-bold text-success" readonly>
                </div>

                <div class="form-group">
                    <label>Cuenta Bancaria / Transferencia SAP (`OACT`) <span class="text-danger">*</span></label>
                    <select id="payBankAccount" class="form-control select2" style="width: 100%;">
                        <option value="">Seleccione la cuenta bancaria de salida...</option>
                    </select>
                    <small class="form-text text-muted">
                        Se creará un Pago Efectuado a Cuenta (`DocType: rAccount`) en SAP Service Layer con los UDFs del empleado.
                    </small>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnConfirmPayEmployee">
                    <i class="fas fa-paper-plane"></i> Emitir Pago en SAP
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var tableRefunds      = null;
    var tableModalDetails = null;

    $(document).ready(function () {

        $('#payBankAccount').select2({
            dropdownParent: $('#modalPayEmployee'),
            ajax: {
                url: "<?= site_url('admin/refunds/getAccountsAjax') ?>",
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        searchTerm: params.term,
                        [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
                    };
                },
                processResults: function (response) {
                    if (response.token) $('.txt_csrfname').val(response.token);
                    return { results: response.data };
                },
                cache: true
            }
        });

        // =====================================================================
        // 1. INICIALIZAR DATATABLE PRINCIPAL
        // =====================================================================
        tableRefunds = $('#tableRefunds').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[0, 'desc']],
            ajax: {
                url: "<?= base_url('admin/refunds') ?>",
                type: "GET"
            },
            columns: [
                { data: 'id', className: 'text-center' },
                {
                    data: 'code',
                    className: 'text-center font-weight-bold',
                    render: function (data) {
                        return data ? `<span class="badge badge-primary">${data}</span>` : '<span class="text-muted">-</span>';
                    }
                },
                { data: 'U_Folio', className: 'font-weight-bold' },
                {
                    data: 'U_TypeVoucher',
                    className: 'text-center',
                    render: function (data) {
                        return parseInt(data) === 2
                            ? '<span class="badge badge-info">Viáticos (CV)</span>'
                            : '<span class="badge badge-secondary">Reembolso (CG)</span>';
                    }
                },
                { data: 'U_Area', className: 'text-center' },
                {
                    data: null,
                    render: function (data, type, row) {
                        var empCode = row.U_Employee || '';
                        var empName = row.empName || '';
                        return empName ? `${empCode} - ${empName}` : empCode;
                    }
                },
                { data: 'U_Date', className: 'text-center' },
                {
                    data: 'total',
                    className: 'text-right font-weight-bold',
                    render: function (data) {
                        return '$' + parseFloat(data || 0).toFixed(2);
                    }
                },
                {
                    data: 'U_Status',
                    className: 'text-center',
                    render: function (data) {
                        var st = parseInt(data) || 1;
                        if (st === 1) return '<span class="badge badge-secondary">1 - Pendiente</span>';
                        if (st === 2) return '<span class="badge badge-warning">2 - Pendiente Área</span>';
                        if (st === 4) return '<span class="badge badge-success">4 - Autorizado</span>';
                        if (st === 5) return '<span class="badge badge-info">5 - Pagado / Cerrado</span>';
                        return `<span class="badge badge-dark">${st}</span>`;
                    }
                },
                {
                    data: 'sapSynced',
                    className: 'text-center',
                    render: function (data) {
                        return parseInt(data) === 1
                            ? '<span class="badge badge-success"><i class="fas fa-check"></i> En SAP</span>'
                            : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Borrador Local</span>';
                    }
                },
                { data: 'U_UserCode', defaultContent: '' },
                { data: 'U_CodeMov', defaultContent: '-' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        var id       = row.id;
                        var uuid     = row.UUID;
                        var code     = row.code || '';
                        var folio    = row.U_Folio || '';
                        var status   = parseInt(row.U_Status) || 1;
                        var synced   = parseInt(row.sapSynced) || 0;
                        var empLabel = (row.U_Employee || '') + (row.empName ? ' - ' + row.empName : '');
                        var totalVal = parseFloat(row.total || 0).toFixed(2);

                        var btns = `<div class="btn-group">`;

                        btns += `<button type="button" class="btn btn-info btn-xs btnViewDetails" data-id="${id}" data-code="${code}" data-folio="${folio}" title="Ver Líneas">
                                    <i class="fas fa-eye"></i>
                                 </button>`;

                        btns += `<a href="<?= base_url('admin/refunds/edit') ?>/${uuid}" class="btn btn-warning btn-xs" title="Abrir / Editar">
                                    <i class="fas fa-edit"></i>
                                 </a>`;

                        if (synced === 0) {
                            btns += `<button type="button" class="btn btn-danger btn-xs btnSendRowSAP" data-id="${id}" data-uuid="${uuid}" data-folio="${folio}" title="Enviar a SAP Service Layer">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                     </button>`;

                            btns += `<button type="button" class="btn btn-secondary btn-xs btnDeleteRefund" data-id="${id}" data-folio="${folio}" title="Eliminar Borrador">
                                        <i class="fas fa-trash"></i>
                                     </button>`;
                        } else {
                            if (status === 1) {
                                btns += `<button type="button" class="btn btn-primary btn-xs btnNotifyRow" data-code="${code}" data-folio="${folio}" title="Notificar Área (Pasar a Estatus 2)">
                                            <i class="fas fa-bell"></i>
                                         </button>`;
                            }
                            if (status === 2) {
                                btns += `<button type="button" class="btn btn-success btn-xs btnAuthRow" data-code="${code}" data-folio="${folio}" title="Autorizar Comprobante (Pasar a Estatus 4)">
                                            <i class="fas fa-check"></i>
                                         </button>`;
                                btns += `<button type="button" class="btn btn-danger btn-xs btnRejectRow" data-code="${code}" data-folio="${folio}" title="Rechazar Comprobante">
                                            <i class="fas fa-times"></i>
                                         </button>`;
                            }
                            if (status === 4) {
                                btns += `<button type="button" class="btn btn-success btn-xs btnOpenPayEmployee"
                                            data-id="${id}" data-folio="${folio}" data-emp="${empLabel}" data-total="${totalVal}"
                                            title="Generar Pago al Empleado en SAP">
                                            <i class="fas fa-dollar-sign"></i> Pagar
                                         </button>`;
                            }
                        }

                        btns += `</div>`;
                        return btns;
                    }
                }
            ]
        });

        // =====================================================================
        // 2. EVENTOS DE ACCIONES EN LA FILA
        // =====================================================================

        $(document).on('click', '.btnViewDetails', function () {
            var code  = $(this).attr('data-code') || 0;
            var id    = $(this).attr('data-id') || 0;
            var folio = $(this).attr('data-folio');

            $('#lblModalFolio').text(folio);
            $('#modalVoucherDetails').modal('show');

            if (tableModalDetails) {
                tableModalDetails.destroy();
            }

            tableModalDetails = $('#tableModalDetails').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                ajax: {
                    url: "<?= base_url('admin/refunds/showVoucherDetails') ?>",
                    type: "POST",
                    data: function (d) {
                        d.code = code;
                        d.idRefund = id;
                        d[$('.txt_csrfname').attr('name')] = $('.txt_csrfname').val();
                    },
                    dataSrc: function (json) {
                        if (json.token) $('.txt_csrfname').val(json.token);
                        return json.data;
                    }
                },
                columns: [
                    { data: 'No', className: 'text-center' },
                    { data: 'Linea', className: 'text-center' },
                    { data: 'NA', className: 'text-center' },
                    { data: 'Tipo', className: 'text-center' },
                    { data: 'Proveedor' },
                    { data: 'Subtotal', className: 'text-right' },
                    { data: 'IVA', className: 'text-right' },
                    { data: 'Total', className: 'text-right font-weight-bold' },
                    { data: 'Comentarios' }
                ]
            });
        });

        // Enviar a SAP directamente desde la fila del listado
        $(document).on('click', '.btnSendRowSAP', function (e) {
            e.preventDefault();

            var idRefund = $(this).attr('data-id');
            var uuid     = $(this).attr('data-uuid');
            var folio    = $(this).attr('data-folio');

            Swal.fire({
                title: `¿Enviar ${folio} a SAP?`,
                text: "Se crearán las Facturas XML, Pólizas de Notas y el comprobante en SAP Service Layer.",
                icon: 'question',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, enviar a SAP',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed || result.value) {

                    Swal.fire({
                        title: 'Sincronizando con SAP...',
                        text: 'Por favor espere...',
                        allowOutsideClick: false,
                        onBeforeOpen: () => { Swal.showLoading(); },
                        didOpen: () => { Swal.showLoading(); }
                    });

                    var postData = {
                        idRefund: idRefund,
                        UUID: uuid
                    };

                    var csrfName = $('.txt_csrfname').attr('name');
                    var csrfHash = $('.txt_csrfname').val();
                    if (csrfName && csrfHash) {
                        postData[csrfName] = csrfHash;
                    }

                    $.ajax({
                        url: "<?= base_url('admin/refunds/sendToSAP') ?>",
                        method: "POST",
                        data: postData,
                        dataType: "json",
                        success: function (res) {
                            if (res && res.token) {
                                $('.txt_csrfname').val(res.token);
                            }
                            if (res && !res.error) {
                                Swal.fire('¡Enviado!', res.message, 'success');
                                tableRefunds.ajax.reload(null, false);
                            } else {
                                Swal.fire('Error en SAP', (res && res.message) ? res.message : 'Error desconocido al enviar a SAP.', 'error');
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.error("Error AJAX sendToSAP:", jqXHR.responseText);
                            var msg = jqXHR.responseJSON?.message || jqXHR.responseText || errorThrown;
                            Swal.fire('Error del Servidor', msg, 'error');
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btnNotifyRow', function () {
            cambiarEstatusFila("<?= base_url('admin/refunds/notifyVoucher') ?>", $(this).attr('data-code'), $(this).attr('data-folio'), '¿Notificar comprobante al área (Estatus 2)?');
        });

        $(document).on('click', '.btnAuthRow', function () {
            cambiarEstatusFila("<?= base_url('admin/refunds/authorizeVoucher') ?>", $(this).attr('data-code'), $(this).attr('data-folio'), '¿Autorizar comprobante en SAP (Estatus 4)?');
        });

        $(document).on('click', '.btnRejectRow', function () {
            cambiarEstatusFila("<?= base_url('admin/refunds/rejectVoucher') ?>", $(this).attr('data-code'), $(this).attr('data-folio'), '¿Rechazar comprobante y regresarlo a Pendiente?');
        });

        $(document).on('click', '.btnOpenPayEmployee', function () {
            $('#payIdRefund').val($(this).attr('data-id'));
            $('#payFolio').val($(this).attr('data-folio'));
            $('#payEmployee').val($(this).attr('data-emp'));
            $('#payAmount').val('$' + $(this).attr('data-total'));
            $('#payBankAccount').val(null).trigger('change');
            $('#modalPayEmployee').modal('show');
        });

        $('#btnConfirmPayEmployee').on('click', function () {
            var idRefund    = $('#payIdRefund').val();
            var bankAccount = $('#payBankAccount').val();

            if (!bankAccount || bankAccount === '0') {
                Toast.fire({ icon: 'error', title: 'Seleccione la cuenta bancaria de salida.' });
                return;
            }

            $('#btnConfirmPayEmployee').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            var postData = {
                idRefund: idRefund,
                bankAccount: bankAccount
            };
            var csrfName = $('.txt_csrfname').attr('name');
            if (csrfName) postData[csrfName] = $('.txt_csrfname').val();

            $.ajax({
                url: "<?= base_url('admin/refunds/payRefundToEmployeeSAP') ?>",
                method: "POST",
                data: postData,
                dataType: "json",
                success: function (res) {
                    $('#btnConfirmPayEmployee').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Emitir Pago en SAP');
                    if (res.token) $('.txt_csrfname').val(res.token);

                    if (!res.error) {
                        $('#modalPayEmployee').modal('hide');
                        Swal.fire('¡Pago Generado!', res.message, 'success');
                        tableRefunds.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error al generar pago', res.message, 'error');
                    }
                },
                error: function (jqXHR) {
                    $('#btnConfirmPayEmployee').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Emitir Pago en SAP');
                    Swal.fire('Error', jqXHR.responseText || 'Error de red', 'error');
                }
            });
        });

        $(document).on('click', '.btnDeleteRefund', function () {
            var id    = $(this).attr('data-id');
            var folio = $(this).attr('data-folio');

            Swal.fire({
                title: `¿Eliminar borrador ${folio}?`,
                text: "Esta acción eliminará el registro local en MariaDB.",
                icon: 'warning',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed || result.value) {
                    $.ajax({
                        url: "<?= base_url('admin/refunds/delete') ?>/" + id,
                        method: "DELETE",
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function () {
                            Toast.fire({ icon: 'success', title: 'Borrador eliminado correctamente.' });
                            tableRefunds.ajax.reload(null, false);
                        },
                        error: function (jqXHR) {
                            Swal.fire('No permitido', jqXHR.responseJSON?.messages?.error || 'Error al eliminar', 'error');
                        }
                    });
                }
            });
        });
    });

    function cambiarEstatusFila(urlEndpoint, code, folio, tituloConfirmacion) {
        Swal.fire({
            title: tituloConfirmacion,
            text: `Folio: ${folio} (Code SAP: ${code})`,
            icon: 'question',
            type: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar'
        }).then((result) => {
            if (result.isConfirmed || result.value) {
                var postData = { code: code, folio: folio };
                var csrfName = $('.txt_csrfname').attr('name');
                if (csrfName) postData[csrfName] = $('.txt_csrfname').val();

                $.ajax({
                    url: urlEndpoint,
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    success: function (res) {
                        if (res.token) $('.txt_csrfname').val(res.token);
                        if (res.success) {
                            Toast.fire({ icon: 'success', title: res.message });
                            tableRefunds.ajax.reload(null, false);
                        } else {
                            Swal.fire('Atención', res.error || 'Error al cambiar estatus', 'warning');
                        }
                    },
                    error: function (jqXHR) {
                        Swal.fire('Error', jqXHR.responseText || 'Error de red', 'error');
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>