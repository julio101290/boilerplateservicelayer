<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<style>
    :root {
        --corp-red: #a82020;
        --corp-red-dark: #821414;
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
    .select2-container--default .select2-selection--multiple {
        min-height: 31px !important;
        border-color: #ced4da !important;
        font-size: 0.82rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: var(--corp-red) !important;
        border-color: var(--corp-red-dark) !important;
        color: #ffffff !important;
        font-size: 0.75rem;
        padding: 1px 6px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #ffffff !important;
        margin-right: 4px;
    }
    .badge-purple {
        background-color: #6f42c1;
        color: #ffffff;
    }
    .table-mov th {
        font-size: 0.77rem;
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .table-mov td {
        font-size: 0.79rem;
        vertical-align: middle !important;
        white-space: nowrap;
    }
    #tableMovimientos tbody tr {
        cursor: pointer;
    }
    .row-cancelado {
        background-color: #f4f4f4 !important;
        text-decoration: line-through;
        color: #888888;
    }
    .quick-doc-btn {
        font-size: 0.70rem;
        padding: 0 5px;
        line-height: 1.4;
    }
</style>

<input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<!-- KPIs RESUMEN -->
<div class="row mb-2">
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-corp-red"><i class="fas fa-boxes"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold">Partidas Encontradas</span>
                <span class="info-box-number h5 mb-0" id="kpiPartidas">0</span>
                <small class="text-muted">Documentos únicos: <span id="kpiDocumentos">0</span></small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-primary"><i class="fas fa-cubes"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-primary">Cantidad Total</span>
                <span class="info-box-number h5 mb-0 text-primary" id="kpiCantidad">0.000</span>
                <small class="text-muted">Suma de unidades en filtro</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-info">Costo Total (Inv.)</span>
                <span class="info-box-number h5 mb-0 text-info" id="kpiCosto">$ 0.00</span>
                <small class="text-muted">Cantidad × Costo SAP</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-success">Importe Total (Precio)</span>
                <span class="info-box-number h5 mb-0 text-success" id="kpiImporte">$ 0.00</span>
                <small class="text-muted">Subtotal de líneas consultadas</small>
            </div>
        </div>
    </div>
</div>

<!-- CARD PRINCIPAL -->
<div class="card card-corp-red card-outline shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
        <h3 class="card-title font-weight-bold" style="color: var(--corp-red);">
            <i class="fas fa-warehouse mr-1"></i> <?= esc($title) ?>
        </h3>
        <div class="card-tools ml-auto d-flex align-items-center" style="gap: 6px;">
            <button type="button" class="btn btn-outline-success btn-sm font-weight-bold" id="btnExportCsv">
                <i class="fas fa-file-excel mr-1"></i> Excel (CSV)
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm font-weight-bold" id="btnExportPdf">
                <i class="fas fa-file-pdf mr-1"></i> PDF Detallado
            </button>
            <span class="badge badge-secondary p-2">
                <i class="fas fa-eye mr-1"></i> Solo Lectura (SAP HANA)
            </span>
        </div>
    </div>

    <div class="card-body p-3">
        <!-- FILTROS -->
        <div class="bg-light p-2 rounded border mb-3">
            <!-- FILA 1: Empresa, Sucursal, Fechas, Estatus, Cancelados -->
            <div class="form-row align-items-end">
                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-building mr-1"></i> Empresa
                    </label>
                    <select class="form-control form-control-sm select2" id="idEmpresa" style="width:100%;">
                        <?php foreach ($empresas as $emp) : ?>
                            <option value="<?= $emp['id'] ?>" <?= (int) $emp['id'] === (int) $idEmpresa ? 'selected' : '' ?>>
                                <?= $emp['id'] ?> - <?= esc($emp['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-store mr-1"></i> Sucursal
                    </label>
                    <select class="form-control form-control-sm select2" id="filtroSucursal" style="width:100%;">
                        <option value="TODOS">TODOS</option>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-calendar-alt mr-1"></i> Fecha Ini
                    </label>
                    <input type="date" class="form-control form-control-sm" id="fechaIni" value="<?= esc($fechaIni) ?>">
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-calendar-check mr-1"></i> Fecha Fin
                    </label>
                    <input type="date" class="form-control form-control-sm" id="fechaFin" value="<?= esc($fechaFin) ?>">
                </div>

                <div class="form-group col-lg-1 col-md-2 col-sm-6 col-6 mb-2">
                    <label class="mb-1 small font-weight-bold">Estatus</label>
                    <select class="form-control form-control-sm" id="filtroEstatus">
                        <option value="TODOS" selected>Todos</option>
                        <option value="O">Abierto</option>
                        <option value="C">Cerrado</option>
                    </select>
                </div>

                <div class="form-group col-lg-1 col-md-2 col-sm-6 col-6 mb-2">
                    <label class="mb-1 small font-weight-bold">Canc.</label>
                    <select class="form-control form-control-sm" id="filtroCancelados">
                        <option value="N" selected>No</option>
                        <option value="S">Sí</option>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-12 col-12 mb-2">
                    <label class="mb-1 small font-weight-bold">Folio / Referencia</label>
                    <input type="text" class="form-control form-control-sm" id="filtroFolio" placeholder="* Todos">
                </div>
            </div>

            <!-- FILA 2: Multiselects (Documentos y Almacenes) -->
            <div class="form-row align-items-end border-top pt-2 mt-1">
                <div class="form-group col-lg-6 col-md-12 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="mb-0 small font-weight-bold" style="color: var(--corp-red);">
                            <i class="fas fa-file-alt mr-1"></i> Tipo(s) de Documento <span class="text-muted font-weight-normal">(vacío = Todos)</span>
                        </label>
                        <div>
                            <button type="button" class="btn btn-outline-secondary quick-doc-btn btn-preset-docs" data-group="INVENTARIO">Inventario</button>
                            <button type="button" class="btn btn-outline-primary quick-doc-btn btn-preset-docs" data-group="COMPRAS">Compras</button>
                            <button type="button" class="btn btn-outline-info quick-doc-btn btn-preset-docs" data-group="VENTAS">Ventas</button>
                            <button type="button" class="btn btn-outline-danger quick-doc-btn" id="btnLimpiarDocs">Limpiar</button>
                        </div>
                    </div>
                    <select class="form-control form-control-sm" id="filtroTipoDoc" multiple="multiple" style="width:100%;">
                        <optgroup label="Inventario">
                            <?php foreach ($docTypes as $objKey => $docMeta) : ?>
                                <?php if ($docMeta['group'] === 'INVENTARIO') : ?>
                                    <option value="<?= esc($objKey) ?>" data-group="INVENTARIO"><?= esc($docMeta['short']) ?> - <?= esc($docMeta['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Compras">
                            <?php foreach ($docTypes as $objKey => $docMeta) : ?>
                                <?php if ($docMeta['group'] === 'COMPRAS') : ?>
                                    <option value="<?= esc($objKey) ?>" data-group="COMPRAS"><?= esc($docMeta['short']) ?> - <?= esc($docMeta['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Ventas">
                            <?php foreach ($docTypes as $objKey => $docMeta) : ?>
                                <?php if ($docMeta['group'] === 'VENTAS') : ?>
                                    <option value="<?= esc($objKey) ?>" data-group="VENTAS"><?= esc($docMeta['short']) ?> - <?= esc($docMeta['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group col-lg-6 col-md-12 mb-2">
                    <label class="mb-1 small font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-warehouse mr-1"></i> Almacén(es) <span class="text-muted font-weight-normal">(vacío = Todos)</span>
                    </label>
                    <select class="form-control form-control-sm" id="filtroAlmacen" multiple="multiple" style="width:100%;"></select>
                </div>
            </div>

            <!-- FILA 3: Multiselects (Proveedores, Artículos) + Botón Consultar -->
            <div class="form-row align-items-end pt-1">
                <div class="form-group col-lg-5 col-md-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-industry mr-1"></i> Proveedor(es) <span class="text-muted font-weight-normal">(vacío = Todos)</span>
                    </label>
                    <select class="form-control form-control-sm" id="filtroProveedor" multiple="multiple" style="width:100%;"></select>
                </div>

                <div class="form-group col-lg-5 col-md-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-barcode mr-1"></i> Artículo(s) <span class="text-muted font-weight-normal">(vacío = Todos)</span>
                    </label>
                    <select class="form-control form-control-sm" id="filtroArticulo" multiple="multiple" style="width:100%;"></select>
                </div>

                <div class="form-group col-lg-2 col-md-12 col-12 mb-1">
                    <button type="button" class="btn btn-corp-red btn-sm btn-block font-weight-bold" id="btnConsultar">
                        <i class="fas fa-search mr-1"></i> Consultar
                    </button>
                </div>
            </div>
        </div>

        <div id="alertTruncated" class="alert alert-warning py-1 px-3 small d-none mb-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Se muestran los primeros <b>5,000 registros</b> en pantalla. Aplica un filtro más específico o usa <b>Excel (CSV)</b> para descargar hasta 25,000 partidas.
        </div>

        <!-- TABLA DE MOVIMIENTOS -->
        <div class="mb-1 text-muted small">
            <i class="fas fa-info-circle text-info mr-1"></i>
            Haz clic en cualquier fila o en el botón <i class="fas fa-eye"></i> para ver el <b>Detalle Completo del Documento</b> en SAP.
        </div>
        <div class="table-responsive">
            <table id="tableMovimientos" class="table table-bordered table-hover table-sm w-100 table-mov">
                <thead class="bg-corp-red text-center">
                    <tr>
                        <th>Ver</th>
                        <th>Documento</th>
                        <th>Alm. Clave</th>
                        <th>Nombre Almacén</th>
                        <th>Folio</th>
                        <th>Estatus</th>
                        <th>Fecha</th>
                        <th>Proveedor / Socio</th>
                        <th>Clave Art.</th>
                        <th>Artículo</th>
                        <th>Cantidad</th>
                        <th>Costo</th>
                        <th>Precio</th>
                        <th>Importe</th>
                        <th>Sucursal</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: DETALLE DEL DOCUMENTO -->
<div class="modal fade" id="modalDocDetail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-file-invoice mr-1"></i> <span id="detailTitle">Detalle de Documento</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div id="detailLoading" class="text-center py-5 d-none">
                    <i class="fas fa-spinner fa-spin fa-2x text-danger"></i>
                    <p class="mt-2 mb-0 text-muted">Consultando partidas en SAP HANA...</p>
                </div>
                <div id="detailBodyContent"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var tableMovimientos = null;

    $(document).ready(function () {
        $('#idEmpresa, #filtroSucursal').select2();

        $('#filtroTipoDoc').select2({
            placeholder: 'Todos los documentos (selecciona uno o varios)...',
            allowClear: true,
            closeOnSelect: false
        });

        $('#filtroAlmacen').select2({
            placeholder: 'Todos los almacenes (selecciona uno o varios)...',
            allowClear: true,
            closeOnSelect: false
        });

        inicializarSelectProveedor();
        inicializarSelectArticulo();
        inicializarDataTable();
        cargarSucursales();
        cargarAlmacenes();

        // Botones rápidos para seleccionar grupos de documentos
        $('.btn-preset-docs').on('click', function () {
            var grp = $(this).data('group');
            var actuales = $('#filtroTipoDoc').val() || [];
            $('#filtroTipoDoc option[data-group="' + grp + '"]').each(function () {
                var v = $(this).val();
                if (actuales.indexOf(v) === -1) {
                    actuales.push(v);
                }
            });
            $('#filtroTipoDoc').val(actuales).trigger('change');
        });

        $('#btnLimpiarDocs').on('click', function () {
            $('#filtroTipoDoc').val(null).trigger('change');
        });

        $('#idEmpresa').on('change', function () {
            $('#filtroProveedor').val(null).trigger('change');
            $('#filtroArticulo').val(null).trigger('change');
            $('#filtroAlmacen').val(null).trigger('change');
            cargarSucursales();
            cargarAlmacenes();
        });

        $('#filtroSucursal').on('change', function () {
            cargarAlmacenes();
        });

        $('#btnConsultar').on('click', function () {
            consultarMovimientos();
        });

        $('#filtroFolio').on('keypress', function (e) {
            if (e.which === 13) consultarMovimientos();
        });

        $('#btnExportPdf').on('click', function () {
            exportarReporte("<?= site_url('admin/inventoryMovementsSAP/report') ?>");
        });

        $('#btnExportCsv').on('click', function () {
            exportarReporte("<?= site_url('admin/inventoryMovementsSAP/exportCsv') ?>");
        });

        $('#tableMovimientos tbody').on('click', 'tr', function () {
            var rowData = tableMovimientos.row(this).data();
            if (!rowData) return;
            abrirDetalleDocumento(rowData.ObjType, rowData.DocEntry);
        });
    });

    function fmtMoney(val, dec) {
        var d = (typeof dec === 'number') ? dec : 2;
        var n = parseFloat(val || 0);
        return '$ ' + n.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function fmtNum(val, dec) {
        var d = (typeof dec === 'number') ? dec : 2;
        var n = parseFloat(val || 0);
        return n.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function inicializarSelectProveedor() {
        $('#filtroProveedor').select2({
            placeholder: 'Todos los proveedores (buscar clave, nombre o RFC)...',
            allowClear: true,
            closeOnSelect: false,
            minimumInputLength: 2,
            ajax: {
                url: "<?= site_url('admin/inventoryMovementsSAP/searchSuppliersAjax') ?>",
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: params.term || '',
                        idEmpresa: $('#idEmpresa').val()
                    };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true
            }
        });
    }

    function inicializarSelectArticulo() {
        $('#filtroArticulo').select2({
            placeholder: 'Todos los artículos (buscar uno o varios)...',
            allowClear: true,
            closeOnSelect: false,
            minimumInputLength: 2,
            ajax: {
                url: "<?= site_url('admin/inventoryMovementsSAP/searchItemsAjax') ?>",
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: params.term || '',
                        idEmpresa: $('#idEmpresa').val()
                    };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true
            }
        });
    }

    function cargarSucursales() {
        $.ajax({
            url: "<?= site_url('admin/inventoryMovementsSAP/getBranchesAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: $('#idEmpresa').val(),
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                var $suc = $('#filtroSucursal').empty();
                $suc.append(new Option('TODOS', 'TODOS', true, true));
                if (res.sucursales && res.sucursales.length) {
                    res.sucursales.forEach(function (s) {
                        $suc.append(new Option(s.text, s.bplId, false, false));
                    });
                }
                $suc.trigger('change.select2');
            }
        });
    }

    function cargarAlmacenes() {
        $.ajax({
            url: "<?= site_url('admin/inventoryMovementsSAP/getWarehousesAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: $('#idEmpresa').val(),
                bplId: $('#filtroSucursal').val() || 'TODOS',
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                var $alm = $('#filtroAlmacen').empty();
                if (res.warehouses && res.warehouses.length) {
                    res.warehouses.forEach(function (w) {
                        $alm.append(new Option(w.text, w.id, false, false));
                    });
                }
                $alm.val(null).trigger('change');
            }
        });
    }

    function inicializarDataTable() {
        tableMovimientos = $('#tableMovimientos').DataTable({
            processing: true,
            serverSide: false,
            responsive: false,
            scrollX: true,
            autoWidth: false,
            ordering: true,
            order: [[6, 'asc'], [4, 'asc']],
            pageLength: 25,
            lengthMenu: [[25, 50, 100, 250, -1], [25, 50, 100, 250, "Todos"]],
            data: [],
            createdRow: function (row, data) {
                if (data.Cancelado === 'S') {
                    $(row).addClass('row-cancelado');
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function () {
                        return `<button type="button" class="btn btn-outline-dark btn-xs" title="Ver Detalle"><i class="fas fa-eye"></i></button>`;
                    }
                },
                {
                    data: 'TMA_NOMBRE',
                    render: function (d, type, row) {
                        return `<span class="badge badge-${row.BadgeColor} mr-1">${escHtml(row.CodigoCorto)}</span> ${escHtml(d)}`;
                    }
                },
                { data: 'ALM_CLAVE', className: 'text-center font-weight-bold' },
                { data: 'ALM_NOMBRE' },
                { data: 'MAE_FOLIO', className: 'text-center font-weight-bold' },
                {
                    data: 'MAE_ESTATUS',
                    className: 'text-center',
                    render: function (d) {
                        var map = { 'ABIERTO': 'warning', 'CERRADO': 'success', 'CANCELADO': 'dark' };
                        return `<span class="badge badge-${map[d] || 'secondary'}">${escHtml(d)}</span>`;
                    }
                },
                { data: 'MAE_FECHA', className: 'text-center' },
                {
                    data: null,
                    render: function (d, type, row) {
                        if (!row.CardCode && !row.CardName) return '<span class="text-muted">-</span>';
                        var lbl = row.CardCode ? `<b>${escHtml(row.CardCode)}</b> - ${escHtml(row.CardName)}` : escHtml(row.CardName);
                        return lbl;
                    }
                },
                { data: 'ART_CLAVE', className: 'font-weight-bold text-dark' },
                { data: 'ART_NOMBRE' },
                {
                    data: 'MAD_CANT',
                    className: 'text-right font-weight-bold',
                    render: function (d, type, row) {
                        if (type === 'sort' || type === 'type') return parseFloat(d || 0);
                        return `${fmtNum(d, 3)} <small class="text-muted">${escHtml(row.UNIDAD)}</small>`;
                    }
                },
                {
                    data: 'MAD_COSTO',
                    className: 'text-right text-info',
                    render: function (d, type) {
                        if (type === 'sort' || type === 'type') return parseFloat(d || 0);
                        return fmtNum(d, 4);
                    }
                },
                {
                    data: 'MAD_PRECIO',
                    className: 'text-right text-primary',
                    render: function (d, type) {
                        if (type === 'sort' || type === 'type') return parseFloat(d || 0);
                        return fmtNum(d, 5);
                    }
                },
                {
                    data: 'IMPORTE',
                    className: 'text-right font-weight-bold text-success',
                    render: function (d, type) {
                        if (type === 'sort' || type === 'type') return parseFloat(d || 0);
                        return fmtNum(d, 2);
                    }
                },
                { data: 'BPLName' },
                { data: 'Referencia' }
            ]
        });
    }

    function obtenerFiltros() {
        return {
            idEmpresa:   $('#idEmpresa').val(),
            bplId:       $('#filtroSucursal').val() || 'TODOS',
            fechaIni:    $('#fechaIni').val(),
            fechaFin:    $('#fechaFin').val(),
            tipoDocs:    $('#filtroTipoDoc').val() || [],
            almacenes:   $('#filtroAlmacen').val() || [],
            articulos:   $('#filtroArticulo').val() || [],
            proveedores: $('#filtroProveedor').val() || [],
            estatus:     $('#filtroEstatus').val() || 'TODOS',
            folio:       $('#filtroFolio').val() || '',
            cancelados:  $('#filtroCancelados').val() || 'N'
        };
    }

    function consultarMovimientos() {
        var payload = obtenerFiltros();
        payload[$('.txt_csrfname').attr('name')] = $('.txt_csrfname').val();

        var $btn = $('#btnConsultar');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Consultando...');

        $.ajax({
            url: "<?= site_url('admin/inventoryMovementsSAP/getMovementsAjax') ?>",
            method: "POST",
            dataType: "json",
            data: payload,
            success: function (res) {
                $btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Consultar');
                if (res.token) $('.txt_csrfname').val(res.token);

                if (res.error) {
                    Swal.fire('Error', res.message || 'Error al consultar SAP HANA', 'error');
                    return;
                }

                var k = res.kpis || {};
                $('#kpiPartidas').text((k.totalPartidas || 0).toLocaleString('en-US'));
                $('#kpiDocumentos').text((k.totalDocumentos || 0).toLocaleString('en-US'));
                $('#kpiCantidad').text(fmtNum(k.totalCantidad, 3));
                $('#kpiCosto').text(fmtMoney(k.totalCosto, 2));
                $('#kpiImporte').text(fmtMoney(k.totalImporte, 2));

                if (res.truncated) {
                    $('#alertTruncated').removeClass('d-none');
                } else {
                    $('#alertTruncated').addClass('d-none');
                }

                tableMovimientos.clear().rows.add(res.data || []).draw();
            },
            error: function (jqXHR) {
                $btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Consultar');
                Swal.fire('Error', jqXHR.responseText || 'Error de conexión con el servidor', 'error');
            }
        });
    }

    function exportarReporte(baseUrl) {
        var qs = $.param(obtenerFiltros());
        window.open(baseUrl + '?' + qs, '_blank');
    }

    function abrirDetalleDocumento(objType, docEntry) {
        $('#detailTitle').text('Cargando documento...');
        $('#detailBodyContent').empty();
        $('#detailLoading').removeClass('d-none');
        $('#modalDocDetail').modal('show');

        $.ajax({
            url: "<?= site_url('admin/inventoryMovementsSAP/getDocumentDetailAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: $('#idEmpresa').val(),
                objType:   objType,
                docEntry:  docEntry,
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                $('#detailLoading').addClass('d-none');

                if (res.error || !res.detail) {
                    $('#detailBodyContent').html(`<div class="alert alert-danger">${escHtml(res.message || 'No se pudo cargar el detalle.')}</div>`);
                    return;
                }

                var d = res.detail;
                $('#detailTitle').html(`<span class="badge badge-${d.badge} mr-2">${escHtml(d.tipo)}</span> Folio #${escHtml(d.docNum)} <small class="text-muted">(DocEntry: ${d.docEntry})</small>`);

                var headerHtml = '<div class="row bg-light border rounded p-2 mb-3 small">';
                Object.keys(d.header || {}).forEach(function (k) {
                    headerHtml += `<div class="col-md-4 col-sm-6 mb-1"><span class="text-muted">${escHtml(k)}:</span> <b>${escHtml(d.header[k] || '-')}</b></div>`;
                });
                headerHtml += '</div>';

                var bodyHtml = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-striped small">
                            <thead class="bg-corp-red text-center">
                                <tr>
                                    <th>Clave Art.</th>
                                    <th>Descripción</th>
                                    <th>Almacén</th>
                                    <th>Cantidad</th>
                                    <th>Costo Unit.</th>
                                    <th>Precio Unit.</th>
                                    <th>Importe Línea</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                (d.lines || []).forEach(function (l) {
                    bodyHtml += `
                        <tr>
                            <td><b>${escHtml(l.itemCode)}</b></td>
                            <td>${escHtml(l.dscription)}</td>
                            <td>${escHtml(l.warehouse)}</td>
                            <td class="text-right">${fmtNum(l.quantity, 3)} <small class="text-muted">${escHtml(l.uom)}</small></td>
                            <td class="text-right text-info">${fmtNum(l.cost, 4)}</td>
                            <td class="text-right text-primary">${fmtNum(l.price, 5)}</td>
                            <td class="text-right font-weight-bold">${fmtNum(l.lineTotal, 2)}</td>
                        </tr>
                    `;
                });
                bodyHtml += '</tbody></table></div>';

                var t = d.totals || {};
                bodyHtml += `
                    <div class="row justify-content-end mt-2">
                        <div class="col-md-4">
                            <table class="table table-sm table-bordered small mb-0">
                                <tr><td>Costo Total (Inv.)</td><td class="text-right text-info"><b>${fmtMoney(t.CostoTotal, 2)}</b></td></tr>
                                <tr><td>Subtotal</td><td class="text-right"><b>${fmtMoney(t.Subtotal, 2)}</b></td></tr>
                                <tr><td>IVA</td><td class="text-right"><b>${fmtMoney(t.IVA, 2)}</b></td></tr>
                                <tr class="table-active"><td><b>Total Documento</b></td><td class="text-right text-danger"><b>${fmtMoney(t.Total, 2)}</b></td></tr>
                            </table>
                        </div>
                    </div>
                `;

                $('#detailBodyContent').html(headerHtml + bodyHtml);
            },
            error: function (jqXHR) {
                $('#detailLoading').addClass('d-none');
                $('#detailBodyContent').html(`<div class="alert alert-danger">${escHtml(jqXHR.responseText || 'Error de servidor')}</div>`);
            }
        });
    }
</script>
<?= $this->endSection() ?>