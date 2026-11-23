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
    .badge-purple {
        background-color: #6f42c1;
        color: #fff;
    }
    .table-edo th {
        font-size: 0.78rem;
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .table-edo td {
        font-size: 0.80rem;
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
    .row-vencido {
        background-color: #fff5f5 !important;
    }
    /* Estilos Mapa de Relaciones */
    .rel-map-wrapper {
        position: relative;
        overflow-x: auto;
        min-height: 360px;
        background: #f8f9fb;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 24px 18px;
    }
    .rel-map-canvas {
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 64px;
        min-width: max-content;
        z-index: 2;
    }
    .rel-map-svg {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
        z-index: 1;
    }
    .rel-stage-col {
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 220px;
    }
    .rel-stage-title {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #6c757d;
        text-align: center;
        border-bottom: 1px dashed #ced4da;
        padding-bottom: 4px;
    }
    .rel-node-card {
        width: 225px;
        background: #ffffff;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        cursor: pointer;
    }
    .rel-node-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0, 0, 0, 0.14);
        border-color: var(--corp-red);
    }
    .rel-node-card.is-current {
        border: 2.5px solid var(--corp-red);
        box-shadow: 0 0 0 3px rgba(168, 32, 32, 0.18);
    }
    .rel-node-header {
        padding: 5px 8px;
        font-size: 0.75rem;
        font-weight: 700;
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .rel-node-body {
        padding: 7px 9px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
</style>

<input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<!-- KPIs SALDOS AL CORTE -->
<div class="row mb-2">
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-corp-red"><i class="fas fa-balance-scale"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold">Saldo al Corte</span>
                <span class="info-box-number h5 mb-0" id="kpiSaldoCorte">$ 0.00</span>
                <small class="text-muted">Inicial: <span id="kpiSaldoInicial">$ 0.00</span></small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-danger">Vencido al Corte</span>
                <span class="info-box-number h5 mb-0 text-danger" id="kpiVencido">$ 0.00</span>
                <small class="text-muted">Por vencer: <span id="kpiPorVencer">$ 0.00</span></small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-success">Anticipo Efectivo</span>
                <span class="info-box-number h5 mb-0 text-success" id="kpiAnticipoEfvo">$ 0.00</span>
                <small class="text-muted">Pagos a cuenta sin aplicar</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-info">Anticipos Facturación</span>
                <span class="info-box-number h5 mb-0 text-info" id="kpiAnticipoFact">$ 0.00</span>
                <small class="text-muted">ODPI pagados por aplicar</small>
            </div>
        </div>
    </div>
</div>

<!-- CARD PRINCIPAL -->
<div class="card card-corp-red card-outline shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
        <h3 class="card-title font-weight-bold" style="color: var(--corp-red);">
            <i class="fas fa-user-check mr-1"></i> <?= esc($title) ?>
        </h3>
        <div class="card-tools ml-auto d-flex align-items-center" style="gap: 8px;">
            <button type="button" class="btn btn-outline-danger btn-sm font-weight-bold" id="btnExportPdf" disabled>
                <i class="fas fa-file-pdf mr-1"></i> PDF Estado de Cuenta
            </button>
            <span class="badge badge-secondary p-2">
                <i class="fas fa-eye mr-1"></i> Solo Lectura (SAP HANA)
            </span>
        </div>
    </div>

    <div class="card-body p-3">
        <!-- FILTROS PRINCIPALES -->
        <div class="bg-light p-2 rounded border mb-3">
            <div class="form-row align-items-end">
                <!-- EMPRESA -->
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

                <!-- SUCURSAL -->
                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-store mr-1"></i> Sucursal
                    </label>
                    <select class="form-control form-control-sm select2" id="filtroSucursal" style="width:100%;">
                        <option value="TODOS">TODOS</option>
                    </select>
                </div>

                <!-- CLIENTE (OCRD AJAX) -->
                <div class="form-group col-lg-4 col-md-8 col-sm-12 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-user-tie mr-1"></i> Cliente (Clave / Nombre / RFC)
                    </label>
                    <select class="form-control form-control-sm" id="filtroCliente" style="width:100%;"></select>
                </div>

                <!-- FECHA INICIO -->
                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-calendar-alt mr-1"></i> Fecha Ini
                    </label>
                    <input type="date" class="form-control form-control-sm" id="fechaIni" value="<?= esc($fechaIni) ?>">
                </div>

                <!-- FECHA CORTE / FIN -->
                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                    <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                        <i class="fas fa-calendar-check mr-1"></i> Fecha Corte (Fin)
                    </label>
                    <input type="date" class="form-control form-control-sm" id="fechaFin" value="<?= esc($fechaFin) ?>">
                </div>
            </div>

            <!-- FILTROS SECUNDARIOS (TIPO, SALDO, CONCEPTO, REFERENCIA, CANCELADOS) -->
            <div class="form-row align-items-end border-top pt-2 mt-1">
                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold">Tipo Movimiento</label>
                    <select class="form-control form-control-sm" id="filtroTipoMov">
                        <option value="AMBOS" selected>Ambos (Cargos y Abonos)</option>
                        <option value="CARGOS">Solo Cargos</option>
                        <option value="ABONOS">Solo Abonos</option>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold">Saldo Documento</label>
                    <select class="form-control form-control-sm" id="filtroEstatusSal">
                        <option value="TODOS" selected>Saldados / Sin Saldar</option>
                        <option value="SIN_SALDAR">Solo Sin Saldar</option>
                        <option value="SALDADOS">Solo Saldados</option>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold">Concepto / Documento</label>
                    <select class="form-control form-control-sm" id="filtroConcepto">
                        <option value="TODOS" selected>Todos</option>
                        <option value="13">Facturas (13)</option>
                        <option value="203">Anticipos Fact. (203)</option>
                        <option value="24">Pagos Recibidos (24)</option>
                        <option value="14">Notas de Crédito (14)</option>
                        <option value="30">Asientos Contables (30)</option>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold">Referencia / Folio</label>
                    <input type="text" class="form-control form-control-sm" id="filtroReferencia" placeholder="* Todos">
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-1">
                    <label class="mb-1 small font-weight-bold">¿Incluir Cancelados?</label>
                    <select class="form-control form-control-sm" id="filtroCancelados">
                        <option value="N" selected>No</option>
                        <option value="S">Sí</option>
                    </select>
                </div>

                <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-1">
                    <button type="button" class="btn btn-corp-red btn-sm btn-block font-weight-bold" id="btnConsultar">
                        <i class="fas fa-search mr-1"></i> Consultar
                    </button>
                </div>
            </div>
        </div>

        <!-- RESUMEN DEL CLIENTE Y ANTIGÜEDAD -->
        <div id="panelInfoCliente" class="alert alert-light border py-2 px-3 mb-3 d-none">
            <div class="row align-items-center small">
                <div class="col-lg-5 col-12 mb-1 mb-lg-0">
                    <strong class="text-danger" id="infoCliNombre"></strong><br>
                    <span>RFC: <b id="infoCliRFC">-</b></span> &nbsp;|&nbsp;
                    <span>Condición: <b id="infoCliCond">-</b></span> &nbsp;|&nbsp;
                    <span>Límite: <b id="infoCliLimite">$ 0.00</b></span>
                </div>
                <div class="col-lg-7 col-12 text-lg-right">
                    <span class="badge badge-light border mr-1">Cargos Periodo: <b id="infoCargos">$ 0.00</b></span>
                    <span class="badge badge-light border mr-1">Abonos Periodo: <b id="infoAbonos">$ 0.00</b></span>
                    <span class="badge badge-warning mr-1">1-30d: <b id="aging1_30">$ 0.00</b></span>
                    <span class="badge badge-warning mr-1">31-60d: <b id="aging31_60">$ 0.00</b></span>
                    <span class="badge badge-danger mr-1">61-90d: <b id="aging61_90">$ 0.00</b></span>
                    <span class="badge badge-dark">+90d: <b id="aging90Mas">$ 0.00</b></span>
                </div>
            </div>
        </div>

        <!-- TABLA DE MOVIMIENTOS -->
        <div class="mb-1 text-muted small">
            <i class="fas fa-info-circle text-info mr-1"></i>
            Haz clic en cualquier fila para abrir su <b>Mapa de Relaciones</b>, o usa los botones de acción para ver el <b>Detalle del Documento</b>.
        </div>
        <div class="table-responsive">
            <table id="tableMovimientos" class="table table-bordered table-hover table-sm w-100 table-edo">
                <thead class="bg-corp-red text-center">
                    <tr>
                        <th>Acciones</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Folio</th>
                        <th>Doc. Aplicado</th>
                        <th>Fec. Ven.</th>
                        <th>Sucursal</th>
                        <th>Cargo</th>
                        <th>Abono</th>
                        <th>Saldo Doc.</th>
                        <th>Saldo Acum.</th>
                        <th>Estatus</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: MAPA DE RELACIONES -->
<div class="modal fade" id="modalRelMap" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 94%;">
        <div class="modal-content">
            <div class="modal-header bg-corp-red py-2">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-project-diagram mr-1"></i> Mapa de Relaciones <span id="relMapTitulo" class="font-weight-normal"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                    <div>
                        <i class="fas fa-mouse-pointer mr-1 text-danger"></i>
                        Haz clic en cualquier tarjeta del diagrama para ver el <b>detalle completo</b> de ese documento.
                    </div>
                    <div>
                        <span class="badge badge-danger mr-1">Borde Rojo = Documento Seleccionado</span>
                        <span class="badge badge-success mr-1">Línea Verde = Pago</span>
                        <span class="badge badge-warning mr-1">Línea Ámbar = Anticipo</span>
                        <span class="badge badge-info">Línea Punteada = Reconciliación</span>
                    </div>
                </div>
                <div id="relMapLoading" class="text-center py-5 d-none">
                    <i class="fas fa-spinner fa-spin fa-2x text-danger"></i>
                    <p class="mt-2 mb-0 text-muted">Construyendo mapa de relaciones en SAP HANA...</p>
                </div>
                <div id="relMapWrapper" class="rel-map-wrapper">
                    <svg id="relMapSvg" class="rel-map-svg"></svg>
                    <div id="relMapCanvas" class="rel-map-canvas"></div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
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
                    <p class="mt-2 mb-0 text-muted">Consultando detalle en SAP HANA...</p>
                </div>
                <div id="detailBodyContent"></div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnAbrirMapaDesdeDetalle">
                    <i class="fas fa-project-diagram mr-1"></i> Ver Mapa de Relaciones
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var tableMovimientos = null;
    var currentDetailRef = null;
    var lastRelMapData   = null;

    $(document).ready(function () {
        $('#idEmpresa, #filtroSucursal').select2();

        inicializarSelectCliente();
        inicializarDataTable();
        cargarSucursales();

        $('#idEmpresa').on('change', function () {
            $('#filtroCliente').val(null).trigger('change');
            cargarSucursales();
        });

        $('#btnConsultar').on('click', function () {
            consultarEstadoCuenta();
        });

        $('#filtroCliente').on('select2:select', function () {
            consultarEstadoCuenta();
        });

        $('#btnExportPdf').on('click', function () {
            exportarEstadoCuentaPDF();
        });

        // Clic en fila -> abre Mapa de Relaciones (excepto si se hace clic en botones de acción)
        $('#tableMovimientos tbody').on('click', 'tr', function (e) {
            if ($(e.target).closest('button').length) return;
            var rowData = tableMovimientos.row(this).data();
            if (!rowData) return;
            abrirMapaRelaciones(rowData.ObjType, rowData.DocEntry, rowData.TransId, `${rowData.NombreTipo} #${rowData.Folio}`);
        });

        $('#tableMovimientos tbody').on('click', '.btn-ver-mapa', function (e) {
            e.stopPropagation();
            var rowData = tableMovimientos.row($(this).closest('tr')).data();
            if (!rowData) return;
            abrirMapaRelaciones(rowData.ObjType, rowData.DocEntry, rowData.TransId, `${rowData.NombreTipo} #${rowData.Folio}`);
        });

        $('#tableMovimientos tbody').on('click', '.btn-ver-detalle', function (e) {
            e.stopPropagation();
            var rowData = tableMovimientos.row($(this).closest('tr')).data();
            if (!rowData) return;
            abrirDetalleDocumento(rowData.ObjType, rowData.DocEntry, rowData.TransId);
        });

        $('#btnAbrirMapaDesdeDetalle').on('click', function () {
            if (!currentDetailRef) return;
            $('#modalDocDetail').modal('hide');
            setTimeout(function () {
                abrirMapaRelaciones(currentDetailRef.objType, currentDetailRef.docEntry, currentDetailRef.transId, currentDetailRef.label);
            }, 250);
        });

        // Redibujar flechas SVG al terminar de abrir modal o redimensionar
        $('#modalRelMap').on('shown.bs.modal', function () {
            if (lastRelMapData) dibujarConectoresSVG(lastRelMapData.edges || []);
        });
        $(window).on('resize', function () {
            if ($('#modalRelMap').hasClass('show') && lastRelMapData) {
                dibujarConectoresSVG(lastRelMapData.edges || []);
            }
        });
    });

    function fmtMoney(val) {
        var n = parseFloat(val || 0);
        return '$ ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtNum(val) {
        var n = parseFloat(val || 0);
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function inicializarSelectCliente() {
        $('#filtroCliente').select2({
            placeholder: 'Buscar cliente por clave, nombre o RFC...',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: "<?= site_url('admin/clientStatementSAP/searchClientsAjax') ?>",
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
            url: "<?= site_url('admin/clientStatementSAP/getBranchesAjax') ?>",
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

    function inicializarDataTable() {
        tableMovimientos = $('#tableMovimientos').DataTable({
            processing: true,
            serverSide: false,
            responsive: false,
            scrollX: true,
            autoWidth: false,
            ordering: false,
            pageLength: 25,
            lengthMenu: [[15, 25, 50, 100, -1], [15, 25, 50, 100, "Todos"]],
            data: [],
            createdRow: function (row, data) {
                if (data.Cancelado === 'S') {
                    $(row).addClass('row-cancelado');
                } else if (data.Estatus === 'VENCIDO') {
                    $(row).addClass('row-vencido');
                }
            },
            columns: [
                {
                    data: null,
                    className: 'text-center',
                    render: function () {
                        return `
                            <div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-outline-danger btn-xs btn-ver-mapa" title="Mapa de Relaciones">
                                    <i class="fas fa-project-diagram"></i>
                                </button>
                                <button type="button" class="btn btn-outline-dark btn-xs btn-ver-detalle" title="Ver Detalle del Documento">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        `;
                    }
                },
                { data: 'Fecha', className: 'text-center' },
                {
                    data: 'CodigoTipo',
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `<span class="badge badge-${row.BadgeColor} px-2 py-1" title="${escHtml(row.NombreTipo)}">${escHtml(data)}</span>`;
                    }
                },
                { data: 'Folio', className: 'text-center font-weight-bold' },
                { data: 'FacturaRel', className: 'text-center text-muted' },
                { data: 'FechaVen', className: 'text-center' },
                { data: 'BPLName' },
                {
                    data: 'Cargo',
                    className: 'text-right',
                    render: function (d) { return parseFloat(d) > 0 ? fmtNum(d) : ''; }
                },
                {
                    data: 'Abono',
                    className: 'text-right text-success',
                    render: function (d) { return parseFloat(d) > 0 ? fmtNum(d) : ''; }
                },
                {
                    data: 'SaldoPendiente',
                    className: 'text-right font-weight-bold',
                    render: function (d) {
                        var v = parseFloat(d || 0);
                        var cls = v > 0.005 ? 'text-danger' : (v < -0.005 ? 'text-success' : 'text-muted');
                        return `<span class="${cls}">${fmtNum(v)}</span>`;
                    }
                },
                {
                    data: 'SaldoAcumulado',
                    className: 'text-right font-weight-bold',
                    render: function (d) { return fmtNum(d); }
                },
                {
                    data: 'Estatus',
                    className: 'text-center',
                    render: function (d) {
                        var map = {
                            'VENCIDO': 'danger',
                            'PENDIENTE': 'warning',
                            'SALDADO': 'secondary',
                            'A FAVOR': 'success',
                            'CANCELADO': 'dark'
                        };
                        return `<span class="badge badge-${map[d] || 'light'}">${escHtml(d)}</span>`;
                    }
                },
                { data: 'Referencia' }
            ]
        });
    }

    function consultarEstadoCuenta() {
        var cardCode = $('#filtroCliente').val();
        if (!cardCode) {
            Swal.fire('Atención', 'Selecciona un cliente primero.', 'warning');
            return;
        }

        var $btn = $('#btnConsultar');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Consultando...');

        $.ajax({
            url: "<?= site_url('admin/clientStatementSAP/getStatementAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa:  $('#idEmpresa').val(),
                cardCode:   cardCode,
                bplId:      $('#filtroSucursal').val(),
                fechaIni:   $('#fechaIni').val(),
                fechaFin:   $('#fechaFin').val(),
                tipoMov:    $('#filtroTipoMov').val(),
                estatusSal: $('#filtroEstatusSal').val(),
                concepto:   $('#filtroConcepto').val(),
                referencia: $('#filtroReferencia').val(),
                cancelados: $('#filtroCancelados').val(),
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                $btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Consultar');
                if (res.token) $('.txt_csrfname').val(res.token);

                if (res.error) {
                    Swal.fire('Error', res.message || 'Error al consultar SAP HANA', 'error');
                    return;
                }

                // KPIs
                var k = res.kpis || {};
                var a = res.aging || {};
                $('#kpiSaldoCorte').text(fmtMoney(k.saldoCorte));
                $('#kpiSaldoInicial').text(fmtMoney(k.saldoInicial));
                $('#kpiVencido').text(fmtMoney(k.vencido));
                $('#kpiPorVencer').text(fmtMoney(a.porVencer));
                $('#kpiAnticipoEfvo').text(fmtMoney(k.anticipoEfectivo));
                $('#kpiAnticipoFact').text(fmtMoney(k.anticipoFacturacion));

                // Resumen cliente + Antigüedad
                var c = res.cliente || {};
                $('#infoCliNombre').text(`${c.CardCode || ''} - ${c.CardName || ''}`);
                $('#infoCliRFC').text(c.RFC || '-');
                $('#infoCliCond').text(c.CondicionPago || '-');
                $('#infoCliLimite').text(fmtMoney(c.CreditLine));
                $('#infoCargos').text(fmtMoney(k.totalCargos));
                $('#infoAbonos').text(fmtMoney(k.totalAbonos));
                $('#aging1_30').text(fmtMoney(a.d1_30));
                $('#aging31_60').text(fmtMoney(a.d31_60));
                $('#aging61_90').text(fmtMoney(a.d61_90));
                $('#aging90Mas').text(fmtMoney(a.d90Mas));
                $('#panelInfoCliente').removeClass('d-none');

                $('#btnExportPdf').prop('disabled', false);

                tableMovimientos.clear().rows.add(res.data || []).draw();
            },
            error: function (jqXHR) {
                $btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Consultar');
                Swal.fire('Error', jqXHR.responseText || 'Error de conexión con el servidor', 'error');
            }
        });
    }

    function exportarEstadoCuentaPDF() {
        var cardCode = $('#filtroCliente').val();
        if (!cardCode) {
            Swal.fire('Atención', 'Selecciona un cliente para exportar su estado de cuenta.', 'warning');
            return;
        }

        var qs = $.param({
            idEmpresa:  $('#idEmpresa').val(),
            cardCode:   cardCode,
            bplId:      $('#filtroSucursal').val(),
            fechaIni:   $('#fechaIni').val(),
            fechaFin:   $('#fechaFin').val(),
            tipoMov:    $('#filtroTipoMov').val(),
            estatusSal: $('#filtroEstatusSal').val(),
            concepto:   $('#filtroConcepto').val(),
            referencia: $('#filtroReferencia').val(),
            cancelados: $('#filtroCancelados').val()
        });

        window.open("<?= site_url('admin/clientStatementSAP/report') ?>?" + qs, '_blank');
    }

    function abrirMapaRelaciones(objType, docEntry, transId, label) {
        $('#relMapTitulo').text(label ? `— ${label}` : '');
        $('#relMapCanvas').empty();
        $('#relMapSvg').empty();
        $('#relMapWrapper').addClass('d-none');
        $('#relMapLoading').removeClass('d-none');
        $('#modalRelMap').modal('show');

        $.ajax({
            url: "<?= site_url('admin/clientStatementSAP/getRelationshipMapAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: $('#idEmpresa').val(),
                objType:   objType,
                docEntry:  docEntry,
                transId:   transId,
                cardCode:  $('#filtroCliente').val() || '',
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                $('#relMapLoading').addClass('d-none');
                $('#relMapWrapper').removeClass('d-none');

                if (res.error || !res.map) {
                    $('#relMapCanvas').html(`<div class="alert alert-danger m-3">${escHtml(res.message || 'No se pudo generar el mapa de relaciones.')}</div>`);
                    return;
                }

                lastRelMapData = res.map;
                renderizarMapaRelaciones(res.map);
            },
            error: function (jqXHR) {
                $('#relMapLoading').addClass('d-none');
                $('#relMapWrapper').removeClass('d-none');
                $('#relMapCanvas').html(`<div class="alert alert-danger m-3">${escHtml(jqXHR.responseText || 'Error de servidor')}</div>`);
            }
        });
    }

    function renderizarMapaRelaciones(map) {
        var nodes = map.nodes || [];
        var edges = map.edges || [];
        var $canvas = $('#relMapCanvas').empty();

        if (!nodes.length) {
            $canvas.html('<div class="alert alert-info m-3">Este movimiento no tiene documentos relacionados adicionales.</div>');
            return;
        }

        var stageLabels = {
            1: '1. Cotización',
            2: '2. Orden de Venta',
            3: '3. Entrega',
            4: '4. Factura / Anticipo',
            5: '5. Pagos / NC / Asientos'
        };

        var grouped = {};
        nodes.forEach(function (n) {
            var st = n.stage || 5;
            if (!grouped[st]) grouped[st] = [];
            grouped[st].push(n);
        });

        Object.keys(grouped).sort().forEach(function (st) {
            var $col = $(`<div class="rel-stage-col"><div class="rel-stage-title">${stageLabels[st] || 'Documentos'}</div></div>`);

            grouped[st].forEach(function (n) {
                var headerCls = `badge-${n.badge || 'secondary'}`;
                var currentCls = n.isCurrent ? 'is-current' : '';
                var statusBadge = n.cancelado
                    ? '<span class="badge badge-dark">CANCELADO</span>'
                    : (n.estatus === 'CERRADO' || n.estatus === 'APLICADO'
                        ? `<span class="badge badge-success">${escHtml(n.estatus)}</span>`
                        : `<span class="badge badge-warning">${escHtml(n.estatus)}</span>`);

                var $card = $(`
                    <div class="rel-node-card ${currentCls}" data-node-id="${escHtml(n.id)}"
                         data-obj="${escHtml(n.objType)}" data-entry="${n.docEntry}" data-trans="${n.transId || 0}">
                        <div class="rel-node-header ${headerCls}">
                            <span><i class="fas ${escHtml(n.icon || 'fa-file')} mr-1"></i> ${escHtml(n.tipo)}</span>
                            <span>#${escHtml(n.docNum)}</span>
                        </div>
                        <div class="rel-node-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Fecha:</span>
                                <b>${escHtml(n.fecha)}</b>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total:</span>
                                <b>${fmtMoney(n.total)} ${escHtml(n.moneda)}</b>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Saldo / Disp.:</span>
                                <span class="${parseFloat(n.saldo) > 0.005 ? 'text-danger font-weight-bold' : 'text-muted'}">${fmtMoney(n.saldo)}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                                ${statusBadge}
                                <small class="text-primary"><i class="fas fa-eye"></i> Detalle</small>
                            </div>
                        </div>
                    </div>
                `);

                $card.on('click', function () {
                    abrirDetalleDocumento($(this).data('obj'), $(this).data('entry'), $(this).data('trans'));
                });

                $col.append($card);
            });

            $canvas.append($col);
        });

        setTimeout(function () {
            dibujarConectoresSVG(edges);
        }, 60);
    }

    function dibujarConectoresSVG(edges) {
        var wrapper = document.getElementById('relMapWrapper');
        var canvas  = document.getElementById('relMapCanvas');
        var svg     = document.getElementById('relMapSvg');
        if (!wrapper || !canvas || !svg) return;

        var width  = Math.max(wrapper.scrollWidth, canvas.scrollWidth + 40);
        var height = Math.max(wrapper.scrollHeight, canvas.scrollHeight + 40);
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);

        var colorMap = {
            flow: '#6c757d',
            payment: '#28a745',
            downpayment: '#e0a800',
            recon: '#17a2b8'
        };

        var defs = `
            <defs>
                <marker id="arrow-flow" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#6c757d"/></marker>
                <marker id="arrow-payment" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#28a745"/></marker>
                <marker id="arrow-downpayment" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#e0a800"/></marker>
                <marker id="arrow-recon" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#17a2b8"/></marker>
            </defs>
        `;

        var wrapperRect = wrapper.getBoundingClientRect();
        var pathsHtml   = defs;

        edges.forEach(function (edge) {
            var elFrom = canvas.querySelector(`[data-node-id="${edge.from}"]`);
            var elTo   = canvas.querySelector(`[data-node-id="${edge.to}"]`);
            if (!elFrom || !elTo) return;

            var r1 = elFrom.getBoundingClientRect();
            var r2 = elTo.getBoundingClientRect();

            var sameCol = Math.abs(r1.left - r2.left) < 30;
            var x1, y1, x2, y2, cx1, cy1, cx2, cy2;

            if (sameCol) {
                x1 = (r1.right - wrapperRect.left) + wrapper.scrollLeft;
                y1 = (r1.top + r1.height / 2 - wrapperRect.top) + wrapper.scrollTop;
                x2 = (r2.right - wrapperRect.left) + wrapper.scrollLeft;
                y2 = (r2.top + r2.height / 2 - wrapperRect.top) + wrapper.scrollTop;
                cx1 = x1 + 45; cy1 = y1;
                cx2 = x2 + 45; cy2 = y2;
            } else if (r1.left < r2.left) {
                x1 = (r1.right - wrapperRect.left) + wrapper.scrollLeft;
                y1 = (r1.top + r1.height / 2 - wrapperRect.top) + wrapper.scrollTop;
                x2 = (r2.left - wrapperRect.left) + wrapper.scrollLeft;
                y2 = (r2.top + r2.height / 2 - wrapperRect.top) + wrapper.scrollTop;
                var dx = Math.max(35, (x2 - x1) / 2);
                cx1 = x1 + dx; cy1 = y1;
                cx2 = x2 - dx; cy2 = y2;
            } else {
                x1 = (r1.left - wrapperRect.left) + wrapper.scrollLeft;
                y1 = (r1.top + r1.height / 2 - wrapperRect.top) + wrapper.scrollTop;
                x2 = (r2.right - wrapperRect.left) + wrapper.scrollLeft;
                y2 = (r2.top + r2.height / 2 - wrapperRect.top) + wrapper.scrollTop;
                var dx2 = Math.max(35, (x1 - x2) / 2);
                cx1 = x1 - dx2; cy1 = y1;
                cx2 = x2 + dx2; cy2 = y2;
            }

            var type  = colorMap[edge.type] ? edge.type : 'flow';
            var color = colorMap[type];
            var dash  = type === 'recon' ? 'stroke-dasharray="5,4"' : '';

            pathsHtml += `<path d="M ${x1} ${y1} C ${cx1} ${cy1}, ${cx2} ${cy2}, ${x2} ${y2}" fill="none" stroke="${color}" stroke-width="2" ${dash} marker-end="url(#arrow-${type})"/>`;

            if (edge.label) {
                var mx = (x1 + cx1 + cx2 + x2) / 4;
                var my = (y1 + cy1 + cy2 + y2) / 4 - 6;
                pathsHtml += `<text x="${mx}" y="${my}" fill="${color}" font-size="10.5" font-weight="700" text-anchor="middle">${escHtml(edge.label)}</text>`;
            }
        });

        svg.innerHTML = pathsHtml;
    }

    function abrirDetalleDocumento(objType, docEntry, transId) {
        $('#detailTitle').text('Cargando documento...');
        $('#detailBodyContent').empty();
        $('#detailLoading').removeClass('d-none');
        $('#modalDocDetail').modal('show');

        $.ajax({
            url: "<?= site_url('admin/clientStatementSAP/getDocumentDetailAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: $('#idEmpresa').val(),
                objType:   objType,
                docEntry:  docEntry,
                transId:   transId || 0,
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
                currentDetailRef = {
                    objType:  d.objType,
                    docEntry: d.docEntry,
                    transId:  d.transId || 0,
                    label:    `${d.tipo} #${d.docNum}`
                };

                $('#detailTitle').html(`<span class="badge badge-${d.badge} mr-2">${escHtml(d.tipo)}</span> Folio #${escHtml(d.docNum)} <small class="text-muted">(DocEntry: ${d.docEntry})</small>`);
                $('#detailBodyContent').html(construirHtmlDetalle(d));
            },
            error: function (jqXHR) {
                $('#detailLoading').addClass('d-none');
                $('#detailBodyContent').html(`<div class="alert alert-danger">${escHtml(jqXHR.responseText || 'Error de servidor')}</div>`);
            }
        });
    }

    function construirHtmlDetalle(d) {
        // Encabezado en grid
        var headerHtml = '<div class="row bg-light border rounded p-2 mb-3 small">';
        Object.keys(d.header || {}).forEach(function (k) {
            headerHtml += `<div class="col-md-4 col-sm-6 mb-1"><span class="text-muted">${escHtml(k)}:</span> <b>${escHtml(d.header[k] || '-')}</b></div>`;
        });
        headerHtml += '</div>';

        var bodyHtml = '';

        if (d.mode === 'MARKETING') {
            bodyHtml += `
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped small">
                        <thead class="bg-corp-red text-center">
                            <tr>
                                <th>Artículo</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Almacén</th>
                                <th>Precio Unit.</th>
                                <th>% Desc.</th>
                                <th>IVA</th>
                                <th>Total Línea</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            (d.lines || []).forEach(function (l) {
                bodyHtml += `
                    <tr>
                        <td><b>${escHtml(l.col1)}</b></td>
                        <td>${escHtml(l.col2)}</td>
                        <td class="text-center">${escHtml(l.col3)}</td>
                        <td class="text-center">${escHtml(l.extra)}</td>
                        <td class="text-right">${fmtNum(l.col4)}</td>
                        <td class="text-right">${fmtNum(l.col5)}%</td>
                        <td class="text-right">${fmtNum(l.col6)}</td>
                        <td class="text-right font-weight-bold">${fmtNum(l.col7)}</td>
                    </tr>
                `;
            });
            bodyHtml += `</tbody></table></div>`;

            var t = d.totals || {};
            bodyHtml += `
                <div class="row justify-content-end mt-2">
                    <div class="col-md-4">
                        <table class="table table-sm table-bordered small mb-0">
                            <tr><td>Subtotal</td><td class="text-right"><b>${fmtMoney(t.Subtotal)}</b></td></tr>
                            ${parseFloat(t.AnticipoApl || 0) > 0 ? `<tr><td>Anticipo Aplicado</td><td class="text-right text-warning"><b>- ${fmtMoney(t.AnticipoApl)}</b></td></tr>` : ''}
                            <tr><td>IVA</td><td class="text-right"><b>${fmtMoney(t.IVA)}</b></td></tr>
                            <tr class="table-active"><td><b>Total</b></td><td class="text-right text-danger"><b>${fmtMoney(t.Total)}</b></td></tr>
                            <tr><td>Pagado a la fecha</td><td class="text-right text-success"><b>${fmtMoney(t.Pagado)}</b></td></tr>
                            <tr><td>Saldo actual documento</td><td class="text-right"><b>${fmtMoney(t.SaldoActual)}</b></td></tr>
                        </table>
                    </div>
                </div>
            `;
        } else if (d.mode === 'PAYMENT') {
            bodyHtml += `
                <h6 class="font-weight-bold text-danger mb-2">Documentos Pagados en este Pago</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped small">
                        <thead class="bg-corp-red text-center">
                            <tr>
                                <th>Tipo Documento</th>
                                <th>Folio</th>
                                <th>Fecha Doc.</th>
                                <th>Total Documento</th>
                                <th>Importe Aplicado</th>
                                <th>Ver</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            if ((d.lines || []).length === 0) {
                bodyHtml += '<tr><td colspan="6" class="text-center text-muted">Pago recibido a cuenta (sin facturas aplicadas directamente en RCT2).</td></tr>';
            } else {
                d.lines.forEach(function (l) {
                    bodyHtml += `
                        <tr>
                            <td>${escHtml(l.tipoDoc)}</td>
                            <td class="text-center"><b>${escHtml(l.folio)}</b></td>
                            <td class="text-center">${escHtml(l.fecha)}</td>
                            <td class="text-right">${fmtMoney(l.totalDoc)}</td>
                            <td class="text-right text-success font-weight-bold">${fmtMoney(l.aplicado)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-dark" onclick="abrirDetalleDocumento('${escHtml(l.objType)}', ${l.docEntry}, 0)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            bodyHtml += '</tbody></table></div>';

            var tp = d.totals || {};
            bodyHtml += `
                <div class="row justify-content-end mt-2">
                    <div class="col-md-4">
                        <table class="table table-sm table-bordered small mb-0">
                            <tr><td>Efectivo</td><td class="text-right">${fmtMoney(tp.Efectivo)}</td></tr>
                            <tr><td>Transferencia</td><td class="text-right">${fmtMoney(tp.Transferencia)}</td></tr>
                            <tr><td>Cheque</td><td class="text-right">${fmtMoney(tp.Cheque)}</td></tr>
                            <tr><td>Tarjeta</td><td class="text-right">${fmtMoney(tp.Tarjeta)}</td></tr>
                            <tr><td>Pago a Cuenta</td><td class="text-right text-info"><b>${fmtMoney(tp.PagoACuenta)}</b></td></tr>
                            <tr class="table-active"><td><b>Total Pago</b></td><td class="text-right text-success"><b>${fmtMoney(tp.Total)}</b></td></tr>
                        </table>
                    </div>
                </div>
            `;
        } else {
            bodyHtml += `
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped small">
                        <thead class="bg-corp-red text-center">
                            <tr>
                                <th>Cuenta</th>
                                <th>Nombre Cuenta</th>
                                <th>Socio / Auxiliar</th>
                                <th>Sucursal</th>
                                <th>Comentario</th>
                                <th>Débito</th>
                                <th>Crédito</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            (d.lines || []).forEach(function (l) {
                bodyHtml += `
                    <tr>
                        <td><b>${escHtml(l.account)}</b></td>
                        <td>${escHtml(l.acctName)}</td>
                        <td>${escHtml(l.shortName)}</td>
                        <td>${escHtml(l.bplName)}</td>
                        <td>${escHtml(l.memo)}</td>
                        <td class="text-right">${l.debit > 0 ? fmtNum(l.debit) : ''}</td>
                        <td class="text-right">${l.credit > 0 ? fmtNum(l.credit) : ''}</td>
                    </tr>
                `;
            });
            var tj = d.totals || {};
            bodyHtml += `
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="5" class="text-right">Totales:</td>
                                <td class="text-right">${fmtMoney(tj.TotalDebito)}</td>
                                <td class="text-right">${fmtMoney(tj.TotalCredito)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
        }

        return headerHtml + bodyHtml;
    }
</script>
<?= $this->endSection() ?>