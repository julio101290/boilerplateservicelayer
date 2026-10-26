<!-- Modal More Info -->
<div class="modal fade" id="modelMoreInfoRow" tabindex="-1" role="dialog" aria-labelledby="modelMoreInfoRow" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Información del Renglon</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sat-tab" data-toggle="tab" data-target="#datosSAT" type="button" role="tab" aria-controls="home" aria-selected="true">Datos SAT</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="impuestos-tab" data-toggle="tab" data-target="#impuestos" type="button" role="tab" aria-controls="impuestos" aria-selected="false">Impuestos</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sap-tab" data-toggle="tab" data-target="#datosSAP" type="button" role="tab" aria-controls="datosSAP" aria-selected="false">Datos SAP</button>
                    </li>
                </ul>

                <form id="form-otrosDatosRenglon" class="form-horizontal">
                    <div class="tab-content" id="myTabContent">

                        <!-- TAB PARA LOS DATOS DEL SAT -->
                        <div class="tab-pane fade show active" id="datosSAT" role="tabpanel" aria-labelledby="datosSAT">
                            <p>
                            <h3>Datos Generales</h3>
                            <div class="form-group row">
                                <label for="unidadSATRow" class="col-sm-2 col-form-label">
                                    Clave Unidad SAT
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                        </div>
                                        <select name="unidadSATRow" id="unidadSATRow" style="width: 90%;" class="form-control unidadSAT unidadSATRow form-controlProducts">
                                            <option value="0" selected>
                                                Seleccione Clave De Unidad SAT
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="claveProductoSATRow" class="col-sm-2 col-form-label">
                                    Clave Producto SAT
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                        </div>
                                        <select name="claveProductoSATRow" id="claveProductoSATRow" style="width: 90%;" class="form-control claveProductoSAT claveProductoSATRow form-controlProducts">
                                            <option value="0" selected>
                                                Seleccione Clave de Producto SAT
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="unidadProducto" class="col-sm-2 col-form-label">
                                    Unidad Producto
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                        </div>
                                        <input name="unidadProducto" id="unidadProducto" class="form-control unidadProducto form-controlProducts">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="predialRenglon" class="col-sm-2 col-form-label">
                                    Predial
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                        </div>
                                        <input name="predialRenglon" id="predialRenglon" class="form-control predialRenglon form-controlProducts">
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>

                        <!-- TAB PARA LOS IMPUESTOS -->
                        <div class="tab-pane fade" id="impuestos" role="tabpanel" aria-labelledby="impuestos">
                            <p>
                            <h3>Impuestos</h3>

                            <div class="form-group row">
                                <label for="tasaExcentaRenglon" class="col-sm-2 col-form-label">
                                    Tasa Exenta
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                        </div>
                                        <input type="checkbox" id="tasaExcentaRenglon" name="tasaExcentaRenglon" class="tasaExcentaRenglon" data-width="250" data-height="40" checked data-toggle="toggle" data-on="Exenta" data-off="No exenta" data-onstyle="success" data-offstyle="danger">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="porcentTaxRenglon" class="col-sm-2 col-form-label">
                                    <?= lang('products.fields.porcentTax') ?>
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                        </div>
                                        <input type="number" name="porcentTaxRenglon" id="porcentTaxRenglon" class="form-control form-controlProducts <?= session('error.porcentTax') ? 'is-invalid' : '' ?>" value="" placeholder="<?= lang('products.fields.porcentTax') ?>" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="porcentIVARetenidoRenglon" class="col-sm-2 col-form-label">
                                    Iva Retenido
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                        </div>
                                        <input type="number" name="porcentIVARetenidoRenglon" id="porcentIVARetenidoRenglon" class="form-control form-controlProducts <?= session('error.porcentIVARetenido') ? 'is-invalid' : '' ?>" value="" placeholder="Iva Retenido" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="porcentISRRetenidoRenglon" class="col-sm-2 col-form-label">
                                    ISR Retenido
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                        </div>
                                        <input type="number" name="porcentISRRetenidoRenglon" id="porcentISRRetenidoRenglon" class="form-control form-controlProducts <?= session('error.porcentISRRetenido') ? 'is-invalid' : '' ?>" value="" placeholder="ISR Retenido" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>

                        <!-- TAB PARA LOS DATOS DE SAP -->
                        <div class="tab-pane fade" id="datosSAP" role="tabpanel" aria-labelledby="sap-tab">
                            <p>
                            <h3>Datos SAP</h3>

                            <div class="form-group row">
                                <label for="itemCodeRenglon" class="col-sm-2 col-form-label">
                                    Item Code
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                        </div>
                                        <select name="itemCodeRenglon" id="itemCodeRenglon" style="width: 90%;" class="form-control itemCodeRenglon form-controlProducts">
                                            <option value="0" selected>
                                                Seleccione Código de Artículo SAP
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="prjCodeRenglon" class="col-sm-2 col-form-label">
                                    Proyecto (PrjCode)
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-project-diagram"></i></span>
                                        </div>
                                        <select name="prjCodeRenglon" id="prjCodeRenglon" style="width: 90%;" class="form-control prjCodeRenglon form-controlProducts">
                                            <option value="0" selected>
                                                Seleccione Proyecto SAP (OPRJ)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="ocrCodeRenglon" class="col-sm-2 col-form-label">
                                    OcrCode (Activos Fijos)
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        </div>
                                        <select name="ocrCodeRenglon" id="ocrCodeRenglon" style="width: 90%;" class="form-control ocrCodeRenglon form-controlProducts">
                                            <option value="0" selected>
                                                Seleccione Dimensión - Activos Fijos
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="ocrCode2Renglon" class="col-sm-2 col-form-label">
                                    OcrCode2 (Departamento)
                                </label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-sitemap"></i></span>
                                        </div>
                                        <select name="ocrCode2Renglon" id="ocrCode2Renglon" style="width: 90%;" class="form-control ocrCode2Renglon form-controlProducts">
                                            <option value="0" selected>
                                                Seleccione Dimensión - Departamento
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            </p>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm btnGuardarRenglon" id="btnGuardarRenglon" data-dismiss="modal">Guardar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->section('js') ?>
<script>
    /* 
     * Al hacer click al editar
     */
    $(document).on('click', '.btnGuardarRenglon', function (e) {

        nombreDiv.parent().parent().find(".claveUnidadSatR").val($("#unidadSATRow").val());
        nombreDiv.parent().parent().find(".claveProductoSATR").val($("#claveProductoSATRow").val());
        nombreDiv.parent().parent().find(".unidadProducto").val($("#unidadProducto").val());

        /**
         * Impuestos
         */
        nombreDiv.parent().parent().find(".porcentTax").val($("#porcentTaxRenglon").val());
        nombreDiv.parent().parent().find(".porcentIVARetenido").val($("#porcentIVARetenidoRenglon").val());
        nombreDiv.parent().parent().find(".porcentISRRetenido").val($("#porcentISRRetenidoRenglon").val());

        nombreDiv.parent().parent().find(".predialR").val($("#predialRenglon").val());

        /**
         * SAP Fields
         */
        nombreDiv.parent().parent().find(".itemCode").val($("#itemCodeRenglon").val());
        nombreDiv.parent().parent().find(".prjCode").val($("#prjCodeRenglon").val());
        nombreDiv.parent().parent().find(".ocrCode").val($("#ocrCodeRenglon").val());
        nombreDiv.parent().parent().find(".ocrCode2").val($("#ocrCode2Renglon").val());

        /*
         * Agregamos si lleva tasa Exenta
         */
        if ($("#tasaExcentaRenglon").is(':checked')) {
            var tasaExentaRenglon = "on";
        } else {
            var tasaExentaRenglon = "off";
        }

        nombreDiv.parent().parent().find(".tasaExcenta").val(tasaExentaRenglon);

        nombreDiv.parent().parent().find(".cant").trigger("change");

        listProducts();
    });

    /**
     * Categorias por empresa (Unidad SAT)
     */
    $(".unidadSATRow").select2({
        dropdownParent: $('#modelMoreInfoRow'),
        ajax: {
            url: "<?= base_url('admin/products/getUnidadSATAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('.idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });

    /**
     * Categorias por empresa (Producto SAT)
     */
    $(".claveProductoSATRow").select2({
        dropdownParent: $('#modelMoreInfoRow'),
        ajax: {
            url: "<?= base_url('admin/products/getProductosSATAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('.idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });

    /**
     * SAP 1: ItemCode del catálogo de productos
     */
    $(".itemCodeRenglon").select2({
        dropdownParent: $('#modelMoreInfoRow'),
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getItemCodeAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('.idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });

    /**
     * SAP 2: Proyecto en tabla OPRJ (PrjCode)
     */
    $(".prjCodeRenglon").select2({
        dropdownParent: $('#modelMoreInfoRow'),
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getPrjCodeAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('.idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });

    /**
     * SAP 3: OcrCode en tabla de Activos Fijos
     */
    $(".ocrCodeRenglon").select2({
        dropdownParent: $('#modelMoreInfoRow'),
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getOcrCodeAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('.idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });

    /**
     * SAP 4: OcrCode2 (Departamento) en tabla OPCR donde DymCode = 2
     */
    $(".ocrCode2Renglon").select2({
        dropdownParent: $('#modelMoreInfoRow'),
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getOcrCode2Ajax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();
                var idEmpresa = $('.idEmpresa').val();

                return {
                    searchTerm: params.term,
                    [csrfName]: csrfHash,
                    idEmpresa: idEmpresa
                };
            },
            processResults: function (response) {
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });
    
    
    
    
    $(document).ready(function () {

    // Inicializar Select2 Global para Item Code
    $("#globalItemCode").select2({
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getItemCodeAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    searchTerm: params.term,
                    [ $('.txt_csrfname').attr('name') ]: $('.txt_csrfname').val(),
                    idEmpresa: $('.idEmpresaSells').val() || $('.idEmpresa').val()
                };
            },
            processResults: function (response) {
                if(response.token) $('.txt_csrfname').val(response.token);
                return { results: response.data };
            },
            cache: true
        }
    });

    // Inicializar Select2 Global para Proyecto (OPRJ)
    $("#globalPrjCode").select2({
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getPrjCodeAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    searchTerm: params.term,
                    [ $('.txt_csrfname').attr('name') ]: $('.txt_csrfname').val(),
                    idEmpresa: $('.idEmpresaSells').val() || $('.idEmpresa').val()
                };
            },
            processResults: function (response) {
                if(response.token) $('.txt_csrfname').val(response.token);
                return { results: response.data };
            },
            cache: true
        }
    });

    // Inicializar Select2 Global para Activo Fijo (OcrCode)
    $("#globalOcrCode").select2({
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getOcrCodeAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    searchTerm: params.term,
                    [ $('.txt_csrfname').attr('name') ]: $('.txt_csrfname').val(),
                    idEmpresa: $('.idEmpresaSells').val() || $('.idEmpresa').val()
                };
            },
            processResults: function (response) {
                if(response.token) $('.txt_csrfname').val(response.token);
                return { results: response.data };
            },
            cache: true
        }
    });

    // Inicializar Select2 Global para Departamento (OcrCode2)
    $("#globalOcrCode2").select2({
        ajax: {
            url: "<?= base_url('admin/vendorInvoice/getOcrCode2Ajax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    searchTerm: params.term,
                    [ $('.txt_csrfname').attr('name') ]: $('.txt_csrfname').val(),
                    idEmpresa: $('.idEmpresaSells').val() || $('.idEmpresa').val()
                };
            },
            processResults: function (response) {
                if(response.token) $('.txt_csrfname').val(response.token);
                return { results: response.data };
            },
            cache: true
        }
    });

    // Botón: Aplicar Item Code Global a todos los renglones
    $('#btnApplyGlobalItemCode').on('click', function () {
        var val = $('#globalItemCode').val();
        if (!val || val === '0') return;
        $('.nuevoProduct').each(function () {
            $(this).find('.itemCode').val(val);
        });
        Toast.fire({ icon: 'success', title: 'Item Code aplicado a todos los renglones' });
    });

    // Botón: Aplicar Proyecto Global a todos los renglones
    $('#btnApplyGlobalPrjCode').on('click', function () {
        var val = $('#globalPrjCode').val();
        if (!val || val === '0') return;
        $('.nuevoProduct').each(function () {
            $(this).find('.prjCode').val(val);
        });
        Toast.fire({ icon: 'success', title: 'Proyecto aplicado a todos los renglones' });
    });

    // Botón: Aplicar Activo Fijo Global a todos los renglones
    $('#btnApplyGlobalOcrCode').on('click', function () {
        var val = $('#globalOcrCode').val();
        if (!val || val === '0') return;
        $('.nuevoProduct').each(function () {
            $(this).find('.ocrCode').val(val);
        });
        Toast.fire({ icon: 'success', title: 'Activo Fijo aplicado a todos los renglones' });
    });

    // Botón: Aplicar Departamento Global a todos los renglones
    $('#btnApplyGlobalOcrCode2').on('click', function () {
        var val = $('#globalOcrCode2').val();
        if (!val || val === '0') return;
        $('.nuevoProduct').each(function () {
            $(this).find('.ocrCode2').val(val);
        });
        Toast.fire({ icon: 'success', title: 'Departamento aplicado a todos los renglones' });
    });

});
    
</script>
<?= $this->endSection() ?>