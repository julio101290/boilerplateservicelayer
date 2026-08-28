<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesAuthReq/modalShowProducts') ?>

<div class="card card-default">
    <div class="card-header">
        <div class="float-right">
            <!-- Botón de Estado / Filtro -->
            <button type="button" id="btnToggleAuth" class="btn btn-secondary btn-sm">
                <i class="fas fa-clock"></i> No autorizadas
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tableAuthReq" class="table table-striped table-hover va-middle tableUser_sap_link">
                        <thead>
                            <tr>
                                <th><?= lang('authreq.fields.actions') ?></th>
                                <th><?= lang('authreq.fields.warehouse') ?></th>
                                <th><?= lang('authreq.fields.folio') ?></th>
                                <th><?= lang('authreq.fields.date') ?></th>
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
    // Variable de control de estado (false: No autorizadas | true: Ya autorizadas)
    var showAuthorized = false;

    // Inicializar DataTable
    var tableAuthReq = $('#tableAuthReq').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        ajax: {
            url: '<?= base_url('admin/servicelayer/getauthreq') ?>',
            method: 'GET',
            dataType: "json",
            data: function (d) {
                d.authorized = showAuthorized ? 1 : 0;
            },
            dataSrc: function (json) {
                console.log('AJAX response (DataTables):', json);
                return json.data || [];
            }
        },
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                width: '220px'
            }
        ],
        columns: [
            {
                data: function (row) {
                    var docEntry = row.DocEntry ?? row.docEntry ?? (row._raw && (row._raw.DocEntry ?? row._raw.DocEntry)) ?? '';
                    var docNum   = row.DocNum   ?? row.docNum   ?? (row._raw && (row._raw.DocNum ?? row._raw.DocNum)) ?? '';
                    var almacen  = row.Almacen  ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';

                    var eDocEntry = String(docEntry).replace(/"/g, '&quot;');
                    var eDocNum   = String(docNum).replace(/"/g, '&quot;');
                    var eAlmacen  = String(almacen).replace(/"/g, '&quot;');

                    // Alternar botón según el filtro actual
                    var botonAccion = '';
                    if (showAuthorized) {
                        botonAccion = `
                        <button class="btn btn-danger btnDeauthorize btn-sm"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}"
                                title="Desautorizar">
                            <i class="fas fa-times-circle"></i> Desautorizar
                        </button>`;
                    } else {
                        botonAccion = `
                        <button class="btn btn-success btnAuthorize btn-sm"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}"
                                title="Autorizar">
                            <i class="fas fa-check-circle"></i> Autorizar
                        </button>`;
                    }

                    return `
                    <div class="btn-group" role="group" aria-label="Acciones">
                        ${botonAccion}
                        <button class="btn btn-info btnViewItems btn-sm ml-1"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}"
                                title="Ver artículos">
                            <i class="fas fa-boxes"></i>
                        </button>
                    </div>`;
                }
            },
            {
                data: function (row) {
                    return row.Almacen ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';
                },
                name: 'Almacen'
            },
            {
                data: function (row) {
                    return row.DocNum ?? row.docNum ?? (row._raw && (row._raw.DocNum ?? '')) ?? '';
                },
                name: 'DocNum'
            },
            {
                data: function (row) {
                    return row.DocDate ?? row.docDate ?? (row._raw && (row._raw.DocDate ?? '')) ?? '';
                },
                name: 'DocDate'
            }
        ],
        language: {
            processing: "Cargando..."
        }
    });

    // Evento clic para alternar el botón de cabecera
    $('#btnToggleAuth').on('click', function () {
        showAuthorized = !showAuthorized;
        const $btn = $(this);

        if (showAuthorized) {
            $btn.removeClass('btn-secondary').addClass('btn-success');
            $btn.html('<i class="fas fa-check-double"></i> Ya autorizadas');
        } else {
            $btn.removeClass('btn-success').addClass('btn-secondary');
            $btn.html('<i class="fas fa-clock"></i> No autorizadas');
        }

        // Recargar la tabla con el nuevo estado
        tableAuthReq.ajax.reload();
    });

    // 1) Acción: AUTORIZAR (Botón Verde)
    $('#tableAuthReq tbody').on('click', '.btnAuthorize', function () {
        const docEntry = $(this).data('docentry');
        const docNum   = $(this).data('docnum');
        const almacen  = $(this).data('almacen');
        const $btn     = $(this);

        Swal.fire({
            title: '¿Autorizar Requisición?',
            html: `
                <p><strong>DocEntry:</strong> ${docEntry}</p>
                <p><strong>DocNum:</strong> ${docNum}</p>
                <p><strong>Almacén:</strong> ${almacen}</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, autorizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                const payload = {
                    docEntry: docEntry,
                    docNum: docNum,
                    almacen: almacen,
                    action: 'authorize'
                };

                $.ajax({
                    url: '<?= base_url("admin/servicelayer/authorizeReq") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(payload),
                    success: function (resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Requisición autorizada',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            tableAuthReq.ajax.reload(null, false);
                        } else {
                            var msg = (resp && resp.error) ? resp.error : 'Error en la autorización';
                            Swal.fire('Error', msg, 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        console.error('AJAX authorize error', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudo autorizar (error de red o servidor).', 'error');
                    }
                });
            }
        });
    });

    // 2) Acción: DESAUTORIZAR (Botón Rojo)
    $('#tableAuthReq tbody').on('click', '.btnDeauthorize', function () {
        const docEntry = $(this).data('docentry');
        const docNum   = $(this).data('docnum');
        const almacen  = $(this).data('almacen');
        const $btn     = $(this);

        Swal.fire({
            title: '¿Desautorizar Requisición?',
            html: `
                <p><strong>DocEntry:</strong> ${docEntry}</p>
                <p><strong>DocNum:</strong> ${docNum}</p>
                <p><strong>Almacén:</strong> ${almacen}</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, desautorizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                const payload = {
                    docEntry: docEntry,
                    docNum: docNum,
                    almacen: almacen,
                    action: 'deauthorize'
                };

                $.ajax({
                    url: '<?= base_url("admin/servicelayer/deauthorizeReq") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(payload),
                    success: function (resp) {
                        if (resp && resp.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Requisición desautorizada',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            tableAuthReq.ajax.reload(null, false);
                        } else {
                            var msg = (resp && resp.error) ? resp.error : 'Error al desautorizar';
                            Swal.fire('Error', msg, 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        console.error('AJAX deauthorize error', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudo desautorizar (error de red o servidor).', 'error');
                    }
                });
            }
        });
    });

    $(function () {
        $("#modalAddUser_sap_link").draggable();
    });
</script>
<?= $this->endSection() ?>