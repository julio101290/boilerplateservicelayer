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
    .table-mtto th {
        font-size: 0.78rem;
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .table-mtto td {
        font-size: 0.80rem;
        vertical-align: middle !important;
        white-space: nowrap;
    }
    .row-vencido {
        background-color: #fff5f5 !important;
    }
    .row-por-vencer {
        background-color: #fffdf0 !important;
    }
</style>

<input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<!-- TARJETAS KPI RESUMEN -->
<div class="row mb-2">
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-corp-red"><i class="fas fa-tools"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold">Total Programados</span>
                <span class="info-box-number h4 mb-0" id="kpiTotal">0</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-danger">Vencidos</span>
                <span class="info-box-number h4 mb-0 text-danger" id="kpiVencidos">0</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-warning"><i class="fas fa-clock text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-warning">Por Vencer (0.01 - 50)</span>
                <span class="info-box-number h4 mb-0 text-warning" id="kpiPorVencer">0</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text font-weight-bold text-success">No Vencidos</span>
                <span class="info-box-number h4 mb-0 text-success" id="kpiNoVencidos">0</span>
            </div>
        </div>
    </div>
</div>

<!-- CARD PRINCIPAL DE FILTROS Y REPORTE -->
<div class="card card-corp-red card-outline shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
        <h3 class="card-title font-weight-bold" style="color: var(--corp-red);">
            <i class="fas fa-clipboard-list mr-1"></i> <?= esc($title) ?>
        </h3>
        <div class="card-tools ml-auto">
            <span class="badge badge-secondary p-2">
                <i class="fas fa-eye mr-1"></i> Reporte de Solo Lectura (Origen: SAP HANA)
            </span>
        </div>
    </div>

    <div class="card-body p-3">
        <!-- BARRA DE FILTROS -->
        <div class="form-row align-items-end bg-light p-2 rounded border mb-3">
            <!-- EMPRESA -->
            <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                    <i class="fas fa-building mr-1"></i> Empresa
                </label>
                <select class="form-control form-control-sm select2" id="idEmpresa" style="width:100%;">
                    <?php foreach ($empresas as $emp) : ?>
                        <option value="<?= $emp['id'] ?>" <?= (int)$emp['id'] === (int)$idEmpresa ? 'selected' : '' ?>>
                            <?= $emp['id'] ?> - <?= esc($emp['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ESTADO ([%0]) -->
            <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                    <i class="fas fa-traffic-light mr-1"></i> Estado
                </label>
                <select class="form-control form-control-sm select2" id="filtroEstado" style="width:100%;">
                    <option value="TODOS" selected>TODOS</option>
                    <option value="VENCIDOS">VENCIDOS</option>
                    <option value="POR VENCER">POR VENCER</option>
                    <option value="NO VENCIDOS">NO VENCIDOS</option>
                </select>
            </div>

            <!-- SUCURSAL (BPLId / BPLName) -->
            <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                    <i class="fas fa-store mr-1"></i> Sucursal
                </label>
                <select class="form-control form-control-sm select2" id="filtroSucursal" style="width:100%;">
                    <option value="TODOS">TODOS</option>
                </select>
            </div>

            <!-- NÚMERO DE ARTÍCULO / EQUIPO (ItemCode) -->
            <div class="form-group col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                    <i class="fas fa-truck-monster mr-1"></i> Número de Artículo (Equipo)
                </label>
                <select class="form-control form-control-sm select2" id="filtroArticulo" style="width:100%;">
                    <option value="TODOS">TODOS</option>
                </select>
            </div>

            <!-- TIPO MTTO (U_TipoMtto) -->
            <div class="form-group col-lg-2 col-md-4 col-sm-6 col-12 mb-2">
                <label class="mb-1 font-weight-bold" style="color: var(--corp-red);">
                    <i class="fas fa-wrench mr-1"></i> Tipo MTTO
                </label>
                <select class="form-control form-control-sm select2" id="filtroTipoMtto" style="width:100%;">
                    <option value="TODOS">TODOS</option>
                </select>
            </div>

            <!-- BOTÓN CONSULTAR -->
            <div class="form-group col-lg-1 col-md-2 col-sm-6 col-12 mb-2">
                <button type="button" class="btn btn-corp-red btn-sm btn-block" id="btnConsultar" title="Ejecutar consulta">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </div>
        </div>

        <!-- TABLA DE RESULTADOS -->
        <div class="table-responsive">
            <table id="tableReportMtto" class="table table-bordered table-hover table-sm w-100 table-mtto">
                <thead class="bg-corp-red text-center">
                    <tr>
                        <th>Sucursal</th>
                        <th>DocEntry</th>
                        <th>Artículo</th>
                        <th>Descripción Equipo</th>
                        <th>Tipo MTTO</th>
                        <th>Fecha Últ. Mtto</th>
                        <th>Folio Últ. Mtto</th>
                        <th class="bg-dark">Horo. Mtto</th>
                        <th class="bg-dark">Hrs Últ. Mtto</th>
                        <th class="bg-dark">Horo. Actual</th>
                        <th class="bg-dark">Horo. Acum</th>
                        <th class="bg-dark">Horo. Dif</th>
                        <th class="bg-dark">Estatus Horo.</th>
                        <th class="bg-secondary">Odo. Mtto</th>
                        <th class="bg-secondary">Kms Últ. Mtto</th>
                        <th class="bg-secondary">Odo. Actual</th>
                        <th class="bg-secondary">Odo. Acum</th>
                        <th class="bg-secondary">Odo. Dif</th>
                        <th class="bg-secondary">Estatus Odo.</th>
                        <th>Semanas</th>
                        <th>Estatus Sem.</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var tableReportMtto = null;
    var isUpdatingFilters = false;

    $(document).ready(function () {
        $('.select2').select2();

        inicializarDataTable();
        cargarCatalogosFiltros(true);

        $('#idEmpresa').on('change', function () {
            cargarCatalogosFiltros(true);
        });

        $('#filtroSucursal').on('change', function () {
            if (isUpdatingFilters) return;
            recargarArticulosPorSucursal();
        });

        $('#filtroEstado, #filtroArticulo, #filtroTipoMtto').on('change', function () {
            if (isUpdatingFilters) return;
            consultarReporte();
        });

        $('#btnConsultar').on('click', function () {
            consultarReporte();
        });
    });

    function cargarCatalogosFiltros(ejecutarConsultaAlTerminar) {
        isUpdatingFilters = true;
        var idEmpresa = $('#idEmpresa').val();

        $.ajax({
            url: "<?= site_url('admin/reportMttoSAP/getFiltersAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: idEmpresa,
                bplId: 'TODOS',
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);

                var $suc = $('#filtroSucursal').empty();
                $suc.append(new Option('TODOS', 'TODOS', true, true));
                if (res.sucursales && res.sucursales.length > 0) {
                    res.sucursales.forEach(function (s) {
                        $suc.append(new Option(s.text, s.bplId, false, false));
                    });
                }
                $suc.trigger('change.select2');

                var $art = $('#filtroArticulo').empty();
                $art.append(new Option('TODOS', 'TODOS', true, true));
                if (res.articulos && res.articulos.length > 0) {
                    res.articulos.forEach(function (a) {
                        $art.append(new Option(a.text, a.id, false, false));
                    });
                }
                $art.trigger('change.select2');

                var $tipo = $('#filtroTipoMtto').empty();
                $tipo.append(new Option('TODOS', 'TODOS', true, true));
                if (res.tiposMtto && res.tiposMtto.length > 0) {
                    res.tiposMtto.forEach(function (t) {
                        $tipo.append(new Option(t.text, t.id, false, false));
                    });
                }
                $tipo.trigger('change.select2');

                isUpdatingFilters = false;

                if (ejecutarConsultaAlTerminar) {
                    consultarReporte();
                }
            },
            error: function (jqXHR) {
                isUpdatingFilters = false;
                console.error("Error getFiltersAjax:", jqXHR.responseText);
            }
        });
    }

    function recargarArticulosPorSucursal() {
        isUpdatingFilters = true;
        var idEmpresa = $('#idEmpresa').val();
        var bplId     = $('#filtroSucursal').val();

        $.ajax({
            url: "<?= site_url('admin/reportMttoSAP/getFiltersAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: idEmpresa,
                bplId: bplId,
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);

                var $art = $('#filtroArticulo').empty();
                $art.append(new Option('TODOS', 'TODOS', true, true));
                if (res.articulos && res.articulos.length > 0) {
                    res.articulos.forEach(function (a) {
                        $art.append(new Option(a.text, a.id, false, false));
                    });
                }
                $art.trigger('change.select2');

                isUpdatingFilters = false;
                consultarReporte();
            },
            error: function () {
                isUpdatingFilters = false;
                consultarReporte();
            }
        });
    }

    function inicializarDataTable() {
        tableReportMtto = $('#tableReportMtto').DataTable({
            processing: true,
            serverSide: false,
            responsive: false,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[15, 25, 50, 100, -1], [15, 25, 50, 100, "Todos"]],
            order: [[2, 'asc'], [4, 'asc']],
            data: [],
            createdRow: function (row, data) {
                if (data.EstadoGeneral === 'VENCIDO') {
                    $(row).addClass('row-vencido');
                } else if (data.EstadoGeneral === 'POR VENCER') {
                    $(row).addClass('row-por-vencer');
                }
            },
            columns: [
                { data: 'BPLName' },
                { data: 'DocEntry', className: 'text-center' },
                { data: 'ItemCode', className: 'font-weight-bold text-danger' },
                { data: 'ItemName' },
                {
                    data: 'U_TipoMtto',
                    className: 'text-center font-weight-bold',
                    render: function (data) {
                        return `<span class="badge badge-dark px-2 py-1">${data}</span>`;
                    }
                },
                { data: 'FechaUltMtto', className: 'text-center' },
                { data: 'FolioUltMtto', className: 'text-center' },
                {
                    data: 'HoroMtto',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'HrsUltMtto',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'HorometroActual',
                    className: 'text-right font-weight-bold',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'HoroAcum',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'HoroDif',
                    className: 'text-right font-weight-bold',
                    render: function (data) {
                        var val = parseFloat(data || 0);
                        var color = val < 0 ? 'text-danger' : (val > 0 && val <= 50 ? 'text-warning' : 'text-dark');
                        return `<span class="${color}">${val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
                    }
                },
                {
                    data: 'HoroEstatus',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (data === 'VENCIDO') {
                            return '<span class="badge badge-danger">VENCIDO</span>';
                        }
                        var dif = parseFloat(row.HoroDif || 0);
                        if (dif >= 0.01 && dif <= 50) {
                            return '<span class="badge badge-warning">POR VENCER</span>';
                        }
                        return '';
                    }
                },
                {
                    data: 'OdoMtto',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'KmsUltMtto',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'OdometroActual',
                    className: 'text-right font-weight-bold',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'OdoAcum',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 2, '')
                },
                {
                    data: 'OroDif',
                    className: 'text-right font-weight-bold',
                    render: function (data) {
                        var val = parseFloat(data || 0);
                        var color = val < 0 ? 'text-danger' : (val > 0 && val <= 50 ? 'text-warning' : 'text-dark');
                        return `<span class="${color}">${val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;
                    }
                },
                {
                    data: 'OdoEstatus',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (data === 'VENCIDO') {
                            return '<span class="badge badge-danger">VENCIDO</span>';
                        }
                        var dif = parseFloat(row.OroDif || 0);
                        if (dif >= 0.01 && dif <= 50) {
                            return '<span class="badge badge-warning">POR VENCER</span>';
                        }
                        return '';
                    }
                },
                {
                    data: 'Semanas',
                    className: 'text-right',
                    render: $.fn.dataTable.render.number(',', '.', 0, '')
                },
                {
                    data: 'SemanasEstatus',
                    className: 'text-center',
                    render: function (data) {
                        return data === 'VENCIDO'
                            ? '<span class="badge badge-danger">VENCIDO</span>'
                            : '';
                    }
                }
            ]
        });
    }

    function consultarReporte() {
        var $btn = $('#btnConsultar');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: "<?= site_url('admin/reportMttoSAP/getReportDataAjax') ?>",
            method: "POST",
            dataType: "json",
            data: {
                idEmpresa: $('#idEmpresa').val(),
                estado:    $('#filtroEstado').val(),
                bplId:     $('#filtroSucursal').val(),
                itemCode:  $('#filtroArticulo').val(),
                tipoMtto:  $('#filtroTipoMtto').val(),
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            },
            success: function (res) {
                $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Filtrar');
                if (res.token) $('.txt_csrfname').val(res.token);

                if (res.error) {
                    Swal.fire('Error', res.message || 'Error al consultar SAP HANA', 'error');
                    return;
                }

                if (res.kpis) {
                    $('#kpiTotal').text(res.kpis.total || 0);
                    $('#kpiVencidos').text(res.kpis.vencidos || 0);
                    $('#kpiPorVencer').text(res.kpis.porVencer || 0);
                    $('#kpiNoVencidos').text(res.kpis.noVencidos || 0);
                }

                tableReportMtto.clear().rows.add(res.data || []).draw();
            },
            error: function (jqXHR) {
                $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Filtrar');
                Swal.fire('Error', jqXHR.responseText || 'Error de conexión con el servidor', 'error');
            }
        });
    }
</script>
<?= $this->endSection() ?>