<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>

<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<div class="card card-default">
    <div class="card-header">
        <div class="float-right">
            <div class="btn-group">
                <button class="btn btn-primary btnAddUser_pricelist" data-toggle="modal" data-target="#modalAddUser_pricelist">
                    <i class="fa fa-plus"></i> <?= lang('user_pricelist.add') ?>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableUser_pricelist" class="table table-striped table-hover va-middle tableUser_pricelist">
                        <thead>
                            <tr>
                                <th><?= lang('user_pricelist.fields.actions') ?></th>
                                <th><?= lang('user_pricelist.fields.idEmpresa') ?></th>
                                <th><?= lang('user_pricelist.fields.iduser') ?></th>
                                <th><?= lang('user_pricelist.fields.idPriceList') ?></th>
                                <th><?= lang('user_pricelist.fields.created_at') ?></th>
                                <th><?= lang('user_pricelist.fields.updated_at') ?></th>
                                <th><?= lang('user_pricelist.fields.deleted_at') ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Captura / Edición -->
<div class="modal fade" id="modalAddUser_pricelist" tabindex="-1" role="dialog" aria-labelledby="modalAddUser_pricelist" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('user_pricelist.createEdit') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-user_pricelist" class="form-horizontal">
                    <input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />
                    <input type="hidden" id="idUser_pricelist" name="idUser_pricelist" value="0">

                    <!-- Empresa -->
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
                                        <option value="<?= $value['id'] ?>" <?= $key === 0 ? 'selected' : '' ?>>
                                            <?= $value['id'] ?> - <?= $value['nombre'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Usuario -->
                    <div class="form-group row">
                        <label for="iduser" class="col-sm-2 col-form-label"><?= lang('user_pricelist.fields.iduser') ?></label>
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

                    <!-- Lista de Precios SAP -->
                    <div class="form-group row">
                        <label for="idPriceList" class="col-sm-2 col-form-label"><?= lang('user_pricelist.fields.idPriceList') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <select class="form-control idPriceList" name="idPriceList" id="idPriceList" style="width:80%;">
                                    <option value="0">Seleccione Lista de Precios SAP</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveUser_pricelist"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    $("#idEmpresa").select2();

    $("#idEmpresa").on("change", function () {
        $("#iduser").val("0").trigger("change");
        $("#idPriceList").val("0").trigger("change");
    });

    // Usuarios (MariaDB)
    $("#iduser").select2({
        ajax: {
            url: "<?= site_url('admin/user_pricelist/getUsersAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 500,
            data: function (params) {
                var idEmpresa = $('#idEmpresa').val();
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();

                return {
                    searchTerm: params.term || '',
                    idEmpresa: idEmpresa || 0,
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

    // Listas de Precios (SAP vía ODBC)
    $("#idPriceList").select2({
        ajax: {
            url: "<?= site_url('admin/user_pricelist/getPriceListsAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                var idEmpresa = $('#idEmpresa').val();
                var csrfName = $('.txt_csrfname').attr('name');
                var csrfHash = $('.txt_csrfname').val();

                return {
                    searchTerm: params.term || '',
                    idEmpresa: idEmpresa || 0,
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

    var tableUser_pricelist = $('#tableUser_pricelist').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/user_pricelist') ?>',
            method: 'GET',
            dataType: "json"
        },
        columnDefs: [{
            orderable: false,
            searchable: false,
            targets: [0]
        }],
        columns: [
            {
                "data": function (data) {
                    return `<td class="text-right py-0 align-middle">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning btnEditUser_pricelist" data-toggle="modal" idUser_pricelist="${data.id}" data-target="#modalAddUser_pricelist"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-danger btn-delete" data-id="${data.id}"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>`;
                }
            },
            {'data': 'nombreEmpresa'},
            {'data': 'username'},
            {'data': 'namePriceList'},
            {'data': 'created_at'},
            {'data': 'updated_at'},
            {'data': 'deleted_at'}
        ]
    });

    $(document).on('click', '.btnAddUser_pricelist', function () {
        $("#idUser_pricelist").val("0");
        $("#iduser").empty().append('<option value="0">Seleccione Usuario</option>').val("0").trigger('change');
        $("#idPriceList").empty().append('<option value="0">Seleccione Lista de Precios SAP</option>').val("0").trigger('change');
        $("#btnSaveUser_pricelist").removeAttr("disabled");
    });

    $(document).on('click', '#btnSaveUser_pricelist', function () {
        var idUser_pricelist = $("#idUser_pricelist").val();
        var idEmpresa        = $("#idEmpresa").val();
        var iduser           = $("#iduser").val();
        var idPriceList      = $("#idPriceList").val();
        var namePriceList    = $("#idPriceList option:selected").text();

        if (!idEmpresa || idEmpresa == "0" || !iduser || iduser == "0" || !idPriceList || idPriceList == "0") {
            Toast.fire({
                icon: 'warning',
                title: 'Seleccione empresa, usuario y lista de precios'
            });
            return;
        }

        $("#btnSaveUser_pricelist").attr("disabled", true);

        var datos = new FormData();
        datos.append("idUser_pricelist", idUser_pricelist);
        datos.append("idEmpresa", idEmpresa);
        datos.append("iduser", iduser);
        datos.append("idPriceList", idPriceList);
        datos.append("namePriceList", namePriceList);

        $.ajax({
            url: "<?= base_url('admin/user_pricelist/save') ?>",
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
                    tableUser_pricelist.ajax.reload();
                    $("#btnSaveUser_pricelist").removeAttr("disabled");
                    $('#modalAddUser_pricelist').modal('hide');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: respuesta.message || "Error desconocido"
                    });
                    $("#btnSaveUser_pricelist").removeAttr("disabled");
                }
            }
        }).fail(function (jqXHR) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: jqXHR.responseText
            });
            $("#btnSaveUser_pricelist").removeAttr("disabled");
        });
    });

    $(".tableUser_pricelist").on("click", ".btnEditUser_pricelist", function () {
        var idUser_pricelist = $(this).attr("idUser_pricelist");
        $("#btnSaveUser_pricelist").removeAttr("disabled");

        var datos = new FormData();
        datos.append("idUser_pricelist", idUser_pricelist);

        $.ajax({
            url: "<?= base_url('admin/user_pricelist/getUser_pricelist') ?>",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#idUser_pricelist").val(respuesta["id"]);

                $("#idEmpresa").val(respuesta["idEmpresa"]).trigger("change.select2");

                var newOptionUser = new Option(respuesta["iduser"] + ' - ' + respuesta["username"], respuesta["iduser"], true, true);
                $('#iduser').empty().append(newOptionUser).trigger('change');

                var newOptionPriceList = new Option(respuesta["namePriceList"], respuesta["idPriceList"], true, true);
                $('#idPriceList').empty().append(newOptionPriceList).trigger('change');
            }
        });
    });

    $(".tableUser_pricelist").on("click", ".btn-delete", function () {
        var idUser_pricelist = $(this).attr("data-id");
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
                    url: `<?= base_url('admin/user_pricelist') ?>/` + idUser_pricelist,
                    method: 'DELETE',
                }).done((data, textStatus, jqXHR) => {
                    Toast.fire({
                        icon: 'success',
                        title: jqXHR.statusText,
                    });
                    tableUser_pricelist.ajax.reload();
                }).fail((error) => {
                    Toast.fire({
                        icon: 'error',
                        title: error.responseJSON?.messages?.error || 'Error al eliminar',
                    });
                });
            }
        });
    });

    $(function () {
        $("#modalAddUser_pricelist").draggable();
    });
</script>
<?= $this->endSection() ?>