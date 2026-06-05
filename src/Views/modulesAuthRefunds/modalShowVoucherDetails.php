<!-- Modal responsivo para mostrar líneas del comprobante (GLO_VODE) -->
<div class="modal fade" id="modalShowVoucherDetails" tabindex="-1" role="dialog" aria-labelledby="modalVoucherDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
        <div class="modal-content shadow-sm">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalVoucherDetailsLabel">Detalle del Comprobante</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div id="modalVoucherDetailsBody" class="p-3">
                    <!-- Aquí se inyectará la tabla o mensaje -->
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-pulse fa-2x text-info"></i>
                        <p class="mt-2">Cargando líneas...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ajustes responsivos para la tabla dentro del modal */
    #modalVoucherDetailsBody .table-responsive {
        border-radius: 0.5rem;
        overflow-x: auto;
    }
    #modalVoucherDetailsBody table {
        min-width: 800px; /* Permite scroll horizontal en móviles */
        font-size: 0.85rem;
    }
    #modalVoucherDetailsBody th,
    #modalVoucherDetailsBody td {
        vertical-align: middle;
        white-space: nowrap;
    }
    @media (max-width: 576px) {
        #modalVoucherDetailsBody table {
            font-size: 0.75rem;
        }
        #modalVoucherDetailsBody th,
        #modalVoucherDetailsBody td {
            padding: 0.4rem 0.3rem;
        }
    }
</style>