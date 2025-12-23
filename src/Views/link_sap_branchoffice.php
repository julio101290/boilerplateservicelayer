<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesLink_sap_branchoffice/modalCaptureLink_sap_branchoffice') ?>
<div class="card card-default">
    <div class="card-header">
        <div class="float-right">
            <div class="btn-group">
                <button class="btn btn-primary btnAddLink_sap_branchoffice" data-toggle="modal" data-target="#modalAddLink_sap_branchoffice">
                    <i class="fa fa-plus"></i> <?= lang('link_sap_branchoffice.add') ?>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableLink_sap_branchoffice" class="table table-striped table-hover va-middle tableLink_sap_branchoffice">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= lang('link_sap_branchoffice.fields.idEmpresa') ?></th>
                                <th><?= lang('link_sap_branchoffice.fields.idBranchOffice') ?></th>
                                <th><?= lang('link_sap_branchoffice.fields.idBranchOfficeSAP') ?></th>
                                <th><?= lang('link_sap_branchoffice.fields.created_at') ?></th>
                                <th><?= lang('link_sap_branchoffice.fields.updated_at') ?></th>
                                <th><?= lang('link_sap_branchoffice.fields.deleted_at') ?></th>

                                <th><?= lang('link_sap_branchoffice.fields.actions') ?></th>
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
    var tableLink_sap_branchoffice = $('#tableLink_sap_branchoffice').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/link_sap_branchoffice') ?>',
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
            {'data': 'idBranchOffice'},
            {'data': 'idBranchOfficeSAP'},
            {'data': 'created_at'},
            {'data': 'updated_at'},
            {'data': 'deleted_at'},

            {
                "data": function (data) {
                    return `<td class="text-right py-0 align-middle">
                         <div class="btn-group btn-group-sm">
                             <button class="btn btn-warning btnEditLink_sap_branchoffice" data-toggle="modal" idLink_sap_branchoffice="${data.id}" data-target="#modalAddLink_sap_branchoffice">  <i class=" fa fa-edit"></i></button>
                             <button class="btn btn-danger btn-delete" data-id="${data.id}"><i class="fas fa-trash"></i></button>
                         </div>
                         </td>`
                }
            }
        ]
    });

    $(document).on('click', '#btnSaveLink_sap_branchoffice', function (e) {
        var idLink_sap_branchoffice = $("#idLink_sap_branchoffice").val();
        var idEmpresa = $("#idEmpresa").val();
        var idBranchOffice = $("#idBranchOffice").val();
        var idBranchOfficeSAP = $("#idBranchOfficeSAP").val();

        $("#btnSaveLink_sap_branchoffice").attr("disabled", true);
        var datos = new FormData();
        datos.append("idLink_sap_branchoffice", idLink_sap_branchoffice);
        datos.append("idEmpresa", idEmpresa);
        datos.append("idBranchOffice", idBranchOffice);
        datos.append("idBranchOfficeSAP", idBranchOfficeSAP);

        $.ajax({
            url: "<?= base_url('admin/link_sap_branchoffice/save') ?>",
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
                    tableLink_sap_branchoffice.ajax.reload();
                    $("#btnSaveLink_sap_branchoffice").removeAttr("disabled");
                    $('#modalAddLink_sap_branchoffice').modal('hide');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: respuesta.message || "Error desconocido"
                    });
                    $("#btnSaveLink_sap_branchoffice").removeAttr("disabled");
                }
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: jqXHR.responseText
            });
            $("#btnSaveLink_sap_branchoffice").removeAttr("disabled");
        });
    });

    $(".tableLink_sap_branchoffice").on("click", ".btnEditLink_sap_branchoffice", function () {
        var idLink_sap_branchoffice = $(this).attr("idLink_sap_branchoffice");
        var datos = new FormData();
        datos.append("idLink_sap_branchoffice", idLink_sap_branchoffice);
        $.ajax({
            url: "<?= base_url('admin/link_sap_branchoffice/getLink_sap_branchoffice') ?>",
            method: "POST",
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (respuesta) {
                $("#idLink_sap_branchoffice").val(respuesta["id"]);
                $("#idEmpresa").val(respuesta["idEmpresa"]).trigger("change");
                
                
                
                $("#idBranchOffice").val(respuesta["idBranchOffice"]);
                var newOptionBranchoffice = new Option(respuesta["idBranchOffice"] + ' ' + respuesta["descriptionBranchofficelocal"], respuesta["idBranchOffice"], true, true);
                $('#idBranchOffice').append(newOptionBranchoffice).trigger('change');
                $("#idBranchOffice").val(respuesta["idBranchOffice"]);
                
                
                
                
                $("#idBranchOfficeSAP").val(respuesta["idBranchOfficeSAP"]);
                
                 var newOptionBranchofficeSAP = new Option(respuesta["idBranchOfficeSAP"] + ' ' + respuesta["descriptionBranchOfficeSAP"], respuesta["idBranchOfficeSAP"], true, true);
                $('#idBranchOfficeSAP').append(newOptionBranchofficeSAP).trigger('change');
                $("#idBranchOfficeSAP").val(respuesta["idBranchOfficeSAP"]);

            }
        });
    });

    $(".tableLink_sap_branchoffice").on("click", ".btn-delete", function () {
        var idLink_sap_branchoffice = $(this).attr("data-id");
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
                    url: `<?= base_url('admin/link_sap_branchoffice') ?>/` + idLink_sap_branchoffice,
                    method: 'DELETE',
                }).done((data, textStatus, jqXHR) => {
                    Toast.fire({
                        icon: 'success',
                        title: jqXHR.statusText,
                    });
                    tableLink_sap_branchoffice.ajax.reload();
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
        $("#modalAddLink_sap_branchoffice").draggable();
    });
</script>
<?= $this->endSection() ?>