<!-- Modal User_sap_link -->
<div class="modal fade" id="modalAddUser_sap_link" tabindex="-1" role="dialog" aria-labelledby="modalAddUser_sap_link" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('user_sap_link.createEdit') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-user_sap_link" class="form-horizontal">
                    <input type="hidden" id="idUser_sap_link" name="idUser_sap_link" value="0">

                    <div class="form-group row">
                        <label for="emitidoRecibido" class="col-sm-2 col-form-label">Empresa</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                </div>

                                <select class="form-control idEmpresa" name="idEmpresa" id="idEmpresa" style = "width:80%;">
                                    <option value="0">Seleccione empresa</option>
                                    <?php
                                    foreach ($empresas as $key => $value) {

                                        echo "<option value='$value[id]' selected>$value[id] - $value[nombre] </option>  ";
                                    }
                                    ?>

                                </select>

                            </div>
                        </div>
                    </div>


                    <div class="form-group row">
                        <label for="idProyecto" class="col-sm-2 col-form-label"><?= lang('user_sap_link.fields.iduser') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                </div>

                                <select class="form-control iduser" name="iduser" id="iduser" style = "width:80%;">
                                    <option value="0">Seleccione Usuario</option>

                                </select>

                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="idProyecto" class="col-sm-2 col-form-label"><?= lang('user_sap_link.fields.sapuser') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                </div>

                                <select class="form-control sapuser" name="sapuser" id="sapuser" style = "width:80%;">
                                    <option value="0">Seleccione Usuario SAP</option>

                                </select>

                            </div>
                        </div>
                    </div>



                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveUser_sap_link"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->section('js') ?>


<script>

    $(document).on('click', '.btnAddUser_sap_link', function (e) {


        $(".form-control").val("");

        $("#idUser_sap_link").val("0");

        $("#btnSaveUser_sap_link").removeAttr("disabled");

    });

    /* 
     * AL hacer click al editar
     */



    $(document).on('click', '.btnEditUser_sap_link', function (e) {


        var idUser_sap_link = $(this).attr("idUser_sap_link");

        //LIMPIAMOS CONTROLES
        $(".form-control").val("");

        $("#idUser_sap_link").val(idUser_sap_link);
        $("#btnGuardarUser_sap_link").removeAttr("disabled");

    });

    $("#sapuser").select2({
        ajax: {
            url: "<?= site_url('admin/sapservicelayer/getUsersSAPAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 2000, // ⏱ espera 2 segundos después de la última tecla
            data: function (params) {
                var csrfName = $('.txt_csrfname').attr('name'); // CSRF Token name
                var csrfHash = $('.txt_csrfname').val(); // CSRF value

                return {
                    searchTerm: params.term || '', // search term
                    [csrfName]: csrfHash
                };
            },
            processResults: function (response) {
                // Si el servidor devuelve un nuevo token, lo actualizamos
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


    $("#iduser").select2({
        ajax: {
            url: "<?= site_url('admin/sapservicelayer/getUsersAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 2000, // ⏱ espera 2 segundos después de la última tecla
            data: function (params) {
                var idEmpresa = $('.idEmpresa').val(); // CSRF Token name
                var csrfName = $('.txt_csrfname').attr('name'); // CSRF Token name
                var csrfHash = $('.txt_csrfname').val(); // CSRF value

                return {
                    searchTerm: params.term || '', // search term
                    idEmpresa: idEmpresa|| '', // search term
                    [csrfName]: csrfHash
                };
            },
            processResults: function (response) {
                // Si el servidor devuelve un nuevo token, lo actualizamos
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




    $("#idEmpresa").select2();

</script>


<?= $this->endSection() ?>
        