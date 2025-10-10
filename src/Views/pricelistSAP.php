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
    <div class="card-header d-flex justify-content-between align-items-center">


        <!-- NUEVO: Campo código de artículo + botones Buscar / Limpiar -->
        <div class="form-inline">
            <label for="inputArticleCode" class="mr-2 mb-0">Código artículo:</label>
            <input id="inputArticleCode" type="text" class="form-control form-control-sm mr-2" placeholder="Ej. A1001" />
            <button id="btnSearchArticle" class="btn btn-primary btn-sm mr-2"><i class="fas fa-search"></i> Buscar</button>
            <button id="btnClearArticle" class="btn btn-secondary btn-sm" title="Limpiar búsqueda"><i class="fas fa-eraser"></i></button>
        </div>
        <!-- FIN NUEVO -->
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableListPrice" class="table table-striped table-hover va-middle tableUser_sap_link">
                        <thead>
                            <tr>
                                <th>Clave Lista</th>
                                <th>Nombre Lista</th>
                                <th>Clave Articulo</th>
                                <th>Descripción Articulo</th>
                                <th>Precio actual Sin IVA</th>
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



    /* --------------------------------------
     DataTable: Lista de precios por artículo (POST)
     -------------------------------------- */
    var tableListPrice = $('#tableListPrice').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/servicelayer/loaddatatable') ?>',
            method: 'POST', // <- ahora POST
            data: function (d) {
                // d es el objeto que DataTables va a enviar (draw, start, length, order, search, etc.)
                d.articleCode = $('#inputArticleCode').val().trim(); // agregamos nuestro parámetro
                // si quieres enviar token CSRF u otros campos:
                // d.csrf_test_name = '<?= csrf_hash() ?>';
                return d; // DataTables jQuery enviará esto como application/x-www-form-urlencoded
            },
            dataType: 'json',
            dataSrc: function (json) {
                // DataTables espera json.data -> array de filas
                return json.data || [];
            }
        },
        columnDefs: [
            {targets: 0, orderable: false, searchable: false, width: '160px'},
            {targets: [3], className: 'text-right'}
        ],
        columns: [

            {data: function (row) {
                    return row.PriceListName ?? row.ListName ?? row.Lista ?? '';
                }, name: 'PriceListName'},
            {data: function (row) {
                    return row.ItemName ?? row.ItemDescription ?? row.Nombre ?? '';
                }, name: 'ItemName'},
            {data: function (row) {
                    var v = row.Price ?? row.UnitPrice ?? row.Precio ?? null;
                    return fmtMoney(v);
                }, name: 'Price'},
            {
                data: function (row) {
                    var codigo = row.ItemCode ?? row.Codigo ?? row.Code ?? '';
                    var nombre = row.ItemName ?? row.ItemDescription ?? '';
                    var escCodigo = String(codigo).replace(/"/g, '&quot;');
                    return `<button class="btn btn-sm btn-info btnSelectPrice" data-code="${escCodigo}"><i class="fas fa-check"></i> Seleccionar</button>`;
                }
            },
        ],
        language: {processing: "Cargando..."}
    });

    // Buscar al hacer click en botón Buscar
    $('#btnSearchArticle').on('click', function () {
        var code = $('#inputArticleCode').val().trim();
        if (!code) {
            Swal.fire({icon: 'warning', title: 'Escribe un código de artículo', toast: true, position: 'top-end', timer: 1800, showConfirmButton: false});
            return;
        }
        tableListPrice.ajax.reload();
    });

    // Presionar Enter en el input ejecuta búsqueda
    $('#inputArticleCode').on('keypress', function (e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            $('#btnSearchArticle').trigger('click');
        }
    });

    // Limpiar campo y recargar tabla
    $('#btnClearArticle').on('click', function () {
        $('#inputArticleCode').val('');
        tableListPrice.ajax.reload();
    });

    // Delegación: botón seleccionar precio
    $('#tableListPrice tbody').on('click', '.btnSelectPrice', function () {
        var code = $(this).data('code');
        console.log('Artículo seleccionado:', code);
        // ejemplo: rellenar input principal con el codigo seleccionado
        $('#inputArticleCode').val(code);
        // opcional: si quieres recargar la tabla con el código seleccionado:
        // tableListPrice.ajax.reload();
    });

    $(function () {
        // Hacer modales draggable si aplica
        $("#modalAddUser_sap_link").draggable();
    });
</script>
<?= $this->endSection() ?>
