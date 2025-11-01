/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/assets/js/panel-control.js
 * Descripción: JavaScript para el panel de control (index.php)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * =======================================================
 */

var tableGuias;

// =====================================================
// DOCUMENT READY
// =====================================================
$(document).ready(function() {
    // Inicializar tabla de guías
    cargarTablaGuias();
    
    console.log('Panel de control inicializado');
});

// =====================================================
// FUNCIONES DE TABLA
// =====================================================

/**
 * Cargar y configurar DataTable de guías
 */
function cargarTablaGuias() {
    tableGuias = $('#tableGuias').DataTable({
        ajax: {
            url: 'api/listar_guias.php',
            type: 'POST',
            data: function(d) {
                d.filtro_documento = $('#filtro_documento').val();
                d.filtro_cliente = $('#filtro_cliente').val();
                d.filtro_vendedor = $('#filtro_vendedor').val();
                d.filtro_estado = $('#filtro_estado').val();
                d.filtro_fecha_desde = $('#filtro_fecha_desde').val();
                d.filtro_fecha_hasta = $('#filtro_fecha_hasta').val();
            },
            dataSrc: function(json) {
                // Actualizar badge de total
                $('#badgeTotalGuias').text(json.data.length + ' guías');
                return json.data;
            }
        },
        columns: [
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-link icon-expand" onclick="toggleDetalle(${row.ID}, this)">
                                <i class="fas fa-plus-circle fa-lg text-primary"></i>
                            </button>`;
                }
            },
            { 
                data: 'NRO_GUIA',
                render: function(data, type, row) {
                    return `<strong class="text-primary">${data}</strong>`;
                }
            },
            { 
                data: 'FECHA_EMISION',
                render: function(data, type, row) {
                    return `<small>${data}</small>`;
                }
            },
            { 
                data: 'DOC_RELACIONADO',
                render: function(data, type, row) {
                    if (data === '-') {
                        return '<span class="text-muted">-</span>';
                    }
                    return `<span class="badge bg-secondary">${data}</span>`;
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <small class="text-muted">${row.NUM_DOC_DESTINATARIO}</small><br>
                        <strong>${row.RAZON_SOCIAL_DESTINATARIO}</strong>
                    `;
                }
            },
            { 
                data: 'DESTINO',
                render: function(data, type, row) {
                    return `<small>${data}</small>`;
                }
            },
            { 
                data: 'ESTADO_SUNAT',
                className: 'text-center',
                render: function(data, type, row) {
                    var badgeClass = '';
                    var icon = '';
                    
                    switch(data) {
                        case 'ACEPTADO':
                            badgeClass = 'bg-success';
                            icon = '<i class="fas fa-check-circle"></i>';
                            break;
                        case 'PENDIENTE':
                            badgeClass = 'bg-warning';
                            icon = '<i class="fas fa-clock"></i>';
                            break;
                        case 'RECHAZADO':
                            badgeClass = 'bg-danger';
                            icon = '<i class="fas fa-times-circle"></i>';
                            break;
                        case 'ANULADO':
                            badgeClass = 'bg-secondary';
                            icon = '<i class="fas fa-ban"></i>';
                            break;
                        default:
                            badgeClass = 'bg-secondary';
                            icon = '<i class="fas fa-question-circle"></i>';
                    }
                    
                    return `<span class="badge ${badgeClass}">${icon} ${data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    var botones = `
                        <button class="btn btn-sm btn-primary" onclick="imprimirGuia(${row.ID})" 
                                title="Imprimir" data-bs-toggle="tooltip">
                            <i class="fas fa-print"></i>
                        </button>
                    `;
                    
                    if (row.ESTADO_SUNAT === 'PENDIENTE') {
                        botones += `
                            <button class="btn btn-sm btn-success" onclick="reenviarSunat(${row.ID})" 
                                    title="Enviar a SUNAT" data-bs-toggle="tooltip">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        `;
                    }
                    
                    if (row.ESTADO_SUNAT === 'ACEPTADO' && !row.ESTADO_ANULACION) {
                        botones += `
                            <button class="btn btn-sm btn-danger" onclick="anularGuia(${row.ID})" 
                                    title="Anular" data-bs-toggle="tooltip">
                                <i class="fas fa-ban"></i>
                            </button>
                        `;
                    }
                    
                    return botones;
                }
            }
        ],
        order: [[1, 'desc']],
        pageLength: 20,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        drawCallback: function() {
            // Reinicializar tooltips después de dibujar la tabla
            initTooltips();
        }
    });
}

/**
 * Buscar guías con filtros
 */
function buscarGuias() {
    tableGuias.ajax.reload();
    mostrarToast('Búsqueda actualizada', 'success');
}

/**
 * Limpiar filtros
 */
function limpiarFiltros() {
    $('#formFiltros')[0].reset();
    
    // Restablecer fechas por defecto
    var hoy = new Date();
    var hace30dias = new Date();
    hace30dias.setDate(hoy.getDate() - 30);
    
    $('#filtro_fecha_desde').val(hace30dias.toISOString().split('T')[0]);
    $('#filtro_fecha_hasta').val(hoy.toISOString().split('T')[0]);
    
    tableGuias.ajax.reload();
    mostrarToast('Filtros limpiados', 'info');
}

/**
 * Expandir/colapsar detalle de guía
 */
function toggleDetalle(idGuia, btn) {
    var $btn = $(btn);
    var $icon = $btn.find('i');
    var $tr = $btn.closest('tr');
    var row = tableGuias.row($tr);
    
    if (row.child.isShown()) {
        // Ocultar
        row.child.hide();
        $tr.removeClass('shown');
        $icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');
    } else {
        // Mostrar - cargar detalle
        mostrarLoading('Cargando detalle...');
        
        $.ajax({
            url: 'api/detalle_guia.php',
            type: 'POST',
            data: { id: idGuia },
            success: function(html) {
                ocultarLoading();
                row.child(html, 'detalle-row').show();
                $tr.addClass('shown');
                $icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
            },
            error: function() {
                ocultarLoading();
                mostrarError('Error al cargar el detalle');
            }
        });
    }
}

// =====================================================
// FUNCIONES DE ACCIONES
// =====================================================

/**
 * Reenviar guía a SUNAT
 */
function reenviarSunat(idGuia) {
    mostrarConfirmacion(
        'Se generará el XML, se firmará y enviará a SUNAT. ¿Desea continuar?',
        function() {
            mostrarLoading('Procesando guía...');
            
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
                            title: '¡Guía Aceptada!',
                            html: `
                                <p>${response.mensaje}</p>
                                <hr>
                                <small class="text-muted">Hash: ${response.hash || 'N/A'}</small>
                            `,
                            confirmButtonText: 'Imprimir Guía',
                            showCancelButton: true,
                            cancelButtonText: 'Cerrar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                imprimirGuia(idGuia);
                            }
                        });
                        
                        tableGuias.ajax.reload(null, false);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error en SUNAT',
                            html: `
                                <p><strong>Estado:</strong> ${response.estado}</p>
                                <p><strong>Mensaje:</strong> ${response.error || response.mensaje}</p>
                            `
                        });
                    }
                },
                error: function(xhr, status, error) {
                    ocultarLoading();
                    mostrarError('Error al conectar con el servidor: ' + error);
                }
            });
        }
    );
}

/**
 * Anular guía
 */
function anularGuia(idGuia) {
    Swal.fire({
        title: '<i class="fas fa-ban text-danger"></i> Anular Guía de Remisión',
        html: `
            <div class="mb-3">
                <label class="form-label fw-bold text-start d-block">Motivo de Anulación:</label>
                <select id="swal-motivo" class="form-select">
                    <option value="">Seleccione motivo...</option>
                    <option value="Error en datos">Error en datos</option>
                    <option value="Cliente canceló pedido">Cliente canceló pedido</option>
                    <option value="Documento duplicado">Documento duplicado</option>
                    <option value="Error en destinatario">Error en destinatario</option>
                    <option value="Error en productos">Error en productos</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-start d-block">Observaciones:</label>
                <textarea id="swal-observacion" class="form-control" rows="3" 
                          placeholder="Ingrese detalles adicionales..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check"></i> Confirmar Anulación',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        width: '600px',
        preConfirm: () => {
            const motivo = document.getElementById('swal-motivo').value;
            const observacion = document.getElementById('swal-observacion').value;
            
            if (!motivo) {
                Swal.showValidationMessage('Debe seleccionar un motivo');
                return false;
            }
            
            return { motivo: motivo, observacion: observacion };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            mostrarLoading('Anulando guía...');
            
            $.ajax({
                url: 'api/anular_guia.php',
                type: 'POST',
                data: { 
                    id_guia: idGuia,
                    motivo: result.value.motivo,
                    observacion: result.value.observacion
                },
                dataType: 'json',
                success: function(response) {
                    ocultarLoading();
                    
                    if (response.success) {
                        if (response.warning) {
                            mostrarAdvertencia(response.mensaje);
                        } else {
                            mostrarExito(response.mensaje);
                        }
                        tableGuias.ajax.reload(null, false);
                    } else {
                        mostrarError(response.error);
                    }
                },
                error: function() {
                    ocultarLoading();
                    mostrarError('Error al procesar la anulación');
                }
            });
        }
    });
}

// =====================================================
// FUNCIONES DE EXPORTACIÓN
// =====================================================

/**
 * Exportar a Excel
 */
function exportarExcel() {
    var params = new URLSearchParams({
        filtro_documento: $('#filtro_documento').val(),
        filtro_cliente: $('#filtro_cliente').val(),
        filtro_vendedor: $('#filtro_vendedor').val(),
        filtro_estado: $('#filtro_estado').val(),
        filtro_fecha_desde: $('#filtro_fecha_desde').val(),
        filtro_fecha_hasta: $('#filtro_fecha_hasta').val()
    });
    
    var url = 'api/exportar_excel.php?' + params.toString();
    window.open(url, '_blank');
    mostrarToast('Generando archivo Excel...', 'info');
}

/**
 * Exportar a PDF
 */
function exportarPDF() {
    var params = new URLSearchParams({
        filtro_documento: $('#filtro_documento').val(),
        filtro_cliente: $('#filtro_cliente').val(),
        filtro_vendedor: $('#filtro_vendedor').val(),
        filtro_estado: $('#filtro_estado').val(),
        filtro_fecha_desde: $('#filtro_fecha_desde').val(),
        filtro_fecha_hasta: $('#filtro_fecha_hasta').val()
    });
    
    var url = 'api/exportar_pdf.php?' + params.toString();
    window.open(url, '_blank');
    mostrarToast('Generando archivo PDF...', 'info');
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

/**
 * Abrir reporte del mes actual
 */
function abrirReporteMes() {
    var hoy = new Date();
    var primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    var ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    
    $('#filtro_fecha_desde').val(primerDia.toISOString().split('T')[0]);
    $('#filtro_fecha_hasta').val(ultimoDia.toISOString().split('T')[0]);
    $('#filtro_estado').val('');
    
    buscarGuias();
}
```

---

## 📄 ARCHIVO #22: assets/js/emitir-guia.js
```javascript
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
    var serie = $('#txtSerie').val();
    var numero = $('#txtNumero').val();
    
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