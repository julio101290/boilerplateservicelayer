<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesAuthPO/modalShowProductsPO') ?>

<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title">Pedidos de Compra</h3>
        <div class="float-right">
            <!-- Botón de Estado / Filtro -->
            <button type="button" id="btnToggleAuthPO" class="btn btn-secondary btn-sm">
                <i class="fas fa-clock"></i> No autorizadas
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableAuthPO" class="table table-striped table-hover va-middle tableUser_sap_link">
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Almacén</th>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Solicita</th>
                                <th>Proveedor</th>
                                <th>Total</th>
                                <th>Descuento</th>
                                <th>Impuestos</th>
                                <th>Total c/ Impuestos</th>
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
    // Variable de control de estado (false: No autorizadas | true: Ya autorizadas)
    var showAuthorizedPO = false;

    // Helper formato moneda
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

    // Inicializar DataTable
    var tableAuthPO = $('#tableAuthPO').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/servicelayer/getauthpo') ?>',
            method: 'GET',
            dataType: "json",
            data: function (d) {
                d.authorized = showAuthorizedPO ? 1 : 0;
            },
            dataSrc: function (json) {
                console.log('AJAX response (DataTables PO):', json);
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
            { targets: [6, 7, 8, 9], className: 'text-right' }
        ],
        columns: [
            // Columna acciones
            {
                data: function (row) {
                    var docEntry = row.DocEntry ?? row.docEntry ?? (row._raw && (row._raw.DocEntry ?? row._raw.DocEntry)) ?? '';
                    var docNum   = row.DocNum   ?? row.docNum   ?? (row._raw && (row._raw.DocNum ?? row._raw.DocNum)) ?? '';
                    var almacen  = row.Almacen  ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';

                    var eDocEntry = String(docEntry).replace(/"/g, '&quot;');
                    var eDocNum   = String(docNum).replace(/"/g, '&quot;');
                    var eAlmacen  = String(almacen).replace(/"/g, '&quot;');

                    var botonAccion = '';
                    if (showAuthorizedPO) {
                        botonAccion = `
                        <button class="btn btn-danger btnDeauthorizePO btn-sm"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}"
                                title="Desautorizar">
                            <i class="fas fa-times-circle"></i> Desautorizar
                        </button>`;
                    } else {
                        botonAccion = `
                        <button class="btn btn-success btnAuthorizePO btn-sm"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}"
                                title="Autorizar">
                            <i class="fas fa-check-circle"></i> Autorizar Orden
                        </button>`;
                    }

                    return `
                    <div class="btn-group" role="group" aria-label="Acciones">
                        ${botonAccion}
                        <button class="btn btn-info btnViewPOItems btn-sm ml-1"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}"
                                title="Ver artículos">
                            <i class="fas fa-boxes"></i>
                        </button>
                    </div>`;
                }
            },
            {
                data: function (row) {
                    return row.Almacen ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';
                },
                name: 'Almacen'
            },
            {
                data: function (row) {
                    return row.DocNum ?? row.docNum ?? (row._raw && (row._raw.DocNum ?? '')) ?? '';
                },
                name: 'DocNum'
            },
            {
                data: function (row) {
                    return row.DocDate ?? row.docDate ?? (row._raw && (row._raw.DocDate ?? '')) ?? '';
                },
                name: 'DocDate'
            },
            {
                data: function (row) {
                    return row.NombreDeUsuario ?? (row._raw && row._raw.NombreDeUsuario) ?? '';
                },
                name: 'NombreDeUsuario'
            },
            {
                data: function (row) {
                    return row.CardName ?? (row._raw && row._raw.CardName) ?? '';
                },
                name: 'CardName'
            },
            {
                data: function (row) {
                    var v = row.TotalSinImpuestos ?? (row._raw && row._raw.TotalSinImpuestos) ?? null;
                    return fmtMoney(v);
                },
                name: 'TotalSinImpuestos'
            },
            {
                data: function (row) {
                    var v = row.Descuento ?? (row._raw && row._raw.Descuento) ?? null;
                    return fmtMoney(v);
                },
                name: 'Descuento'
            },
            {
                data: function (row) {
                    var v = row.Impuestos ?? (row._raw && row._raw.Impuestos) ?? null;
                    return fmtMoney(v);
                },
                name: 'Impuestos'
            },
            {
                data: function (row) {
                    var v = row.TotalConImpuestos ?? (row._raw && row._raw.TotalConImpuestos) ?? null;
                    return fmtMoney(v);
                },
                name: 'TotalConImpuestos'
            }
        ],
        language: {
            processing: "Cargando..."
        }
    });

    // Alternar filtro No autorizadas / Ya autorizadas
    $('#btnToggleAuthPO').on('click', function () {
        showAuthorizedPO = !showAuthorizedPO;
        const $btn = $(this);

        if (showAuthorizedPO) {
            $btn.removeClass('btn-secondary').addClass('btn-success');
            $btn.html('<i class="fas fa-check-double"></i> Ya autorizadas');
        } else {
            $btn.removeClass('btn-success').addClass('btn-secondary');
            $btn.html('<i class="fas fa-clock"></i> No autorizadas');
        }

        tableAuthPO.ajax.reload();
    });

    // 1) Handler: Autorizar Pedido
    $('#tableAuthPO tbody').on('click', '.btnAuthorizePO', function () {
        const $btn     = $(this);
        const docEntry = $btn.data('docentry');
        const docNum   = $btn.data('docnum');
        const almacen  = $btn.data('almacen');

        Swal.fire({
            title: '¿Autorizar Pedido de Compra?',
            html: `
                <p><strong>DocEntry:</strong> ${docEntry}</p>
                <p><strong>DocNum:</strong> ${docNum}</p>
                <p><strong>Almacén:</strong> ${almacen}</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, autorizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                const payload = {
                    docEntry: docEntry,
                    docNum: docNum,
                    almacen: almacen
                };

                $.ajax({
                    url: '<?= base_url("admin/servicelayer/authorizePO") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(payload),
                    success: function (resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Pedido autorizado',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            tableAuthPO.ajax.reload(null, false);
                        } else {
                            var msg = (resp && resp.error) ? resp.error : 'Error en la autorización';
                            Swal.fire('Error', msg, 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        console.error('AJAX authorizePO error', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudo autorizar el pedido (error de red o servidor).', 'error');
                    }
                });
            }
        });
    });

    // 2) Handler: Desautorizar Pedido
    $('#tableAuthPO tbody').on('click', '.btnDeauthorizePO', function () {
        const $btn     = $(this);
        const docEntry = $btn.data('docentry');
        const docNum   = $btn.data('docnum');
        const almacen  = $btn.data('almacen');

        Swal.fire({
            title: '¿Desautorizar Pedido de Compra?',
            html: `
                <p><strong>DocEntry:</strong> ${docEntry}</p>
                <p><strong>DocNum:</strong> ${docNum}</p>
                <p><strong>Almacén:</strong> ${almacen}</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, desautorizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                const payload = {
                    docEntry: docEntry,
                    docNum: docNum,
                    almacen: almacen
                };

                $.ajax({
                    url: '<?= base_url("admin/servicelayer/deauthorizeOrder") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(payload),
                    success: function (resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Pedido desautorizado',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            tableAuthPO.ajax.reload(null, false);
                        } else {
                            var msg = (resp && resp.error) ? resp.error : 'Error al desautorizar';
                            Swal.fire('Error', msg, 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        console.error('AJAX deauthorizeOrder error', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudo desautorizar (error de red o servidor).', 'error');
                    }
                });
            }
        });
    });

    // 3) Handler: Ver artículos del PO
    $('#tableAuthPO tbody').on('click', '.btnViewPOItems', function () {
        var $btn = $(this);
        var docEntry = $btn.data('docentry');

        $.ajax({
            url: '<?= base_url("admin/servicelayer/showlistProductsPO") ?>',
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({docEntry: docEntry}),
            success: function (resp) {
                if (resp && resp.data) {
                    $('#modalPOItemsBody').empty();
                    var html = '<table class="table table-sm table-bordered"><thead><tr><th>No</th><th>Artículo</th><th>Descripción</th><th>Cantidad</th></tr></thead><tbody>';
                    resp.data.forEach(function (r) {
                        html += `<tr>
                                    <td>${r.No ?? ''}</td>
                                    <td>${r.Articulo ?? ''}</td>
                                    <td>${r.Descripcion ?? ''}</td>
                                    <td>${r.Cantidad ?? ''}</td>
                                 </tr>`;
                    });
                    html += '</tbody></table>';
                    $('#modalPOItemsBody').append(html);
                    $('#modalShowProductsPO').modal('show');
                } else {
                    Swal.fire('Info', 'No se encontraron artículos para este pedido.', 'info');
                }
            },
            error: function (xhr, status, err) {
                console.error('AJAX showPOItems error', status, err, xhr.responseText);
                Swal.fire('Error', 'No fue posible obtener los artículos.', 'error');
            }
        });
    });

    $(function () {
        $("#modalListProductsPO").draggable();
    });
</script>
<?= $this->endSection() ?>