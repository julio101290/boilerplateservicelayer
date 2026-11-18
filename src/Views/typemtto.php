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
                <button class="btn btn-primary btnAddTypemtto" data-toggle="modal" data-target="#modalAddTypemtto">
                    <i class="fa fa-plus"></i> Agregar Tipo de Mantenimiento
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableTypemtto" class="table table-striped table-hover va-middle tableTypemtto">
                        <thead>
                            <tr>
                                <th style="width: 110px;">Acciones</th>
                                <th>Code</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Typemtto -->
<div class="modal fade" id="modalAddTypemtto" tabindex="-1" role="dialog" aria-labelledby="modalAddTypemtto" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear / Editar Tipo de Mantenimiento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-typemtto" class="form-horizontal">
                    <!-- Bandera para distinguir Alta (0) de Cambio (1) -->
                    <input type="hidden" id="isEdit" name="isEdit" value="0">

                    <div class="form-group row">
                        <label for="Code" class="col-sm-3 col-form-label">Code <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                </div>
                                <input type="text" class="form-control text-uppercase" name="Code" id="Code" maxlength="50" placeholder="Ej. SERVICIO MOTOR" required>
                            </div>
                            <small class="form-text text-muted">Clave única del tipo de mantenimiento en SAP (no modificable al editar).</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="Name" class="col-sm-3 col-form-label">Name <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tools"></i></span>
                                </div>
                                <input type="text" class="form-control text-uppercase" name="Name" id="Name" maxlength="100" placeholder="Ej. SERVICIO MOTOR" required>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveTypemtto"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var syncNameConCode = true;

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Inicializar DataTable
    var tableTypemtto = $('#tableTypemtto').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/typemtto') ?>',
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
                    var safeCode = escapeHtml(data.Code);
                    return `<td class="text-right py-0 align-middle">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-warning btnEditTypemtto" data-toggle="modal" data-code="${safeCode}" data-target="#modalAddTypemtto"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-danger btn-delete" data-code="${safeCode}"><i class="fas fa-trash"></i></button>
                            </div>
                            </td>`;
                }
            },
            {'data': 'Code'},
            {'data': 'Name'}
        ]
    });

    // En altas, autocompletar Name conforme se escribe Code mientras el usuario no edite Name manualmente
    $(document).on('input', '#Code', function () {
        if ($("#isEdit").val() === "0" && syncNameConCode) {
            $("#Name").val($(this).val());
        }
    });

    $(document).on('input', '#Name', function () {
        if ($("#isEdit").val() === "0") {
            syncNameConCode = ($(this).val() === $("#Code").val());
        }
    });

    // Abrir modal para nuevo registro
    $(document).on('click', '.btnAddTypemtto', function () {
        $("#form-typemtto")[0].reset();
        $("#isEdit").val("0");
        $("#Code").removeAttr("readonly");
        $("#btnSaveTypemtto").removeAttr("disabled");
        syncNameConCode = true;
    });

    // Guardar o Actualizar
    $(document).on('click', '#btnSaveTypemtto', function () {
        var isEdit = $("#isEdit").val();
        var Code   = isEdit === "1" ? $("#Code").val() : $("#Code").val().trim().toUpperCase();
        var Name   = $("#Name").val().trim().toUpperCase();

        if (Code.trim() === '' || Name === '') {
            Swal.fire({
                icon: "warning",
                title: "Campos requeridos",
                text: "Los campos Code y Name son obligatorios."
            });
            return;
        }

        $("#btnSaveTypemtto").attr("disabled", true);

        var datos = new FormData();
        datos.append("isEdit", isEdit);
        datos.append("Code", Code);
        datos.append("Name", Name);

        $.ajax({
            url: "<?= base_url('admin/typemtto/save') ?>",
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
                    tableTypemtto.ajax.reload(null, false);
                    $("#btnSaveTypemtto").removeAttr("disabled");
                    $('#modalAddTypemtto').modal('hide');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: respuesta.message || "Error desconocido"
                    });
                    $("#btnSaveTypemtto").removeAttr("disabled");
                }
            }
        }).fail(function (jqXHR) {
            var errorMsg = jqXHR.responseJSON?.message || jqXHR.responseText || "Error en el servidor";
            Swal.fire({
                icon: "error",
                title: "Validación",
                text: errorMsg
            });
            $("#btnSaveTypemtto").removeAttr("disabled");
        });
    });

    // Cargar datos al editar
    $(".tableTypemtto").on("click", ".btnEditTypemtto", function () {
        var code = $(this).attr("data-code");
        var datos = new FormData();
        datos.append("Code", code);

        $("#btnSaveTypemtto").removeAttr("disabled");
        syncNameConCode = false;

        $.ajax({
            url: "<?= base_url('admin/typemtto/getTypemtto') ?>",
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
            }
        });
    });

    // Eliminar registro con validación de dependencias
    $(".tableTypemtto").on("click", ".btn-delete", function () {
        var code = $(this).attr("data-code");

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
                var datos = new FormData();
                datos.append("Code", code);

                $.ajax({
                    url: "<?= base_url('admin/typemtto/delete') ?>",
                    method: "POST",
                    data: datos,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: "json"
                }).done((data) => {
                    Toast.fire({
                        icon: 'success',
                        title: data.message || "Registro eliminado correctamente",
                    });
                    tableTypemtto.ajax.reload(null, false);
                }).fail((jqXHR) => {
                    var msg = jqXHR.responseJSON?.message || "Error al eliminar";
                    Swal.fire({
                        icon: jqXHR.status === 409 ? 'warning' : 'error',
                        title: jqXHR.status === 409 ? 'No se puede eliminar' : 'Error',
                        text: msg
                    });
                });
            }
        });
    });

    $(function () {
        $("#modalAddTypemtto").draggable();
    });
</script>
<?= $this->endSection() ?>