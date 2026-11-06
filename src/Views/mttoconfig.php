<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<style>
    .table-mtto-detail th {
        background-color: #343a40;
        color: #ffffff;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle !important;
    }
    .table-mtto-detail td {
        vertical-align: middle !important;
        padding: 0.45rem !important;
    }
    .input-inactive {
        background-color: #f4f6f9 !important;
        color: #999 !important;
    }
    .input-active-metric {
        border-color: #007bff !important;
        background-color: #f0f8ff !important;
        font-weight: 600;
    }
    .header-box-sap {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-left: 4px solid #007bff;
        border-radius: 0.35rem;
        padding: 15px;
        margin-bottom: 18px;
    }
</style>

<!-- Card Principal -->
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-cogs text-primary mr-1"></i> Planes de Mantenimiento Configurados
        </h3>
        <div class="float-right">
            <button class="btn btn-primary btn-sm btnAddMttoconfig shadow-sm" data-toggle="modal" data-target="#modalAddMttoconfig">
                <i class="fa fa-plus-circle mr-1"></i> Nueva Configuración
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableMttoconfig" class="table table-striped table-hover table-bordered va-middle tableMttoconfig">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 110px;" class="text-center">Acciones</th>
                        <th style="width: 90px;">DocEntry</th>
                        <th style="width: 120px;">Tipo</th>
                        <th style="width: 140px;">Código Tipo</th>
                        <th>Nombre / Descripción</th>
                        <th style="width: 130px;" class="text-center">Servicios</th>
                        <th style="width: 120px;">Fecha Alta</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Maestro-Detalle ConfigMtto -->
<div class="modal fade" id="modalAddMttoconfig" tabindex="-1" role="dialog" aria-labelledby="modalAddMttoconfigLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title" id="modalTitleMttoconfig">
                    <i class="fas fa-wrench mr-2 text-info"></i> Configuración de Orden de Mantenimiento (ConfigMtto)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="form-mttoconfig" onsubmit="return false;">
                    <input type="hidden" id="isEdit" name="isEdit" value="0">

                    <!-- Encabezado estilo SAP mejorado -->
                    <div class="header-box-sap">
                        <div class="form-row">
                            <!-- DocEntry -->
                            <div class="form-group col-md-2 mb-2">
                                <label for="DocEntry" class="small font-weight-bold text-uppercase text-muted">DocEntry</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    </div>
                                    <input type="text" class="form-control font-weight-bold" id="DocEntry" name="DocEntry" placeholder="Auto" readonly>
                                </div>
                            </div>

                            <!-- Tipo (Equipo / TipoMq) -->
                            <div class="form-group col-md-2 mb-2">
                                <label for="U_Tipo" class="small font-weight-bold text-uppercase text-muted">Tipo <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" id="U_Tipo" name="U_Tipo" required>
                                    <option value="Equipo">Equipo (OITM)</option>
                                    <option value="TipoMq">Tipo Maquinaria (@TIPOEQUIPO)</option>
                                </select>
                            </div>

                            <!-- Código Tipo (Buscador Select2 conectado a SAP) -->
                            <div class="form-group col-md-4 mb-2">
                                <label for="U_Code" class="small font-weight-bold text-uppercase text-muted">Código Tipo <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm select2-codigo" id="U_Code" name="U_Code" style="width: 100%;" required>
                                    <option value="">Seleccione un código...</option>
                                </select>
                            </div>

                            <!-- Nombre del Equipo / Tipo -->
                            <div class="form-group col-md-4 mb-2">
                                <label for="U_Name" class="small font-weight-bold text-uppercase text-muted">Nombre</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-truck-Pickup"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="U_Name" name="U_Name" placeholder="Nombre del equipo o maquinaria">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de herramientas del Detalle -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0 font-weight-bold text-secondary">
                                <i class="fas fa-list-ol mr-1"></i> Matriz de Servicios y Periodicidad
                            </h6>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info" id="btnLoadAllServices" title="Agrega todos los servicios de @TYPEMTTO">
                                <i class="fas fa-magic mr-1"></i> Cargar Todos los Servicios
                            </button>
                            <button type="button" class="btn btn-success" id="btnAddRow">
                                <i class="fas fa-plus mr-1"></i> Agregar Servicio
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Detalle -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-hover table-sm table-mtto-detail mb-0" id="tableDetailMtto">
                            <thead>
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th style="min-width: 260px;">TipoMtto (@TYPEMTTO)</th>
                                    <th style="width: 200px;">Periodicidad</th>
                                    <th style="width: 140px;" class="text-right">Kms</th>
                                    <th style="width: 140px;" class="text-right">Horas</th>
                                    <th style="width: 120px;" class="text-center">Semana</th>
                                    <th style="width: 55px;" class="text-center"><i class="fas fa-trash-alt"></i></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDetailMtto">
                                <!-- Filas dinámicas -->
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="btnSaveMttoconfig">
                    <i class="fas fa-save mr-1"></i> Guardar Configuración
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var catalogoTiposMtto = [];
    var catalogoCodigos   = [];

    // 1. Cargar catálogo de @TYPEMTTO al iniciar
    function cargarCatalogoTiposMtto() {
        $.ajax({
            url: "<?= base_url('admin/mttoconfig/getTiposMtto') ?>",
            method: "GET",
            dataType: "json",
            success: function (res) {
                if (Array.isArray(res)) {
                    catalogoTiposMtto = res;
                }
            }
        });
    }

    // 2. Cargar códigos disponibles según Tipo ('Equipo' o 'TipoMq')
    function cargarCodigosDisponibles(tipo, selectedCode = '', selectedName = '') {
        $("#U_Code").empty().append('<option value="">Cargando disponibles...</option>');

        $.ajax({
            url: "<?= base_url('admin/mttoconfig/getCodigosDisponibles') ?>",
            method: "POST",
            data: { tipo: tipo, currentCode: selectedCode },
            dataType: "json",
            success: function (lista) {
                catalogoCodigos = lista || [];
                $("#U_Code").empty().append('<option value="">-- Seleccione Código --</option>');

                let encontrado = false;
                catalogoCodigos.forEach(function (item) {
                    let isSelected = (item.Code === selectedCode) ? 'selected' : '';
                    if (isSelected) encontrado = true;
                    $("#U_Code").append(
                        `<option value="${item.Code}" data-name="${ item.Name ? item.Name.replace(/"/g, '&quot;') : '' }" ${isSelected}>
                            ${item.Code} - ${item.Name || ''}
                        </option>`
                    );
                });

                // Si estamos editando y por alguna razón no vino en el query, lo aseguramos en el select
                if (selectedCode !== '' && !encontrado) {
                    $("#U_Code").append(
                        `<option value="${selectedCode}" data-name="${selectedName.replace(/"/g, '&quot;')}" selected>
                            ${selectedCode} - ${selectedName}
                        </option>`
                    );
                }

                $("#U_Code").trigger('change.select2');
            }
        });
    }

    // 3. Construir opciones HTML de @TYPEMTTO
    function buildOpcionesTipoMtto(valorSeleccionado = '') {
        let html = '<option value="">-- Seleccione Servicio --</option>';
        let existe = false;

        catalogoTiposMtto.forEach(function (code) {
            let sel = (code === valorSeleccionado) ? 'selected' : '';
            if (sel) existe = true;
            html += `<option value="${code}" ${sel}>${code}</option>`;
        });

        if (valorSeleccionado !== '' && !existe) {
            html += `<option value="${valorSeleccionado}" selected>${valorSeleccionado}</option>`;
        }
        return html;
    }

    // 4. Agregar fila a la tabla de detalle
    function agregarFilaDetalle(datos = {}) {
        let tipoMtto = datos.U_TipoMtto || '';
        let periodi  = datos.U_Periodi || 'Horas';
        let kms      = (datos.U_Kms !== undefined && datos.U_Kms !== null) ? parseFloat(String(datos.U_Kms).replace(/,/g, '')) || 0 : 0;
        let horas    = (datos.U_Horas !== undefined && datos.U_Horas !== null) ? parseFloat(String(datos.U_Horas).replace(/,/g, '')) || 0 : 0;
        let semana   = (datos.U_Semana !== undefined && datos.U_Semana !== null) ? datos.U_Semana : '';

        let tr = $(`
            <tr class="fila-mtto">
                <td class="text-center font-weight-bold num-linea"></td>
                <td>
                    <select class="form-control form-control-sm select-tipomtto" style="width: 100%;" required>
                        ${buildOpcionesTipoMtto(tipoMtto)}
                    </select>
                </td>
                <td>
                    <select class="form-control form-control-sm select-periodi">
                        <option value="" ${periodi === '' ? 'selected' : ''}>-- Sin definir --</option>
                        <option value="Horas" ${periodi === 'Horas' ? 'selected' : ''}>Horas</option>
                        <option value="Ocurra Primero" ${periodi === 'Ocurra Primero' ? 'selected' : ''}>Ocurra Primero</option>
                        <option value="Kilometros" ${periodi === 'Kilometros' ? 'selected' : ''}>Kilometros</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="any" min="0" class="form-control form-control-sm text-right input-kms" value="${kms}">
                </td>
                <td>
                    <input type="number" step="any" min="0" class="form-control form-control-sm text-right input-horas" value="${horas}">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm text-center input-semana" value="${semana}" placeholder="Opcional">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-xs btnRemoveRow" title="Quitar línea">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `);

        $("#tbodyDetailMtto").append(tr);

        // Inicializar Select2 en el nuevo renglón
        tr.find('.select-tipomtto').select2({
            dropdownParent: $('#modalAddMttoconfig'),
            theme: 'bootstrap4'
        });

        actualizarEstiloPeriodicidad(tr);
        renumerarFilas();
    }

    // 5. Resaltar campos Kms / Horas según la Periodicidad elegida
    function actualizarEstiloPeriodicidad(tr) {
        let periodi = tr.find('.select-periodi').val();
        let inputKms = tr.find('.input-kms');
        let inputHrs = tr.find('.input-horas');

        inputKms.removeClass('input-active-metric input-inactive');
        inputHrs.removeClass('input-active-metric input-inactive');

        if (periodi === 'Horas') {
            inputHrs.addClass('input-active-metric');
            inputKms.addClass('input-inactive');
        } else if (periodi === 'Kilometros') {
            inputKms.addClass('input-active-metric');
            inputHrs.addClass('input-inactive');
        } else if (periodi === 'Ocurra Primero') {
            inputKms.addClass('input-active-metric');
            inputHrs.addClass('input-active-metric');
        }
    }

    // 6. Renumerar columna #
    function renumerarFilas() {
        $("#tbodyDetailMtto tr.fila-mtto").each(function (index) {
            $(this).find('.num-linea').text(index + 1);
        });
    }

    $(document).ready(function () {
        cargarCatalogoTiposMtto();

        $('#U_Code').select2({
            dropdownParent: $('#modalAddMttoconfig'),
            theme: 'bootstrap4'
        });

        // DataTable Principal
        var tableMttoconfig = $('#tableMttoconfig').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            order: [[1, 'desc']],
            ajax: {
                url: '<?= base_url('admin/mttoconfig') ?>',
                method: 'GET',
                dataType: "json"
            },
            columnDefs: [{
                orderable: false,
                targets: [0, 5],
                searchable: false
            }],
            columns: [
                {
                    "data": function (data) {
                        return `<div class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-warning btnEditMttoconfig" data-toggle="modal" data-docentry="${data.DocEntry}" data-target="#modalAddMttoconfig" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-delete" data-id="${data.DocEntry}" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>`;
                    }
                },
                { 'data': 'DocEntry', 'className': 'font-weight-bold' },
                {
                    'data': 'U_Tipo',
                    'render': function (data) {
                        if (data === 'Equipo') {
                            return '<span class="badge badge-primary px-2 py-1"><i class="fas fa-truck mr-1"></i> Equipo</span>';
                        }
                        return '<span class="badge badge-info px-2 py-1"><i class="fas fa-layer-group mr-1"></i> TipoMq</span>';
                    }
                },
                { 'data': 'U_Code', 'className': 'font-weight-bold text-primary' },
                { 'data': 'U_Name' },
                {
                    'data': 'TotalServicios',
                    'className': 'text-center',
                    'render': function (data) {
                        return `<span class="badge badge-pill badge-secondary px-2 py-1">${data || 0} servicios</span>`;
                    }
                },
                { 'data': 'CreateDate' }
            ]
        });

        // Cambio entre 'Equipo' y 'TipoMq'
        $('#U_Tipo').on('change', function () {
            if ($("#isEdit").val() === "0") {
                $("#U_Name").val("");
                cargarCodigosDisponibles($(this).val());
            }
        });

        // Al seleccionar un Código Tipo, autorrellenar el campo Nombre
        $('#U_Code').on('change', function () {
            let nombre = $(this).find(':selected').data('name');
            if (nombre !== undefined) {
                $('#U_Name').val(nombre);
            }
        });

        // Cambio de Periodicidad en cualquier fila
        $(document).on('change', '.select-periodi', function () {
            let tr = $(this).closest('tr');
            let val = $(this).val();
            if (val === 'Horas' && parseFloat(tr.find('.input-kms').val()) === 0) {
                tr.find('.input-kms').val(0);
            } else if (val === 'Kilometros' && parseFloat(tr.find('.input-horas').val()) === 0) {
                tr.find('.input-horas').val(0);
            }
            actualizarEstiloPeriodicidad(tr);
        });

        // Evitar duplicados de TipoMtto al cambiar el select
        $(document).on('change', '.select-tipomtto', function () {
            let actualSelect = this;
            let valor = $(actualSelect).val();
            if (!valor) return;

            let repetido = false;
            $('.select-tipomtto').not(actualSelect).each(function () {
                if ($(this).val() === valor) {
                    repetido = true;
                }
            });

            if (repetido) {
                Toast.fire({
                    icon: 'warning',
                    title: `El servicio "${valor}" ya está en la lista.`
                });
                $(actualSelect).val('').trigger('change.select2');
            }
        });

        // Botón Agregar Servicio individual
        $('#btnAddRow').on('click', function () {
            agregarFilaDetalle({ U_Periodi: 'Horas', U_Kms: 0, U_Horas: 300 });
        });

        // Botón Cargar Todos los Servicios de @TYPEMTTO
        $('#btnLoadAllServices').on('click', function () {
            if (catalogoTiposMtto.length === 0) {
                Toast.fire({ icon: 'info', title: 'No se encontraron servicios en @TYPEMTTO' });
                return;
            }

            let existentes = [];
            $('.select-tipomtto').each(function () {
                if ($(this).val()) existentes.push($(this).val());
            });

            // Si hay una sola fila completamente vacía, la removemos antes de poblar
            if ($("#tbodyDetailMtto tr.fila-mtto").length === 1 && existentes.length === 0) {
                $("#tbodyDetailMtto").empty();
            }

            let agregados = 0;
            catalogoTiposMtto.forEach(function (servicio) {
                if (!existentes.includes(servicio)) {
                    agregarFilaDetalle({
                        U_TipoMtto: servicio,
                        U_Periodi: 'Horas',
                        U_Kms: 0,
                        U_Horas: 300,
                        U_Semana: ''
                    });
                    agregados++;
                }
            });

            Toast.fire({
                icon: 'success',
                title: `Se agregaron ${agregados} servicios a la matriz.`
            });
        });

        // Eliminar fila del detalle
        $(document).on('click', '.btnRemoveRow', function () {
            $(this).closest('tr').remove();
            renumerarFilas();
        });

        // Abrir modal para Nuevo Registro
        $(document).on('click', '.btnAddMttoconfig', function () {
            $("#form-mttoconfig")[0].reset();
            $("#isEdit").val("0");
            $("#DocEntry").val("Nuevo");
            $("#U_Tipo").val("Equipo").prop("disabled", false);
            $("#U_Code").prop("disabled", false);
            $("#tbodyDetailMtto").empty();

            cargarCodigosDisponibles("Equipo");
            agregarFilaDetalle({ U_Periodi: 'Horas', U_Kms: 0, U_Horas: 300 });
            $("#btnSaveMttoconfig").prop("disabled", false);
        });

        // Cargar datos al Editar
        $(".tableMttoconfig").on("click", ".btnEditMttoconfig", function () {
            let docEntry = $(this).data("docentry");
            let datos = new FormData();
            datos.append("DocEntry", docEntry);

            $("#tbodyDetailMtto").empty();
            $("#btnSaveMttoconfig").prop("disabled", false);

            $.ajax({
                url: "<?= base_url('admin/mttoconfig/getMttoconfig') ?>",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (res) {
                    if (res.header) {
                        $("#isEdit").val("1");
                        $("#DocEntry").val(res.header.DocEntry);
                        $("#U_Tipo").val(res.header.U_Tipo);
                        $("#U_Name").val(res.header.U_Name);

                        cargarCodigosDisponibles(res.header.U_Tipo, res.header.U_Code, res.header.U_Name || '');

                        if (Array.isArray(res.details) && res.details.length > 0) {
                            res.details.forEach(function (linea) {
                                agregarFilaDetalle(linea);
                            });
                        } else {
                            agregarFilaDetalle();
                        }
                    }
                }
            });
        });

        // Guardar o Actualizar Maestro-Detalle
        $('#btnSaveMttoconfig').on('click', function () {
            let isEdit   = $("#isEdit").val();
            let docEntry = $("#DocEntry").val();
            let uTipo    = $("#U_Tipo").val();
            let uCode    = $("#U_Code").val();
            let uName    = $("#U_Name").val();

            if (!uCode) {
                Toast.fire({ icon: 'warning', title: 'Selecciona un Código Tipo válido.' });
                return;
            }

            let details = [];
            let errorLinea = false;

            $("#tbodyDetailMtto tr.fila-mtto").each(function (idx) {
                let tipoMtto = $(this).find('.select-tipomtto').val();
                let periodi  = $(this).find('.select-periodi').val();
                let kms      = $(this).find('.input-kms').val();
                let horas    = $(this).find('.input-horas').val();
                let semana   = $(this).find('.input-semana').val();

                if (!tipoMtto) {
                    errorLinea = true;
                    return false;
                }

                details.push({
                    U_TipoMtto: tipoMtto,
                    U_Periodi: periodi,
                    U_Kms: kms,
                    U_Horas: horas,
                    U_Semana: semana
                });
            });

            if (errorLinea) {
                Toast.fire({ icon: 'warning', title: 'Todas las filas deben tener seleccionado un Tipo de Mantenimiento.' });
                return;
            }

            if (details.length === 0) {
                Toast.fire({ icon: 'warning', title: 'Agrega al menos un servicio al detalle.' });
                return;
            }

            $("#btnSaveMttoconfig").prop("disabled", true);

            let datos = new FormData();
            datos.append("isEdit", isEdit);
            datos.append("DocEntry", isEdit === "1" ? docEntry : 0);
            datos.append("U_Tipo", uTipo);
            datos.append("U_Code", uCode);
            datos.append("U_Name", uName);
            datos.append("details", JSON.stringify(details));

            $.ajax({
                url: "<?= base_url('admin/mttoconfig/save') ?>",
                method: "POST",
                data: datos,
                cache: false,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function (respuesta) {
                    if (respuesta?.message?.includes("Guardado") || respuesta?.message?.includes("Actualizado")) {
                        Toast.fire({
                            icon: 'success',
                            title: respuesta.message
                        });
                        tableMttoconfig.ajax.reload(null, false);
                        $('#modalAddMttoconfig').modal('hide');
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: respuesta.message || "Error al guardar"
                        });
                    }
                    $("#btnSaveMttoconfig").prop("disabled", false);
                }
            }).fail(function (jqXHR) {
                let errorMsg = jqXHR.responseJSON?.message || jqXHR.responseText || "Error en el servidor";
                Swal.fire({
                    icon: "error",
                    title: "Error al guardar",
                    text: errorMsg
                });
                $("#btnSaveMttoconfig").prop("disabled", false);
            });
        });

        // Eliminar Configuración
        $(".tableMttoconfig").on("click", ".btn-delete", function () {
            let docEntry = $(this).data("id");
            Swal.fire({
                title: '<?= lang('boilerplate.global.sweet.title') ?>',
                text: "Se eliminará el encabezado y todas sus líneas de mantenimiento.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?= lang('boilerplate.global.sweet.confirm_delete') ?>'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: `<?= base_url('admin/mttoconfig') ?>/` + encodeURIComponent(docEntry),
                        method: 'DELETE',
                    }).done((data, textStatus, jqXHR) => {
                        Toast.fire({
                            icon: 'success',
                            title: jqXHR.statusText,
                        });
                        tableMttoconfig.ajax.reload(null, false);
                    }).fail((error) => {
                        Toast.fire({
                            icon: 'error',
                            title: error.responseJSON?.messages?.error || "Error al eliminar",
                        });
                    });
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>