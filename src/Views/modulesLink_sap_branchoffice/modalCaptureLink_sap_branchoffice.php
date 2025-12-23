<!-- Modal Link_sap_branchoffice -->
<div class="modal fade" id="modalAddLink_sap_branchoffice" tabindex="-1" role="dialog" aria-labelledby="modalAddLink_sap_branchoffice" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('link_sap_branchoffice.createEdit') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-link_sap_branchoffice" class="form-horizontal">
                    <input type="hidden" id="idLink_sap_branchoffice" name="idLink_sap_branchoffice" value="0">

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
                        <label for="idBranchOffice" class="col-sm-2 col-form-label"><?= lang('link_sap_branchoffice.fields.idBranchOffice') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                </div>

                                <select id='idBranchOffice' name='idBranchOffice' class="idBranchOffice" style='width: 80%;'>

                                    <?php
                                    if (isset($idSucursal)) {

                                        echo "   <option value='$idSucursal'>$idSucursal - $nombreSucursal</option>";
                                    }
                                    ?>

                                </select>
                            </div>
                        </div>
                    </div>



                    <div class="form-group row">
                        <label for="idBranchOfficeSAP" class="col-sm-2 col-form-label"><?= lang('link_sap_branchoffice.fields.idBranchOfficeSAP') ?></label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-pencil-alt"></i></span>
                                </div>
                                 <select id='idBranchOfficeSAP' name='idBranchOfficeSAP' class="idBranchOfficeSAP" style='width: 80%;'>

                                    <?php
                                    if (isset($idSucursal)) {

                                        echo "   <option value='$idSucursal'>$idSucursal - $nombreSucursal</option>";
                                    }
                                    ?>

                                </select>
                                
                             
                            </div>
                        </div>
                    </div>


                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?= lang('boilerplate.global.close') ?></button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveLink_sap_branchoffice"><?= lang('boilerplate.global.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->section('js') ?>


<script>

    $(document).on('click', '.btnAddLink_sap_branchoffice', function (e) {


        $(".form-control").val("");

        $("#idLink_sap_branchoffice").val("0").trigger("change");

        $("#btnSaveLink_sap_branchoffice").removeAttr("disabled");

        $("#idEmpresa").val("0").trigger("change");

    });

    /* 
     * AL hacer click al editar
     */



    $(document).on('click', '.btnEditLink_sap_branchoffice', function (e) {


        var idLink_sap_branchoffice = $(this).attr("idLink_sap_branchoffice");

        //LIMPIAMOS CONTROLES
        $(".form-control").val("");

        $("#idLink_sap_branchoffice").val(idLink_sap_branchoffice);
        $("#btnGuardarLink_sap_branchoffice").removeAttr("disabled");

    });


    $("#idEmpresa").select2();

    $("#idBranchOffice").select2({
        ajax: {
            url: "<?= site_url('admin/sucursales/getSucursalesAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                // CSRF Hash
                var csrfName = $('.txt_csrfname').attr('name'); // CSRF Token name
                var csrfHash = $('.txt_csrfname').val(); // CSRF hash
                var idEmpresa = $('.idEmpresa').val(); // CSRF hash

                return {
                    searchTerm: params.term, // search term
                    [csrfName]: csrfHash, // CSRF Token
                    idEmpresa: idEmpresa // search term
                };
            },
            processResults: function (response) {

                // Update CSRF Token
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });



    /**
     *  Select brachoffice SAP
     */

    $("#idBranchOfficeSAP").select2({
        ajax: {
            url: "<?= site_url('admin/branchoffice/getBranchOfficeSAPAjax') ?>",
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                // CSRF Hash
                var csrfName = $('.txt_csrfname').attr('name'); // CSRF Token name
                var csrfHash = $('.txt_csrfname').val(); // CSRF hash
                var idEmpresa = $('.idEmpresa').val(); // CSRF hash

                return {
                    searchTerm: params.term, // search term
                    [csrfName]: csrfHash, // CSRF Token
                    idEmpresa: idEmpresa // search term
                };
            },
            processResults: function (response) {

                // Update CSRF Token
                $('.txt_csrfname').val(response.token);
                return {
                    results: response.data
                };
            },
            cache: true
        }
    });

</script>


<?= $this->endSection() ?>
        