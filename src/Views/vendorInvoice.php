<?= $this->include('julio101290\boilerplate\Views\load/daterangapicker') ?>
<?= $this->include('julio101290\boilerplate\Views\load/toggle') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load/extrasDatatable') ?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>

<!-- Extend from layout index -->
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<!-- Section content -->
<?= $this->section('content') ?>

<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/listaFacturas') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/xmlList') ?>

<!-- FILTROS Y ENCABEZADO -->
<div class="card card-default">
    <div class="card-header">
        <div class="float-left">
            <!-- Rango de Fechas -->
            <div class="btn-group">
                <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                    <i class="fa fa-calendar"></i>&nbsp;
                    <span></span> <i class="fa fa-caret-down"></i>
                </div>
            </div>

            <!-- Empresa -->
            <div class="btn-group">
                <div class="form-group mb-0">
                    <label for="idEmpresa" class="mr-1"><?= lang('invoiceVendor.companie') ?? 'Empresa' ?>: </label>
                    <select id='idEmpresa' name='idEmpresa' class="idEmpresa select2" style='width: 200px;'>
                        <?php
                        if (isset($idEmpresa) &&$idEmpresa > 0) {
                            echo "<option value='$idEmpresa' selected>$idEmpresa -$nombreEmpresa</option>";
                        } else {
                            echo "<option value='0'>" . (lang('invoiceVendor.allCompanies') ?? 'Todas las Empresas') . "</option>";
                            foreach ($empresas as $key =>$value) {
                                echo "<option value='{$value['id']}'>{$value['id']} - {$value['nombre']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Sucursal -->
            <div class="btn-group">
                <div class="form-group mb-0">
                    <label for="idSucursal" class="mr-1"><?= lang('invoiceVendor.branchoffice') ?? 'Sucursal' ?>: </label>
                    <select id='idSucursal' name='idSucursal' class="idSucursal select2" style='width: 200px;'>
                        <option value='0'><?= lang('invoiceVendor.AllBranchoffice') ?? 'Todas las Sucursales' ?></option>
                        <?php if (isset($idSucursal) &&$idSucursal > 0): ?>
                            <option value='<?= $idSucursal ?>' selected><?= "$idSucursal -$nombreSucursal" ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Proveedores -->
            <div class="btn-group">
                <div class="form-group mb-0">
                    <label for="clientes" class="mr-1"><?= lang('invoiceVendor.custumer') ?? 'Proveedor' ?>: </label>
                    <select id='clientes' name='clientes' class="clientes select2" style='width: 220px;'>
                        <option value='0'><?= lang('invoiceVendor.allCustumer') ?? 'Todos los Proveedores' ?></option>
                    </select>
                </div>
            </div>

            <!-- Toggle Saldos -->
            <div class="btn-group ml-2">
                <input type="checkbox" id="chkTodasLasVentas" name="chkTodasLasVentas" class="chkTodasLasVentas" data-width="200" data-height="35" checked data-toggle="toggle" data-on="<?= lang('invoiceVendor.allInvoicesVendors') ?? 'Todas' ?>" data-off="<?= lang('invoiceVendor.pendingPayment') ?? 'Pendientes' ?>" data-onstyle="success" data-offstyle="danger">
            </div>
        </div>

        <div class="float-right">
            <div class="btn-group">
                <button type="button" class="btn btn-info mr-1" data-toggle="modal" data-target="#modalXmlListVendor" id="btnModalXmlVendor">
                    <i class="fas fa-file-code"></i> Agregar desde XML
                </button>
                <a href="<?= base_url("admin/newVendorInvoice") ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> <?= lang('invoiceVendor.add') ?? 'Nueva Factura' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableInvoiceVendor" class="table table-striped table-hover va-middle tableInvoiceVendor" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= lang('invoiceVendor.fields.folio') ?? 'Folio' ?></th>
                                <th><?= lang('invoiceVendor.fields.custumer') ?? 'Proveedor' ?></th>
                                <th><?= lang('invoiceVendor.fields.date') ?? 'Fecha' ?></th>
                                <th><?= lang('invoiceVendor.fields.expirationDate') ?? 'Vencimiento' ?></th>
                                <th><?= lang('invoiceVendor.fields.subTotal') ?? 'SubTotal' ?></th>
                                <th><?= lang('invoiceVendor.fields.tax') ?? 'Impuestos' ?></th>
                                <th><?= lang('invoiceVendor.fields.total') ?? 'Total' ?></th>
                                <th><?= lang('invoiceVendor.fields.pending') ?? 'Saldo' ?></th>
                                <th><?= lang('invoiceVendor.fields.timeDelevery') ?? 'Tiempo Entrega' ?></th>
                                <th><?= lang('invoiceVendor.fields.created_at') ?? 'Creado' ?></th>
                                <th><?= lang('invoiceVendor.fields.updated_at') ?? 'Actualizado' ?></th>
                                <th><?= lang('invoiceVendor.fields.deleted_at') ?? 'Eliminado' ?></th>
                                <th><?= lang('invoiceVendor.fields.actions') ?? 'Acciones' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SELECCIONAR XML PARA FACTURA DE PROVEEDOR -->
<div class="modal fade" id="modalXmlListVendor" tabindex="-1" role="dialog" aria-labelledby="modalXmlListVendorLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalXmlListVendorLabel">
                    <i class="fas fa-file-invoice"></i> Seleccionar XML de Proveedor
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableXmlVendorModal" class="table table-striped table-hover va-middle" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>UUID</th>
                                <th>Serie</th>
                                <th>Folio</th>
                                <th>RFC Emisor</th>
                                <th>Nombre Emisor</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    /**
     * Cargar DataTable de Facturas de Proveedor
     */
    var tableQuotesInvoiceVendor = $('#tableInvoiceVendor').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'pageLength'],
        lengthMenu: [
            [150, 200, 500, -1],
            ['150 renglones', '200 renglones', '500 renglones', 'Todos']
        ],
        responsive: true,
        autoWidth: false,
        order: [[1, 'desc']],
        ajax: {
            url: '<?= base_url('admin/vendorInvoice') ?>',
            method: 'GET',
            dataType: "json"
        },
        columnDefs: [
            {
                orderable: false,
                searchable: false,
                targets: [13]
            }
        ],
        columns: [
            { 'data': 'id' },
            { 'data': 'folio' },
            { 'data': 'razonSocial' },
            { 'data': 'date' },
            { 'data': 'dateVen' },
            { 'data': 'subTotal' },
            { 'data': 'taxes' },
            { 'data': 'total' },
            { 'data': 'balance' },
            { 'data': 'delivaryTime' },
            { 'data': 'created_at' },
            { 'data': 'updated_at' },
            { 'data': 'deleted_at' },
            {
                "data": function (data) {
                    var buttonPayment = "";
                    if (Number(data.balance) > 0) {
                        buttonPayment = `<button class="btn btn-success btnSetPayment" balance="${data.balance}" uuid="${data.UUID}" folio="${data.folio}" data-toggle="modal" data-target="#modalPayment" title="Abonar / Pagar"><i class="fab fa-cc-visa"></i></button>`;
                    } else {
                        buttonPayment = `<button class="btn btn-secondary disabled" title="Pagada" disabled><i class="far fa-check-square"></i></button>`;
                    }

                    return `<div class="text-right py-0 align-middle">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= base_url('admin/editVendorInvoice') ?>/${data.UUID}" class="btn btn-primary btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                    <button class="btn btn-success btnSendMail" data-toggle="modal" correoCliente="${data.correoCliente}" uuid="${data.UUID}" folio="${data.folio}" data-target="#modalSendMail" title="Enviar Correo"><i class="fas fa-envelope"></i></button>
                                    <button class="btn bg-warning btnImprimirVenta" uuid="${data.UUID}" title="Imprimir"><i class="far fa-file-pdf"></i></button>
                                    <button class="btn bg-maroon btnTimbrar" uuid="${data.UUID}" title="Enlazar XML"><i class="fas fa-qrcode"></i></button>
                                    <button class="btn btn-danger btn-delete" data-id="${data.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    ${buttonPayment}
                                    <button class="btn bg-maroon btnPaymentsList" uuid="${data.UUID}" data-toggle="modal" data-target="#modalPaymentsList" title="Historial Pagos"><i class="fas fa-money-bill-wave"></i></button>
                                    <button class="btn bg-gray btnListXML" uuid="${data.UUID}" data-toggle="modal" data-target="#modalListXML" title="XML sin Asignar"><i class="fas fa-list"></i></button>
                                </div>
                            </div>`;
                }
            }
        ]
    });

    /**
     * DataTable del Modal XMLs Proveedores
     */
    var tableXmlVendorModal = null;

    $('#modalXmlListVendor').on('shown.bs.modal', function () {
        if (!$.fn.DataTable.isDataTable('#tableXmlVendorModal')) {
            tableXmlVendorModal = $('#tableXmlVendorModal').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                order: [[5, 'desc']],
                ajax: {
                    url: '<?= base_url('admin/xml') ?>',
                    method: 'GET',
                    dataType: "json"
                },
                columnDefs: [
                    {
                        orderable: false,
                        searchable: false,
                        targets: [7]
                    }
                ],
                columns: [
                    { 'data': 'uuidTimbre' },
                    { 'data': 'serie' },
                    { 'data': 'folio' },
                    { 'data': 'rfcEmisor' },
                    { 'data': 'nombreEmisor' },
                    { 'data': 'fecha' },
                    { 'data': 'total' },
                    {
                        "data": function (data) {
                            return `<div class="text-center py-0 align-middle">
                                        <a href="<?= base_url('admin/fromXMLVendorInvoice') ?>/${data.uuidTimbre}" class="btn btn-success btn-sm" title="Generar Factura de Proveedor">
                                            <i class="fas fa-file-import"></i> Generar Factura
                                        </a>
                                    </div>`;
                        }
                    }
                ]
            });
        } else {
            tableXmlVendorModal.columns.adjust().responsive.recalc();
        }
    });

    $("#idEmpresa").select2();

    $("#idSucursal").select2({
        ajax: {
            url: "<?= site_url('admin/sucursales/getSucursalAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('#idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return { results: response.data };
            },
            cache: true
        }
    });

    $("#clientes").select2({
        ajax: {
            url: "<?= site_url('admin/custumers/getCustumersTodosAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('#idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return { results: response.data };
            },
            cache: true
        }
    });

    /**
     * Filtrar al cambiar toggle de pagos
     */
    $("#chkTodasLasVentas").on("change", function () {
        aplicarFiltrosTabla();
    });

    $("#idEmpresa, #idSucursal, #clientes").on("change", function () {
        aplicarFiltrosTabla();
    });

    function aplicarFiltrosTabla() {
        var datePicker = $('#reportrange').data('daterangepicker');
        var desdeFecha = datePicker ? datePicker.startDate.format('YYYY-MM-DD') : moment().subtract(29, 'days').format('YYYY-MM-DD');
        var hastaFecha = datePicker ? datePicker.endDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
        var todas      = $("#chkTodasLasVentas").is(':checked');
        var idEmpresa  = $("#idEmpresa").val() || 0;
        var idSucursal = $("#idSucursal").val() || 0;
        var idCliente  = $("#clientes").val() || 0;

        tableQuotesInvoiceVendor.ajax.url(`<?= base_url('admin/vendorInvoice') ?>/${desdeFecha}/${hastaFecha}/${todas}/${idEmpresa}/${idSucursal}/${idCliente}`).load();
    }

    /**
     * Modal Pagos
     */
    $(".tableInvoiceVendor").on("click", '.btnPaymentsList', function () {
        var uuid = $(this).attr("uuid");
        if (typeof tableProducts !== 'undefined') {
            tableProducts.ajax.url(`<?= base_url('admin/payments/getPayments') ?>/` + uuid).load();
        }
    });

    /**
     * Modal XML sin asignar
     */
    $(".tableInvoiceVendor").on("click", '.btnListXML', function () {
        var uuidInvoiceVendor = $(this).attr("uuid");
        $("#idVentaSinAsignar").val(uuidInvoiceVendor);
        if (typeof tableListXML !== 'undefined') {
            tableListXML.ajax.url(`<?= base_url('admin/xml/xmlSinAsignar/I') ?>`).load();
        }
    });

    /**
     * Imprimir Reporte de Factura
     */
    $(".tableInvoiceVendor").on("click", '.btnImprimirVenta', function () {
        var uuid = $(this).attr("uuid");
        window.open(`<?= base_url('admin/vendorInvoice/report') ?>/${uuid}`, "_blank");
    });

    /**
     * Eliminar Factura
     */
    $(".tableInvoiceVendor").on("click", ".btn-delete", function () {
        var idInvoiceVendor = $(this).attr("data-id");

        Swal.fire({
            title: '<?= lang('boilerplate.global.sweet.title') ?>',
            text: "<?= lang('boilerplate.global.sweet.text') ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= lang('boilerplate.global.sweet.confirm_delete') ?>'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: `<?= base_url('admin/vendorInvoice') ?>/${idInvoiceVendor}`,
                    method: 'DELETE',
                }).done((data, textStatus, jqXHR) => {
                    Toast.fire({
                        icon: 'success',
                        title: "Eliminado Correctamente"
                    });
                    tableQuotesInvoiceVendor.ajax.reload();
                }).fail((error) => {
                    Toast.fire({
                        icon: 'error',
                        title: error.responseJSON?.messages?.error || "Error al eliminar"
                    });
                });
            }
        });
    });

    /**
     * Asignar Pago
     */
    $(".tableInvoiceVendor").on("click", '.btnSetPayment', function () {
        var uuid    = $(this).attr("uuid");
        var balance = $(this).attr("balance");

        $("#uuidInvoiceVendorPayment").val(uuid);
        $("#pago").val("0.00");
        $("#granTotal").val(balance);
    });

    /**
     * Enviar Correo
     */
    $(".tableInvoiceVendor").on("click", '.btnSendMail', function () {
        var uuid   = $(this).attr("uuid");
        var folio  = $(this).attr("folio");
        var correo = $(this).attr("correocliente") || '';

        $('#correos').empty();
        if (correo) {
            var arr = correo.split(',');
            $.each(arr, function (index, value) {
                var newOption = new Option(value.trim(), value.trim(), true, true);
                $('#correos').append(newOption).trigger('change');
            });
        }

        $("#uuidMail").val(uuid);
        $("#folioVentaMail").val(folio);
    });

    /**
     * DateRangePicker Init
     */
    $(function () {
        var start = moment().subtract(29, 'days');
        var end   = moment();

        function cb(start, end) {
            $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            aplicarFiltrosTabla();
        }

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Hoy': [moment(), moment()],
                'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Últimos 7 Días': [moment().subtract(6, 'days'), moment()],
                'Últimos 30 Días': [moment().subtract(29, 'days'), moment()],
                'Este Mes': [moment().startOf('month'), moment().endOf('month')],
                'Último Mes': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Todo': [moment().subtract(100, 'year').startOf('month'), moment().add(100, 'year').endOf('year')]
            }
        }, cb);

        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    });

    $(document).ready(function () {
        <?php if (isset($cliente)): ?>
            tableQuotesInvoiceVendor.ajax.url('<?= base_url('admin/vendorInvoice') ?>/<?= "$desdeFecha/$hastaFecha/$todas/$empresa/$sucursal/$cliente" ?>').load();
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>