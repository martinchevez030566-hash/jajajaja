<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/includes/footer.php
 * Descripción: Pie de página común y scripts JS
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * =======================================================
 */
?>
    
    </div><!-- Fin #mainContent -->
    
    <!-- FOOTER -->
    <footer class="footer mt-auto py-3 bg-light border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <small class="text-muted">
                        Sistema de Guías de Remisión Electrónica &copy; <?= date('Y') ?>
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-muted">
                        Versión 1.0.0 | 
                        <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                            <i class="fas fa-question-circle"></i> Ayuda
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- MODAL DE AYUDA -->
    <div class="modal fade" id="modalAyuda" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-question-circle"></i> Ayuda del Sistema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6><i class="fas fa-info-circle"></i> Acerca del Sistema GRE</h6>
                    <p>Sistema de emisión de Guías de Remisión Electrónica - Remitente integrado con SUNAT.</p>
                    
                    <hr>
                    
                    <h6><i class="fas fa-book"></i> Funciones Principales:</h6>
                    <ul>
                        <li><strong>Nueva Guía:</strong> Emitir guías desde facturas/boletas existentes</li>
                        <li><strong>Panel Principal:</strong> Consultar y gestionar guías emitidas</li>
                        <li><strong>Gestión:</strong> Administrar transportistas, almacenes y configuración</li>
                        <li><strong>Reportes:</strong> Generar reportes y exportar a Excel/PDF</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-keyboard"></i> Atajos de Teclado:</h6>
                    <ul>
                        <li><kbd>Ctrl</kbd> + <kbd>N</kbd>: Nueva guía</li>
                        <li><kbd>Ctrl</kbd> + <kbd>P</kbd>: Imprimir</li>
                        <li><kbd>Esc</kbd>: Cerrar modal actual</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-phone"></i> Soporte Técnico:</h6>
                    <p>Para soporte técnico, contactar a sistemas@empresa.com</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- LOADING OVERLAY -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="text-light mt-3">Procesando...</div>
    </div>
    
    <!-- ============================================ -->
    <!-- SCRIPTS JAVASCRIPT -->
    <!-- ============================================ -->
    
    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- jQuery UI (para Autocomplete) -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <!-- Bootstrap 5.3.0 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Moment.js (para manejo de fechas) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/es.min.js"></script>
    
    <!-- InputMask (para formateo de campos) -->
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/jquery.inputmask.min.js"></script>
    
    <!-- Scripts personalizados GRE -->
    <script src="assets/js/gre-functions.js"></script>
    
    <!-- Scripts adicionales por página -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Script inline si existe -->
    <?php if (isset($inlineScript)): ?>
    <script>
        <?= $inlineScript ?>
    </script>
    <?php endif; ?>
    
    <!-- Configuración global -->
    <script>
        // Configurar Moment.js en español
        moment.locale('es');
        
        // Configurar DataTables en español
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            }
        });
        
        // Configurar Select2
        $.fn.select2.defaults.set('theme', 'bootstrap-5');
        $.fn.select2.defaults.set('language', 'es');
        
        // Atajos de teclado globales
        $(document).keydown(function(e) {
            // Ctrl + N = Nueva guía
            if (e.ctrlKey && e.keyCode == 78) {
                e.preventDefault();
                window.location.href = 'emitir_guia.php';
            }
            
            // ESC = Cerrar modal abierto
            if (e.keyCode == 27) {
                $('.modal.show').modal('hide');
            }
        });
        
        // Función global para probar conexión SUNAT
        function probarConexionSunat() {
            mostrarLoading('Probando conexión con SUNAT...');
            
            $.ajax({
                url: 'api/probar_conexion_sunat.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    ocultarLoading();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Conexión Exitosa!',
                            html: '<p>' + response.mensaje + '</p><small class="text-muted">Token: ' + response.token + '</small>',
                            timer: 3000
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: response.error
                        });
                    }
                },
                error: function() {
                    ocultarLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo conectar con el servidor'
                    });
                }
            });
        }
    </script>
    
</body>
</html>