<!-- Modal Lista de Productos -->
<div class="modal fade" id="modalListProducts" tabindex="-1" role="dialog" aria-labelledby="modalListProductsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalListProductsLabel" class="modal-title">Lista de productos</h5>

                <!-- hidden inputs para pasar datos a la petición -->
                <input type="hidden" id="modal_docEntry" name="modal_docEntry" value="">
                <input type="hidden" id="modal_docNum" name="modal_docNum" value="">
                <input type="hidden" id="modal_almacen" name="modal_almacen" value="">

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table id="table-listProducts" class="table table-striped table-hover va-middle table-sm" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Artículo</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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

// Instancia global de DataTable
    var tableListProducts = null;

// Inicializar DataTable (sin datos todavía, lo cargaremos dinámicamente al abrir modal)
    function initProductsTable() {
        if ($.fn.DataTable.isDataTable('#table-listProducts')) {
            tableListProducts = $('#table-listProducts').DataTable();
            return;
        }

        tableListProducts = $('#table-listProducts').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ordering: true,
            order: [[1, 'asc']],
            pageLength: 10,
            ajax: function (data, callback, settings) {
                // data contiene draw, start, length, search, order...
                // añadimos docEntry/docNum/almacen desde inputs hidden
                var payload = {
                    draw: data.draw,
                    start: data.start,
                    length: data.length,
                    search: data.search && data.search.value ? data.search.value : '',
                    order: data.order || [],
                    docEntry: $('#modal_docEntry').val(),
                    docNum: $('#modal_docNum').val(),
                    almacen: $('#modal_almacen').val()
                };

                $.ajax({
                    url: '<?= base_url('admin/servicelayer/showlistProductsReq') ?>', // tu ruta POST
                    method: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify(payload),
                    success: function (resp) {
                        // Si tu backend devuelve { recordsTotal, recordsFiltered, data }
                        // lo adaptamos al formato que DataTables espera
                        if (resp === null) {
                            return callback({
                                draw: payload.draw,
                                recordsTotal: 0,
                                recordsFiltered: 0,
                                data: []
                            });
                        }

                        // Algunas implementaciones ya devuelven draw; si no, lo añadimos
                        var draw = resp.draw || payload.draw || 0;

                        // Si tu backend devolvió recordsTotal/recordsFiltered/data:
                        if (typeof resp.recordsTotal !== 'undefined') {
                            return callback({
                                draw: draw,
                                recordsTotal: parseInt(resp.recordsTotal) || 0,
                                recordsFiltered: parseInt(resp.recordsFiltered || resp.recordsTotal) || 0,
                                data: resp.data || []
                            });
                        }

                        // Si tu backend devolvió la estructura de DataTables directamente:
                        if (typeof resp.data !== 'undefined' && typeof resp.recordsTotal !== 'undefined') {
                            return callback(resp);
                        }

                        // Fallback: si el backend devolvió un array directamente en resp (sin contadores)
                        if (Array.isArray(resp)) {
                            return callback({
                                draw: draw,
                                recordsTotal: resp.length,
                                recordsFiltered: resp.length,
                                data: resp
                            });
                        }

                        // último fallback: vacio
                        return callback({
                            draw: draw,
                            recordsTotal: 0,
                            recordsFiltered: 0,
                            data: []
                        });
                    },
                    error: function (xhr, status, err) {
                        console.error('Error al obtener productos:', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudieron obtener los productos. Revisa la consola.', 'error');
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
                        // Si backend no envía No, calculamos desde meta (start + index)
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
                {data: 'Cantidad', name: 'Cantidad', render: function (d) {
                        return (d === null || typeof d === 'undefined') ? '' : d;
                    }}
            ],
            language: {
                processing: "Cargando..."
            },
            drawCallback: function (settings) {
                // Opcional: ejecutar algo cuando la tabla se haya pintado
            }
        });
    }

// Abrir modal y pasar parámetros (ejemplo: desde el botón .btnViewItems)
    $('body').on('click', '.btnViewItems', function (e) {
        e.preventDefault();

        // obtener valores (pudiste haberlos puesto en data-* del botón)
        var docEntry = $(this).data('docentry') || $(this).attr('data-docentry') || '';
        var docNum = $(this).data('docnum') || $(this).attr('data-docnum') || '';
        var almacen = $(this).data('almacen') || $(this).attr('data-almacen') || '';

        // setear hidden inputs
        $('#modal_docEntry').val(docEntry);
        $('#modal_docNum').val(docNum);
        $('#modal_almacen').val(almacen);

        // inicializar la tabla si no lo está
        initProductsTable();

        // Abrir modal
        $('#modalListProducts').modal('show');

        // recargar la tabla (traerá con el docEntry actual)
        if (tableListProducts) {
            tableListProducts.ajax.reload();
        }
    });

// Si prefieres abrir el modal desde otro lugar donde ya tengas docEntry, llama:
// $('#modal_docEntry').val(245); $('#modal_docNum').val(400000047); $('#modal_almacen').val('AGM'); initProductsTable(); $('#modalListProducts').modal('show'); tableListProducts.ajax.reload();


</script>

<?= $this->endSection() ?>
