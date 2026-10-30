<?= $this->include('julio101290\boilerplate\Views\load\toggle') ?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<?= $this->section('content') ?>

<input type="hidden" class="txt_csrfname" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<div class="row">
    <div class="col-12">
        <!-- =================================================================
             1. ENCABEZADO DEL REEMBOLSO / COMPROBACIÓN (frmReceipts.cs)
             ================================================================= -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar mr-1"></i>
                    <?= esc($title ?? 'Nuevo Reembolso / Comprobación de Gastos') ?>
                </h3>
                <div class="card-tools">
                    <?php
                    $statusActual = (int) ($U_Status ?? 1);
                    $badgeClass   = 'badge-secondary';
                    $statusText   = '1 - Pendiente';
                    if ($statusActual === 2) {
                        $badgeClass = 'badge-warning';
                        $statusText = '2 - Pendiente Autorización Área';
                    } elseif ($statusActual === 4) {
                        $badgeClass = 'badge-success';
                        $statusText = '4 - Autorizado';
                    } elseif ($statusActual === 5) {
                        $badgeClass = 'badge-info';
                        $statusText = '5 - Cerrado / Pagado';
                    }
                    ?>
                    <span class="badge <?= $badgeClass ?> p-2 mr-2" style="font-size: 0.9rem;">
                        Estatus: <?= $statusText ?>
                    </span>
                    <?php if (!empty($code)): ?>
                        <span class="badge badge-primary p-2 mr-2" style="font-size: 0.9rem;">
                            Code SAP: <?= esc($code) ?>
                        </span>
                    <?php endif; ?>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <form id="formRefunds" autocomplete="off">
                    <input type="hidden" id="idRefund" name="idRefund" value="<?= esc($idRefund ?? 0) ?>">
                    <input type="hidden" id="uuid" name="UUID" value="<?= esc($uuid ?? '') ?>">
                    <input type="hidden" id="idUser" name="idUser" value="<?= esc($idUser ?? '') ?>">
                    <input type="hidden" id="codeSAP" name="code" value="<?= esc($code ?? '') ?>">
                    <input type="hidden" id="U_Status" name="U_Status" value="<?= esc($U_Status ?? 1) ?>">
                    <input type="hidden" id="sapSynced" name="sapSynced" value="<?= esc($sapSynced ?? 0) ?>">

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="idEmpresaRefunds">Empresa <span class="text-danger">*</span></label>
                            <select id="idEmpresaRefunds" name="idEmpresa" class="form-control select2 idEmpresaRefunds" style="width: 100%;" <?= (!empty($sapSynced) && $sapSynced == 1) ? 'disabled' : '' ?>>
                                <option value="0">Seleccione Empresa</option>
                                <?php if (!empty($empresas)): ?>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?= $emp['id'] ?>" <?= (isset($idEmpresa) && (int)$idEmpresa === (int)$emp['id']) ? 'selected' : '' ?>>
                                            <?= $emp['id'] ?> - <?= esc($emp['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="idSucursal">Sucursal (SAP Branch) <span class="text-danger">*</span></label>
                            <select id="idSucursal" name="idSucursal" class="form-control select2 idSucursal" style="width: 100%;" <?= (!empty($sapSynced) && $sapSynced == 1) ? 'disabled' : '' ?>>
                                <?php if (!empty($idSucursal)): ?>
                                    <option value="<?= esc($idSucursal) ?>" selected><?= esc($nombreSucursal ?? $idSucursal) ?></option>
                                <?php else: ?>
                                    <option value="0">Seleccione Sucursal</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="U_TypeVoucher">Tipo de Operación <span class="text-danger">*</span></label>
                            <select id="U_TypeVoucher" name="U_TypeVoucher" class="form-control select2" style="width: 100%;" <?= (!empty($sapSynced) && $sapSynced == 1) ? 'disabled' : '' ?>>
                                <option value="1" <?= (!isset($U_TypeVoucher) || (int)$U_TypeVoucher === 1) ? 'selected' : '' ?>>1 - Reembolso de Gastos (CG)</option>
                                <option value="2" <?= (isset($U_TypeVoucher) && (int)$U_TypeVoucher === 2) ? 'selected' : '' ?>>2 - Comprobación de Viáticos (CV)</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group" id="divCodeMov" style="<?= (isset($U_TypeVoucher) && (int)$U_TypeVoucher === 2) ? '' : 'display:none;' ?>">
                            <label for="U_CodeMov">Folio Anticipo / Viático (`CodeMov`)</label>
                            <input type="text" id="U_CodeMov" name="U_CodeMov" class="form-control" value="<?= esc($U_CodeMov ?? '') ?>" placeholder="Ej. VIAT-001">
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="U_Folio">Folio Comprobante</label>
                            <input type="text" id="U_Folio" name="U_Folio" class="form-control font-weight-bold" value="<?= esc($U_Folio ?? '0') ?>" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="U_Area">Área / Centro de Costo (SAP) <span class="text-danger">*</span></label>
                            <select id="U_Area" name="U_Area" class="form-control select2" style="width: 100%;" <?= (!empty($sapSynced) && $sapSynced == 1) ? 'disabled' : '' ?>>
                                <?php if (!empty($U_Area)): ?>
                                    <option value="<?= esc($U_Area) ?>" selected><?= esc($U_Area) ?></option>
                                <?php else: ?>
                                    <option value="0">Seleccione Área / C. Costo</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="employeeRefund">Empleado (OHEM SAP) <span class="text-danger">*</span></label>
                            <select id="employeeRefund" name="U_Employee" class="form-control select2" style="width: 100%;" <?= (!empty($sapSynced) && $sapSynced == 1) ? 'disabled' : '' ?>>
                                <?php if (!empty($U_Employee)): ?>
                                    <option value="<?= esc($U_Employee) ?>" selected><?= esc($U_Employee . ' - ' . ($empName ?? '')) ?></option>
                                <?php else: ?>
                                    <option value="0">Seleccione Empleado</option>
                                <?php endif; ?>
                            </select>
                            <input type="hidden" id="empName" name="empName" value="<?= esc($empName ?? '') ?>">
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="accountCredit">Cuenta Acreedora / Puente (`OACT`)</label>
                            <select id="accountCredit" name="accountCredit" class="form-control select2" style="width: 100%;">
                                <?php if (!empty($accountCredit)): ?>
                                    <option value="<?= esc($accountCredit) ?>" selected><?= esc($accountCredit) ?></option>
                                <?php else: ?>
                                    <option value="">Automática según @QSYS_CONFIG</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-2 form-group">
                            <label for="date">Fecha Documento <span class="text-danger">*</span></label>
                            <input type="date" id="date" name="date" class="form-control" value="<?= esc($fecha ?? date('Y-m-d')) ?>">
                            <input type="hidden" id="dateVen" name="dateVen" value="<?= esc($dateVen ?? date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-9 form-group">
                            <label for="generalObservations">Comentarios del Reembolso (`U_Coments`)</label>
                            <input type="text" id="generalObservations" name="generalObservations" class="form-control" maxlength="254" value="<?= esc($observations ?? '') ?>" placeholder="Motivo del reembolso o comprobación...">
                        </div>

                        <div class="col-md-3 form-group pt-4">
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" id="copyComments" name="copyComments" value="Y" checked>
                                <label for="copyComments">Copiar comentario a comprobantes</label>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="listProducts" name="listProducts" value="[]">
                </form>
            </div>
        </div>

        <!-- =================================================================
             2. TABLA PRINCIPAL DE DOCUMENTOS DEL REEMBOLSO (@QSYS_GLO_VODE)
             ================================================================= -->
        <div class="card card-default">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-list-ol mr-1"></i> Documentos del Comprobante (`@QSYS_GLO_VODE`)
                </h3>
                <div class="card-tools ml-auto">
                    <?php if (empty($sapSynced) || (int)$sapSynced === 0): ?>
                        <button type="button" class="btn btn-success btn-sm mr-1" id="btnOpenModalXML">
                            <i class="fas fa-file-code"></i> + Capturar Factura CFDI (XML Multilínea)
                        </button>
                        <button type="button" class="btn btn-info btn-sm" id="btnOpenModalNota">
                            <i class="fas fa-receipt"></i> + Capturar Notas / Póliza (Multilínea)
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 380px;">
                    <table class="table table-sm table-bordered table-striped table-hover text-nowrap mb-0" id="tableRefundLines">
                        <thead class="bg-navy text-white text-center">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 50px;">N/A</th>
                                <th style="width: 95px;">Fecha</th>
                                <th style="width: 70px;">Tipo</th>
                                <th>Folio / UUID</th>
                                <th>Proveedor / Establecimiento</th>
                                <th>Partidas / Renglones</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">IVA</th>
                                <th class="text-right">Ret. IVA</th>
                                <th class="text-right">Ret. ISR</th>
                                <th class="text-right">IEPS</th>
                                <th class="text-right">Total</th>
                                <th>Observaciones</th>
                                <th>Estatus SAP</th>
                                <th>DocEntry SAP</th>
                                <th style="width: 90px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRefundLines"></tbody>
                    </table>
                </div>
            </div>

            <!-- =================================================================
                 3. TOTALES Y BOTONES DE GUARDADO (MARIADB + SAP SERVICE LAYER)
                 ================================================================= -->
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-7">
                        <div class="callout callout-info py-2 mb-2">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                <strong>Flujo:</strong>
                                1) Agregue sus <b>Facturas XML</b> (con todos sus conceptos) y sus <b>Notas sin XML</b> (con su PDF opcional).
                                2) Presione <b>Guardar en MariaDB</b>.
                                3) Presione <b>Enviar a SAP (Service Layer)</b>.
                            </small>
                        </div>

                        <?php if (!empty($code)): ?>
                            <div class="btn-group mt-2">
                                <button type="button" class="btn btn-warning btn-sm" id="btnNotifySAP">
                                    <i class="fas fa-bell"></i> Notificar Área (Estatus 2)
                                </button>
                                <button type="button" class="btn btn-success btn-sm" id="btnAuthorizeSAP">
                                    <i class="fas fa-check-circle"></i> Autorizar (Estatus 4)
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" id="btnRejectSAP">
                                    <i class="fas fa-times-circle"></i> Rechazar (Estatus 1)
                                </button>
                                <button type="button" class="btn btn-purple bg-purple btn-sm" id="btnPayInvoicesSAP">
                                    <i class="fas fa-money-check-alt"></i> Saldar Facturas XML (`DoPayments`)
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-5">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-right font-weight-bold" style="width: 60%;">Subtotal:</td>
                                <td><input type="text" id="subTotal" name="subTotal" class="form-control form-control-sm text-right" value="<?= esc($subTotal ?? '0.00') ?>" readonly></td>
                            </tr>
                            <tr>
                                <td class="text-right font-weight-bold">IVA Trasladado:</td>
                                <td><input type="text" id="totalImpuesto" name="taxes" class="form-control form-control-sm text-right" value="<?= esc($taxes ?? '0.00') ?>" readonly></td>
                            </tr>
                            <tr>
                                <td class="text-right font-weight-bold">IEPS:</td>
                                <td><input type="text" id="totalIEPS" name="totalIEPS" class="form-control form-control-sm text-right" value="0.00" readonly></td>
                            </tr>
                            <tr>
                                <td class="text-right font-weight-bold text-danger">(-) Retención IVA:</td>
                                <td><input type="text" id="totalRetencionIVA" name="IVARetenido" class="form-control form-control-sm text-right" value="<?= esc($IVARetenido ?? '0.00') ?>" readonly></td>
                            </tr>
                            <tr>
                                <td class="text-right font-weight-bold text-danger">(-) Retención ISR:</td>
                                <td><input type="text" id="totalRetencionISR" name="ISRRetenido" class="form-control form-control-sm text-right" value="<?= esc($ISRRetenido ?? '0.00') ?>" readonly></td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-right font-weight-bold" style="font-size: 1.1rem;">TOTAL REEMBOLSO:</td>
                                <td>
                                    <input type="text" id="granTotal" name="total" class="form-control form-control-sm text-right font-weight-bold bg-light" style="font-size: 1.1rem;" value="<?= esc($total ?? '0.00') ?>" readonly>
                                    <input type="hidden" id="totalExento" name="tasaCero" value="<?= esc($totalExento ?? '0.00') ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr class="my-2">

                <div class="row">
                    <div class="col-12 text-right">
                        <a href="<?= base_url('admin/refunds') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la Lista
                        </a>
                        <button type="button" class="btn btn-primary btnSaveRefunds" <?= (!empty($sapSynced) && (int)$sapSynced === 1) ? 'disabled' : '' ?>>
                            <i class="fas fa-save"></i> 1. Guardar en MariaDB
                        </button>
                        <button type="button" class="btn btn-danger btnSendToSAP" <?= (!empty($sapSynced) && (int)$sapSynced === 1) ? 'disabled' : '' ?>>
                            <i class="fas fa-cloud-upload-alt"></i> 2. Enviar a SAP (Service Layer)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 1: CAPTURA MULTILÍNEA DE FACTURA CFDI XML (frmPurchaseXML.cs)
     ========================================================================= -->
<div class="modal fade" id="modalAddXML" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" style="max-width: 96%;" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title"><i class="fas fa-file-code mr-1"></i> Captura de Factura CFDI Multilínea (`frmPurchaseXML`)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="xmlEditIndex" value="-1">

                <div class="row bg-light p-2 mb-2 border rounded align-items-end">
                    <div class="col-md-5 form-group mb-1">
                        <label>UUID del CFDI (Bóveda XML)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="xmlUUID" class="form-control" placeholder="Seleccione de la bóveda o pegue el UUID...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" id="btnOpenVaultXML">
                                    <i class="fas fa-search"></i> Buscar XML
                                </button>
                                <button type="button" class="btn btn-success" id="btnLoadXmlByUuid">
                                    <i class="fas fa-sync-alt"></i> Cargar Conceptos
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2 form-group mb-1">
                        <label>Folio Factura</label>
                        <input type="text" id="xmlFolio" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-md-2 form-group mb-1">
                        <label>Fecha Vencimiento</label>
                        <input type="date" id="xmlDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-3 form-group mb-1">
                        <label>Observaciones Factura (`txtObs`)</label>
                        <input type="text" id="xmlComments" class="form-control form-control-sm" maxlength="254">
                    </div>

                    <div class="col-md-3 form-group mb-0">
                        <label>RFC Proveedor</label>
                        <input type="text" id="xmlRfcEmisor" class="form-control form-control-sm" readonly>
                    </div>
                    <div class="col-md-3 form-group mb-0">
                        <label>Socio SAP (`CardCode`)</label>
                        <input type="text" id="xmlCardCode" class="form-control form-control-sm font-weight-bold" readonly>
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label>Razón Social Proveedor</label>
                        <input type="text" id="xmlNombreEmisor" class="form-control form-control-sm" readonly>
                    </div>
                </div>

                <!-- Barra de llenado rápido (btnRefresh en frmPurchaseXML.cs) -->
                <div class="card card-outline card-info mb-2">
                    <div class="card-header py-1">
                        <h6 class="card-title mb-0" style="font-size: 0.85rem;">
                            <i class="fas fa-magic mr-1"></i> Valores por defecto para rellenar conceptos (`btnRefresh`)
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row align-items-end">
                            <div class="col-md-3 form-group mb-1">
                                <label class="small mb-0">Artículo SAP (`txtItem`)</label>
                                <select id="xmlDefaultItem" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option value="">Seleccione Artículo SAP</option>
                                </select>
                            </div>
                            <div class="col-md-2 form-group mb-1">
                                <label class="small mb-0">Admón/Oper (`cboAdmOper`)</label>
                                <select id="xmlDefaultAdmin" class="form-control form-control-sm">
                                    <option value="A">A - Administración</option>
                                    <option value="O">O - Operativo</option>
                                </select>
                            </div>
                            <div class="col-md-2 form-group mb-1">
                                <label class="small mb-0">Almacén (`WhsCode`)</label>
                                <select id="xmlDefaultWhs" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option value="">Automático Sucursal</option>
                                </select>
                            </div>
                            <div class="col-md-2 form-group mb-1">
                                <label class="small mb-0">Cuenta (`txtAcc`)</label>
                                <select id="xmlDefaultAccount" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option value="">Cuenta contable</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group mb-1">
                                <label class="small mb-0">Proyecto (`txtProject`)</label>
                                <select id="xmlDefaultProject" class="form-control form-control-sm select2" style="width: 100%;">
                                    <option value="">Sin Proyecto</option>
                                </select>
                            </div>

                            <div class="col-md-2 form-group mb-0">
                                <label class="small mb-0">Dim 1 (Área)</label>
                                <select id="xmlDefaultDim1" class="form-control form-control-sm select2" style="width: 100%;"></select>
                            </div>
                            <div class="col-md-2 form-group mb-0">
                                <label class="small mb-0">Dim 2</label>
                                <select id="xmlDefaultDim2" class="form-control form-control-sm select2" style="width: 100%;"></select>
                            </div>
                            <div class="col-md-2 form-group mb-0">
                                <label class="small mb-0">Dim 3</label>
                                <select id="xmlDefaultDim3" class="form-control form-control-sm select2" style="width: 100%;"></select>
                            </div>
                            <div class="col-md-2 form-group mb-0">
                                <label class="small mb-0">Dim 4</label>
                                <select id="xmlDefaultDim4" class="form-control form-control-sm select2" style="width: 100%;"></select>
                            </div>
                            <div class="col-md-2 form-group mb-0">
                                <label class="small mb-0">Dim 5</label>
                                <select id="xmlDefaultDim5" class="form-control form-control-sm select2" style="width: 100%;"></select>
                            </div>
                            <div class="col-md-2 form-group mb-0">
                                <button type="button" class="btn btn-info btn-sm btn-block" id="btnApplyDefaultsXML">
                                    <i class="fas fa-arrow-down"></i> Aplicar a Líneas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Matriz Multilínea de Conceptos del XML -->
                <div class="table-responsive border" style="max-height: 270px;">
                    <table class="table table-sm table-bordered table-striped text-nowrap mb-0" id="tableXmlMatrix">
                        <thead class="bg-secondary text-white text-center small">
                            <tr>
                                <th>#</th>
                                <th>Clave SAT</th>
                                <th style="min-width: 180px;">Descripción Factura</th>
                                <th style="min-width: 160px;">Artículo SAP (`ItemCode`) *</th>
                                <th style="width: 85px;">Adm/Oper</th>
                                <th style="width: 110px;">Almacén</th>
                                <th style="width: 120px;">Cuenta</th>
                                <th style="width: 110px;">Proyecto</th>
                                <th style="width: 100px;">Dim 1</th>
                                <th style="width: 100px;">Dim 2</th>
                                <th style="width: 100px;">Dim 3</th>
                                <th style="width: 85px;">Cant.</th>
                                <th style="width: 100px;">Precio U.</th>
                                <th style="width: 75px;">% IVA</th>
                                <th style="width: 90px;">IVA</th>
                                <th style="width: 100px;">Total Línea</th>
                                <th style="width: 75px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyXmlMatrix">
                            <tr><td colspan="17" class="text-center text-muted">Busque un UUID y presione "Cargar Conceptos".</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2 bg-light p-2 border rounded">
                    <div class="col-md-2">
                        <small class="text-muted d-block">Subtotal</small>
                        <input type="number" step="0.01" id="xmlSubtotal" class="form-control form-control-sm font-weight-bold" value="0.00" readonly>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">IVA Trasladado</small>
                        <input type="number" step="0.01" id="xmlIVA" class="form-control form-control-sm" value="0.00" readonly>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted d-block">IEPS</small>
                        <input type="number" step="0.01" id="xmlIEPS" class="form-control form-control-sm" value="0.00">
                    </div>
                    <div class="col-md-2">
                        <small class="text-danger d-block">(-) Retención IVA</small>
                        <input type="number" step="0.01" id="xmlRetIVA" class="form-control form-control-sm" value="0.00">
                    </div>
                    <div class="col-md-2">
                        <small class="text-danger d-block">(-) Retención ISR</small>
                        <input type="number" step="0.01" id="xmlRetISR" class="form-control form-control-sm" value="0.00">
                    </div>
                    <div class="col-md-2">
                        <small class="font-weight-bold d-block text-success">TOTAL FACTURA XML</small>
                        <input type="number" step="0.01" id="xmlTotal" class="form-control form-control-sm font-weight-bold bg-white" value="0.00" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="btnConfirmAddXML">
                    <i class="fas fa-check"></i> Guardar Factura XML en el Reembolso
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: BÓVEDA DE CFDI RECIBIDOS (xmlSinAsignar I, E, P, T)
     ========================================================================= -->
<div class="modal fade" id="modalVaultXML" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title"><i class="fas fa-folder-open mr-1"></i> Seleccionar CFDI de la Bóveda</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 align-items-center">
                    <div class="col-md-4">
                        <label class="small font-weight-bold mb-0">Tipo de Comprobante CFDI:</label>
                        <select id="filtroTipoComprobanteXML" class="form-control form-control-sm">
                            <option value="I" selected>I - Ingreso (Facturas)</option>
                            <option value="E">E - Egreso (Notas de Crédito)</option>
                            <option value="P">P - Pago (Complementos de Pago)</option>
                            <option value="T">T - Traslado</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped w-100" id="tableVaultXML">
                        <thead>
                            <tr>
                                <th>UUID</th>
                                <th>Fecha</th>
                                <th>RFC Emisor</th>
                                <th>Nombre Emisor</th>
                                <th>Total</th>
                                <th>Seleccionar</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL 3: CAPTURA MULTILÍNEA DE NOTAS + PDF OPCIONAL (frmPurchaseNotes.cs)
     ========================================================================= -->
<div class="modal fade" id="modalAddNota" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl" style="max-width: 94%;" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title"><i class="fas fa-receipt mr-1"></i> Captura Multilínea de Notas sin XML (`frmPurchaseNotes`)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="notaEditIndex" value="-1">

                <div class="border rounded p-2 bg-light mb-2">
                    <div class="row">
                        <div class="col-md-2 form-group mb-2">
                            <label class="small mb-0">Tipo de Cuenta *</label>
                            <select id="notaAccountType" class="form-control form-control-sm">
                                <option value="Gastos/Costos">Gastos/Costos</option>
                                <option value="Deudora">Deudora</option>
                            </select>
                        </div>

                        <div class="col-md-2 form-group mb-2">
                            <label class="small mb-0">Folio Nota *</label>
                            <input type="text" id="notaFolio" class="form-control form-control-sm" placeholder="Ej. S/N, 0045">
                        </div>

                        <div class="col-md-3 form-group mb-2">
                            <label class="small mb-0">Proveedor / Concepto *</label>
                            <input type="text" id="notaProvider" class="form-control form-control-sm" placeholder="Establecimiento...">
                        </div>

                        <div class="col-md-3 form-group mb-2">
                            <label class="small mb-0">Cuenta de Cargo (`txtAccount`) *</label>
                            <select id="notaAccount" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>

                        <div class="col-md-2 form-group mb-2">
                            <label class="small mb-0">Importe *</label>
                            <input type="number" step="0.01" id="notaAmount" class="form-control form-control-sm font-weight-bold" value="0.00">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group mb-2">
                            <label class="small mb-0">Cuenta Acreedora (`txtCredit`) *</label>
                            <select id="notaAffectable" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>

                        <div class="col-md-2 form-group mb-2">
                            <label class="small mb-0">Fecha Nota *</label>
                            <input type="date" id="notaDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-4 form-group mb-2">
                            <label class="small mb-0">Anexo PDF Opcional (`U_Archivo` en renglón póliza)</label>
                            <div class="input-group input-group-sm">
                                <input type="file" id="notaFileInput" class="form-control form-control-sm" accept=".pdf,.xml,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <div class="col-md-3 form-group mb-2">
                            <label class="small mb-0">Observaciones (`txtObs`)</label>
                            <input type="text" id="notaComments" class="form-control form-control-sm" maxlength="254">
                        </div>
                    </div>

                    <div class="row" id="divNotaDeudora" style="display: none;">
                        <div class="col-md-6 form-group mb-2">
                            <label class="small mb-0">Empleado Auxiliar (`txtAux`) *</label>
                            <select id="notaAuxEmployee" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-6 form-group mb-2">
                            <label class="small mb-0">Código de Movimiento (`cboMovement`)</label>
                            <input type="text" id="notaCodeMov" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="row align-items-end">
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-0">Proyecto (`PrjCode`)</label>
                            <select id="notaProject" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-0">Dim 1 (Área)</label>
                            <select id="notaDim1" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-0">Dim 2</label>
                            <select id="notaDim2" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-0">Dim 3</label>
                            <select id="notaDim3" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-0">Dim 4</label>
                            <select id="notaDim4" class="form-control form-control-sm select2" style="width: 100%;"></select>
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <button type="button" class="btn btn-primary btn-sm btn-block" id="btnAddRowToNotaMatrix">
                                <i class="fas fa-plus"></i> Agregar a Matriz (`btnAdd`)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Matriz Multilínea de Notas (mtxNotes en frmPurchaseNotes.cs) -->
                <div class="table-responsive border" style="max-height: 240px;">
                    <table class="table table-sm table-bordered table-striped text-nowrap mb-0" id="tableNotaMatrix">
                        <thead class="bg-secondary text-white text-center small">
                            <tr>
                                <th>#</th>
                                <th>Folio</th>
                                <th>Proveedor</th>
                                <th>Cuenta Cargo</th>
                                <th>Cuenta Acreedora</th>
                                <th>Proyecto</th>
                                <th>Dim 1</th>
                                <th>Dim 2</th>
                                <th>Dim 3</th>
                                <th>Anexo PDF (`writable`)</th>
                                <th>Observaciones</th>
                                <th class="text-right">Importe</th>
                                <th>Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyNotaMatrix">
                            <tr><td colspan="13" class="text-center text-muted">Capture los datos arriba y presione "Agregar a Matriz".</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2">
                    <div class="col-md-9"></div>
                    <div class="col-md-3 text-right">
                        <label class="small font-weight-bold mb-0">Total Póliza de Notas:</label>
                        <input type="text" id="notaMatrixTotal" class="form-control form-control-sm text-right font-weight-bold bg-light" value="0.00" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info btn-sm" id="btnConfirmAddNota">
                    <i class="fas fa-check"></i> Guardar Póliza de Notas en el Reembolso (`btnCreate`)
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
    var refundLines        = <?= json_encode($listProducts ?? [], JSON_UNESCAPED_UNICODE) ?>;
    var currentXmlConcepts = [];
    var currentNotaRows    = [];
    var tableVaultXML      = null;

    $(document).ready(function () {

        // =====================================================================
        // 1. SELECT2 DEL ENCABEZADO (Con ruta getSucursalesAjax)
        // =====================================================================
        $('#idEmpresaRefunds, #U_TypeVoucher').select2();

        $('#idSucursal').select2({
            ajax: {
                url: "<?= site_url('admin/sucursales/getSucursalesAjax') ?>",
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        searchTerm: params.term,
                        idEmpresa: $('#idEmpresaRefunds').val(),
                        [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
                    };
                },
                processResults: function (response) {
                    if (response.token) $('.txt_csrfname').val(response.token);
                    return { results: response.data };
                },
                cache: true
            }
        });

        initSelect2Ajax('#U_Area', "<?= site_url('admin/refunds/getDimensionAjax/1') ?>", null);
        initSelect2Ajax('#accountCredit', "<?= site_url('admin/refunds/getAccountsAjax') ?>", null);

        $('#employeeRefund').select2({
            ajax: {
                url: "<?= site_url('admin/refunds/getEmployeesAjax') ?>",
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        searchTerm: params.term,
                        idBranchOffice: $('#idSucursal').val(),
                        [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
                    };
                },
                processResults: function (response) {
                    if (response.token) $('.txt_csrfname').val(response.token);
                    return { results: response.data };
                },
                cache: true
            }
        }).on('select2:select', function (e) {
            $('#empName').val(e.params.data.empName || e.params.data.text || '');
        });

        $('#U_TypeVoucher').on('change', function () {
            if ($(this).val() === '2') {
                $('#divCodeMov').show();
            } else {
                $('#divCodeMov').hide();
                $('#U_CodeMov').val('');
            }
        });

        // =====================================================================
        // 2. SELECT2 DE LOS MODALES (XML Y NOTAS)
        // =====================================================================
        initSelect2Ajax('#xmlDefaultItem', "<?= site_url('admin/refunds/getItemCodeAjax') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultWhs', "<?= site_url('admin/refunds/getWarehousesAjax') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultAccount', "<?= site_url('admin/refunds/getAccountsAjax') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultProject', "<?= site_url('admin/refunds/getPrjCodeAjax') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultDim1', "<?= site_url('admin/refunds/getDimensionAjax/1') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultDim2', "<?= site_url('admin/refunds/getDimensionAjax/2') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultDim3', "<?= site_url('admin/refunds/getDimensionAjax/3') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultDim4', "<?= site_url('admin/refunds/getDimensionAjax/4') ?>", '#modalAddXML');
        initSelect2Ajax('#xmlDefaultDim5', "<?= site_url('admin/refunds/getDimensionAjax/5') ?>", '#modalAddXML');

        initSelect2Ajax('#notaAccount', "<?= site_url('admin/refunds/getAccountsAjax') ?>", '#modalAddNota');
        initSelect2Ajax('#notaAffectable', "<?= site_url('admin/refunds/getAccountsAjax') ?>", '#modalAddNota');
        initSelect2Ajax('#notaAuxEmployee', "<?= site_url('admin/refunds/getEmployeesAjax') ?>", '#modalAddNota');
        initSelect2Ajax('#notaProject', "<?= site_url('admin/refunds/getPrjCodeAjax') ?>", '#modalAddNota');
        initSelect2Ajax('#notaDim1', "<?= site_url('admin/refunds/getDimensionAjax/1') ?>", '#modalAddNota');
        initSelect2Ajax('#notaDim2', "<?= site_url('admin/refunds/getDimensionAjax/2') ?>", '#modalAddNota');
        initSelect2Ajax('#notaDim3', "<?= site_url('admin/refunds/getDimensionAjax/3') ?>", '#modalAddNota');
        initSelect2Ajax('#notaDim4', "<?= site_url('admin/refunds/getDimensionAjax/4') ?>", '#modalAddNota');

        $('#notaAccountType').on('change', function () {
            if ($(this).val() === 'Deudora') {
                $('#divNotaDeudora').slideDown(150);
            } else {
                $('#divNotaDeudora').slideUp(150);
            }
        });

        renderRefundLines();

        // =====================================================================
        // 3. LÓGICA DEL MODAL CFDI XML MULTILÍNEA (frmPurchaseXML.cs)
        // =====================================================================
        $('#btnOpenModalXML').on('click', function () {
            if (!validarEncabezadoBasico()) return;
            resetModalXML();
            $('#modalAddXML').modal('show');
        });

        $('#btnOpenVaultXML').on('click', function () {
            $('#modalVaultXML').modal('show');
            var tipoComp = $('#filtroTipoComprobanteXML').val() || 'I';
            var urlVault = "<?= base_url('admin/refunds/xmlSinAsignar') ?>/" + tipoComp;

            if (tableVaultXML) {
                tableVaultXML.ajax.url(urlVault).load();
            } else {
                tableVaultXML = $('#tableVaultXML').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: urlVault,
                        type: "GET"
                    },
                    columns: [
                        { data: 'uuidTimbre' },
                        { data: 'fecha' },
                        { data: 'rfcEmisor' },
                        { data: 'nombreEmisor' },
                        { data: 'total' },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row) {
                                return `<button type="button" class="btn btn-success btn-xs btnSelectVaultUuid" data-uuid="${row.uuidTimbre}">
                                            <i class="fas fa-check"></i> Elegir
                                        </button>`;
                            }
                        }
                    ]
                });
            }
        });

        $('#filtroTipoComprobanteXML').on('change', function () {
            if (tableVaultXML) {
                var tipoComp = $(this).val() || 'I';
                tableVaultXML.ajax.url("<?= base_url('admin/refunds/xmlSinAsignar') ?>/" + tipoComp).load();
            }
        });

        $(document).on('click', '.btnSelectVaultUuid', function () {
            $('#xmlUUID').val($(this).data('uuid'));
            $('#modalVaultXML').modal('hide');
            $('#btnLoadXmlByUuid').trigger('click');
        });

        $('#btnLoadXmlByUuid').on('click', function () {
            var uuid = $('#xmlUUID').val().trim();
            if (!uuid) {
                Toast.fire({ icon: 'warning', title: 'Ingrese o seleccione un UUID.' });
                return;
            }

            $.post("<?= site_url('admin/refunds/getXmlDataAjax') ?>", {
                uuid: uuid,
                [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
            }, function (res) {
                if (res.token) $('.txt_csrfname').val(res.token);
                if (res.error) {
                    Swal.fire('Atención', res.message, 'warning');
                    return;
                }

                $('#xmlFolio').val(res.folio);
                $('#xmlDate').val(res.fecha);
                $('#xmlRfcEmisor').val(res.rfcEmisor);
                $('#xmlCardCode').val(res.cardCode || '');
                $('#xmlNombreEmisor').val(res.nombreEmisor);
                $('#xmlIEPS').val(parseFloat(res.ieps || 0).toFixed(2));
                $('#xmlRetIVA').val(parseFloat(res.retIVA || 0).toFixed(2));
                $('#xmlRetISR').val(parseFloat(res.retISR || 0).toFixed(2));

                if (!$('#xmlComments').val()) {
                    $('#xmlComments').val(res.descripcionConceptos || '');
                }

                var areaDefault = $('#U_Area').val() || '';
                currentXmlConcepts = (res.conceptLines || []).map(function (c) {
                    c.dim1 = c.dim1 || areaDefault;
                    return c;
                });

                renderXmlMatrix();
            }, 'json');
        });

        $('#btnApplyDefaultsXML').on('click', function () {
            var defItem  = $('#xmlDefaultItem').val() || '';
            var defAdmin = $('#xmlDefaultAdmin').val() || 'A';
            var defWhs   = $('#xmlDefaultWhs').val() || '';
            var defAcc   = $('#xmlDefaultAccount').val() || '';
            var defProj  = $('#xmlDefaultProject').val() || '';
            var defDim1  = $('#xmlDefaultDim1').val() || $('#U_Area').val() || '';
            var defDim2  = $('#xmlDefaultDim2').val() || '';
            var defDim3  = $('#xmlDefaultDim3').val() || '';
            var defDim4  = $('#xmlDefaultDim4').val() || '';
            var defDim5  = $('#xmlDefaultDim5').val() || '';

            currentXmlConcepts.forEach(function (c) {
                if (defItem && defItem !== '0') c.itemCode = defItem;
                if (defAdmin) c.adminOper = defAdmin;
                if (defWhs && defWhs !== '0') c.whsCode = defWhs;
                if (defAcc && defAcc !== '0') c.account = defAcc;
                if (defProj && defProj !== '0') c.project = defProj;
                if (defDim1 && defDim1 !== '0') c.dim1 = defDim1;
                if (defDim2 && defDim2 !== '0') c.dim2 = defDim2;
                if (defDim3 && defDim3 !== '0') c.dim3 = defDim3;
                if (defDim4 && defDim4 !== '0') c.dim4 = defDim4;
                if (defDim5 && defDim5 !== '0') c.dim5 = defDim5;
            });

            renderXmlMatrix();
            Toast.fire({ icon: 'success', title: 'Valores aplicados a las líneas del XML.' });
        });

        $('#xmlIEPS, #xmlRetIVA, #xmlRetISR').on('input change', function () {
            recalcularTotalesMatrizXML();
        });

        $('#btnConfirmAddXML').on('click', function () {
            var uuid = $('#xmlUUID').val().trim();
            if (!uuid || currentXmlConcepts.length === 0) {
                Toast.fire({ icon: 'error', title: 'Debe cargar un CFDI con al menos un concepto.' });
                return;
            }

            var sinArticulo = currentXmlConcepts.some(function (c) {
                return !c.itemCode || c.itemCode.trim() === '' || c.itemCode === '0';
            });
            if (sinArticulo) {
                Toast.fire({ icon: 'error', title: 'Todos los conceptos del XML deben tener un Artículo SAP (ItemCode) asignado.' });
                return;
            }

            var editIdx = parseInt($('#xmlEditIndex').val());
            var total   = parseFloat($('#xmlTotal').val()) || 0;

            var lineObj = {
                U_NA: 'N',
                U_Date: $('#xmlDate').val(),
                U_Type: 'XML',
                docFolio: $('#xmlFolio').val() || uuid.slice(-6),
                UUID: uuid,
                U_Provider: $('#xmlCardCode').val() || $('#xmlRfcEmisor').val(),
                providerName: $('#xmlNombreEmisor').val(),
                codeProduct: currentXmlConcepts[0].itemCode,
                itemCode: currentXmlConcepts[0].itemCode,
                description: $('#xmlComments').val(),
                U_Coments: $('#xmlComments').val(),
                dim1: currentXmlConcepts[0].dim1 || $('#U_Area').val(),
                project: currentXmlConcepts[0].project || '',
                U_Subtotal: parseFloat($('#xmlSubtotal').val()) || 0,
                U_IVA: parseFloat($('#xmlIVA').val()) || 0,
                U_IEPS: parseFloat($('#xmlIEPS').val()) || 0,
                U_RetIVA: parseFloat($('#xmlRetIVA').val()) || 0,
                U_ISR: parseFloat($('#xmlRetISR').val()) || 0,
                U_Total: total,
                neto: total,
                U_Status: 'Abierto',
                U_DocEntry: '',
                conceptLines: JSON.parse(JSON.stringify(currentXmlConcepts))
            };

            if (editIdx >= 0) {
                refundLines[editIdx] = lineObj;
            } else {
                refundLines.push(lineObj);
            }

            renderRefundLines();
            $('#modalAddXML').modal('hide');
        });

        // =====================================================================
        // 4. LÓGICA DEL MODAL NOTAS MULTILÍNEA + SUBIDA PDF A WRITABLE
        // =====================================================================
        $('#btnOpenModalNota').on('click', function () {
            if (!validarEncabezadoBasico()) return;
            resetModalNota();
            $('#modalAddNota').modal('show');
        });

        $('#btnAddRowToNotaMatrix').on('click', function () {
            var folio      = $('#notaFolio').val().trim();
            var provider   = $('#notaProvider').val().trim();
            var account    = $('#notaAccount').val();
            var affectable = $('#notaAffectable').val() || $('#accountCredit').val();
            var amount     = parseFloat($('#notaAmount').val()) || 0;

            if (!folio || !provider || !account || account === '0' || !affectable || affectable === '0' || amount <= 0) {
                Toast.fire({ icon: 'error', title: 'Complete Folio, Proveedor, Cuenta Cargo, Cuenta Acreedora e Importe > 0.' });
                return;
            }

            var fileInput = $('#notaFileInput')[0];
            if (fileInput.files && fileInput.files.length > 0) {
                var fd = new FormData();
                fd.append('notePdfFile', fileInput.files[0]);
                fd.append($('.txt_csrfname').attr('name'), $('.txt_csrfname').val());

                $('#btnAddRowToNotaMatrix').prop('disabled', true);
                $.ajax({
                    url: "<?= site_url('admin/refunds/uploadNoteFileAjax') ?>",
                    type: "POST",
                    data: fd,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                    success: function (res) {
                        $('#btnAddRowToNotaMatrix').prop('disabled', false);
                        if (res.token) $('.txt_csrfname').val(res.token);
                        if (res.error) {
                            Swal.fire('Error al subir archivo', res.message, 'error');
                            return;
                        }
                        pushRowToNotaMatrix(res.relativePath, res.originalName);
                    },
                    error: function (jqXHR) {
                        $('#btnAddRowToNotaMatrix').prop('disabled', false);
                        Swal.fire('Error', jqXHR.responseText || 'Error al subir el PDF', 'error');
                    }
                });
            } else {
                pushRowToNotaMatrix('', '');
            }
        });

        $('#btnConfirmAddNota').on('click', function () {
            if (currentNotaRows.length === 0) {
                Toast.fire({ icon: 'error', title: 'Agregue al menos un renglón a la matriz de notas con el botón "Agregar a Matriz".' });
                return;
            }

            var editIdx   = parseInt($('#notaEditIndex').val());
            var sumAmount = currentNotaRows.reduce((acc, r) => acc + parseFloat(r.U_Total || 0), 0);
            var firstRow  = currentNotaRows[0];

            var notaDocObj = {
                U_NA: 'N',
                U_Date: firstRow.U_Date,
                U_Type: 'Nota',
                docFolio: firstRow.docFolio,
                UUID: '',
                U_Provider: firstRow.U_Provider,
                accountType: firstRow.accountType,
                account: firstRow.account,
                affectable: firstRow.affectable,
                dim1: firstRow.dim1,
                project: firstRow.project,
                pdfFile: firstRow.pdfFile || '',
                U_Coments: firstRow.U_Coments,
                U_Subtotal: sumAmount,
                U_IVA: 0,
                U_IEPS: 0,
                U_RetIVA: 0,
                U_ISR: 0,
                U_Total: sumAmount,
                neto: sumAmount,
                U_Status: 'Cerrado',
                U_DocEntry: '',
                noteLines: JSON.parse(JSON.stringify(currentNotaRows))
            };

            if (editIdx >= 0) {
                refundLines[editIdx] = notaDocObj;
            } else {
                refundLines.push(notaDocObj);
            }

            renderRefundLines();
            $('#modalAddNota').modal('hide');
        });

        // =====================================================================
        // 5. GUARDAR EN MARIADB Y ENVIAR A SAP SERVICE LAYER
        // =====================================================================
        $('.btnSaveRefunds').on('click', function () {
            if (!validarEncabezadoBasico()) return;
            if (refundLines.length === 0) {
                Toast.fire({ icon: 'error', title: 'Agregue al menos una factura XML o una Nota.' });
                return;
            }

            $('#listProducts').val(JSON.stringify(refundLines));
            var formData = new FormData($('#formRefunds')[0]);
            formData.append($('.txt_csrfname').attr('name'), $('.txt_csrfname').val());

            $('.btnSaveRefunds').prop('disabled', true);
            $.ajax({
                url: "<?= base_url('admin/refunds/save') ?>",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (res) {
                    $('.btnSaveRefunds').prop('disabled', false);
                    if (res.token) $('.txt_csrfname').val(res.token);
                    if (!res.error) {
                        $('#idRefund').val(res.idRefund);
                        $('#U_Folio').val(res.folio);
                        Swal.fire({ icon: 'success', title: 'Guardado en MariaDB', text: res.message, timer: 2000 });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function (jqXHR) {
                    $('.btnSaveRefunds').prop('disabled', false);
                    Swal.fire('Error del Servidor', jqXHR.responseText || 'Error al guardar en MariaDB', 'error');
                }
            });
        });

        $('.btnSendToSAP').on('click', function (e) {
            e.preventDefault();

            var idRefund = parseInt($('#idRefund').val()) || 0;
            if (idRefund <= 0) {
                Swal.fire('Atención', 'Primero guarde el reembolso en MariaDB (Botón 1).', 'warning');
                return;
            }

            Swal.fire({
                title: '¿Enviar Reembolso a SAP?',
                text: 'Se enviarán las Facturas Multilínea, Pólizas de Notas (con sus anexos PDF) y el registro en @QSYS_GLO_VOUC por Service Layer.',
                icon: 'question',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, enviar a SAP',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed || result.value) {
                    $('.btnSendToSAP').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando a SAP...');

                    var postData = {
                        idRefund: idRefund,
                        UUID: $('#uuid').val()
                    };
                    var csrfName = $('.txt_csrfname').attr('name');
                    if (csrfName) {
                        postData[csrfName] = $('.txt_csrfname').val();
                    }

                    $.ajax({
                        url: "<?= base_url('admin/refunds/sendToSAP') ?>",
                        method: "POST",
                        data: postData,
                        dataType: "json",
                        success: function (res) {
                            if (res && res.token) $('.txt_csrfname').val(res.token);
                            if (res && !res.error) {
                                Swal.fire('¡Sincronizado!', res.message, 'success').then(() => {
                                    window.location.href = "<?= base_url('admin/refunds/edit') ?>/" + $('#uuid').val();
                                });
                            } else {
                                $('.btnSendToSAP').prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i> 2. Enviar a SAP (Service Layer)');
                                Swal.fire('Error en SAP Service Layer', (res && res.message) ? res.message : 'Error al procesar', 'error');
                            }
                        },
                        error: function (jqXHR) {
                            console.error("Error AJAX sendToSAP:", jqXHR.responseText);
                            $('.btnSendToSAP').prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i> 2. Enviar a SAP (Service Layer)');
                            Swal.fire('Error del Servidor', jqXHR.responseText || 'Fallo en la petición', 'error');
                        }
                    });
                }
            });
        });

        // Botones de Flujo de Autorización y Pagos en SAP
        $('#btnNotifySAP').on('click', function () {
            ejecutarAccionFlujoSAP("<?= base_url('admin/refunds/notifyVoucher') ?>", '¿Notificar comprobante al área (Estatus 2)?');
        });

        $('#btnAuthorizeSAP').on('click', function () {
            ejecutarAccionFlujoSAP("<?= base_url('admin/refunds/authorizeVoucher') ?>", '¿Autorizar comprobante en SAP (Estatus 4)?');
        });

        $('#btnRejectSAP').on('click', function () {
            ejecutarAccionFlujoSAP("<?= base_url('admin/refunds/rejectVoucher') ?>", '¿Rechazar comprobante y regresarlo a Pendiente?');
        });

        $('#btnPayInvoicesSAP').on('click', function () {
            Swal.fire({
                title: '¿Saldar facturas XML en SAP?',
                text: 'Aplicará el Pago Efectuado en SAP a cada factura XML abierta contra la cuenta puente del reembolso.',
                icon: 'warning',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, generar pagos'
            }).then((res) => {
                if (res.isConfirmed || res.value) {
                    var postData = { idRefund: $('#idRefund').val() };
                    var csrfName = $('.txt_csrfname').attr('name');
                    if (csrfName) postData[csrfName] = $('.txt_csrfname').val();

                    $.ajax({
                        url: "<?= base_url('admin/refunds/payRefundInvoicesSAP') ?>",
                        method: "POST",
                        data: postData,
                        dataType: "json",
                        success: function (resp) {
                            if (resp.token) $('.txt_csrfname').val(resp.token);
                            if (!resp.error) {
                                Swal.fire('Éxito', resp.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', resp.message, 'error');
                            }
                        },
                        error: function (jqXHR) {
                            Swal.fire('Error', jqXHR.responseText || 'Error de red', 'error');
                        }
                    });
                }
            });
        });
    });

    // =========================================================================
    // FUNCIONES DE MATRIZ MULTILÍNEA XML Y MATRIZ MULTILÍNEA NOTAS
    // =========================================================================

    function renderXmlMatrix() {
        var html = '';
        currentXmlConcepts.forEach(function (c, i) {
            html += `<tr>
                <td class="text-center">${i + 1}</td>
                <td><small>${c.claveProdServ || ''}</small></td>
                <td><input type="text" class="form-control form-control-sm" value="${c.description || ''}" onchange="updateXmlConcept(${i}, 'description', this.value)"></td>
                <td><input type="text" class="form-control form-control-sm font-weight-bold" value="${c.itemCode || ''}" placeholder="ItemCode SAP" onchange="updateXmlConcept(${i}, 'itemCode', this.value)"></td>
                <td>
                    <select class="form-control form-control-sm" onchange="updateXmlConcept(${i}, 'adminOper', this.value)">
                        <option value="A" ${c.adminOper === 'A' ? 'selected' : ''}>A</option>
                        <option value="O" ${c.adminOper === 'O' ? 'selected' : ''}>O</option>
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm" value="${c.whsCode || ''}" placeholder="Auto Sucursal" onchange="updateXmlConcept(${i}, 'whsCode', this.value)"></td>
                <td><input type="text" class="form-control form-control-sm" value="${c.account || ''}" onchange="updateXmlConcept(${i}, 'account', this.value)"></td>
                <td><input type="text" class="form-control form-control-sm" value="${c.project || ''}" onchange="updateXmlConcept(${i}, 'project', this.value)"></td>
                <td><input type="text" class="form-control form-control-sm" value="${c.dim1 || ''}" onchange="updateXmlConcept(${i}, 'dim1', this.value)"></td>
                <td><input type="text" class="form-control form-control-sm" value="${c.dim2 || ''}" onchange="updateXmlConcept(${i}, 'dim2', this.value)"></td>
                <td><input type="text" class="form-control form-control-sm" value="${c.dim3 || ''}" onchange="updateXmlConcept(${i}, 'dim3', this.value)"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-right" value="${c.cant}" onchange="updateXmlConceptQtyPrice(${i}, 'cant', this.value)"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm text-right" value="${c.price}" onchange="updateXmlConceptQtyPrice(${i}, 'price', this.value)"></td>
                <td class="text-right">${c.porcentTax}%</td>
                <td class="text-right">${parseFloat(c.iva || 0).toFixed(2)}</td>
                <td class="text-right font-weight-bold">${parseFloat(c.total || 0).toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-info btn-xs" onclick="duplicateXmlConcept(${i})" title="Duplicar línea"><i class="fas fa-copy"></i></button>
                    <button type="button" class="btn btn-danger btn-xs" onclick="removeXmlConcept(${i})" title="Eliminar línea"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });

        $('#tbodyXmlMatrix').html(html);
        recalcularTotalesMatrizXML();
    }

    function updateXmlConcept(idx, field, val) {
        currentXmlConcepts[idx][field] = val;
    }

    function updateXmlConceptQtyPrice(idx, field, val) {
        currentXmlConcepts[idx][field] = parseFloat(val) || 0;
        var cant = parseFloat(currentXmlConcepts[idx].cant) || 0;
        var prc  = parseFloat(currentXmlConcepts[idx].price) || 0;
        var desc = parseFloat(currentXmlConcepts[idx].discount) || 0;
        var rate = (parseFloat(currentXmlConcepts[idx].porcentTax) || 0) / 100.0;

        var sub = (cant * prc) - desc;
        var iva = sub * rate;
        currentXmlConcepts[idx].subTotal = sub;
        currentXmlConcepts[idx].iva      = iva;
        currentXmlConcepts[idx].total    = sub + iva;
        renderXmlMatrix();
    }

    function duplicateXmlConcept(idx) {
        var copy = JSON.parse(JSON.stringify(currentXmlConcepts[idx]));
        currentXmlConcepts.splice(idx + 1, 0, copy);
        renderXmlMatrix();
    }

    function removeXmlConcept(idx) {
        currentXmlConcepts.splice(idx, 1);
        renderXmlMatrix();
    }

    function recalcularTotalesMatrizXML() {
        var sub = 0;
        var iva = 0;
        currentXmlConcepts.forEach(function (c) {
            sub += parseFloat(c.subTotal || 0);
            iva += parseFloat(c.iva || 0);
        });
        var ieps   = parseFloat($('#xmlIEPS').val()) || 0;
        var retIva = parseFloat($('#xmlRetIVA').val()) || 0;
        var retIsr = parseFloat($('#xmlRetISR').val()) || 0;
        var total  = sub + iva + ieps - retIva - retIsr;

        $('#xmlSubtotal').val(sub.toFixed(2));
        $('#xmlIVA').val(iva.toFixed(2));
        $('#xmlTotal').val(total.toFixed(2));
    }

    function pushRowToNotaMatrix(savedRelativePath, originalFileName) {
        var amount = parseFloat($('#notaAmount').val()) || 0;
        currentNotaRows.push({
            accountType: $('#notaAccountType').val(),
            docFolio: $('#notaFolio').val().trim(),
            U_Provider: $('#notaProvider').val().trim(),
            account: $('#notaAccount').val(),
            affectable: $('#notaAffectable').val() || $('#accountCredit').val(),
            U_Date: $('#notaDate').val(),
            project: ($('#notaProject').val() && $('#notaProject').val() !== '0') ? $('#notaProject').val() : '',
            dim1: ($('#notaDim1').val() && $('#notaDim1').val() !== '0') ? $('#notaDim1').val() : $('#U_Area').val(),
            dim2: ($('#notaDim2').val() && $('#notaDim2').val() !== '0') ? $('#notaDim2').val() : '',
            dim3: ($('#notaDim3').val() && $('#notaDim3').val() !== '0') ? $('#notaDim3').val() : '',
            dim4: ($('#notaDim4').val() && $('#notaDim4').val() !== '0') ? $('#notaDim4').val() : '',
            dim5: '',
            pdfFile: savedRelativePath,
            pdfOriginalName: originalFileName,
            U_Coments: $('#notaComments').val(),
            U_Total: amount
        });

        $('#notaFolio, #notaProvider').val('');
        $('#notaAmount').val('0.00');
        $('#notaFileInput').val('');
        renderNotaMatrix();
    }

    function renderNotaMatrix() {
        var html = '';
        var sum  = 0;
        currentNotaRows.forEach(function (r, i) {
            var imp = parseFloat(r.U_Total || 0);
            sum += imp;
            var badgePdf = r.pdfFile
                ? `<span class="badge badge-success" title="${r.pdfFile}"><i class="fas fa-paperclip"></i> ${r.pdfOriginalName || 'PDF Adjunto'}</span>`
                : '<span class="text-muted">Sin anexo</span>';

            html += `<tr>
                <td class="text-center">${i + 1}</td>
                <td>${r.docFolio}</td>
                <td>${r.U_Provider}</td>
                <td>${r.account}</td>
                <td>${r.affectable}</td>
                <td>${r.project || ''}</td>
                <td>${r.dim1 || ''}</td>
                <td>${r.dim2 || ''}</td>
                <td>${r.dim3 || ''}</td>
                <td class="text-center">${badgePdf}</td>
                <td><small>${r.U_Coments || ''}</small></td>
                <td class="text-right font-weight-bold">${imp.toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-xs" onclick="removeNotaMatrixRow(${i})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });

        if (currentNotaRows.length === 0) {
            html = `<tr><td colspan="13" class="text-center text-muted">Capture los datos arriba y presione "Agregar a Matriz".</td></tr>`;
        }
        $('#tbodyNotaMatrix').html(html);
        $('#notaMatrixTotal').val(sum.toFixed(2));
    }

    function removeNotaMatrixRow(idx) {
        currentNotaRows.splice(idx, 1);
        renderNotaMatrix();
    }

    function renderRefundLines() {
        var html     = '';
        var sumSub   = 0, sumIva = 0, sumIeps = 0, sumRetIva = 0, sumRetIsr = 0, sumTotal = 0;
        var isSynced = parseInt($('#sapSynced').val()) === 1;

        refundLines.forEach(function (item, idx) {
            var sub    = parseFloat(item.U_Subtotal || 0);
            var iva    = parseFloat(item.U_IVA || 0);
            var ieps   = parseFloat(item.U_IEPS || 0);
            var retIva = parseFloat(item.U_RetIVA || 0);
            var retIsr = parseFloat(item.U_ISR || 0);
            var tot    = parseFloat(item.U_Total || 0);

            if (item.U_Status !== 'Cancelado') {
                sumSub += sub; sumIva += iva; sumIeps += ieps;
                sumRetIva += retIva; sumRetIsr += retIsr; sumTotal += tot;
            }

            var badgeType = (item.U_Type === 'XML')
                ? '<span class="badge badge-success">XML</span>'
                : '<span class="badge badge-info">Nota</span>';

            var cantPartidas = (item.U_Type === 'XML')
                ? `${(item.conceptLines ? item.conceptLines.length : 1)} concepto(s)`
                : `${(item.noteLines ? item.noteLines.length : 1)} renglón(es) de póliza`;

            html += `<tr>
                <td class="text-center">${idx + 1}</td>
                <td class="text-center"><input type="checkbox" onchange="toggleLineNA(${idx}, this.checked)" ${item.U_NA === 'Y' ? 'checked' : ''} ${isSynced ? 'disabled' : ''}></td>
                <td class="text-center">${item.U_Date || ''}</td>
                <td class="text-center">${badgeType}</td>
                <td>${item.docFolio || ''}</td>
                <td>${item.U_Provider || ''}</td>
                <td class="text-center"><span class="badge badge-light border">${cantPartidas}</span></td>
                <td class="text-right">${sub.toFixed(2)}</td>
                <td class="text-right">${iva.toFixed(2)}</td>
                <td class="text-right text-danger">${retIva.toFixed(2)}</td>
                <td class="text-right text-danger">${retIsr.toFixed(2)}</td>
                <td class="text-right">${ieps.toFixed(2)}</td>
                <td class="text-right font-weight-bold">${tot.toFixed(2)}</td>
                <td><small>${item.U_Coments || ''}</small></td>
                <td class="text-center"><span class="badge badge-secondary">${item.U_Status || 'Abierto'}</span></td>
                <td class="text-center font-weight-bold">${item.U_DocEntry || '-'}</td>
                <td class="text-center">
                    ${!isSynced ? `
                        <button type="button" class="btn btn-warning btn-xs" onclick="editRefundLine(${idx})"><i class="fas fa-pencil-alt"></i></button>
                        <button type="button" class="btn btn-danger btn-xs" onclick="removeRefundLine(${idx})"><i class="fas fa-trash"></i></button>
                    ` : '<i class="fas fa-lock text-muted"></i>'}
                </td>
            </tr>`;
        });

        if (refundLines.length === 0) {
            html = `<tr><td colspan="17" class="text-center text-muted py-3">Sin documentos agregados.</td></tr>`;
        }

        $('#tbodyRefundLines').html(html);
        $('#subTotal').val(sumSub.toFixed(2));
        $('#totalImpuesto').val(sumIva.toFixed(2));
        $('#totalIEPS').val(sumIeps.toFixed(2));
        $('#totalRetencionIVA').val(sumRetIva.toFixed(2));
        $('#totalRetencionISR').val(sumRetIsr.toFixed(2));
        $('#granTotal').val(sumTotal.toFixed(2));
        $('#listProducts').val(JSON.stringify(refundLines));
    }

    function toggleLineNA(idx, isChecked) {
        if (refundLines[idx]) {
            refundLines[idx].U_NA = isChecked ? 'Y' : 'N';
            $('#listProducts').val(JSON.stringify(refundLines));
        }
    }

    function editRefundLine(idx) {
        var ln = refundLines[idx];
        if (!ln) return;

        if (ln.U_Type === 'XML') {
            resetModalXML();
            $('#xmlEditIndex').val(idx);
            $('#xmlUUID').val(ln.UUID || '');
            $('#xmlFolio').val(ln.docFolio || '');
            $('#xmlDate').val(ln.U_Date || '');
            $('#xmlCardCode').val(ln.U_Provider || '');
            $('#xmlNombreEmisor').val(ln.providerName || '');
            $('#xmlComments').val(ln.U_Coments || '');
            $('#xmlIEPS').val(parseFloat(ln.U_IEPS || 0).toFixed(2));
            $('#xmlRetIVA').val(parseFloat(ln.U_RetIVA || 0).toFixed(2));
            $('#xmlRetISR').val(parseFloat(ln.U_ISR || 0).toFixed(2));
            currentXmlConcepts = ln.conceptLines ? JSON.parse(JSON.stringify(ln.conceptLines)) : [];
            renderXmlMatrix();
            $('#modalAddXML').modal('show');
        } else {
            resetModalNota();
            $('#notaEditIndex').val(idx);
            currentNotaRows = ln.noteLines ? JSON.parse(JSON.stringify(ln.noteLines)) : [ln];
            renderNotaMatrix();
            $('#modalAddNota').modal('show');
        }
    }

    function removeRefundLine(idx) {
        refundLines.splice(idx, 1);
        renderRefundLines();
    }

    function resetModalXML() {
        $('#xmlEditIndex').val(-1);
        currentXmlConcepts = [];
        $('#xmlUUID, #xmlFolio, #xmlRfcEmisor, #xmlCardCode, #xmlNombreEmisor').val('');
        $('#xmlSubtotal, #xmlIVA, #xmlIEPS, #xmlRetIVA, #xmlRetISR, #xmlTotal').val('0.00');
        $('#xmlComments').val($('#copyComments').is(':checked') ? $('#generalObservations').val() : '');
        $('#tbodyXmlMatrix').html('<tr><td colspan="17" class="text-center text-muted">Busque un UUID y presione "Cargar Conceptos".</td></tr>');
    }

    function resetModalNota() {
        $('#notaEditIndex').val(-1);
        currentNotaRows = [];
        $('#notaFolio, #notaProvider').val('');
        $('#notaAmount').val('0.00');
        $('#notaFileInput').val('');
        $('#notaComments').val($('#copyComments').is(':checked') ? $('#generalObservations').val() : '');
        renderNotaMatrix();
    }

    function initSelect2Ajax(selector, urlAjax, modalParent) {
        var cfg = {
            ajax: {
                url: urlAjax,
                type: "POST",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        searchTerm: params.term,
                        idEmpresa: $('#idEmpresaRefunds').val(),
                        idSucursal: $('#idSucursal').val(),
                        [$('.txt_csrfname').attr('name')]: $('.txt_csrfname').val()
                    };
                },
                processResults: function (response) {
                    if (response.token) $('.txt_csrfname').val(response.token);
                    return { results: response.data };
                },
                cache: true
            }
        };
        if (modalParent) cfg.dropdownParent = $(modalParent);
        $(selector).select2(cfg);
    }

    function validarEncabezadoBasico() {
        if (!$('#idEmpresaRefunds').val() || $('#idEmpresaRefunds').val() === '0') {
            Toast.fire({ icon: 'error', title: 'Seleccione la Empresa.' });
            return false;
        }
        if (!$('#idSucursal').val() || $('#idSucursal').val() === '0') {
            Toast.fire({ icon: 'error', title: 'Seleccione la Sucursal.' });
            return false;
        }
        if (!$('#U_Area').val() || $('#U_Area').val() === '0') {
            Toast.fire({ icon: 'error', title: 'Seleccione el Área / Centro de Costo.' });
            return false;
        }
        if (!$('#employeeRefund').val() || $('#employeeRefund').val() === '0') {
            Toast.fire({ icon: 'error', title: 'Seleccione el Empleado.' });
            return false;
        }
        return true;
    }

    function ejecutarAccionFlujoSAP(urlEndpoint, pregunta) {
        Swal.fire({
            title: pregunta,
            icon: 'question',
            type: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar'
        }).then((result) => {
            if (result.isConfirmed || result.value) {
                var postData = {
                    code: $('#codeSAP').val(),
                    folio: $('#U_Folio').val()
                };
                var csrfName = $('.txt_csrfname').attr('name');
                if (csrfName) postData[csrfName] = $('.txt_csrfname').val();

                $.ajax({
                    url: urlEndpoint,
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    success: function (res) {
                        if (res.token) $('.txt_csrfname').val(res.token);
                        if (res.success) {
                            Swal.fire('Actualizado', res.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Atención', res.error || 'No se pudo actualizar', 'warning');
                        }
                    },
                    error: function (jqXHR) {
                        Swal.fire('Error', jqXHR.responseText || 'Error de red', 'error');
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>