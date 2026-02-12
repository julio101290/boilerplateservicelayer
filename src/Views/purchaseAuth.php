<?php
/**
 * Adaptado por julio101290
 *
 * Vista: Autorización de Pedidos de Compra (Purchase Orders)
 * Basada en la vista original de requisiciones, adaptada para PurchaseOrders.
 */
?>
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
                                <!-- Nuevas columnas añadidas -->
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
    // Helper formato moneda (usa locale del navegador, fallback simple)
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

    // Inicializar DataTable para Purchase Orders
    var tableAuthPO = $('#tableAuthPO').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            // Asumimos que el endpoint para listar POs es getauthpo
            url: '<?= base_url('admin/servicelayer/getauthpo') ?>',
            method: 'GET',
            dataType: "json",
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
            // Opcional: ajustar ancho de columnas de totales
            {targets: [5, 6, 7], className: 'text-right'}
        ],
        columns: [
            // Columna acciones (botones)
            {
                data: function (row) {
                    var docEntry = row.DocEntry ?? row.docEntry ?? (row._raw && (row._raw.DocEntry ?? row._raw.DocEntry)) ?? '';
                    var docNum = row.DocNum   ?? row.docNum   ?? (row._raw && (row._raw.DocNum ?? row._raw.DocNum)) ?? '';
                    var almacen = row.Almacen  ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';

                    var eDocEntry = String(docEntry).replace(/"/g, '&quot;');
                    var eDocNum = String(docNum).replace(/"/g, '&quot;');
                    var eAlmacen = String(almacen).replace(/"/g, '&quot;');

                    return `
                    <div class="btn-group" role="group" aria-label="Acciones">
                        <button class="btn btn-success btnAuthorizePO btn-sm"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}">
                            <i class="fas fa-check-circle"></i> Autorizar Orden
                        </button>
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


            // Columna Almacén
            {
                data: function (row) {
                    return row.Almacen ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';
                },
                name: 'Almacen'
            },
                    



            // Columna DocNum (Folio)
            {
                data: function (row) {
                    return row.DocNum ?? row.docNum ?? (row._raw && (row._raw.DocNum ?? '')) ?? '';
                },
                name: 'DocNum'
            },

            // Columna DocDate
            {
                data: function (row) {
                    return row.DocDate ?? row.docDate ?? (row._raw && (row._raw.DocDate ?? '')) ?? '';
                },
                name: 'DocDate'
            },
                    
                        // Columna Solicita
            {
                data: function (row) {
                    return row.NombreDeUsuario ?? row.NombreDeUsuario ?? row.NombreDeUsuario ?? row.NombreDeUsuario ?? (row._raw && (row._raw.NombreDeUsuario ?? row._raw.NombreDeUsuario)) ?? '';
                },
                name: 'NombreDeUsuario'
            },

            // Columna Proveedor (CardName)
            {
                data: function (row) {
                    // puede venir en CardName o en _raw.CardName
                    return row.CardName ?? (row._raw && (row._raw.CardName ?? '')) ?? '';
                },
                name: 'CardName'
            },

            // Columna Descuento
            {
                data: function (row) {
                    var v = row.DocTotal ?? row.TotalSinImpuestos ?? (row._raw && (row._raw.TotalSinImpuestos ?? null)) ?? null;
                    return fmtMoney(v);
                },
                name: 'TotalSinImpuestos'
            },

            // Columna DocTotal (Total sin impuestos)
            {
                data: function (row) {
                    var v = row.Descuento ?? row.Descuento ?? (row._raw && (row._raw.Descuento ?? null)) ?? null;
                    return fmtMoney(v);
                },
                name: 'Descuento'
            },

            // Columna TaxTotal (Total impuestos)
            {
                data: function (row) {
                    var v = row.Impuestos ?? (row._raw && (row._raw.Impuestos ?? null)) ?? null;
                    return fmtMoney(v);
                },
                name: 'Impuestos'
            },

            // Columna Total con impuestos
            {
                data: function (row) {
                    // TotalConImpuestos puede venir calculado por el backend; si no intentar sumar
                    var v = row.TotalConImpuestos ?? null;
                    if (v === null || typeof v === 'undefined' || v === '') {
                        var dt = parseFloat(row.DocTotal ?? (row._raw && row._raw.DocTotal) ?? 0) || 0;
                        var tx = parseFloat(row.TaxTotal ?? (row._raw && row._raw.TaxTotal) ?? 0) || 0;
                        v = dt + tx;
                    }
                    return fmtMoney(v);
                },
                name: 'TotalConImpuestos'
            }
        ],
        language: {
            processing: "Cargando..."
        }
    });

    // Handler: Autorizar Pedido (PATCH)
    $('#tableAuthPO tbody').on('click', '.btnAuthorizePO', function (e) {

        // guardar referencia del botón que abrió el modal/confirm
        const $btn = $(this);
        const docEntry = $btn.data('docentry');
        const docNum = $btn.data('docnum');
        const almacen = $btn.data('almacen');

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
                            // actualizar botón
                            if ($btn && $btn.length) {
                                $btn.removeClass('btn-success').addClass('btn-secondary').attr('disabled', true);
                                $btn.html('<i class="fas fa-check"></i> Autorizado');
                            }

                            // intentar actualizar fila con updatedRow si viene
                            if (resp.updatedRow) {
                                // buscar fila por DocEntry y actualizar
                                var idx = tableAuthPO.rows().indexes().filter(function (i) {
                                    var d = tableAuthPO.row(i).data();
                                    return (String(d.DocEntry) === String(resp.updatedRow.DocEntry));
                                })[0];
                                if (typeof idx !== 'undefined') {
                                    tableAuthPO.row(idx).data(resp.updatedRow).invalidate().draw(false);
                                } else {
                                    tableAuthPO.ajax.reload(null, false);
                                }
                            } else {
                                tableAuthPO.ajax.reload(null, false);
                            }

                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Pedido autorizado',
                                showConfirmButton: false,
                                timer: 2000
                            });
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

    // Handler: Ver artículos del PO (abre modal con items)
    $('#tableAuthPO tbody').on('click', '.btnViewPOItems', function () {
        var $btn = $(this);
        var docEntry = $btn.data('docentry');

        // Cargar items vía endpoint showPOItems (asumido)
        $.ajax({
            url: '<?= base_url("admin/servicelayer/showlistProductsPO") ?>',
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({docEntry: docEntry}),
            success: function (resp) {
                if (resp && resp.data) {
                    // popular modal (asumiendo que modalShowProductsPO tiene un contenedor #modalPOItemsBody)
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
        // Hacer modales draggable si aplica
        $("#modalListProductsPO").draggable();
    });
</script>
<?= $this->endSection() ?>
