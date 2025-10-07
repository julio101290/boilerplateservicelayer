<!-- Modal Lista de Productos (Purchase Order) -->
<div class="modal fade" id="modalListProductsPO" tabindex="-1" role="dialog" aria-labelledby="modalListProductsPOLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalListProductsPOLabel" class="modal-title">Lista de productos - Orden de Compra</h5>

                <!-- hidden inputs para pasar datos a la petición (PO) -->
                <input type="hidden" id="modal_po_docEntry" name="modal_po_docEntry" value="">
                <input type="hidden" id="modal_po_docNum" name="modal_po_docNum" value="">
                <input type="hidden" id="modal_po_almacen" name="modal_po_almacen" value="">

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table id="table-listProductsPO" class="table table-striped table-hover va-middle table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Artículo</th>
                                <th>Descripción</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Totales:</th>
                                <th id="po-items-total-cantidad" class="text-right">0</th>
                                <th></th>
                                <th id="po-items-total-lineTotal" class="text-right">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?? 'Cerrar' ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->section('js') ?>

<script>
// Asegúrate de cargar jQuery y DataTables antes de este script

    var tableListProductsPO = null;

    function formatNumber2(v) {
        if (v === null || typeof v === 'undefined' || v === '') return '';
        var n = Number(v);
        if (isNaN(n)) {
            // intentar limpiar comas/espacios
            var cleaned = String(v).replace(/[, ]+/g, '');
            n = Number(cleaned);
            if (isNaN(n)) return '';
        }
        return n.toFixed(2);
    }

    function initProductsTablePO() {
        if ($.fn.DataTable.isDataTable('#table-listProductsPO')) {
            tableListProductsPO = $('#table-listProductsPO').DataTable();
            return;
        }

        tableListProductsPO = $('#table-listProductsPO').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ordering: true,
            order: [[1, 'asc']],
            pageLength: 10,
            ajax: function (data, callback, settings) {
                var payload = {
                    draw: data.draw,
                    start: data.start,
                    length: data.length,
                    search: data.search && data.search.value ? data.search.value : '',
                    order: data.order || [],
                    docEntry: $('#modal_po_docEntry').val(),
                    docNum: $('#modal_po_docNum').val(),
                    almacen: $('#modal_po_almacen').val()
                };

                $.ajax({
                    url: '<?= base_url('admin/servicelayer/showlistProductsPO') ?>',
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify(payload),
                    success: function (resp) {
                        if (resp === null) {
                            return callback({
                                draw: payload.draw,
                                recordsTotal: 0,
                                recordsFiltered: 0,
                                data: []
                            });
                        }

                        var draw = resp.draw || payload.draw || 0;

                        if (typeof resp.recordsTotal !== 'undefined') {
                            // calcular totales de pies de tabla si vienen en resp (no obligatorio)
                            if (Array.isArray(resp.data)) {
                                // calcular sumas en el frontend (por si el backend no las devuelve)
                                var sumCantidad = 0;
                                var sumLineTotal = 0;
                                resp.data.forEach(function (r) {
                                    var cantidad = parseFloat(String(r.Cantidad ?? 0).replace(/[, ]+/g, '')) || 0;
                                    var total = parseFloat(String(r.Total ?? 0).replace(/[, ]+/g, '')) || 0;
                                    sumCantidad += cantidad;
                                    sumLineTotal += total;
                                });
                                $('#po-items-total-cantidad').text(sumCantidad);
                                $('#po-items-total-lineTotal').text(sumLineTotal.toFixed(2));
                            }

                            return callback({
                                draw: draw,
                                recordsTotal: parseInt(resp.recordsTotal) || 0,
                                recordsFiltered: parseInt(resp.recordsFiltered || resp.recordsTotal) || 0,
                                data: resp.data || []
                            });
                        }

                        if (Array.isArray(resp)) {
                            // calcular totales
                            var sumCantidad = 0;
                            var sumLineTotal = 0;
                            resp.forEach(function (r) {
                                var cantidad = parseFloat(String(r.Cantidad ?? 0).replace(/[, ]+/g, '')) || 0;
                                var total = parseFloat(String(r.Total ?? 0).replace(/[, ]+/g, '')) || 0;
                                sumCantidad += cantidad;
                                sumLineTotal += total;
                            });
                            $('#po-items-total-cantidad').text(sumCantidad);
                            $('#po-items-total-lineTotal').text(sumLineTotal.toFixed(2));

                            return callback({
                                draw: draw,
                                recordsTotal: resp.length,
                                recordsFiltered: resp.length,
                                data: resp
                            });
                        }

                        return callback({
                            draw: draw,
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            data: []
                        });
                    },
                    error: function (xhr, status, err) {
                        console.error('Error al obtener productos PO:', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudieron obtener los productos del pedido. Revisa la consola.', 'error');
                        $('#po-items-total-cantidad').text('0');
                        $('#po-items-total-lineTotal').text('0.00');
                        return callback({
                            draw: payload.draw,
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            data: []
                        });
                    }
                });
            },
            columns: [
                {data: 'No', name: 'No', orderable: false, searchable: false, width: '60px',
                    render: function (data, type, row, meta) {
                        if (typeof data !== 'undefined' && data !== null && data !== '')
                            return data;
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {data: 'Articulo', name: 'Articulo', render: function (d) {
                        return d ?? '';
                    }},
                {data: 'Descripcion', name: 'Descripcion', render: function (d) {
                        return d ?? '';
                    }},
                {data: 'Cantidad', name: 'Cantidad', className: "text-right", render: function (d) {
                        if (d === null || typeof d === 'undefined' || d === '') return '';
                        var n = Number(String(d).replace(/[, ]+/g, ''));
                        return isNaN(n) ? '' : n;
                    }},
                {data: 'Precio', name: 'Precio', className: "text-right", render: function (d) {
                        var f = formatNumber2(d);
                        return f === '' ? '' : f;
                    }},
                {data: 'Total', name: 'Total', className: "text-right", render: function (d) {
                        var f = formatNumber2(d);
                        return f === '' ? '' : f;
                    }}
            ],
            language: {
                processing: "Cargando..."
            },
            drawCallback: function (settings) {
                // recalc totals en caso de paginación parcial (si prefieres, puedes pedir totales al backend)
                var api = this.api();
                var data = api.rows({page: 'current'}).data().toArray();

                var sumCantidad = 0;
                var sumLineTotal = 0;
                data.forEach(function (r) {
                    var cantidad = parseFloat(String(r.Cantidad ?? 0).replace(/[, ]+/g, '')) || 0;
                    var total = parseFloat(String(r.Total ?? 0).replace(/[, ]+/g, '')) || 0;
                    sumCantidad += cantidad;
                    sumLineTotal += total;
                });
                $('#po-items-total-cantidad').text(sumCantidad);
                $('#po-items-total-lineTotal').text(sumLineTotal.toFixed(2));
            }
        });
    }

    // Abrir modal y cargar artículos del PO
    $('body').on('click', '.btnViewPOItems', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var docEntry = $btn.data('docentry') || $btn.attr('data-docentry') || '';
        var docNum = $btn.data('docnum') || $btn.attr('data-docnum') || '';
        var almacen = $btn.data('almacen') || $btn.attr('data-almacen') || '';

        $('#modal_po_docEntry').val(docEntry);
        $('#modal_po_docNum').val(docNum);
        $('#modal_po_almacen').val(almacen);

        initProductsTablePO();

        $('#modalListProductsPO').modal('show');

        if (tableListProductsPO) {
            tableListProductsPO.ajax.reload();
        }
    });

// Si necesitas abrir el modal programáticamente:
// $('#modal_po_docEntry').val(123); $('#modal_po_docNum').val('PO-0001'); $('#modal_po_almacen').val('AGM'); initProductsTablePO(); $('#modalListProductsPO').modal('show'); tableListProductsPO.ajax.reload();

</script>

<?= $this->endSection() ?>
