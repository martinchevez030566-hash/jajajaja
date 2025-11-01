/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/assets/js/emitir-guia.js
 * Descripción: JavaScript para emisión de guías (emitir_guia.php)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * =======================================================
 */

var datosFactura = null;

// =====================================================
// FUNCIONES DE BÚSQUEDA
// =====================================================

/**
 * Buscar factura o boleta
 */
function buscarFactura() {
   const serie  = $('#txtSerie').val().trim();
    const numero = $('#txtNumero').val().trim();

    console.log({ serie, numero });   // <-- ¡debe aparecer en la consola!
    
    if (!serie || !numero) {
        mostrarAdvertencia('Debe ingresar la serie y número del documento');
        return;
    }
    
    mostrarLoading('Buscando documento...');
    
    $.ajax({
        url: 'api/buscar_factura.php',
        type: 'POST',
        data: {
            serie: serie,
            numero: numero
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                datosFactura = response.data;
                cargarDatosFactura(response.data);
                mostrarSecciones();
                mostrarToast('Documento encontrado', 'success');
            } else {
                if (response.warning) {
                    mostrarAdvertencia(response.message);
                } else {
                    mostrarError(response.message);
                }
                ocultarSecciones();
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al buscar el documento');
        }
    });
}

/**
 * Cargar datos de la factura en el formulario
 */
function cargarDatosFactura(data) {
    // Información del documento
    $('#infoTipoDoc').text(data.tipo_doc_nombre);
    $('#infoNumDoc').text(data.serie + '-' + data.numero);
    $('#infoFechaDoc').text(formatearFecha(data.fecha));
    $('#infoNombreCliente').text(data.nombre_cliente);
    $('#infoDocCliente').text(data.num_doc_cliente);
    $('#infoTotalItems').text(data.total_items);
    $('#infoDocumento').fadeIn();
    
    // Destinatario
    var tipoDocDesc = data.tipo_doc_cliente === '6' ? 'RUC' : 'DNI';
    $('#txtTipoDocDest').val(tipoDocDesc);
    $('#txtNumDocDest').val(data.num_doc_cliente);
    $('#txtNombreDest').val(data.nombre_cliente);
    
    // Punto de llegada (precarga si tiene ubigeo)
    if (data.ubigeo_cliente && data.ubigeo_desc) {
        $('#txtUbigeoLlegada').val(data.ubigeo_cliente);
        $('#txtUbigeoLlegadaDesc').val(data.ubigeo_desc);
    }
    if (data.direccion_cliente) {
        $('#txtDireccionLlegada').val(data.direccion_cliente);
    }
    
    // Detalle de productos
    cargarDetalleProductos(data.detalle);
    
    // Guardar en campos ocultos
    $('#hiddenTipoDocRel').val(data.tipo_doc);
    $('#hiddenSerieDocRel').val(data.serie);
    $('#hiddenNumeroDocRel').val(data.numero);
    $('#hiddenTipoDocDest').val(data.tipo_doc_cliente);
    $('#hiddenCodigoVendedor').val(data.codigo_vendedor);
    $('#hiddenDetalleJSON').val(JSON.stringify(data.detalle));
}

/**
 * Cargar detalle de productos en la tabla
 */
function cargarDetalleProductos(detalle) {
    var tbody = $('#tableDetalle tbody');
    tbody.empty();
    
    if (!detalle || detalle.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> No hay productos
                </td>
            </tr>
        `);
        return;
    }
    
    $.each(detalle, function(index, item) {
        var row = `
            <tr>
                <td class="text-center">${item.item}</td>
                <td><small>${escapeHtml(item.codigo)}</small></td>
                <td>${escapeHtml(item.descripcion)}</td>
                <td class="text-end">${formatearNumero(item.cantidad, 2)}</td>
                <td class="text-center">${escapeHtml(item.unidad)}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

// =====================================================
// FUNCIONES DE INTERFAZ
// =====================================================

/**
 * Mostrar todas las secciones del formulario
 */
function mostrarSecciones() {
    $('#seccionDestinatario').slideDown();
    $('#seccionLlegada').slideDown();
    $('#seccionTraslado').slideDown();
    $('#seccionTransportista').slideDown();
    $('#seccionAlmacen').slideDown();
    $('#seccionDetalle').slideDown();
    $('#seccionBotones').slideDown();
}

/**
 * Ocultar secciones del formulario
 */
function ocultarSecciones() {
    $('#seccionDestinatario').slideUp();
    $('#seccionLlegada').slideUp();
    $('#seccionTraslado').slideUp();
    $('#seccionTransportista').slideUp();
    $('#seccionAlmacen').slideUp();
    $('#seccionDetalle').slideUp();
    $('#seccionBotones').slideUp();
    $('#infoDocumento').fadeOut();
}

/**
 * Limpiar formulario completo
 */
function limpiarFormulario() {
    mostrarConfirmacion('¿Está seguro de limpiar el formulario?', function() {
        $('#formEmitirGuia')[0].reset();
        $('#formEmitirGuia').removeClass('was-validated');
        datosFactura = null;
        ocultarSecciones();
        $('#tableDetalle tbody').html(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> No hay productos cargados
                </td>
            </tr>
        `);
        
        // Restablecer fecha
        var hoy = new Date().toISOString().split('T')[0];
        $('#txtFechaTraslado').val(hoy);
        
        mostrarToast('Formulario limpiado', 'info');
    });
}

/**
 * Inicializar autocomplete de ubigeo
 */
function initUbigeoAutocomplete() {
    $('#txtUbigeoLlegadaDesc').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'api/buscar_ubigeo.php',
                data: { term: request.term },
                dataType: 'json',
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 3,
        select: function(event, ui) {
            $('#txtUbigeoLlegada').val(ui.item.ubigeo);
            $('#txtUbigeoLlegadaDesc').val(ui.item.label);
            return false;
        },
        focus: function(event, ui) {
            $('#txtUbigeoLlegadaDesc').val(ui.item.label);
            return false;
        }
    });
}

// =====================================================
// FUNCIONES DE VALIDACIÓN
// =====================================================

/**
 * Validar formulario completo
 */
function validarFormularioGuia() {
    var form = document.getElementById('formEmitirGuia');
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        mostrarAdvertencia('Por favor complete todos los campos requeridos');
        
        // Scroll al primer campo inválido
        var firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
        
        return false;
    }
    
    // Validaciones adicionales
    if (!$('#txtUbigeoLlegada').val()) {
        mostrarAdvertencia('Debe seleccionar un ubigeo válido de llegada');
        $('#txtUbigeoLlegadaDesc').focus();
        return false;
    }
    
    if (!datosFactura || !datosFactura.detalle || datosFactura.detalle.length === 0) {
        mostrarError('No hay productos en el detalle');
        return false;
    }
    
    return true;
}

// =====================================================
// FUNCIONES DE GUARDADO
// =====================================================

/**
 * Guardar guía como borrador (sin enviar a SUNAT)
 */
function guardarBorrador() {
    if (!validarFormularioGuia()) {
        return;
    }
    
    mostrarConfirmacion(
        'Se guardará la guía sin enviar a SUNAT. ¿Desea continuar?',
        function() {
            procesarGuia(false);
        }
    );
}

/**
 * Emitir y enviar a SUNAT inmediatamente
 */
function emitirYEnviarSunat() {
    if (!validarFormularioGuia()) {
        return;
    }
    
    Swal.fire({
        title: '¿Confirmar Emisión?',
        html: `
            <p>Se generará la guía y se enviará a SUNAT para su validación.</p>
            <p class="text-muted"><small>Este proceso puede tardar unos segundos.</small></p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Sí, Emitir',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            procesarGuia(true);
        }
    });
}

/**
 * Procesar y guardar guía
 */
function procesarGuia(enviarSunat) {
    mostrarLoading(enviarSunat ? 'Emitiendo guía y enviando a SUNAT...' : 'Guardando guía...');
    
    var formData = {
        codigo_almacen: $('#cboAlmacen').val(),
        tipo_doc_rel: $('#hiddenTipoDocRel').val(),
        serie_doc_rel: $('#hiddenSerieDocRel').val(),
        numero_doc_rel: $('#hiddenNumeroDocRel').val(),
        tipo_doc_destinatario: $('#hiddenTipoDocDest').val(),
        num_doc_destinatario: $('#txtNumDocDest').val(),
        razon_social_destinatario: $('#txtNombreDest').val(),
        ubigeo_llegada: $('#txtUbigeoLlegada').val(),
        direccion_llegada: $('#txtDireccionLlegada').val(),
        motivo_traslado: $('#cboMotivoTraslado').val(),
        peso_total: $('#txtPesoTotal').val(),
        num_bultos: $('#txtNumBultos').val(),
        id_transportista: $('#cboTransportista').val(),
        fecha_inicio_traslado: $('#txtFechaTraslado').val(),
        codigo_vendedor: $('#hiddenCodigoVendedor').val(),
        detalle: $('#hiddenDetalleJSON').val(),
        enviar_sunat: enviarSunat
    };
    
    // Guardar guía
    $.ajax({
        url: 'api/guardar_guia.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (enviarSunat) {
                    // Firmar y enviar a SUNAT
                    firmarYEnviarGuia(response.id_guia, response.nro_guia);
                } else {
                    // Solo guardar
                    ocultarLoading();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guía Guardada!',
                        text: 'Guía ' + response.nro_guia + ' guardada como borrador',
                        confirmButtonText: 'Ver en Panel',
                        showCancelButton: true,
                        cancelButtonText: 'Nueva Guía'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'index.php';
                        } else {
                            limpiarFormulario();
                        }
                    });
                }
            } else {
                ocultarLoading();
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al guardar la guía');
        }
    });
}

/**
 * Firmar y enviar guía a SUNAT
 */
function firmarYEnviarGuia(idGuia, nroGuia) {
$.ajax({
url: 'api/firmar_enviar.php',
type: 'POST',
data: { id_guia: idGuia },
dataType: 'json',
success: function(response) {
ocultarLoading();
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Guía Emitida Correctamente!',
                html: `
                    <div class="alert alert-success mb-3">
                        <h5><i class="fas fa-check-circle"></i> ACEPTADO POR SUNAT</h5>
                        <p class="mb-0">Guía Nro: <strong>${nroGuia}</strong></p>
                    </div>
                    <hr>
                    <p><strong>Estado:</strong> ${response.estado}</p>
                    <p><strong>Mensaje:</strong> ${response.mensaje || 'Guía aceptada correctamente'}</p>
                    ${response.hash ? '<p class="text-muted"><small>Hash: ' + response.hash.substring(0, 40) + '...</small></p>' : ''}
                `,
                confirmButtonText: '<i class="fas fa-print"></i> Imprimir Guía',
                showDenyButton: true,
                denyButtonText: '<i class="fas fa-list"></i> Ver Panel',
                showCancelButton: true,
                cancelButtonText: '<i class="fas fa-plus"></i> Nueva Guía',
                confirmButtonColor: '#2563eb',
                denyButtonColor: '#10b981',
                cancelButtonColor: '#64748b'
            }).then((result) => {
                if (result.isConfirmed) {
                    imprimirGuia(idGuia);
                    setTimeout(() => {
                        limpiarFormulario();
                    }, 1000);
                } else if (result.isDenied) {
                    window.location.href = 'index.php';
                } else {
                    limpiarFormulario();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error en SUNAT',
                html: `
                    <div class="alert alert-danger mb-3">
                        <h5><i class="fas fa-times-circle"></i> ${response.estado || 'RECHAZADO'}</h5>
                        <p class="mb-0">Guía Nro: <strong>${nroGuia}</strong></p>
                    </div>
                    <hr>
                    <p><strong>Error:</strong></p>
                    <p class="text-danger">${response.error || response.mensaje}</p>
                    <hr>
                    <p class="text-muted"><small>La guía quedó guardada como PENDIENTE. Puede corregirla y reenviarla desde el panel de control.</small></p>
                `,
                confirmButtonText: 'Ver Panel',
                showCancelButton: true,
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
        }
    },
    error: function(xhr, status, error) {
        ocultarLoading();
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            html: `
                <p>No se pudo conectar con SUNAT.</p>
                <p class="text-danger">${error}</p>
                <hr>
                <p class="text-muted"><small>La guía quedó guardada. Intente reenviarla desde el panel de control.</small></p>
            `,
            confirmButtonText: 'Ver Panel',
            showCancelButton: true,
            cancelButtonText: 'Cerrar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    }
});
}