<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\sweetalert') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesAuthReq/modalShowProducts') ?>
<div class="card card-default">
    <div class="card-header">

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
            dataSrc: function (json) {
                // debug: ver exactamente qué viene
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
            // Columna acciones (botones) — usamos función para no depender de propiedades fijas
            {
                data: function (row) {
                    // fallbacks robustos para extraer campos
                    var docEntry = row.DocEntry ?? row.docEntry ?? (row._raw && (row._raw.DocEntry ?? row._raw.DocEntry)) ?? '';
                    var docNum = row.DocNum   ?? row.docNum   ?? (row._raw && (row._raw.DocNum ?? row._raw.DocNum)) ?? '';
                    var almacen = row.Almacen  ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';

                    // escapar valores (opcional)
                    var eDocEntry = String(docEntry).replace(/"/g, '&quot;');
                    var eDocNum = String(docNum).replace(/"/g, '&quot;');
                    var eAlmacen = String(almacen).replace(/"/g, '&quot;');

                    return `
                    <div class="btn-group" role="group" aria-label="Acciones">
                        <button class="btn btn-success btnAuthorize btn-sm"
                                data-docentry="${eDocEntry}"
                                data-docnum="${eDocNum}"
                                data-almacen="${eAlmacen}">
                            <i class="fas fa-check-circle"></i> Autorizar
                        </button>
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

            // Columna Almacén — usamos función para evitar warning si la propiedad no existe
            {
                data: function (row) {
                    return row.Almacen ?? row.AlmacenName ?? row.WhsName ?? row.U_WhsCode ?? (row._raw && (row._raw.U_WhsCode ?? row._raw.U_Almacen)) ?? '';
                },
                name: 'Almacen'
            },

            // Columna DocNum
            {
                data: function (row) {
                    return row.DocNum ?? row.docNum ?? (row._raw && (row._raw.DocNum ?? '')) ?? '';
                },
                name: 'DocNum'
            },

            // Columna DocDate
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



    $('#tableAuthReq tbody').on('click', '.btnAuthorize', function () {

        console.log($(this).data());
        const docEntry = $(this).data('docentry');
        const docNum = $(this).data('docnum');
        const almacen = $(this).data('almacen');

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


                const $btn = $(this);  // si estás dentro del handler y `this` es el botón
                // Si no, obtén el botón por selector. Ej: var $btn = $(e.currentTarget);
                // Mejor: obtener el botón que abrió el swal: lo guardamos antes de llamar a Swal
                // (ver ejemplo abajo)

                const payload = {
                    docEntry: docEntry,
                    docNum: docNum,
                    almacen: almacen
                };

                $.ajax({
                    url: '<?= base_url("admin/servicelayer/authorizeReq") ?>',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(payload),
                    // Si necesitas enviar cookies/credenciales cross-domain:
                    // xhrFields: { withCredentials: true },
                    success: function (resp) {
                        if (resp && resp.success) {
                            // 1) Update button visual
                            // if we have the original $btn reference:
                            if ($btn && $btn.length) {
                                $btn.removeClass('btn-success').addClass('btn-secondary').attr('disabled', true);
                                $btn.html('<i class="fas fa-check"></i> Autorizado');
                            }

                            // 2) Update row data in DataTable (si response trae updatedRow)
                            /*
                            if (resp.updatedRow) {
                                // buscar la fila por DocEntry y actualizar
                                var rowIndex = tableAuthReq.rows().indexes().filter(function (idx) {
                                    var d = tableAuthReq.row(idx).data();
                                    return (d.DocEntry == resp.updatedRow.DocEntry);
                                })[0];
                                if (typeof rowIndex !== 'undefined') {
                                    tableAuthReq.row(rowIndex).data(resp.updatedRow).invalidate().draw(false);
                                } else {
                                    // si no encuentra, refresca la fila actual:
                                    tableAuthReq.ajax.reload(null, false);
                                }
                            } else {
                                // si no hay updatedRow, recargamos solo la fila actual gracioso:
                                tableAuthReq.ajax.reload(null, false);
                            }
 * 
                             */
                            
                            // 3) Mostrar toast de éxito
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Requisición autorizada',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            // manejo de error enviado por el servidor
                            var msg = (resp && resp.error) ? resp.error : 'Error en la autorización';
                            Swal.fire('Error', msg, 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        console.error('AJAX authorize error', status, err, xhr.responseText);
                        Swal.fire('Error', 'No se pudo autorizar (error de red o servidor).', 'error');
                    }
                });



            } else {

            }
        });
    });







    $(function () {
        $("#modalAddUser_sap_link").draggable();
    });
</script>
<?= $this->endSection() ?>