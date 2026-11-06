<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<!-- Card Principal -->
<div class="card card-default">
    <div class="card-header">
        <div class="float-right">
            <div class="btn-group">
                <button class="btn btn-primary btnAddSihistorial_eq" data-toggle="modal" data-target="#modalAddSihistorial_eq">
                    <i class="fa fa-plus"></i> Agregar Registro
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableSihistorial_eq" class="table table-striped table-hover va-middle tableSihistorial_eq">
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>U_ItemCode</th>
                                <th>U_Fecha</th>
                                <th>U_TimeUltReg</th>
                                <th>U_OdoUltReg</th>
                                <th>U_HoroUltReg</th>
                                <th>U_OdoHist</th>
                                <th>U_HoroHist</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sihistorial_eq -->
<div class="modal fade" id="modalAddSihistorial_eq" tabindex="-1" role="dialog" aria-labelledby="modalAddSihistorial_eq" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear / Editar Historial de Equipo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-sihistorial_eq" class="form-horizontal">
                    <!-- Bandera para distinguir Alta (0) de Cambio (1) -->
                    <input type="hidden" id="isEdit" name="isEdit" value="0">

                    <div class="form-group row">
                        <label for="Code" class="col-sm-3 col-form-label">Code</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                </div>
                                <input type="text" class="form-control" name="Code" id="Code" placeholder="Dejar en blanco para autogenerar">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="Name" class="col-sm-3 col-form-label">Name (Servicio) <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tools"></i></span>
                                </div>
                                <input type="text" class="form-control" name="Name" id="Name" placeholder="Ej. SERVICIO MOTOR" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_ItemCode" class="col-sm-3 col-form-label">U_ItemCode (Equipo) <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-truck-monster"></i></span>
                                </div>
                                <input type="text" class="form-control" name="U_ItemCode" id="U_ItemCode" placeholder="Ej. EX-020" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_Fecha" class="col-sm-3 col-form-label">U_Fecha <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="date" class="form-control" name="U_Fecha" id="U_Fecha" min="2000-01-01" max="2100-12-31" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_TimeUltReg" class="col-sm-3 col-form-label">U_TimeUltReg (HHMM)</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                </div>
                                <input type="number" step="1" min="0" max="2359" class="form-control" name="U_TimeUltReg" id="U_TimeUltReg" value="1000" placeholder="Formato 24h sin puntos: Ej. 1000 = 10:00, 1430 = 14:30">
                            </div>
                            <small class="form-text text-muted">Formato 24 hrs (0 a 2359). Ejemplo: <strong>830</strong> (08:30 AM), <strong>1000</strong> (10:00 AM), <strong>1645</strong> (04:45 PM).</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_OdoUltReg" class="col-sm-3 col-form-label">U_OdoUltReg</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tachometer-alt"></i></span>
                                </div>
                                <input type="number" step="any" min="0" class="form-control" name="U_OdoUltReg" id="U_OdoUltReg" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_HoroUltReg" class="col-sm-3 col-form-label">U_HoroUltReg</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                                </div>
                                <input type="number" step="any" min="0" class="form-control" name="U_HoroUltReg" id="U_HoroUltReg" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_OdoHist" class="col-sm-3 col-form-label">U_OdoHist</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-history"></i></span>
                                </div>
                                <input type="number" step="any" min="0" class="form-control" name="U_OdoHist" id="U_OdoHist" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_HoroHist" class="col-sm-3 col-form-label">U_HoroHist</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-history"></i></span>
                                </div>
                                <input type="number" step="any" min="0" class="form-control" name="U_HoroHist" id="U_HoroHist" value="0">
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveSihistorial_eq"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    /**
     * Valida que un número entero represente una hora SAP válida (HHMM)
     */
    function esHoraSapValida(valor) {
        if (valor === '' || valor === null || valor === undefined) return false;
        var strVal = String(valor).trim();
        // Solo dígitos enteros
        if (!/^\d+$/.test(strVal)) return false;

        var num = parseInt(strVal, 10);
        if (num < 0 || num > 2359) return false;

        var horas = Math.floor(num / 100);
        var minutos = num % 100;

        return (horas >= 0 && horas <= 23) && (minutos >= 0 && minutos <= 59);
    }

    // Inicializar DataTable
    var tableSihistorial_eq = $('#tableSihistorial_eq').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/sihistorial_eq') ?>',
            method: 'GET',
            dataType: "json"
        },
        columnDefs: [{
            orderable: false,
            targets: [0],
            searchable: false
        }],
        columns: [
            {
                "data": function (data) {
                    return `<td class="text-right py-0 align-middle">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-warning btnEditSihistorial_eq" data-toggle="modal" data-code="${data.Code}" data-target="#modalAddSihistorial_eq"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-danger btn-delete" data-id="${data.Code}"><i class="fas fa-trash"></i></button>
                            </div>
                            </td>`;
                }
            },
            {'data': 'Code'},
            {'data': 'Name'},
            {'data': 'U_ItemCode'},
            {'data': 'U_Fecha'},
            {'data': 'U_TimeUltReg'},
            {'data': 'U_OdoUltReg'},
            {'data': 'U_HoroUltReg'},
            {'data': 'U_OdoHist'},
            {'data': 'U_HoroHist'}
        ]
    });

    // Abrir modal para nuevo registro
    $(document).on('click', '.btnAddSihistorial_eq', function (e) {
        $("#form-sihistorial_eq")[0].reset();
        $("#isEdit").val("0");
        $("#Code").removeAttr("readonly");
        $("#U_TimeUltReg").val("1000");
        $("#U_OdoUltReg, #U_HoroUltReg, #U_OdoHist, #U_HoroHist").val("0");
        $("#btnSaveSihistorial_eq").removeAttr("disabled");
    });

    // Guardar o Actualizar con validaciones previas
    $(document).on('click', '#btnSaveSihistorial_eq', function (e) {
        var isEdit       = $("#isEdit").val();
        var Code         = $("#Code").val().trim();
        var Name         = $("#Name").val().trim();
        var U_ItemCode   = $("#U_ItemCode").val().trim();
        var U_Fecha      = $("#U_Fecha").val().trim();
        var U_TimeUltReg = $("#U_TimeUltReg").val().trim();
        var U_OdoUltReg  = $("#U_OdoUltReg").val().trim();
        var U_HoroUltReg = $("#U_HoroUltReg").val().trim();
        var U_OdoHist    = $("#U_OdoHist").val().trim();
        var U_HoroHist   = $("#U_HoroHist").val().trim();

        // 1. Validar obligatorios
        if (Name === '' || U_ItemCode === '' || U_Fecha === '') {
            Swal.fire({
                icon: "warning",
                title: "Campos requeridos",
                text: "Los campos Name (Servicio), U_ItemCode (Equipo) y U_Fecha son obligatorios."
            });
            return;
        }

        // 2. Validar fecha lógica
        var fechaPartes = U_Fecha.split('-');
        var anio = parseInt(fechaPartes[0], 10);
        if (fechaPartes.length !== 3 || isNaN(anio) || anio < 2000 || anio > 2100) {
            Swal.fire({
                icon: "warning",
                title: "Fecha inválida",
                text: "Ingrese una fecha válida (año entre 2000 y 2100)."
            });
            return;
        }

        // 3. Validar U_TimeUltReg en formato HHMM (0 a 2359, minutos 00 a 59)
        if (!esHoraSapValida(U_TimeUltReg)) {
            Swal.fire({
                icon: "error",
                title: "Hora inválida en U_TimeUltReg",
                html: "El valor <b>" + U_TimeUltReg + "</b> no es una hora válida.<br><br>" +
                      "Debe ser un número entero en formato 24h (<b>HHMM</b>) entre <b>0</b> y <b>2359</b>, " +
                      "donde las horas van de 00 a 23 y los minutos de 00 a 59 (ejemplo: <b>1000</b> para las 10:00)."
            });
            return;
        }

        // 4. Validar que odómetros y horómetros no sean negativos
        var camposNumericos = [
            { nombre: 'U_OdoUltReg', valor: U_OdoUltReg },
            { nombre: 'U_HoroUltReg', valor: U_HoroUltReg },
            { nombre: 'U_OdoHist', valor: U_OdoHist },
            { nombre: 'U_HoroHist', valor: U_HoroHist }
        ];

        for (var i = 0; i < camposNumericos.length; i++) {
            var numVal = parseFloat(camposNumericos[i].valor);
            if (isNaN(numVal) || numVal < 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Valor inválido",
                    text: "El campo " + camposNumericos[i].nombre + " debe ser un número mayor o igual a 0."
                });
                return;
            }
        }

        $("#btnSaveSihistorial_eq").attr("disabled", true);

        var datos = new FormData();
        datos.append("isEdit", isEdit);
        datos.append("Code", Code);
        datos.append("Name", Name);
        datos.append("U_ItemCode", U_ItemCode);
        datos.append("U_Fecha", U_Fecha);
        datos.append("U_TimeUltReg", parseInt(U_TimeUltReg, 10));
        datos.append("U_OdoUltReg", U_OdoUltReg);
        datos.append("U_HoroUltReg", U_HoroUltReg);
        datos.append("U_OdoHist", U_OdoHist);
        datos.append("U_HoroHist", U_HoroHist);

        $.ajax({
            url: "<?= base_url('admin/sihistorial_eq/save') ?>",
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
                    tableSihistorial_eq.ajax.reload(null, false);
                    $("#btnSaveSihistorial_eq").removeAttr("disabled");
                    $('#modalAddSihistorial_eq').modal('hide');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: respuesta.message || "Error desconocido"
                    });
                    $("#btnSaveSihistorial_eq").removeAttr("disabled");
                }
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            var errorMsg = jqXHR.responseJSON?.message || jqXHR.responseText || "Error en el servidor";
            Swal.fire({
                icon: "error",
                title: "Validación",
                text: errorMsg
            });
            $("#btnSaveSihistorial_eq").removeAttr("disabled");
        });
    });

    // Cargar datos al editar
    $(".tableSihistorial_eq").on("click", ".btnEditSihistorial_eq", function () {
        var code = $(this).attr("data-code");
        var datos = new FormData();
        datos.append("Code", code);

        $("#btnSaveSihistorial_eq").removeAttr("disabled");

        $.ajax({
            url: "<?= base_url('admin/sihistorial_eq/getSihistorial_eq') ?>",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#isEdit").val("1");
                $("#Code").val(respuesta["Code"]).attr("readonly", true);
                $("#Name").val(respuesta["Name"]);
                $("#U_ItemCode").val(respuesta["U_ItemCode"]);
                $("#U_Fecha").val(respuesta["U_Fecha"]);
                // Limpiar posibles dos puntos o comas al cargar la hora (ej. "52:92" -> 5292)
                var rawTime = String(respuesta["U_TimeUltReg"] ?? '0').replace(/[:,]/g, '');
                $("#U_TimeUltReg").val(parseInt(rawTime, 10) || 0);
                $("#U_OdoUltReg").val(parseFloat(String(respuesta["U_OdoUltReg"]).replace(/,/g, '')) || 0);
                $("#U_HoroUltReg").val(parseFloat(String(respuesta["U_HoroUltReg"]).replace(/,/g, '')) || 0);
                $("#U_OdoHist").val(parseFloat(String(respuesta["U_OdoHist"]).replace(/,/g, '')) || 0);
                $("#U_HoroHist").val(parseFloat(String(respuesta["U_HoroHist"]).replace(/,/g, '')) || 0);
            }
        });
    });

    // Eliminar registro
    $(".tableSihistorial_eq").on("click", ".btn-delete", function () {
        var code = $(this).attr("data-id");
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
                    url: `<?= base_url('admin/sihistorial_eq') ?>/` + encodeURIComponent(code),
                    method: 'DELETE',
                }).done((data, textStatus, jqXHR) => {
                    Toast.fire({
                        icon: 'success',
                        title: jqXHR.statusText,
                    });
                    tableSihistorial_eq.ajax.reload(null, false);
                }).fail((error) => {
                    Toast.fire({
                        icon: 'error',
                        title: error.responseJSON?.messages?.error || "Error al eliminar",
                    });
                });
            }
        });
    });

    $(function () {
        $("#modalAddSihistorial_eq").draggable();
    });
</script>
<?= $this->endSection() ?>