/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/assets/js/gre-functions.js
 * Descripción: Funciones JavaScript globales del sistema
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * =======================================================
 */

// =====================================================
// VARIABLES GLOBALES
// =====================================================
var loadingOverlay = null;

// =====================================================
// DOCUMENT READY
// =====================================================
$(document).ready(function() {
    // Inicializar componentes globales
    initTooltips();
    initPopovers();
    initSelect2();
    initInputMasks();
    
    // Configurar AJAX globalmente
    setupAjaxDefaults();
    
    console.log('Sistema GRE inicializado correctamente');
});

// =====================================================
// INICIALIZACIONES
// =====================================================

/**
 * Inicializar tooltips de Bootstrap
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Inicializar popovers de Bootstrap
 */
function initPopovers() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Inicializar Select2 en todos los selects con clase .select2
 */
function initSelect2() {
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            language: 'es',
            width: '100%'
        });
    }
}

/**
 * Inicializar máscaras de input
 */
function initInputMasks() {
    if ($.fn.inputmask) {
        // RUC (11 dígitos)
        $('input[data-mask="ruc"]').inputmask('99999999999');
        
        // DNI (8 dígitos)
        $('input[data-mask="dni"]').inputmask('99999999');
        
        // Teléfono
        $('input[data-mask="telefono"]').inputmask('999-999-999');
        
        // Placa vehicular
        $('input[data-mask="placa"]').inputmask('AAA-999');
        
        // Decimal (2 decimales)
        $('input[data-mask="decimal"]').inputmask('decimal', {
            radixPoint: '.',
            groupSeparator: ',',
            digits: 2,
            autoGroup: true,
            rightAlign: false
        });
    }
}

/**
 * Configurar AJAX por defecto
 */
function setupAjaxDefaults() {
    // Mostrar errores de AJAX
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        console.error('Error AJAX:', settings.url, thrownError);
        
        if (jqxhr.status === 403 || jqxhr.status === 401) {
            Swal.fire({
                icon: 'error',
                title: 'Sesión Expirada',
                text: 'Su sesión ha expirado. Será redirigido al login.',
                timer: 3000
            }).then(() => {
                window.location.href = '../login.php';
            });
        }
    });
}

// =====================================================
// FUNCIONES DE LOADING
// =====================================================

/**
 * Mostrar overlay de carga
 * @param {string} mensaje - Mensaje a mostrar
 */
function mostrarLoading(mensaje) {
    mensaje = mensaje || 'Procesando...';
    
    var overlay = $('#loadingOverlay');
    if (overlay.length === 0) {
        overlay = $('<div id="loadingOverlay" class="loading-overlay"></div>');
        overlay.html(`
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <div class="text-light mt-3" id="loadingMessage">${mensaje}</div>
        `);
        $('body').append(overlay);
    } else {
        $('#loadingMessage').text(mensaje);
    }
    
    overlay.fadeIn(200);
}

/**
 * Ocultar overlay de carga
 */
function ocultarLoading() {
    $('#loadingOverlay').fadeOut(200);
}

/**
 * Mostrar loading en un botón específico
 * @param {jQuery} $button - Botón jQuery
 */
function buttonLoading($button) {
    $button.data('original-text', $button.html());
    $button.prop('disabled', true);
    $button.html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando...');
}

/**
 * Restaurar botón después de loading
 * @param {jQuery} $button - Botón jQuery
 */
function buttonReset($button) {
    $button.prop('disabled', false);
    $button.html($button.data('original-text'));
}

// =====================================================
// FUNCIONES DE VALIDACIÓN
// =====================================================

/**
 * Validar RUC peruano
 * @param {string} ruc - RUC a validar
 * @returns {boolean}
 */
function validarRUC(ruc) {
    if (!/^\d{11}$/.test(ruc)) {
        return false;
    }
    
    var suma = 0;
    var factor = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    
    for (var i = 0; i < 10; i++) {
        suma += parseInt(ruc.charAt(i)) * factor[i];
    }
    
    var resto = suma % 11;
    var digito = 11 - resto;
    
    if (digito === 10) digito = 0;
    if (digito === 11) digito = 1;
    
    return digito === parseInt(ruc.charAt(10));
}

/**
 * Validar DNI peruano
 * @param {string} dni - DNI a validar
 * @returns {boolean}
 */
function validarDNI(dni) {
    return /^\d{8}$/.test(dni);
}

/**
 * Validar email
 * @param {string} email - Email a validar
 * @returns {boolean}
 */
function validarEmail(email) {
    var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validar formulario
 * @param {string} formId - ID del formulario
 * @returns {boolean}
 */
function validarFormulario(formId) {
    var form = document.getElementById(formId);
    if (!form) return false;
    
    form.classList.add('was-validated');
    return form.checkValidity();
}

// =====================================================
// FUNCIONES DE FORMATO
// =====================================================

/**
 * Formatear número con separadores de miles
 * @param {number} numero - Número a formatear
 * @param {number} decimales - Cantidad de decimales
 * @returns {string}
 */
function formatearNumero(numero, decimales) {
    decimales = decimales || 2;
    return parseFloat(numero).toLocaleString('es-PE', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}

/**
 * Formatear fecha
 * @param {string|Date} fecha - Fecha a formatear
 * @param {string} formato - Formato deseado (DD/MM/YYYY, YYYY-MM-DD, etc)
 * @returns {string}
 */
function formatearFecha(fecha, formato) {
    formato = formato || 'DD/MM/YYYY';
    return moment(fecha).format(formato);
}

/**
 * Formatear RUC/DNI con guiones
 * @param {string} documento - Documento a formatear
 * @returns {string}
 */
function formatearDocumento(documento) {
    documento = documento.replace(/\D/g, '');
    
    if (documento.length === 11) {
        // RUC: 20-12345678-9
        return documento.replace(/(\d{2})(\d{8})(\d{1})/, '$1-$2-$3');
    } else if (documento.length === 8) {
        // DNI: 12345678
        return documento;
    }
    
    return documento;
}

// =====================================================
// FUNCIONES DE MENSAJES
// =====================================================

/**
 * Mostrar mensaje de éxito
 * @param {string} mensaje - Mensaje a mostrar
 * @param {number} timer - Tiempo en ms (opcional)
 */
function mostrarExito(mensaje, timer) {
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: mensaje,
        timer: timer || 3000,
        showConfirmButton: timer ? false : true
    });
}

/**
 * Mostrar mensaje de error
 * @param {string} mensaje - Mensaje a mostrar
 */
function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje
    });
}

/**
 * Mostrar mensaje de advertencia
 * @param {string} mensaje - Mensaje a mostrar
 */
function mostrarAdvertencia(mensaje) {
    Swal.fire({
        icon: 'warning',
        title: 'Advertencia',
        text: mensaje
    });
}

/**
 * Mostrar confirmación
 * @param {string} mensaje - Mensaje a mostrar
 * @param {function} callback - Función a ejecutar si confirma
 */
function mostrarConfirmacion(mensaje, callback) {
    Swal.fire({
        icon: 'question',
        title: '¿Está seguro?',
        text: mensaje,
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

/**
 * Toast notification
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo: success, error, warning, info
 */
function mostrarToast(mensaje, tipo) {
    tipo = tipo || 'info';
    
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    
    Toast.fire({
        icon: tipo,
        title: mensaje
    });
}

// =====================================================
// FUNCIONES DE EXPORTACIÓN
// =====================================================

/**
 * Exportar tabla a Excel
 * @param {string} tableId - ID de la tabla
 * @param {string} filename - Nombre del archivo
 */
function exportarTablaExcel(tableId, filename) {
    filename = filename || 'export.xlsx';
    
    var table = document.getElementById(tableId);
    if (!table) {
        mostrarError('Tabla no encontrada');
        return;
    }
    
    // Aquí iría la implementación con una librería como SheetJS
    mostrarAdvertencia('Función de exportación en desarrollo');
}

// =====================================================
// FUNCIONES DE IMPRESIÓN
// =====================================================

/**
 * Imprimir guía de remisión
 * @param {number} idGuia - ID de la guía
 */
function imprimirGuia(idGuia) {
    var url = 'prints/guia_remision.php?id=' + idGuia;
    window.open(url, '_blank', 'width=800,height=600');
}

/**
 * Ver XML de guía
 * @param {number} idGuia - ID de la guía
 */
function verXML(idGuia) {
    mostrarLoading('Cargando XML...');
    
    $.ajax({
        url: 'api/ver_xml.php',
        type: 'POST',
        data: { id_guia: idGuia },
        success: function(response) {
            ocultarLoading();
            
            Swal.fire({
                title: 'XML Generado',
                html: '<pre class="text-start" style="max-height: 400px; overflow-y: auto;">' + 
                      escapeHtml(response) + '</pre>',
                width: '80%',
                showCloseButton: true,
                showConfirmButton: false
            });
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al obtener XML');
        }
    });
}

/**
 * Descargar CDR de SUNAT
 * @param {number} idGuia - ID de la guía
 */
function descargarCDR(idGuia) {
    window.open('api/obtener_cdr.php?id=' + idGuia, '_blank');
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

/**
 * Escapar HTML para prevenir XSS
 * @param {string} text - Texto a escapar
 * @returns {string}
 */
function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Obtener parámetro de URL
 * @param {string} name - Nombre del parámetro
 * @returns {string|null}
 */
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

/**
 * Copiar texto al portapapeles
 * @param {string} texto - Texto a copiar
 */
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(function() {
        mostrarToast('Copiado al portapapeles', 'success');
    }, function(err) {
        mostrarError('Error al copiar: ' + err);
    });
}

/**
 * Generar número aleatorio
 * @param {number} min - Mínimo
 * @param {number} max - Máximo
 * @returns {number}
 */
function randomNumber(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

// =====================================================
// EXPORT (si se usa módulos)
// =====================================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        mostrarLoading,
        ocultarLoading,
        mostrarExito,
        mostrarError,
        mostrarAdvertencia,
        mostrarConfirmacion,
        validarRUC,
        validarDNI,
        formatearNumero,
        formatearFecha
    };
}