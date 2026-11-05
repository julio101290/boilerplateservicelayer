<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<style>
    :root {
        --corp-red: #a82020;        /* Rojo vino / corporativo elegante */
        --corp-red-dark: #821414;   /* Tono más oscuro para hovers */
    }
    .bg-corp-red {
        background-color: var(--corp-red) !important;
        color: #ffffff !important;
    }
    .btn-corp-red {
        background-color: var(--corp-red) !important;
        border-color: var(--corp-red-dark) !important;
        color: #ffffff !important;
    }
    .btn-corp-red:hover {
        background-color: var(--corp-red-dark) !important;
        color: #ffffff !important;
    }
    .card-corp-red.card-outline {
        border-top: 3px solid var(--corp-red) !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--corp-red) !important;
    }
    @media (max-width: 575.98px) {
        .renglon-item {
            background-color: #fdf2f2;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px !important;
        }
    }
</style>

<input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<div class="row">
    <!-- COLUMNA IZQUIERDA: FORMULARIO DE LA COTIZACIÓN -->
    <div class="col-lg-7 col-12 mb-3">
        <div class="card card-corp-red card-outline shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
                <h3 class="card-title font-weight-bold text-danger mb-1 mb-sm-0" style="color: var(--corp-red) !important;">
                    <i class="fas fa-file-signature mr-1"></i> <?= esc($title) ?>
                </h3>
                <div class="card-tools ml-auto">
                    <?php if ((int)$idQuote > 0) : ?>
                        <a href="<?= base_url('admin/quotesSAP/report/' . $uuid) ?>" target="_blank" class="btn btn-corp-red btn-xs mr-1">
                            <i class="fas fa-print"></i> Imprimir PDF
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('admin/quotesSAP') ?>" class="btn btn-secondary btn-xs">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
            </div>

            <form id="formQuoteSAP" class="formularioVenta">
                <div class="card-body p-2 p-sm-3">
                    <input type="hidden" id="idQuote" name="idQuote" value="<?= esc($idQuote) ?>">
                    <input type="hidden" id="UUID" name="UUID" value="<?= esc($uuid) ?>">
                    <input type="hidden" id="idVendedor" name="idVendedor" value="<?= esc($idVendedor) ?>">
                    <input type="hidden" id="whsCode" name="whsCode" value="<?= esc($whsCode) ?>">
                    <input type="hidden" id="listaProductos" name="listaProductos" value='<?= json_encode($productos, JSON_UNESCAPED_UNICODE) ?>'>

                    <div class="form-row">
                        <div class="form-group col-md-4 col-12">
                            <label class="mb-1 font-weight-bold" style="color: var(--corp-red);"><i class="fas fa-building mr-1"></i> Empresa</label>
                            <select class="form-control select2" id="idEmpresa" name="idEmpresa" style="width:100%;">
                                <?php foreach ($empresas as $emp) : ?>
                                    <option value="<?= $emp['id'] ?>" <?= (int)$emp['id'] === (int)$idEmpresa ? 'selected' : '' ?>>
                                        <?= $emp['id'] ?> - <?= esc($emp['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group col-md-4 col-sm-6 col-12">
                            <label class="mb-1 font-weight-bold" style="color: var(--corp-red);"><i class="fas fa-store mr-1"></i> Sucursal</label>
                            <select class="form-control select2" id="idSucursal" name="idSucursal" required style="width:100%;">
                                <option value="0">Cargando sucursales...</option>
                            </select>
                        </div>

                        <div class="form-group col-md-4 col-sm-6 col-12">
                            <label class="mb-1 font-weight-bold" style="color: var(--corp-red);"><i class="fas fa-tags mr-1"></i> Lista de Precios</label>
                            <select class="form-control select2" id="idPriceList" name="idPriceList" required style="width:100%;">
                                <option value="0">Cargando listas de precios...</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5 col-sm-8 col-12">
                            <label class="mb-1"><i class="fas fa-user-tie mr-1" style="color: var(--corp-red);"></i> Vendedor</label>
                            <input type="text" class="form-control form-control-sm" id="nuevoVendedor" value="<?= esc($nombreVendedor) ?>" readonly>
                        </div>
                        <div class="form-group col-md-2 col-sm-4 col-4">
                            <label class="mb-1"><i class="fas fa-hashtag mr-1" style="color: var(--corp-red);"></i> Folio</label>
                            <input type="text" class="form-control form-control-sm font-weight-bold text-center" id="nuevaVenta" name="nuevaVenta" value="<?= esc($codigo) ?>" readonly style="color: var(--corp-red);">
                        </div>
                        <div class="form-group col-md-2 col-sm-6 col-4">
                            <label class="mb-1">Fecha</label>
                            <input type="date" class="form-control form-control-sm" id="fecha" name="fecha" value="<?= esc($fecha) ?>" required>
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-4">
                            <label class="mb-1">Vencimiento</label>
                            <input type="date" class="form-control form-control-sm" id="fechaVencimiento" name="fechaVencimiento" value="<?= esc($fechaVencimiento) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="mb-1 font-weight-bold" style="color: var(--corp-red);"><i class="fas fa-users mr-1"></i> Cliente SAP (`OCRD`)</label>
                        <select class="form-control select2" id="cardCode" name="cardCode" required style="width:100%;">
                            <?php if (!empty($cardCode)) : ?>
                                <option value="<?= esc($cardCode) ?>" selected><?= esc($cardCode) ?> - <?= esc($cardName) ?></option>
                            <?php else : ?>
                                <option value="">Seleccione Cliente SAP...</option>
                            <?php endif; ?>
                        </select>
                        <input type="hidden" id="cardName" name="cardName" value="<?= esc($cardName) ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5 col-sm-6 col-12">
                            <label class="mb-1">Atención / Cotizar a:</label>
                            <input type="text" class="form-control form-control-sm" id="cotizarA" name="cotizarA" value="<?= esc($cotizarA) ?>" placeholder="Nombre de contacto u obra">
                        </div>
                        <div class="form-group col-md-3 col-sm-6 col-6">
                            <label class="mb-1">Teléfono:</label>
                            <input type="text" class="form-control form-control-sm" id="telefono" name="telefono" value="<?= esc($telefono) ?>" required>
                        </div>
                        <div class="form-group col-md-2 col-sm-6 col-6">
                            <label class="mb-1">C.P.:</label>
                            <input type="text" class="form-control form-control-sm" id="codigoPostal" name="codigoPostal" value="<?= esc($codigoPostal) ?>" required>
                        </div>
                        <div class="form-group col-md-2 col-sm-6 col-12">
                            <label class="mb-1">Entrega:</label>
                            <input type="text" class="form-control form-control-sm" id="plazoEntrega" name="plazoEntrega" value="<?= esc($plazoEntrega) ?>" placeholder="Ej. 3 días">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4 col-sm-4 col-12">
                            <label class="mb-1">Método de Pago</label>
                            <select class="form-control form-control-sm select2" id="metodoPago" name="metodoPago" style="width:100%;">
                                <?php foreach ($metodosPago as $mp) : ?>
                                    <option value="<?= $mp['id'] ?>" <?= $mp['id'] === $metodoPago ? 'selected' : '' ?>>
                                        <?= $mp['id'] ?> - <?= $mp['texto'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4 col-sm-4 col-12">
                            <label class="mb-1">Forma de Pago</label>
                            <select class="form-control form-control-sm select2" id="formaPago" name="formaPago" style="width:100%;">
                                <?php foreach ($formasPago as $fp) : ?>
                                    <option value="<?= $fp['id'] ?>" <?= $fp['id'] === $formaPago ? 'selected' : '' ?>>
                                        <?= $fp['id'] ?> - <?= $fp['texto'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4 col-sm-4 col-12">
                            <label class="mb-1">Uso CFDI</label>
                            <select class="form-control form-control-sm select2" id="usoCFDI" name="usoCFDI" style="width:100%;">
                                <?php foreach ($usosCFDI as $uc) : ?>
                                    <option value="<?= $uc['id'] ?>" <?= $uc['id'] === $usoCFDI ? 'selected' : '' ?>>
                                        <?= $uc['id'] ?> - <?= $uc['texto'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Observaciones</label>
                        <input type="text" class="form-control form-control-sm" id="observaciones" name="observaciones" value="<?= esc($observaciones) ?>" placeholder="Observaciones o notas adicionales...">
                    </div>

                    <div class="d-lg-none mb-2">
                        <button type="button" class="btn btn-corp-red btn-sm btn-block" id="btnScrollCatalogo">
                            <i class="fas fa-search-plus mr-1"></i> Ir al Catálogo de Artículos SAP
                        </button>
                    </div>

                    <div class="d-none d-sm-block">
                        <table class="table table-sm table-bordered mb-1">
                            <thead class="bg-corp-red text-center">
                                <tr>
                                    <th style="width: 8%;">#</th>
                                    <th style="width: 42%;">Descripción</th>
                                    <th style="width: 16%;">Cantidad</th>
                                    <th style="width: 17%;">Precio Unit.</th>
                                    <th style="width: 17%;">Total</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <div class="nuevoProducto mb-3"></div>

                    <hr>

                    <div class="row justify-content-end">
                        <div class="col-md-6 col-sm-8 col-12">
                            <div class="input-group input-group-sm mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold" style="width: 110px;">IVA %:</span>
                                </div>
                                <input type="number" class="form-control text-right font-weight-bold" id="nuevoImpuestoVenta" name="porcIva" value="<?= esc($porcIva) ?>" min="0" step="any">
                                <input type="hidden" id="nuevoPrecioImpuesto" name="impuesto" value="<?= esc($impuesto) ?>">
                                <input type="hidden" id="nuevoPrecioNeto" name="neto" value="<?= esc($neto) ?>">
                            </div>

                            <div class="input-group input-group-sm mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold" style="width: 110px;">Sub Total $:</span>
                                </div>
                                <input type="text" class="form-control text-right font-weight-bold" id="nuevoSubTotalVenta" value="<?= esc($neto) ?>" readonly>
                            </div>

                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-corp-red font-weight-bold" style="width: 110px;">Total $:</span>
                                </div>
                                <input type="text" class="form-control text-right font-weight-bold" id="nuevoTotalVenta" name="total" value="<?= esc($total) ?>" readonly style="color: var(--corp-red);">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <?php if ((int)$sapSynced === 0) : ?>
                        <button type="submit" class="btn btn-corp-red btn-block d-sm-inline-block w-auto" id="btnGuardarQuote">
                            <i class="fas fa-save mr-1"></i> Guardar Cotización
                        </button>
                    <?php else : ?>
                        <span class="badge badge-secondary p-2"><i class="fas fa-lock"></i> Cotización ya sincronizada en SAP</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================================
         COLUMNA DERECHA: CATÁLOGO DE PRODUCTOS SAP (EXCLUYENDO PRECIO 0)
         ===================================================================== -->
    <div class="col-lg-5 col-12" id="cardCatalogoSAP">
        <div class="card card-corp-red card-outline shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title font-weight-bold" style="color: var(--corp-red);">
                    <i class="fas fa-boxes mr-1"></i> Catálogo de Artículos SAP
                </h3>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="tableProductsSAP" class="table table-bordered table-striped table-hover table-sm w-100">
                        <thead class="bg-corp-red text-center">
                            <tr>
                                <th>Clave</th>
                                <th>Descripción</th>
                                <th class="text-right">Precio</th>
                                <th>+</th>
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
    var tableProductsSAP = null;
    var savedSucursal    = "<?= esc($idSucursal) ?>";
    var savedWhsCode     = "<?= esc($whsCode) ?>";
    var savedPriceList   = "<?= esc($idPriceList) ?>";
    var initialProducts  = <?= json_encode($productos ?: [], JSON_UNESCAPED_UNICODE) ?>;
    var isInitializing   = true;

    $(document).ready(function () {
        $('.select2').select2();

        $('#btnScrollCatalogo').on('click', function () {
            $('html, body').animate({
                scrollTop: $('#cardCatalogoSAP').offset().top - 60
            }, 400);
        });

        $('#cardCode').select2({
            ajax: {
                url: "<?= site_url('admin/quotesSAP/getClientsSAPAjax') ?>",
                type: "POST",
                dataType: 'json',
                delay: 350,
                data: function (params) {
                    return {
                        searchTerm: params.term || '',
                        idEmpresa: $('#idEmpresa').val(),
                        [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
                    };
                },
                processResults: function (response) {
                    if (response.token) $('.txt_csrfname').val(response.token);
                    return { results: response.results || [] };
                },
                cache: true
            }
        });

        $('#cardCode').on('change', function () {
            var code = $(this).val();
            if (!code) return;

            $.post("<?= site_url('admin/quotesSAP/getClientDataSAPAjax') ?>", {
                idEmpresa: $('#idEmpresa').val(),
                cardCode: code,
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            }, function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                if (res.cardName) $('#cardName').val(res.cardName);
                if (res.telefono && !$('#telefono').val()) $('#telefono').val(res.telefono);
                if (res.codigoPostal && !$('#codigoPostal').val()) $('#codigoPostal').val(res.codigoPostal);
            }, 'json');
        });

        if (initialProducts.length > 0) {
            initialProducts.forEach(function (item) {
                agregarRenglonDOM(item.clave, item.descripcion, item.unidad || 'PZA', item.cantidad, item.precio);
            });
            recalcularTotales();
        }

        cargarContextoUsuario(true);

        $('#idEmpresa').on('change', function () {
            savedSucursal  = "0";
            savedWhsCode   = "";
            savedPriceList = "0";
            cargarContextoUsuario(false);
        });

        $('#idSucursal').on('change', function () {
            if (isInitializing) return;
            var porcIva = $(this).find('option:selected').attr('data-iva');
            if (porcIva !== undefined) {
                $('#nuevoImpuestoVenta').val(porcIva);
                recalcularTotales();
            }
            cargarContextoUsuario(false);
        });

        $('#idPriceList').on('change', function () {
            if (isInitializing) return;
            cargarTablaProductosSAP();
        });

        $(document).on('change keyup', '.nuevaCantidadProducto, .nuevoPrecioUnitarioProducto, #nuevoImpuestoVenta', function () {
            recalcularTotales();
        });

        $(document).on('click', '.quitarProducto', function () {
            $(this).closest('.renglon-item').remove();
            recalcularTotales();
        });

        $(document).on('click', '.btnAddProductSAP', function () {
            var clave       = $(this).attr('data-code');
            var descripcion = $(this).attr('data-name');
            var unidad      = $(this).attr('data-unit');
            var precio      = parseFloat($(this).attr('data-price')) || 0;

            agregarRenglonDOM(clave, descripcion, unidad, 1, precio);
            recalcularTotales();

            if (window.innerWidth < 992) {
                Toast.fire({ icon: 'success', title: 'Artículo agregado: ' + clave });
            }
        });

        $('#formQuoteSAP').on('submit', function (e) {
            e.preventDefault();
            recalcularTotales();

            var lista = $('#listaProductos').val();
            if (!lista || lista === '[]') {
                Swal.fire('Atención', 'Debe agregar al menos un producto a la cotización.', 'warning');
                return;
            }

            $('#btnGuardarQuote').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            var formData = new FormData(this);
            formData.append($('.txt_csrfname').attr('name'), $('.txt_csrfname').val());

            $.ajax({
                url: "<?= base_url('admin/quotesSAP/save') ?>",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (res) {
                    $('#btnGuardarQuote').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Guardar Cotización');
                    if (res.token) $('.txt_csrfname').val(res.token);

                    if (!res.error) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: res.message
                        }).then(() => {
                            window.open("<?= base_url('admin/quotesSAP/report') ?>/" + res.uuid, "_blank");
                            window.location.href = "<?= base_url('admin/quotesSAP') ?>";
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function (jqXHR) {
                    $('#btnGuardarQuote').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Guardar Cotización');
                    Swal.fire('Error', jqXHR.responseText || 'Error de servidor', 'error');
                }
            });
        });
    });

    function cargarContextoUsuario(primeraCarga) {
        isInitializing = true;
        var idEmpresa  = $('#idEmpresa').val();
        var idSucursal = primeraCarga ? savedSucursal : $('#idSucursal').val();

        $.ajax({
            url: "<?= site_url('admin/quotesSAP/getUserContextAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: idEmpresa,
                idSucursal: idSucursal,
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);

                if (res.error) {
                    console.error("Error getUserContextAjax:", res.message);
                    Toast.fire({ icon: 'error', title: res.message || 'Error al cargar contexto del usuario' });
                }

                var $suc = $('#idSucursal').empty();
                if (!res.sucursales || res.sucursales.length === 0) {
                    $suc.append('<option value="0">Sin sucursales asignadas al usuario</option>');
                } else {
                    res.sucursales.forEach(function (s, idx) {
                        var isSelected = (String(s.idSucursal) === String(idSucursal)) || (idSucursal == "0" && idx === 0);
                        $suc.append(new Option(s.text, s.idSucursal, isSelected, isSelected));
                        $suc.find(`option[value="${s.idSucursal}"]`).attr('data-iva', s.porcIva);
                    });
                }
                $suc.trigger('change.select2');

                if (res.almacenes && res.almacenes.length > 0) {
                    $('#whsCode').val(primeraCarga && savedWhsCode ? savedWhsCode : res.almacenes[0].id);
                }

                var $lp = $('#idPriceList').empty();
                if (!res.listasPrecios || res.listasPrecios.length === 0) {
                    $lp.append('<option value="0">Sin listas de precios asignadas</option>');
                } else {
                    res.listasPrecios.forEach(function (l, idx) {
                        var isSelected = (primeraCarga && String(l.id) === String(savedPriceList)) || (!savedPriceList || savedPriceList == "0") && idx === 0;
                        $lp.append(new Option(l.text, l.id, isSelected, isSelected));
                    });
                }
                $lp.trigger('change.select2');

                if (parseInt($('#idQuote').val()) === 0 && res.nextFolio) {
                    $('#nuevaVenta').val(res.nextFolio);
                }

                if (parseInt($('#idQuote').val()) === 0) {
                    var ivaSuc = $('#idSucursal option:selected').attr('data-iva');
                    if (ivaSuc !== undefined) $('#nuevoImpuestoVenta').val(ivaSuc);
                }

                isInitializing = false;
                cargarTablaProductosSAP();
            },
            error: function (jqXHR) {
                isInitializing = false;
                console.error("Fallo AJAX getUserContextAjax:", jqXHR.responseText);
            }
        });
    }

    function cargarTablaProductosSAP() {
        if (tableProductsSAP) {
            tableProductsSAP.destroy();
        }

        tableProductsSAP = $('#tableProductsSAP').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 8,
            lengthMenu: [5, 8, 15, 30, 50],
            ajax: {
                url: "<?= site_url('admin/quotesSAP/getProductsSAPAjax') ?>",
                type: "POST",
                data: function (d) {
                    d.idEmpresa   = $('#idEmpresa').val();
                    d.idPriceList = $('#idPriceList').val();
                    d.whsCode     = $('#whsCode').val();
                    d[$('.txt_csrfname').attr('name')] = $('.txt_csrfname').val();
                },
                dataSrc: function (json) {
                    if (json.token) $('.txt_csrfname').val(json.token);
                    return json.data || [];
                }
            },
            columns: [
                { data: 'ItemCode', className: 'font-weight-bold text-danger' },
                { data: 'ItemName' },
                {
                    data: 'Price',
                    className: 'text-right font-weight-bold',
                    render: function (data) {
                        return '$' + parseFloat(data || 0).toFixed(2);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `<button type="button" class="btn btn-corp-red btn-xs btnAddProductSAP"
                                    data-code="${row.ItemCode}"
                                    data-name="${row.ItemName.replace(/"/g, '&quot;')}"
                                    data-unit="${row.Unidad}"
                                    data-price="${row.Price}">
                                    <i class="fas fa-plus"></i>
                                </button>`;
                    }
                }
            ]
        });
    }

    function agregarRenglonDOM(clave, descripcion, unidad, cantidad, precio) {
        var total = (parseFloat(cantidad) * parseFloat(precio)).toFixed(2);
        var html = `
            <div class="form-row align-items-center mb-2 renglon-item">
                <div class="col-2 col-sm-1 text-center mb-1 mb-sm-0">
                    <button type="button" class="btn btn-danger btn-xs quitarProducto" title="Quitar partida">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="col-10 col-sm-5 mb-1 mb-sm-0">
                    <small class="d-sm-none font-weight-bold" style="color: var(--corp-red);">${clave}</small>
                    <input type="text" class="form-control form-control-sm nuevaDescripcionProducto" value="${descripcion}" required>
                    <input type="hidden" class="clave" value="${clave}">
                    <input type="hidden" class="unidad" value="${unidad}">
                </div>
                <div class="col-4 col-sm-2">
                    <small class="d-sm-none text-muted">Cant.</small>
                    <input type="number" step="any" min="0.01" class="form-control form-control-sm text-right nuevaCantidadProducto" value="${cantidad}" required>
                </div>
                <div class="col-4 col-sm-2">
                    <small class="d-sm-none text-muted">Precio</small>
                    <input type="number" step="any" min="0" class="form-control form-control-sm text-right nuevoPrecioUnitarioProducto" value="${precio}" required>
                </div>
                <div class="col-4 col-sm-2">
                    <small class="d-sm-none text-muted">Total</small>
                    <input type="text" class="form-control form-control-sm text-right font-weight-bold nuevoPrecioProducto" value="${total}" readonly>
                </div>
            </div>
        `;
        $('.nuevoProducto').append(html);
    }

    function recalcularTotales() {
        var subtotal = 0;
        var lista    = [];

        $('.renglon-item').each(function (idx) {
            var clave       = $(this).find('.clave').val();
            var descripcion = $(this).find('.nuevaDescripcionProducto').val();
            var unidad      = $(this).find('.unidad').val();
            var cantidad    = parseFloat($(this).find('.nuevaCantidadProducto').val()) || 0;
            var precio      = parseFloat($(this).find('.nuevoPrecioUnitarioProducto').val()) || 0;
            var importe     = cantidad * precio;

            $(this).find('.nuevoPrecioProducto').val(importe.toFixed(2));
            subtotal += importe;

            lista.push({
                renglon: idx + 1,
                id: clave,
                clave: clave,
                descripcion: descripcion,
                unidad: unidad,
                cantidad: cantidad,
                precio: precio.toFixed(2),
                total: importe.toFixed(2)
            });
        });

        var porcIva  = parseFloat($('#nuevoImpuestoVenta').val()) || 0;
        var impuesto = subtotal * (porcIva / 100);
        var total    = subtotal + impuesto;

        $('#nuevoPrecioNeto, #nuevoSubTotalVenta').val(subtotal.toFixed(2));
        $('#nuevoPrecioImpuesto').val(impuesto.toFixed(2));
        $('#nuevoTotalVenta').val(total.toFixed(2));
        $('#listaProductos').val(JSON.stringify(lista));
    }
</script>
<?= $this->endSection() ?>