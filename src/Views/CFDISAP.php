<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>
<?= $this->section('content') ?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Analizador CFDI vs SAP</h3>
    </div>
    <div class="card-body">
        <form id="formAnalizador" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sapConnection">Conexión SAP</label>
                        <select name="sapConnection" id="sapConnection" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($conexiones as $conn): ?>
                                <option value="<?= $conn['id'] ?>"><?= esc($conn['description']) ?> (<?= esc($conn['companyDB']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="excelFile">Archivo Excel (formato SAT)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="excelFile" name="excelFile" accept=".xlsx, .xls" required>
                            <label class="custom-file-label" for="excelFile">Seleccionar archivo</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-success" id="btnProcesar">
                        <i class="fas fa-sync-alt"></i> Procesar y validar contra SAP
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-info" id="cardResultados" style="display:none;">
    <div class="card-header">
        <h3 class="card-title">Resultados de la validación</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="btnExportarExcel">
                <i class="fas fa-file-excel"></i> Exportar a Excel
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="tablaResultados" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>RFC Emisor</th>
                    <th>Nombre Emisor</th>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Subtotal</th>
                    <th>Impuesto</th>
                    <th>Impuesto Retenido</th>
                    <th>Total</th>
                    <th>UUID</th>
                    <th>Método Pago</th>
                    <th>Forma Pago</th>
                    <th>Moneda</th>
                    <th>¿Encontrado en SAP?</th>
                    <th>Registro SAP</th>
                    <th>Tipo Movimiento</th>
                    <th>Importe SAP</th>
                </tr>
            </thead>
            <tbody id="cuerpoResultados"></tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
$(function () {
    // Actualizar label del file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // Procesar formulario vía AJAX
    $('#formAnalizador').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let btn = $('#btnProcesar');
        btn.html('<i class="fas fa-spinner fa-pulse"></i> Procesando...').prop('disabled', true);

        $.ajax({
            url: '<?= base_url('admin/servicelayer/procesarAnalisisCFDI') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.error) {
                    Toast.fire({icon: 'error', title: resp.error});
                    return;
                }
                if (resp.success) {
                    mostrarResultados(resp.data);
                    Toast.fire({icon: 'success', title: `Procesado ${resp.total} registros`});
                } else {
                    Toast.fire({icon: 'error', title: 'Error inesperado'});
                }
            },
            error: function(xhr) {
                let err = xhr.responseJSON?.error || 'Error al procesar el archivo';
                Toast.fire({icon: 'error', title: err});
            },
            complete: function() {
                btn.html('<i class="fas fa-sync-alt"></i> Procesar y validar contra SAP').prop('disabled', false);
            }
        });
    });

    function mostrarResultados(data) {
        let tbody = $('#cuerpoResultados');
        tbody.empty();
        $.each(data, function(idx, row) {
            let tr = `<tr>
                <td>${escapeHtml(row.rfc)}</td>
                <td>${escapeHtml(row.nombre_emisor)}</td>
                <td>${escapeHtml(row.folio)}</td>
                <td>${escapeHtml(row.fecha)}</td>
                <td>${escapeHtml(row.subtotal)}</td>
                <td>${escapeHtml(row.impuesto)}</td>
                <td>${escapeHtml(row.impuesto_retenido)}</td>
                <td>${escapeHtml(row.total)}</td>
                <td>${escapeHtml(row.uuid)}</td>
                <td>${escapeHtml(row.metodo_pago)}</td>
                <td>${escapeHtml(row.forma_pago)}</td>
                <td>${escapeHtml(row.moneda)}</td>
                <td>${row.encontrado_sap == 'SI' ? '<span class="badge badge-success">SI</span>' : (row.encontrado_sap == 'NO' ? '<span class="badge badge-danger">NO</span>' : '<span class="badge badge-warning">ERROR</span>')}</td>
                <td>${escapeHtml(row.registro_sap)}</td>
                <td>${escapeHtml(row.tipo_movimiento)}</td>
                <td>${escapeHtml(row.importe_sap)}</td>
            </td>`;
            tbody.append(tr);
        });
        $('#cardResultados').show();
        $('#tablaResultados').DataTable({
            destroy: true,
            pageLength: 25,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // Exportar a Excel (usando SheetJS)
    $('#btnExportarExcel').on('click', function() {
        let tabla = document.getElementById('tablaResultados');
        let wb = XLSX.utils.book_new();
        let ws = XLSX.utils.table_to_sheet(tabla, { raw: true });
        XLSX.utils.book_append_sheet(wb, ws, 'ValidacionCFDI');
        XLSX.writeFile(wb, `validacion_sap_${new Date().toISOString().slice(0,19)}.xlsx`);
    });
});
</script>
<?= $this->endSection() ?>