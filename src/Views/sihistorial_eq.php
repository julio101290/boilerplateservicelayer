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
                        <label for="Name" class="col-sm-3 col-form-label">Name (Servicio)</label>
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
                        <label for="U_ItemCode" class="col-sm-3 col-form-label">U_ItemCode (Equipo)</label>
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
                        <label for="U_Fecha" class="col-sm-3 col-form-label">U_Fecha</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                                <input type="date" class="form-control" name="U_Fecha" id="U_Fecha" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_TimeUltReg" class="col-sm-3 col-form-label">U_TimeUltReg</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                </div>
                                <input type="number" step="any" class="form-control" name="U_TimeUltReg" id="U_TimeUltReg" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="U_OdoUltReg" class="col-sm-3 col-form-label">U_OdoUltReg</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tachometer-alt"></i></span>
                                </div>
                                <input type="number" step="any" class="form-control" name="U_OdoUltReg" id="U_OdoUltReg" value="0">
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
                                <input type="number" step="any" class="form-control" name="U_HoroUltReg" id="U_HoroUltReg" value="0">
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
                                <input type="number" step="any" class="form-control" name="U_OdoHist" id="U_OdoHist" value="0">
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
                                <input type="number" step="any" class="form-control" name="U_HoroHist" id="U_HoroHist" value="0">
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
        $("#U_TimeUltReg, #U_OdoUltReg, #U_HoroUltReg, #U_OdoHist, #U_HoroHist").val("0");
        $("#btnSaveSihistorial_eq").removeAttr("disabled");
    });

    // Guardar o Actualizar
    $(document).on('click', '#btnSaveSihistorial_eq', function (e) {
        var isEdit       = $("#isEdit").val();
        var Code         = $("#Code").val();
        var Name         = $("#Name").val();
        var U_ItemCode   = $("#U_ItemCode").val();
        var U_Fecha      = $("#U_Fecha").val();
        var U_TimeUltReg = $("#U_TimeUltReg").val();
        var U_OdoUltReg  = $("#U_OdoUltReg").val();
        var U_HoroUltReg = $("#U_HoroUltReg").val();
        var U_OdoHist    = $("#U_OdoHist").val();
        var U_HoroHist   = $("#U_HoroHist").val();

        $("#btnSaveSihistorial_eq").attr("disabled", true);

        var datos = new FormData();
        datos.append("isEdit", isEdit);
        datos.append("Code", Code);
        datos.append("Name", Name);
        datos.append("U_ItemCode", U_ItemCode);
        datos.append("U_Fecha", U_Fecha);
        datos.append("U_TimeUltReg", U_TimeUltReg);
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
                title: "Oops...",
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
                $("#U_TimeUltReg").val(parseFloat(String(respuesta["U_TimeUltReg"]).replace(/,/g, '')) || 0);
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