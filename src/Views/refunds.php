<?= $this->include('julio101290\boilerplate\Views\load/toggle') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load/extrasDatatable') ?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>

<!-- Extend from layout index -->
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<!-- Section content -->
<?= $this->section('content') ?>

<!-- Modal Detalle de Comprobante -->
<div class="modal fade" id="modalVoucherDetails" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= lang('refunds.detailsTitle') ?> <span id="lblFolioDetalle"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tableVoucherDetails" class="table table-striped table-hover va-middle tableVoucherDetails">
                        <thead>
                            <tr>
                                <th><?= lang('refunds.fields.no') ?></th>
                                <th><?= lang('refunds.fields.line') ?></th>
                                <th><?= lang('refunds.fields.na') ?></th>
                                <th><?= lang('refunds.fields.type') ?></th>
                                <th><?= lang('refunds.fields.provider') ?></th>
                                <th><?= lang('refunds.fields.subtotal') ?></th>
                                <th><?= lang('refunds.fields.iva') ?></th>
                                <th><?= lang('refunds.fields.total') ?></th>
                                <th><?= lang('refunds.fields.comments') ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card principal -->
<div class="card card-default">
    <div class="card-header">
        <h3 class="card-title"><?= lang('refunds.title') ?></h3>
    </div>


    <div class="card-header">

        <div class="float-left">
            <div class="btn-group">

                <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                    <i class="fa fa-calendar"></i>&nbsp;
                    <span></span> <i class="fa fa-caret-down"></i>
                </div>


            </div>

            <div class="btn-group">
                <div class="form-group">
                    <label for="idEmpresa"><?= lang('sells.companie') ?> </label>
                    <select id='idEmpresa' name='idEmpresa' class="idEmpresa" style='width: 80%;'>

                        <?php
                        if (isset($idEmpresa)) {

                            echo "   <option value='$idEmpresa'>$idEmpresa - $nombreEmpresa</option>";
                        } else {

                            echo "  <option value='0'>" . lang('sells.allCompanies') . "</option>";

                            foreach ($empresas as $key => $value) {

                                echo "<option value='$value[id]'>$value[id] - $value[nombre] </option>  ";
                            }
                        }
                        ?>

                    </select>
                </div>

            </div>


            <div class="btn-group">



                <div class="form-group">
                    <label for="idSucursal"><?= lang('sells.branchoffice') ?> </label>
                    <select id='idSucursal' name='idSucursal' class="idSucursal" style='width: 100%;'>

                        <?php
                        echo "  <option value='0'>" . lang('sells.AllBranchoffice') . "</option>";
                        if (isset($idSucursal)) {

                            echo "   <option value='$idSucursal'>$idSucursal - $nombreSucursal</option>";
                        }
                        ?>

                    </select>
                </div>

            </div>




            <div class="btn-group">



                <div class="form-group" >
                    <label for="employes"><?= lang('refunds.employes') ?> </label>
                    <select id='employes' name='employes' class="employes" style='width: 100%;'>

                        <?php
                        echo "  <option value='0'>" . lang('refunds.allEmployes') . "</option>";
                        ?>

                    </select>
                </div>

            </div>

            <div class="btn-group">



                <input type="checkbox" id="chkTodasLasVentas" name="chkAllRefunds" class="chkAllRefunds" data-width="250" data-height="40" checked data-toggle="toggle" data-on="<?= lang('refunds.allRefunds') ?>" data-off="<?= lang('refunds.pendingPayment') ?>" data-onstyle="success" data-offstyle="danger">

            </div>


        </div>

        <div class="float-right">
            <div class="btn-group">

                <a href="<?= base_url("admin/servicelayer/newRefund") ?>" class="btn btn-primary btnNewRefunds" ><i class="fa fa-plus"></i>

                    <?= lang('refunds.add') ?>

                </a>

            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">

                    <table id="tableRefunds" class="table table-striped table-hover va-middle tableRefunds">
                        <thead>
                            <tr>
                                <th><?= lang('refunds.fields.code') ?></th>
                                <th><?= lang('refunds.fields.folio') ?></th>
                                <th><?= lang('refunds.fields.area') ?></th>
                                <th><?= lang('refunds.fields.employee') ?></th>
                                <th><?= lang('refunds.fields.date') ?></th>
                                <th><?= lang('refunds.fields.total') ?></th>
                                <th><?= lang('refunds.fields.status') ?></th>
                                <th><?= lang('refunds.fields.userCode') ?></th>
                                <th><?= lang('refunds.fields.codeMov') ?></th>
                                <th><?= lang('refunds.fields.typeVoucher') ?></th>
                                <th><?= lang('refunds.fields.branch') ?></th>
                                <th><?= lang('refunds.fields.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.card -->

<?= $this->endSection() ?>


<?= $this->section('js') ?>
<script>
    
    
    $("#idEmpresa").select2();

    $("#idSucursal").select2({
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
     * Tabla principal de comprobantes pendientes de autorización
     */
    var tableRefunds = $('#tableRefunds').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'pageLength'],
        lengthMenu: [
            [10, 25, 50, 100],
            ['10 <?= lang('refunds.fields.rows') ?>', '25 <?= lang('refunds.fields.rows') ?>', '50 <?= lang('refunds.fields.rows') ?>', '100 <?= lang('refunds.fields.rows') ?>']
        ],
        responsive: true,
        autoWidth: false,
        order: [[0, 'desc']],

        ajax: {
            url: '<?= base_url('admin/servicelayer/listRefunds') ?>',
            method: 'get',
            dataType: "json"
        },
        columnDefs: [{
                orderable: false,
                searchable: false,
                targets: [11]
            }],
        columns: [
            {'data': 'Code'},
            {'data': 'U_Folio'},
            {'data': 'U_Area'},
            {'data': 'U_Employee'},
            {'data': 'U_Date'},
            {'data': 'U_Total'},
            {
                'data': 'U_Status',
                render: function (data) {
                    if (data == 2) {
                        return '<span class="badge badge-warning"><?= lang('refunds.status.pending') ?></span>';
                    } else if (data == 4) {
                        return '<span class="badge badge-success"><?= lang('refunds.status.authorized') ?></span>';
                    }
                    return '<span class="badge badge-secondary">' + data + '</span>';
                }
            },
            {'data': 'U_UserCode'},
            {'data': 'U_CodeMov'},
            {'data': 'U_TypeVoucher'},
            {'data': 'U_Branch'},
            {
                "data": function (data) {

                    var buttonAuthorize = '';

                    if (data.U_Status == 2) {
                        buttonAuthorize = `<button class="btn btn-success btnAuthorize" code="${data.Code}" folio="${data.U_Folio}"><i class="fas fa-check"></i></button>`;
                    }

                    return `<td class="text-right py-0 align-middle">
                         <div class="btn-group btn-group-sm">
                             <button class="btn bg-maroon btnShowDetails" code="${data.Code}" folio="${data.U_Folio}" data-toggle="modal" data-target="#modalVoucherDetails"><i class="fas fa-search"></i></button>
                             ${buttonAuthorize}
                         </div>
                         </td>`;
                }
            }
        ]
    });

</script>
<?= $this->endSection() ?>