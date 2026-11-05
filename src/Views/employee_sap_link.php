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
                <button class="btn btn-primary btnAddEmployee_sap_link" data-toggle="modal" data-target="#modalAddEmployee_sap_link">
                    <i class="fa fa-plus"></i> <?= lang('employee_sap_link.add') ?>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableEmployee_sap_link" class="table table-striped table-hover va-middle tableEmployee_sap_link">
                        <thead>
                            <tr>
                                <th><?= lang('employee_sap_link.fields.actions') ?></th>
                                <th><?= lang('employee_sap_link.fields.idEmpresa') ?></th>
                                <th><?= lang('employee_sap_link.fields.iduser') ?></th>
                                <th><?= lang('employee_sap_link.fields.sapemployee') ?></th>
                                <th><?= lang('employee_sap_link.fields.created_at') ?></th>
                                <th><?= lang('employee_sap_link.fields.updated_at') ?></th>
                                <th><?= lang('employee_sap_link.fields.deleted_at') ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Employee_sap_link integrado en la misma vista -->
<div class="modal fade" id="modalAddEmployee_sap_link" tabindex="-1" role="dialog" aria-labelledby="modalAddEmployee_sap_link" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('employee_sap_link.createEdit') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-employee_sap_link" class="form-horizontal">
                    <input type="hidden" id="idEmployee_sap_link" name="idEmployee_sap_link" value="0">

                    <div class="form-group row">
                        <label for="idEmpresa" class="col-sm-2 col-form-label">Empresa</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                </div>
                                <select class="form-control idEmpresa" name="idEmpresa" id="idEmpresa" style="width:80%;">
                                    <option value="0">Seleccione empresa</option>
                                    <?php foreach ($empresas as $key => $value) : ?>
                                        <option value="<?= $value['id'] ?>" selected><?= $value['id'] ?> - <?= $value['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="iduser" class="col-sm-2 col-form-label"><?= lang('employee_sap_link.fields.iduser') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control iduser" name="iduser" id="iduser" style="width:80%;">
                                    <option value="0">Seleccione Usuario</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="sapemployee" class="col-sm-2 col-form-label"><?= lang('employee_sap_link.fields.sapemployee') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                </div>
                                <select class="form-control sapemployee" name="sapemployee" id="sapemployee" style="width:80%;">
                                    <option value="0">Seleccione Empleado SAP</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveEmployee_sap_link"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    // Inicializar DataTable
    var tableEmployee_sap_link = $('#tableEmployee_sap_link').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/employee_sap_link') ?>',
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
                                <button class="btn btn-warning btnEditEmployee_sap_link" data-toggle="modal" idEmployee_sap_link="${data.id}" data-target="#modalAddEmployee_sap_link"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-danger btn-delete" data-id="${data.id}"><i class="fas fa-trash"></i></button>
                            </div>
                            </td>`;
                }
            },
            {'data': 'nombreEmpresa'},
            {'data': 'username'},
            {'data': 'nameemployeesap'},
            {'data': 'created_at'},
            {'data': 'updated_at'},
            {'data': 'deleted_at'}
        ]
    });

    // Inicializar Select2 Empresa
    $("#idEmpresa").select2();

    // Inicializar Select2 Usuarios del Sistema
    $("#iduser").select2({
        ajax: {
            url: "<?= site_url('admin/employee_sap_link/getUsersAjaxSelect2') ?>",
            type: "post",
            dataType: 'json',
            delay: 500,
            data: function (params) {
                var idEmpresa = $('.idEmpresa').val();
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();

                return {
                    searchTerm: params.term || '',
                    idEmpresa: idEmpresa || '',
                    [csrfName]: csrfHash
                };
            },
            processResults: function (response) {
                if (response.token) {
                    $('.txt_csrfname').val(response.token);
                }
                return {
                    results: response.results || []
                };
            },
            cache: true
        }
    });

    // Inicializar Select2 Empleados SAP (consulta a OHEM)
    $("#sapemployee").select2({
        ajax: {
            url: "<?= site_url('admin/employee_sap_link/employeesSAPSelect2') ?>",
            type: "post",
            dataType: 'json',
            delay: 800,
            data: function (params) {
                var idEmpresa = $('.idEmpresa').val();
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();

                return {
                    searchTerm: params.term || '',
                    idEmpresa: idEmpresa || '',
                    [csrfName]: csrfHash
                };
            },
            processResults: function (response) {
                if (response.token) {
                    $('.txt_csrfname').val(response.token);
                }
                return {
                    results: response.results || []
                };
            },
            cache: true
        }
    });

    // Abrir modal para nuevo registro
    $(document).on('click', '.btnAddEmployee_sap_link', function (e) {
        $(".form-control").val("");
        $("#idEmployee_sap_link").val("0");
        $("#iduser").val("0").trigger('change');
        $("#sapemployee").val("0").trigger('change');
        $("#btnSaveEmployee_sap_link").removeAttr("disabled");
    });

    // Guardar o Actualizar
    $(document).on('click', '#btnSaveEmployee_sap_link', function (e) {
        var idEmployee_sap_link = $("#idEmployee_sap_link").val();
        var idEmpresa = $("#idEmpresa").val();
        var iduser = $("#iduser").val();
        var sapemployee = $("#sapemployee").val();
        var sapemployeeText = $("#sapemployee option:selected").text();

        $("#btnSaveEmployee_sap_link").attr("disabled", true);

        var datos = new FormData();
        datos.append("idEmployee_sap_link", idEmployee_sap_link);
        datos.append("idEmpresa", idEmpresa);
        datos.append("iduser", iduser);
        datos.append("sapemployee", sapemployee);
        datos.append("nameemployeesap", sapemployeeText);

        $.ajax({
            url: "<?= base_url('admin/employee_sap_link/save') ?>",
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
                    tableEmployee_sap_link.ajax.reload();
                    $("#btnSaveEmployee_sap_link").removeAttr("disabled");
                    $('#modalAddEmployee_sap_link').modal('hide');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: respuesta.message || "Error desconocido"
                    });
                    $("#btnSaveEmployee_sap_link").removeAttr("disabled");
                }
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: jqXHR.responseText
            });
            $("#btnSaveEmployee_sap_link").removeAttr("disabled");
        });
    });

    // Cargar datos al editar
    $(".tableEmployee_sap_link").on("click", ".btnEditEmployee_sap_link", function () {
        var idEmployee_sap_link = $(this).attr("idEmployee_sap_link");
        var datos = new FormData();
        datos.append("idEmployee_sap_link", idEmployee_sap_link);

        $("#btnSaveEmployee_sap_link").removeAttr("disabled");

        $.ajax({
            url: "<?= base_url('admin/employee_sap_link/getEmployee_sap_link') ?>",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#idEmployee_sap_link").val(respuesta["id"]);

                var newOption = new Option(respuesta["idEmpresa"] + ' - ' + respuesta["nameCompanie"], respuesta["idEmpresa"], true, true);
                $('#idEmpresa').append(newOption).trigger('change');
                $("#idEmpresa").val(respuesta["idEmpresa"]).trigger("change");

                var newOptionUser = new Option(respuesta["iduser"] + ' - ' + respuesta["username"], respuesta["iduser"], true, true);
                $('#iduser').append(newOptionUser).trigger('change');
                $("#iduser").val(respuesta["iduser"]);

                var newOptionEmployeeSAP = new Option(respuesta["nameemployeesap"], respuesta["sapemployee"], true, true);
                $('#sapemployee').append(newOptionEmployeeSAP).trigger('change');
                $("#sapemployee").val(respuesta["sapemployee"]);
            }
        });
    });

    // Eliminar registro
    $(".tableEmployee_sap_link").on("click", ".btn-delete", function () {
        var idEmployee_sap_link = $(this).attr("data-id");
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
                    url: `<?= base_url('admin/employee_sap_link') ?>/` + idEmployee_sap_link,
                    method: 'DELETE',
                }).done((data, textStatus, jqXHR) => {
                    Toast.fire({
                        icon: 'success',
                        title: jqXHR.statusText,
                    });
                    tableEmployee_sap_link.ajax.reload();
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
        $("#modalAddEmployee_sap_link").draggable();
    });
</script>
<?= $this->endSection() ?>