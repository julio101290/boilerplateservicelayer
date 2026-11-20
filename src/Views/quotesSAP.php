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
                    <i class="fas fa-file-invoice-dollar mr-1"></i>
                    <?= esc($title ?? 'Administración de Cotizaciones SAP') ?>
                </h3>
                <div class="card-tools ml-auto">
                    <a href="<?= base_url('admin/quotesSAP/newQuote') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle"></i> Nueva Cotización SAP
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="mb-1">Fecha Inicial</label>
                        <input type="date" id="filterFechaIni" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">Fecha Final</label>
                        <input type="date" id="filterFechaFin" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">Estatus</label>
                        <select id="filterStatus" class="form-control form-control-sm">
                            <option value="ALL">Todos los estatus</option>
                            <option value="PEN">Pendiente de Autorización</option>
                            <option value="AUT">Autorizada</option>
                            <option value="PED">En Pedido (Orden de Venta)</option>
                            <option value="FAC">Facturada</option>
                            <option value="REC">Rechazada</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btnFilterQuotes" class="btn btn-navy bg-navy btn-sm btn-block">
                            <i class="fas fa-search"></i> Filtrar Cotizaciones
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="tableQuotesSAP" class="table table-bordered table-striped table-hover table-sm text-nowrap w-100">
                        <thead class="bg-navy text-white text-center">
                            <tr>
                                <th>ID</th>
                                <th>Folio</th>
                                <th>DocNum SAP</th>
                                <th>Cliente</th>
                                <th>Cotizar A</th>
                                <th>Vendedor</th>
                                <th>Almacén</th>
                                <th>Lista Precio</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">Total</th>
                                <th>Fecha</th>
                                <th>Vencimiento</th>
                                <th>Estatus</th>
                                <th>SAP</th>
                                <th style="width: 210px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: VER PARTIDAS DE LA COTIZACIÓN -->
<div class="modal fade" id="modalQuoteDetails" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white py-2">
                <h5 class="modal-title">
                    <i class="fas fa-boxes mr-1"></i> Partidas de la Cotización: <span id="lblModalQuoteFolio" class="font-weight-bold"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableModalQuoteDetails" class="table table-sm table-bordered table-striped w-100">
                        <thead class="bg-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Clave SAP</th>
                                <th>Descripción</th>
                                <th>Unidad</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio Unitario</th>
                                <th class="text-right">Importe</th>
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

<!-- MODAL 2: AUTORIZAR / CAMBIAR ESTATUS DE LA COTIZACIÓN -->
<div class="modal fade" id="modalCambiarStatus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle mr-1"></i> Autorizar / Cambiar Estatus
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusIdQuote" value="0">

                <div class="form-group">
                    <label>Estatus de la Cotización</label>
                    <select id="selectQuoteStatus" class="form-control">
                        <option value="PEN">PEN - Pendiente de Autorización</option>
                        <option value="AUT">AUT - Autorizada (Lista para enviar a SAP)</option>
                        <option value="PED">PED - En Pedido (Orden de Venta)</option>
                        <option value="FAC">FAC - Facturada</option>
                        <option value="REC">REC - Rechazada</option>
                    </select>
                </div>

                <div class="form-group" id="groupFolioFactura" style="display:none;">
                    <label id="lblFolioDoc">Folio de Documento SAP</label>
                    <input type="text" id="inputFolioFactura" class="form-control" placeholder="Ingrese el folio de pedido o factura...">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info btn-sm" id="btnConfirmStatus">
                    <i class="fas fa-save"></i> Guardar Estatus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: ENVIAR COTIZACIÓN POR WHATSAPP -->
<div class="modal fade" id="modalSendWhatsApp" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title">
                    <i class="fab fa-whatsapp mr-1"></i> Enviar Cotización por WhatsApp
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="waQuoteUuid" value="">
                <input type="hidden" id="waQuoteFolio" value="">

                <div class="form-group">
                    <label class="mb-1">Teléfono WhatsApp (con lada, ej. 526681234567)</label>
                    <input type="text" id="waPhone" class="form-control form-control-sm" placeholder="Ej. 526681234567">
                    <small class="text-muted">Si escribes 10 dígitos de México, se agregará <strong>52</strong> automáticamente.</small>
                </div>

                <div class="form-group mb-0">
                    <label class="mb-1">Mensaje</label>
                    <textarea id="waMessage" class="form-control form-control-sm" rows="5"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between flex-wrap">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnWaDownloadPdf" title="Descargar PDF para adjuntar manualmente">
                        <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm d-none" id="btnWaShareFile" title="Compartir archivo PDF directo en celular">
                        <i class="fas fa-share-alt mr-1"></i> Compartir archivo
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success btn-sm" id="btnConfirmSendWhatsApp">
                        <i class="fab fa-whatsapp mr-1"></i> Abrir WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var tableQuotesSAP = null;
    var canAuthorize   = <?= !empty($canAuthorize) ? 'true' : 'false' ?>;

    $(document).ready(function () {

        tableQuotesSAP = $('#tableQuotesSAP').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[0, 'desc']],
            ajax: {
                url: "<?= base_url('admin/quotesSAP') ?>",
                type: "GET",
                data: function (d) {
                    d.fechaInicial = $('#filterFechaIni').val();
                    d.fechaFinal   = $('#filterFechaFin').val();
                    d.status       = $('#filterStatus').val();
                }
            },
            columns: [
                { data: 'id', className: 'text-center' },
                { data: 'codigo', className: 'text-center font-weight-bold' },
                {
                    data: 'sapDocNum',
                    className: 'text-center',
                    render: function (data) {
                        return data ? `<span class="badge badge-primary">${data}</span>` : '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `${row.cardCode} - ${row.cardName || ''}`;
                    }
                },
                { data: 'cotizarA', defaultContent: '' },
                { data: 'nombreVendedor', defaultContent: '' },
                { data: 'whsCode', className: 'text-center', defaultContent: '-' },
                { data: 'namePriceList', defaultContent: '' },
                {
                    data: 'neto',
                    className: 'text-right',
                    render: function (data) { return '$' + parseFloat(data || 0).toFixed(2); }
                },
                {
                    data: 'total',
                    className: 'text-right font-weight-bold',
                    render: function (data) { return '$' + parseFloat(data || 0).toFixed(2); }
                },
                { data: 'fecha', className: 'text-center' },
                { data: 'fechaVencimiento', className: 'text-center' },
                {
                    data: 'statusComputed',
                    className: 'text-center',
                    render: function (data, type, row) {
                        var folio  = row.sapInvoiceNum || row.folioFacturaStatus || '';
                        var pedido = row.sapOrderNum || row.folioFacturaStatus || '';

                        if (data === 'FAC') {
                            return `<span class="badge badge-info"><i class="fas fa-file-invoice-dollar mr-1"></i>Facturada${folio ? ' (#' + folio + ')' : ''}</span>`;
                        }
                        if (data === 'PED') {
                            return `<span class="badge badge-primary"><i class="fas fa-shopping-cart mr-1"></i>En Pedido${pedido ? ' (#' + pedido + ')' : ''}</span>`;
                        }
                        if (data === 'AUT' || data === 'LFA') {
                            return '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Autorizada</span>';
                        }
                        if (data === 'VEN') {
                            return '<span class="badge badge-danger"><i class="fas fa-calendar-times mr-1"></i>Vencida</span>';
                        }
                        if (data === 'REC') {
                            return '<span class="badge badge-dark"><i class="fas fa-ban mr-1"></i>Rechazada</span>';
                        }
                        return '<span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1"></i>Pendiente Autorización</span>';
                    }
                },
                {
                    data: 'sapSynced',
                    className: 'text-center',
                    render: function (data) {
                        return parseInt(data) === 1
                            ? '<span class="badge badge-success"><i class="fas fa-check"></i> En SAP</span>'
                            : '<span class="badge badge-secondary"><i class="fas fa-clock"></i> Local</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        var id     = row.id;
                        var uuid   = row.UUID;
                        var codigo = row.codigo;
                        var synced = parseInt(row.sapSynced) || 0;
                        var st     = row.statusComputed || 'PEN';
                        var rawSt  = (row.status === 'LFA') ? 'AUT' : (row.status || 'PEN');
                        var isVenc = st === 'VEN';
                        var isAut  = (rawSt === 'AUT' || st === 'AUT' || st === 'LFA');

                        var btns = `<div class="btn-group">`;

                        btns += `<a href="<?= base_url('admin/quotesSAP/report') ?>/${uuid}" target="_blank" class="btn btn-secondary btn-xs btnPrintQuotePDF" data-uuid="${uuid}" title="Imprimir Cotización PDF">
                                    <i class="fas fa-print"></i>
                                 </a>`;

                        btns += `<button type="button" class="btn btn-success btn-xs btnSendWhatsApp" title="Enviar PDF por WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                 </button>`;

                        btns += `<button type="button" class="btn btn-info btn-xs btnViewQuoteDetails" data-id="${id}" data-codigo="${codigo}" title="Ver Partidas">
                                    <i class="fas fa-eye"></i>
                                 </button>`;

                        if (canAuthorize) {
                            btns += `<button type="button" class="btn btn-primary btn-xs btnCambiarStatus" ${isVenc ? 'disabled' : ''} data-id="${id}" data-status="${rawSt}" data-factura="${row.folioFacturaStatus || ''}" title="Autorizar / Cambiar Estatus">
                                        <i class="fas fa-user-check"></i>
                                     </button>`;
                        }

                        if (synced === 0) {
                            btns += `<a href="<?= base_url('admin/quotesSAP/edit') ?>/${uuid}" class="btn btn-warning btn-xs" title="Editar Cotización">
                                        <i class="fas fa-edit"></i>
                                     </a>`;

                            if (isAut && !isVenc) {
                                btns += `<button type="button" class="btn btn-success btn-xs btnSendQuoteSAP" data-id="${id}" data-codigo="${codigo}" title="Enviar a SAP (OQUT)">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                         </button>`;
                            } else {
                                btns += `<button type="button" class="btn btn-outline-secondary btn-xs" disabled title="Requiere estar Autorizada para enviar a SAP">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                         </button>`;
                            }

                            btns += `<button type="button" class="btn btn-danger btn-xs btnDeleteQuote" data-id="${id}" data-codigo="${codigo}" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                     </button>`;
                        }

                        btns += `</div>`;
                        return btns;
                    }
                }
            ]
        });

        $('#btnFilterQuotes').on('click', function () {
            tableQuotesSAP.ajax.reload();
        });

        // Imprimir PDF con parámetro anti-caché
        $(document).on('click', '.btnPrintQuotePDF', function (e) {
            e.preventDefault();
            var uuid = $(this).attr('data-uuid');
            window.open("<?= base_url('admin/quotesSAP/report') ?>/" + uuid + "?v=" + Date.now(), "_blank");
        });

        // Abrir modal de envío por WhatsApp
        $(document).on('click', '.btnSendWhatsApp', function () {
            var row = tableQuotesSAP.row($(this).closest('tr')).data();
            if (!row) return;

            var uuid     = row.UUID || '';
            var token    = row.pdfToken || '';
            var codigo   = row.codigo || '';
            var cliente  = (row.cotizarA && row.cotizarA.trim() !== '') ? row.cotizarA.trim() : (row.cardName || '');
            var totalFmt = '$' + parseFloat(row.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            var rawPhone = String(row.telefono || '').replace(/\D+/g, '');

            if (rawPhone.length === 10) {
                rawPhone = '52' + rawPhone;
            }

            var pdfPublicUrl = "<?= base_url('cotizacion-pdf') ?>/" + uuid + "/" + token + "?v=" + Date.now();
            var mensaje = `Hola ${cliente}, le comparto la Cotización #${codigo} por un total de ${totalFmt}.\n\nPuede ver o descargar el PDF aquí:\n${pdfPublicUrl}`;

            $('#waQuoteUuid').val(uuid);
            $('#waQuoteFolio').val(codigo);
            $('#waPhone').val(rawPhone);
            $('#waMessage').val(mensaje);

            if (navigator.canShare) {
                $('#btnWaShareFile').removeClass('d-none');
            }

            $('#modalSendWhatsApp').modal('show');
        });

        // Abrir WhatsApp con mensaje y liga al PDF
        $('#btnConfirmSendWhatsApp').on('click', function () {
            var phone = String($('#waPhone').val() || '').replace(/\D+/g, '');
            var msg   = String($('#waMessage').val() || '').trim();

            if (phone.length === 10) {
                phone = '52' + phone;
            }
            if (phone.length < 10) {
                Swal.fire('Teléfono inválido', 'Ingresa un número de WhatsApp válido (mínimo 10 dígitos).', 'warning');
                return;
            }

            var waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(waUrl, '_blank');
        });

        // Descargar PDF actualizado para adjuntar manualmente
        $('#btnWaDownloadPdf').on('click', function () {
            var uuid = $('#waQuoteUuid').val();
            if (!uuid) return;
            window.open("<?= base_url('admin/quotesSAP/report') ?>/" + uuid + "?download=1&v=" + Date.now(), "_blank");
        });

        // Compartir archivo PDF directo desde móvil/navegador compatible
        $('#btnWaShareFile').on('click', async function () {
            var uuid   = $('#waQuoteUuid').val();
            var codigo = $('#waQuoteFolio').val();
            if (!uuid) return;

            try {
                var resp = await fetch("<?= base_url('admin/quotesSAP/report') ?>/" + uuid + "?v=" + Date.now(), {
                    cache: 'no-store'
                });
                var blob = await resp.blob();
                var file = new File([blob], `COTIZACION_${codigo}.pdf`, { type: 'application/pdf' });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: `Cotización #${codigo}`,
                        text: `Cotización #${codigo}`,
                        files: [file]
                    });
                } else {
                    Swal.fire('Aviso', 'Este navegador no soporta compartir archivos directamente. Usa "Descargar PDF" o "Abrir WhatsApp".', 'info');
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    Swal.fire('Error', 'No se pudo preparar el PDF para compartir.', 'error');
                }
            }
        });

        $(document).on('click', '.btnViewQuoteDetails', function () {
            var id     = $(this).attr('data-id');
            var codigo = $(this).attr('data-codigo');

            $('#lblModalQuoteFolio').text('#' + codigo);
            $('#modalQuoteDetails').modal('show');

            $.post("<?= base_url('admin/quotesSAP/showQuoteDetails') ?>", {
                idQuote: id,
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            }, function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                var tbody = $('#tableModalQuoteDetails tbody').empty();
                (res.data || []).forEach(function (item) {
                    tbody.append(`
                        <tr>
                            <td class="text-center">${item.renglon}</td>
                            <td class="text-center font-weight-bold">${item.clave}</td>
                            <td>${item.descripcion}</td>
                            <td class="text-center">${item.unidad}</td>
                            <td class="text-right">${item.cantidad}</td>
                            <td class="text-right">${item.precio}</td>
                            <td class="text-right font-weight-bold">${item.total}</td>
                        </tr>
                    `);
                });
            }, 'json');
        });

        $('#selectQuoteStatus').on('change', function () {
            var val = $(this).val();
            if (val === 'FAC') {
                $('#lblFolioDoc').text('Folio de Factura SAP');
                $('#groupFolioFactura').show();
            } else if (val === 'PED') {
                $('#lblFolioDoc').text('Folio de Pedido / Orden de Venta SAP');
                $('#groupFolioFactura').show();
            } else {
                $('#groupFolioFactura').hide();
            }
        });

        $(document).on('click', '.btnCambiarStatus', function () {
            $('#statusIdQuote').val($(this).attr('data-id'));
            $('#selectQuoteStatus').val($(this).attr('data-status')).trigger('change');
            $('#inputFolioFactura').val($(this).attr('data-factura'));
            $('#modalCambiarStatus').modal('show');
        });

        $('#btnConfirmStatus').on('click', function () {
            $.post("<?= base_url('admin/quotesSAP/changeStatus') ?>", {
                idQuote: $('#statusIdQuote').val(),
                status: $('#selectQuoteStatus').val(),
                folioFacturaStatus: $('#inputFolioFactura').val(),
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            }, function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                if (!res.error) {
                    $('#modalCambiarStatus').modal('hide');
                    Toast.fire({ icon: 'success', title: res.message });
                    tableQuotesSAP.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        });

        $(document).on('click', '.btnSendQuoteSAP', function () {
            var id     = $(this).attr('data-id');
            var codigo = $(this).attr('data-codigo');

            Swal.fire({
                title: `¿Enviar Cotización #${codigo} a SAP?`,
                text: "Se creará el documento en Ofertas de Ventas (OQUT) de SAP Service Layer.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar a SAP'
            }).then((result) => {
                if (result.isConfirmed || result.value) {
                    Swal.fire({ title: 'Enviando a SAP...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    $.post("<?= base_url('admin/quotesSAP/sendToSAP') ?>", {
                        idQuote: id,
                        [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
                    }, function (res) {
                        if (res.token) $('.txt_csrfname').val(res.token);
                        if (!res.error) {
                            Swal.fire('¡Sincronizado!', res.message, 'success');
                            tableQuotesSAP.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error en SAP', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        });

        $(document).on('click', '.btnDeleteQuote', function () {
            var id     = $(this).attr('data-id');
            var codigo = $(this).attr('data-codigo');

            Swal.fire({
                title: `¿Eliminar Cotización #${codigo}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed || result.value) {
                    $.ajax({
                        url: "<?= base_url('admin/quotesSAP/delete') ?>/" + id,
                        method: "DELETE",
                        success: function () {
                            Toast.fire({ icon: 'success', title: 'Cotización eliminada.' });
                            tableQuotesSAP.ajax.reload(null, false);
                        },
                        error: function (jqXHR) {
                            Swal.fire('Error', jqXHR.responseJSON?.messages?.error || 'No se pudo eliminar', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>