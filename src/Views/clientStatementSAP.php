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
        color: #ffffff;
    }
    .badge-reserve {
        background-color: #d97706;
        color: #ffffff;
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

    /* Mapa de Relaciones Interactivo (Arrastrable + Zoom + Agrupación Expandible) */
    .modal-relmap-fullscreen {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0.6rem auto !important;
    }
    .modal-relmap-fullscreen .rel-map-wrapper {
        height: calc(100vh - 180px) !important;
    }
    .rel-map-wrapper {
        position: relative;
        overflow: auto;
        height: 520px;
        background-color: #f4f6f9;
        background-image: radial-gradient(#d5dce4 1px, transparent 1px);
        background-size: 20px 20px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        cursor: grab;
        user-select: none;
    }
    .rel-map-wrapper.is-panning {
        cursor: grabbing;
    }
    .rel-map-stage {
        position: relative;
        min-width: 100%;
        min-height: 100%;
    }
    .rel-map-canvas {
        position: relative;
        transform-origin: 0 0;
    }
    .rel-map-svg {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
        overflow: visible;
        z-index: 1;
    }
    .rel-node-card,
    .rel-group-card {
        position: absolute;
        width: 235px;
        background: #ffffff;
        border: 2px solid #cfd6df;
        border-radius: 7px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.09);
        z-index: 2;
        cursor: move;
        transition: box-shadow 0.12s ease, border-color 0.12s ease;
    }
    .rel-node-card:hover,
    .rel-group-card:hover {
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.16);
        border-color: var(--corp-red);
        z-index: 5;
    }
    .rel-node-card.is-dragging,
    .rel-group-card.is-dragging {
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);
        border-color: var(--corp-red);
        z-index: 20;
        opacity: 0.96;
    }
    .rel-node-card.is-current {
        border: 2.5px solid var(--corp-red);
        box-shadow: 0 0 0 4px rgba(168, 32, 32, 0.18);
    }
    .rel-group-card {
        border-style: dashed;
        border-width: 2px;
        background: #fffdf7;
    }
    .rel-node-header {
        padding: 6px 9px;
        font-size: 0.75rem;
        font-weight: 700;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .rel-node-body {
        padding: 7px 9px;
        font-size: 0.75rem;
        line-height: 1.38;
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

            <!-- FILTROS SECUNDARIOS -->
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
                        <option value="13">Facturas (Normales y Reserva)</option>
                        <option value="13_NORM">Solo Facturas Normales</option>
                        <option value="13_RES">Solo Facturas de Reserva</option>
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

<!-- MODAL: MAPA DE RELACIONES INTERACTIVO -->
<div class="modal fade" id="modalRelMap" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" id="modalRelMapDialog" role="document" style="max-width: 94%;">
        <div class="modal-content">
            <div class="modal-header bg-corp-red py-2">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-project-diagram mr-1"></i> Mapa de Relaciones <span id="relMapTitulo" class="font-weight-normal"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2">
                <!-- BARRA DE CONTROL DEL DIAGRAMA -->
                <div class="d-flex flex-wrap justify-content-between align-items-center bg-light border rounded px-2 py-1 mb-2" style="gap: 6px;">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-danger font-weight-bold" id="btnRelExpandAll" title="Expandir todos los grupos">
                            <i class="fas fa-expand-arrows-alt mr-1"></i> Expandir Todo
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnRelCollapseAll" title="Agrupar cuando hay muchos documentos">
                            <i class="fas fa-compress-arrows-alt mr-1"></i> Agrupar
                        </button>
                        <button type="button" class="btn btn-outline-dark" id="btnRelAutoLayout" title="Reordenar cuadros automáticamente">
                            <i class="fas fa-magic mr-1"></i> Auto-Ordenar
                        </button>
                    </div>

                    <div class="small text-muted d-none d-lg-block">
                        <i class="fas fa-arrows-alt text-danger mr-1"></i> Arrastra cualquier cuadro o el fondo para moverte &nbsp;|&nbsp;
                        <span id="relMapStats" class="font-weight-bold text-dark"></span>
                    </div>

                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="btnRelZoomOut" title="Alejar"><i class="fas fa-search-minus"></i></button>
                        <button type="button" class="btn btn-outline-secondary font-weight-bold" id="btnRelZoomReset" title="Restablecer Zoom">100%</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnRelZoomIn" title="Acercar"><i class="fas fa-search-plus"></i></button>
                        <button type="button" class="btn btn-outline-primary ml-1" id="btnRelFullscreen" title="Expandir / Restaurar ventana">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>

                <div id="relMapLoading" class="text-center py-5 d-none">
                    <i class="fas fa-spinner fa-spin fa-2x text-danger"></i>
                    <p class="mt-2 mb-0 text-muted">Construyendo mapa de relaciones en SAP HANA...</p>
                </div>

                <div id="relMapWrapper" class="rel-map-wrapper">
                    <div id="relMapStage" class="rel-map-stage">
                        <div id="relMapCanvas" class="rel-map-canvas">
                            <svg id="relMapSvg" class="rel-map-svg"></svg>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-1 small text-muted px-1">
                    <div>
                        <span class="badge badge-danger mr-1">Borde Rojo = Seleccionado</span>
                        <span class="badge badge-reserve mr-1">Factura Reserva</span>
                        <span class="badge badge-success mr-1">Línea Verde = Pago</span>
                        <span class="badge badge-warning mr-1">Ámbar = Anticipo</span>
                        <span class="badge badge-info">Punteada = Reconciliación</span>
                    </div>
                    <small>Clic en <b>Detalle</b> o doble clic en un cuadro para inspeccionar partidas.</small>
                </div>
            </div>
            <div class="modal-footer py-1">
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

    // Estado del motor interactivo del Mapa de Relaciones
    var relMapState = {
        rawNodes: [],
        rawEdges: [],
        visibleItems: [],
        visibleEdges: [],
        positions: {},
        expandedGroups: {},
        groupThreshold: 4,
        zoom: 1,
        canvasW: 1100,
        canvasH: 520,
        rafId: null
    };

    $(document).ready(function () {
        $('#idEmpresa, #filtroSucursal').select2();

        inicializarSelectCliente();
        inicializarDataTable();
        inicializarControlesMapaRelaciones();
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

        // Clic en fila -> abre Mapa de Relaciones
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

        // Mantener scroll correcto cuando se abre el modal de detalle encima del mapa de relaciones
        $('#modalDocDetail').on('hidden.bs.modal', function () {
            if ($('#modalRelMap').hasClass('show')) {
                $('body').addClass('modal-open');
            }
        });

        $('#modalRelMap').on('shown.bs.modal', function () {
            if (relMapState.visibleItems.length) {
                dibujarConectoresSVG();
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

                var k = res.kpis || {};
                var a = res.aging || {};
                $('#kpiSaldoCorte').text(fmtMoney(k.saldoCorte));
                $('#kpiSaldoInicial').text(fmtMoney(k.saldoInicial));
                $('#kpiVencido').text(fmtMoney(k.vencido));
                $('#kpiPorVencer').text(fmtMoney(a.porVencer));
                $('#kpiAnticipoEfvo').text(fmtMoney(k.anticipoEfectivo));
                $('#kpiAnticipoFact').text(fmtMoney(k.anticipoFacturacion));

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

    /* =========================================================================
     * MOTOR INTERACTIVO DEL MAPA DE RELACIONES
     * ========================================================================= */
    function inicializarControlesMapaRelaciones() {
        $('#btnRelExpandAll').on('click', function () {
            obtenerClavesGrupos().forEach(function (gk) {
                relMapState.expandedGroups[gk] = true;
            });
            relMapState.positions = {};
            reconstruirMapaRelaciones();
        });

        $('#btnRelCollapseAll').on('click', function () {
            relMapState.expandedGroups = {};
            relMapState.positions = {};
            reconstruirMapaRelaciones();
        });

        $('#btnRelAutoLayout').on('click', function () {
            relMapState.positions = {};
            reconstruirMapaRelaciones();
        });

        $('#btnRelZoomIn').on('click', function () { aplicarZoomMapa(relMapState.zoom + 0.15); });
        $('#btnRelZoomOut').on('click', function () { aplicarZoomMapa(relMapState.zoom - 0.15); });
        $('#btnRelZoomReset').on('click', function () { aplicarZoomMapa(1); });

        $('#btnRelFullscreen').on('click', function () {
            var $dlg = $('#modalRelMapDialog');
            $dlg.toggleClass('modal-relmap-fullscreen');
            var isFull = $dlg.hasClass('modal-relmap-fullscreen');
            $(this).html(isFull ? '<i class="fas fa-compress"></i>' : '<i class="fas fa-expand"></i>');
        });

        // Paneo arrastrando el fondo del canvas
        var panning = false, panStartX = 0, panStartY = 0, panScrollL = 0, panScrollT = 0;
        $('#relMapWrapper').on('mousedown', function (e) {
            if ($(e.target).closest('.rel-node-card, .rel-group-card').length) return;
            panning = true;
            $(this).addClass('is-panning');
            panStartX  = e.clientX;
            panStartY  = e.clientY;
            panScrollL = this.scrollLeft;
            panScrollT = this.scrollTop;
        });

        $(document).on('mousemove.relpan', function (e) {
            if (!panning) return;
            var w = document.getElementById('relMapWrapper');
            if (!w) return;
            w.scrollLeft = panScrollL - (e.clientX - panStartX);
            w.scrollTop  = panScrollT - (e.clientY - panStartY);
        }).on('mouseup.relpan', function () {
            if (panning) {
                panning = false;
                $('#relMapWrapper').removeClass('is-panning');
            }
        });
    }

    function obtenerClavesGrupos() {
        var keys = {};
        relMapState.rawNodes.forEach(function (n) {
            var gk = `grp_${n.stage || 5}_${n.objType}_${n.isReserve ? 'R' : 'N'}`;
            keys[gk] = true;
        });
        return Object.keys(keys);
    }

    function aplicarZoomMapa(nuevoZoom) {
        relMapState.zoom = Math.max(0.45, Math.min(1.6, Math.round(nuevoZoom * 100) / 100));
        $('#btnRelZoomReset').text(Math.round(relMapState.zoom * 100) + '%');
        actualizarDimensionesCanvas();
    }

    function actualizarDimensionesCanvas() {
        var z = relMapState.zoom;
        $('#relMapCanvas').css({
            width: relMapState.canvasW + 'px',
            height: relMapState.canvasH + 'px',
            transform: `scale(${z})`
        });
        $('#relMapStage').css({
            width: Math.ceil(relMapState.canvasW * z) + 'px',
            height: Math.ceil(relMapState.canvasH * z) + 'px'
        });
        var svg = document.getElementById('relMapSvg');
        if (svg) {
            svg.setAttribute('width', relMapState.canvasW);
            svg.setAttribute('height', relMapState.canvasH);
        }
    }

    function abrirMapaRelaciones(objType, docEntry, transId, label) {
        $('#relMapTitulo').text(label ? `— ${label}` : '');
        $('#relMapCanvas').find('.rel-node-card, .rel-group-card').remove();
        $('#relMapSvg').empty();
        $('#relMapStats').text('');
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
                    Swal.fire('Error', res.message || 'No se pudo generar el mapa de relaciones.', 'error');
                    return;
                }

                relMapState.rawNodes       = res.map.nodes || [];
                relMapState.rawEdges       = res.map.edges || [];
                relMapState.positions      = {};
                relMapState.expandedGroups = {};
                relMapState.zoom           = 1;
                $('#btnRelZoomReset').text('100%');

                reconstruirMapaRelaciones();
            },
            error: function (jqXHR) {
                $('#relMapLoading').addClass('d-none');
                $('#relMapWrapper').removeClass('d-none');
                Swal.fire('Error', jqXHR.responseText || 'Error de servidor', 'error');
            }
        });
    }

    function reconstruirMapaRelaciones() {
        var nodes = relMapState.rawNodes;
        var edges = relMapState.rawEdges;

        if (!nodes.length) {
            $('#relMapCanvas').find('.rel-node-card, .rel-group-card').remove();
            $('#relMapSvg').empty();
            $('#relMapStats').text('0 documentos');
            return;
        }

        // 1. Agrupar nodos por (stage, objType, isReserve)
        var buckets = {};
        nodes.forEach(function (n) {
            var gk = `grp_${n.stage || 5}_${n.objType}_${n.isReserve ? 'R' : 'N'}`;
            if (!buckets[gk]) buckets[gk] = [];
            buckets[gk].push(n);
        });

        var visibleItems   = [];
        var nodeToVisualId = {};

        Object.keys(buckets).forEach(function (gk) {
            var list       = buckets[gk];
            var isExpanded = !!relMapState.expandedGroups[gk];

            if (list.length > relMapState.groupThreshold && !isExpanded) {
                var currentNodes = list.filter(function (n) { return n.isCurrent; });
                var otherNodes   = list.filter(function (n) { return !n.isCurrent; });

                currentNodes.forEach(function (n) {
                    visibleItems.push({ kind: 'node', id: n.id, stage: n.stage || 5, groupKey: gk, data: n });
                    nodeToVisualId[n.id] = n.id;
                });

                if (otherNodes.length > 0) {
                    var sumTotal = 0, sumSaldo = 0;
                    otherNodes.forEach(function (n) {
                        sumTotal += parseFloat(n.total || 0);
                        sumSaldo += parseFloat(n.saldo || 0);
                    });

                    var sample = otherNodes[0];
                    visibleItems.push({
                        kind: 'group',
                        id: gk,
                        stage: sample.stage || 5,
                        groupKey: gk,
                        count: otherNodes.length,
                        tipo: sample.tipo,
                        badge: sample.badge,
                        icon: sample.icon,
                        moneda: sample.moneda || 'MXP',
                        sumTotal: sumTotal,
                        sumSaldo: sumSaldo,
                        items: otherNodes
                    });

                    otherNodes.forEach(function (n) {
                        nodeToVisualId[n.id] = gk;
                    });
                }
            } else {
                list.forEach(function (n) {
                    visibleItems.push({
                        kind: 'node',
                        id: n.id,
                        stage: n.stage || 5,
                        groupKey: gk,
                        canCollapse: list.length > relMapState.groupThreshold,
                        data: n
                    });
                    nodeToVisualId[n.id] = n.id;
                });
            }
        });

        // 2. Remapear y fusionar aristas hacia nodos/grupos visibles
        var edgeAgg = {};
        edges.forEach(function (e) {
            var vFrom = nodeToVisualId[e.from];
            var vTo   = nodeToVisualId[e.to];
            if (!vFrom || !vTo || vFrom === vTo) return;

            var sig = `${vFrom}=>${vTo}|${e.type}`;
            if (!edgeAgg[sig]) {
                edgeAgg[sig] = { from: vFrom, to: vTo, type: e.type, labels: [], count: 0 };
            }
            edgeAgg[sig].count++;
            if (e.label) edgeAgg[sig].labels.push(e.label);
        });

        var visibleEdges = Object.keys(edgeAgg).map(function (sig) {
            var item = edgeAgg[sig];
            return {
                from: item.from,
                to: item.to,
                type: item.type,
                label: item.count > 1 ? `${item.count} vínculos` : (item.labels[0] || '')
            };
        });

        relMapState.visibleItems = visibleItems;
        relMapState.visibleEdges = visibleEdges;

        // 3. Layout automático inteligente en subcolumnas
        var byStage = {};
        visibleItems.forEach(function (it) {
            var st = it.stage || 5;
            if (!byStage[st]) byStage[st] = [];
            byStage[st].push(it);
        });

        var cardW = 235, cardH = 148, gapX = 95, gapY = 26, subGapX = 28;
        var maxRowsPerSubCol = 5;
        var cursorX = 32;
        var maxY = 460;

        Object.keys(byStage).map(Number).sort(function (a, b) { return a - b; }).forEach(function (st) {
            var colItems   = byStage[st];
            var numSubCols = Math.max(1, Math.ceil(colItems.length / maxRowsPerSubCol));
            var rowsPerCol = Math.ceil(colItems.length / numSubCols);

            colItems.forEach(function (it, idx) {
                var subCol = Math.floor(idx / rowsPerCol);
                var row    = idx % rowsPerCol;

                if (!relMapState.positions[it.id]) {
                    relMapState.positions[it.id] = {
                        x: cursorX + subCol * (cardW + subGapX),
                        y: 28 + row * (cardH + gapY)
                    };
                }
                var pos = relMapState.positions[it.id];
                if (pos.y + cardH + 60 > maxY) maxY = pos.y + cardH + 60;
            });

            cursorX += numSubCols * cardW + (numSubCols - 1) * subGapX + gapX;
        });

        var maxX = Math.max(1050, cursorX + 40);
        visibleItems.forEach(function (it) {
            var p = relMapState.positions[it.id];
            if (p) {
                if (p.x + cardW + 80 > maxX) maxX = p.x + cardW + 80;
                if (p.y + cardH + 80 > maxY) maxY = p.y + cardH + 80;
            }
        });

        relMapState.canvasW = maxX;
        relMapState.canvasH = maxY;
        actualizarDimensionesCanvas();

        // 4. Renderizar tarjetas en el DOM
        var $canvas = $('#relMapCanvas');
        $canvas.find('.rel-node-card, .rel-group-card').remove();

        visibleItems.forEach(function (it) {
            var pos = relMapState.positions[it.id];
            var $el;

            if (it.kind === 'group') {
                $el = $(`
                    <div class="rel-group-card" data-visual-id="${escHtml(it.id)}" style="left:${pos.x}px; top:${pos.y}px;">
                        <div class="rel-node-header badge-${escHtml(it.badge || 'secondary')}">
                            <span><i class="fas fa-layer-group mr-1"></i> ${escHtml(it.tipo)}</span>
                            <span class="badge badge-light text-dark">${it.count}</span>
                        </div>
                        <div class="rel-node-body">
                            <div class="text-muted mb-1"><b>${it.count} documentos agrupados</b></div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Suma Total:</span>
                                <b>${fmtMoney(it.sumTotal)}</b>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Suma Saldo:</span>
                                <span class="${it.sumSaldo > 0.005 ? 'text-danger font-weight-bold' : 'text-muted'}">${fmtMoney(it.sumSaldo)}</span>
                            </div>
                            <div class="mt-2 pt-1 border-top text-center">
                                <button type="button" class="btn btn-xs btn-corp-red btn-block btn-expand-group" data-gk="${escHtml(it.groupKey)}">
                                    <i class="fas fa-expand-arrows-alt mr-1"></i> Expandir los ${it.count} cuadros
                                </button>
                            </div>
                        </div>
                    </div>
                `);

                $el.find('.btn-expand-group').on('click', function (e) {
                    e.stopPropagation();
                    relMapState.expandedGroups[it.groupKey] = true;
                    relMapState.positions = {};
                    reconstruirMapaRelaciones();
                });
            } else {
                var n          = it.data;
                var currentCls = n.isCurrent ? 'is-current' : '';
                var statusBadge = n.cancelado
                    ? '<span class="badge badge-dark">CANCELADO</span>'
                    : `<span class="badge badge-${(n.estatus || '').indexOf('CERRAD') !== -1 || n.estatus === 'APLICADO' ? 'success' : 'warning'}">${escHtml(n.estatus)}</span>`;

                var collapseBtn = it.canCollapse
                    ? `<button type="button" class="btn btn-link btn-xs p-0 text-secondary btn-collapse-grp mr-2" data-gk="${escHtml(it.groupKey)}" title="Volver a agrupar"><i class="fas fa-compress-arrows-alt"></i></button>`
                    : '';

                $el = $(`
                    <div class="rel-node-card ${currentCls}" data-visual-id="${escHtml(it.id)}"
                         data-obj="${escHtml(n.objType)}" data-entry="${n.docEntry}" data-trans="${n.transId || 0}"
                         style="left:${pos.x}px; top:${pos.y}px;">
                        <div class="rel-node-header badge-${escHtml(n.badge || 'secondary')}">
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
                                <div>
                                    ${collapseBtn}
                                    <button type="button" class="btn btn-link btn-xs p-0 text-primary font-weight-bold btn-open-detail">
                                        <i class="fas fa-eye"></i> Detalle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                $el.find('.btn-open-detail').on('click', function (e) {
                    e.stopPropagation();
                    abrirDetalleDocumento(n.objType, n.docEntry, n.transId);
                });

                $el.find('.btn-collapse-grp').on('click', function (e) {
                    e.stopPropagation();
                    delete relMapState.expandedGroups[it.groupKey];
                    relMapState.positions = {};
                    reconstruirMapaRelaciones();
                });

                $el.on('dblclick', function (e) {
                    e.stopPropagation();
                    abrirDetalleDocumento(n.objType, n.docEntry, n.transId);
                });
            }

            habilitarArrastreTarjeta($el, it.id);
            $canvas.append($el);
        });

        $('#relMapStats').text(`${nodes.length} documento(s) • ${visibleItems.length} cuadro(s) visibles`);
        dibujarConectoresSVG();
    }

    function habilitarArrastreTarjeta($el, visualId) {
        $el.on('mousedown', function (e) {
            if ($(e.target).closest('button').length) return;
            e.preventDefault();
            e.stopPropagation();

            var startX = e.clientX;
            var startY = e.clientY;
            var orig   = relMapState.positions[visualId] || {
                x: parseFloat($el.css('left')) || 0,
                y: parseFloat($el.css('top')) || 0
            };

            $el.addClass('is-dragging');

            $(document).on('mousemove.reldrag', function (ev) {
                var z  = relMapState.zoom || 1;
                var nx = Math.max(10, Math.round(orig.x + (ev.clientX - startX) / z));
                var ny = Math.max(10, Math.round(orig.y + (ev.clientY - startY) / z));

                relMapState.positions[visualId] = { x: nx, y: ny };
                $el.css({ left: nx + 'px', top: ny + 'px' });

                var needResize = false;
                if (nx + $el.outerWidth() + 80 > relMapState.canvasW) {
                    relMapState.canvasW = nx + $el.outerWidth() + 120;
                    needResize = true;
                }
                if (ny + $el.outerHeight() + 80 > relMapState.canvasH) {
                    relMapState.canvasH = ny + $el.outerHeight() + 120;
                    needResize = true;
                }
                if (needResize) actualizarDimensionesCanvas();

                programarRedibujoSVG();
            });

            $(document).on('mouseup.reldrag', function () {
                $el.removeClass('is-dragging');
                $(document).off('mousemove.reldrag mouseup.reldrag');
                dibujarConectoresSVG();
            });
        });
    }

    function programarRedibujoSVG() {
        if (relMapState.rafId) return;
        relMapState.rafId = window.requestAnimationFrame(function () {
            relMapState.rafId = null;
            dibujarConectoresSVG();
        });
    }

    function dibujarConectoresSVG() {
        var canvas = document.getElementById('relMapCanvas');
        var svg    = document.getElementById('relMapSvg');
        if (!canvas || !svg) return;

        var colorMap = {
            flow: '#6c757d',
            payment: '#28a745',
            downpayment: '#e0a800',
            recon: '#17a2b8'
        };

        var pathsHtml = `
            <defs>
                <marker id="arrow-flow" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#6c757d"/></marker>
                <marker id="arrow-payment" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#28a745"/></marker>
                <marker id="arrow-downpayment" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#e0a800"/></marker>
                <marker id="arrow-recon" markerWidth="8" markerHeight="8" refX="7" refY="3" orient="auto"><path d="M0,0 L0,6 L7,3 z" fill="#17a2b8"/></marker>
            </defs>
        `;

        relMapState.visibleEdges.forEach(function (edge) {
            var elFrom = canvas.querySelector(`[data-visual-id="${edge.from}"]`);
            var elTo   = canvas.querySelector(`[data-visual-id="${edge.to}"]`);
            if (!elFrom || !elTo) return;

            var x1L = elFrom.offsetLeft, y1T = elFrom.offsetTop, w1 = elFrom.offsetWidth, h1 = elFrom.offsetHeight;
            var x2L = elTo.offsetLeft,   y2T = elTo.offsetTop,   w2 = elTo.offsetWidth,   h2 = elTo.offsetHeight;

            var x1, y1, x2, y2, cx1, cy1, cx2, cy2;
            var sameCol = Math.abs(x1L - x2L) < 40;

            if (sameCol) {
                x1  = x1L + w1; y1 = y1T + h1 / 2;
                x2  = x2L + w2; y2 = y2T + h2 / 2;
                cx1 = x1 + 55;  cy1 = y1;
                cx2 = x2 + 55;  cy2 = y2;
            } else if (x1L + w1 <= x2L + 20) {
                x1  = x1L + w1; y1 = y1T + h1 / 2;
                x2  = x2L;      y2 = y2T + h2 / 2;
                var dx = Math.max(45, (x2 - x1) / 2);
                cx1 = x1 + dx;  cy1 = y1;
                cx2 = x2 - dx;  cy2 = y2;
            } else {
                x1  = x1L;      y1 = y1T + h1 / 2;
                x2  = x2L + w2; y2 = y2T + h2 / 2;
                var dx2 = Math.max(45, (x1 - x2) / 2);
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

    /* =========================================================================
     * DETALLE DEL DOCUMENTO
     * ========================================================================= */
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