<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesUser_sap_link/modalCaptureUser_sap_link') ?>
<div class="card card-default">
    <div class="card-header">
        <div class="float-right">
            <div class="btn-group">
                <button class="btn btn-primary btnAddUser_sap_link" data-toggle="modal" data-target="#modalAddUser_sap_link">
                    <i class="fa fa-plus"></i> <?= lang('user_sap_link.add') ?>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableUser_sap_link" class="table table-striped table-hover va-middle tableUser_sap_link">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= lang('user_sap_link.fields.idEmpresa') ?></th>
                                <th><?= lang('user_sap_link.fields.iduser') ?></th>
                                <th><?= lang('user_sap_link.fields.sapuser') ?></th>
                                <th><?= lang('user_sap_link.fields.created_at') ?></th>
                                <th><?= lang('user_sap_link.fields.updated_at') ?></th>
                                <th><?= lang('user_sap_link.fields.deleted_at') ?></th>

                                <th><?= lang('user_sap_link.fields.actions') ?></th>
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
    var tableUser_sap_link = $('#tableUser_sap_link').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/user_sap_link') ?>',
            method: 'GET',
            dataType: "json"
        },
        columnDefs: [{
                orderable: false,
                targets: [7],
                searchable: false,
                targets: [7]
            }],
        columns: [{'data': 'id'},
            {'data': 'nombreEmpresa'},
            {'data': 'username'},
            {'data': 'nameusersap'},
            {'data': 'created_at'},
            {'data': 'updated_at'},
            {'data': 'deleted_at'},

            {
                "data": function (data) {
                    return `<td class="text-right py-0 align-middle">
                         <div class="btn-group btn-group-sm">
                             <button class="btn btn-warning btnEditUser_sap_link" data-toggle="modal" idUser_sap_link="${data.id}" data-target="#modalAddUser_sap_link">  <i class=" fa fa-edit"></i></button>
                             <button class="btn btn-danger btn-delete" data-id="${data.id}"><i class="fas fa-trash"></i></button>
                         </div>
                         </td>`
                }
            }
        ]
    });

    $(document).on('click', '#btnSaveUser_sap_link', function (e) {
        var idUser_sap_link = $("#idUser_sap_link").val();
        var idEmpresa = $("#idEmpresa").val();
        var iduser = $("#iduser").val();
        var sapuser = $("#sapuser").val();
        var sapuserText = $("#sapuser option:selected").text();

        $("#btnSaveUser_sap_link").attr("disabled", true);
        var datos = new FormData();
        datos.append("idUser_sap_link", idUser_sap_link);
        datos.append("idEmpresa", idEmpresa);
        datos.append("iduser", iduser);
        datos.append("sapuser", sapuser);
        datos.append("nameusersap", sapuserText);

        $.ajax({
            url: "<?= base_url('admin/user_sap_link/save') ?>",
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
                    tableUser_sap_link.ajax.reload();
                    $("#btnSaveUser_sap_link").removeAttr("disabled");
                    $('#modalAddUser_sap_link').modal('hide');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: respuesta.message || "Error desconocido"
                    });
                    $("#btnSaveUser_sap_link").removeAttr("disabled");
                }
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: jqXHR.responseText
            });
            $("#btnSaveUser_sap_link").removeAttr("disabled");
        });
    });

    $(".tableUser_sap_link").on("click", ".btnEditUser_sap_link", function () {
        var idUser_sap_link = $(this).attr("idUser_sap_link");
        var datos = new FormData();
        datos.append("idUser_sap_link", idUser_sap_link);
        $.ajax({
            url: "<?= base_url('admin/user_sap_link/getUser_sap_link') ?>",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {

                $("#idUser_sap_link").val(respuesta["id"]);

                var newOption = new Option(respuesta["idEmpresa"] + ' ' + respuesta["nameCompanie"], respuesta["idEmpresa"], true, true);
                $('#idEmpresa').append(newOption).trigger('change');
                $("#idEmpresa").val(respuesta["idEmpresa"]);

                $("#idEmpresa").val(respuesta["idEmpresa"]).trigger("change");


                var newOptionUser = new Option(respuesta["iduser"] + ' ' + respuesta["username"], respuesta["username"], true, true);
                $('#iduser').append(newOptionUser).trigger('change');
                $("#iduser").val(respuesta["iduser"]);

                var newOptionUserSAP = new Option(respuesta["nameusersap"], respuesta["sapuser"], true, true);
                $('#sapuser').append(newOptionUserSAP).trigger('change');
                $("#sapuser").val(respuesta["sapuser"]);

            }
        });
    });

    $(".tableUser_sap_link").on("click", ".btn-delete", function () {
        var idUser_sap_link = $(this).attr("data-id");
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
                    url: `<?= base_url('admin/user_sap_link') ?>/` + idUser_sap_link,
                    method: 'DELETE',
                }).done((data, textStatus, jqXHR) => {
                    Toast.fire({
                        icon: 'success',
                        title: jqXHR.statusText,
                    });
                    tableUser_sap_link.ajax.reload();
                }).fail((error) => {
                    Toast.fire({
                        icon: 'error',
                        title: error.responseJSON.messages.error,
                    });
                });
            }
        });
    });

    $(function () {
        $("#modalAddUser_sap_link").draggable();
    });
</script>
<?= $this->endSection() ?>