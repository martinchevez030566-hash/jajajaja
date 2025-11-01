<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/modulos/transportistas.php
 * Descripción: Gestión de transportistas (CRUD completo)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - ../includes/header.php
 * - ../includes/footer.php
 * - ../librerias/Database.class.php
 * 
 * =======================================================
 */

session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../../login.php');
    exit;
}

require_once __DIR__ . '/../librerias/Database.class.php';

$pageTitle = 'Gestión de Transportistas';
$currentPage = 'transportistas';
$breadcrumb = [
    ['text' => 'Gestión', 'url' => '#'],
    ['text' => 'Transportistas', 'url' => '#']
];

include __DIR__ . '/../includes/header.php';
?>

<!-- TÍTULO Y ESTADÍSTICAS -->
<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-truck"></i> Gestión de Transportistas</h2>
        <p class="text-muted">Administre los transportistas para las guías de remisión</p>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-primary btn-lg" onclick="abrirModalNuevo()">
            <i class="fas fa-plus-circle"></i> Nuevo Transportista
        </button>
    </div>
</div>

<!-- FILTROS -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">
                    <i class="fas fa-search"></i> Buscar
                </label>
                <input type="text" class="form-control" id="txtBuscar" 
                       placeholder="Buscar por documento o nombre...">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-filter"></i> Estado
                </label>
                <select class="form-select" id="filtroEstado">
                    <option value="">Todos</option>
                    <option value="1" selected>Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" onclick="buscarTransportistas()">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-success w-100" onclick="exportarTransportistas()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE TRANSPORTISTAS -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Listado de Transportistas
        <span class="badge bg-primary float-end" id="badgeTotal">0 registros</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableTransportistas" class="table table-hover table-striped w-100">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>TIPO DOC</th>
                        <th>DOCUMENTO</th>
                        <th>NOMBRE/RAZÓN SOCIAL</th>
                        <th>PLACA</th>
                        <th>LICENCIA</th>
                        <th>ESTADO</th>
                        <th width="120">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR/EDITAR TRANSPORTISTA -->
<div class="modal fade" id="modalTransportista" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-truck"></i> <span id="modalTitleText">Nuevo Transportista</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTransportista" novalidate>
                    <input type="hidden" id="txtId">
                    
                    <div class="row g-3">
                        <!-- Tipo de Documento -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-id-card"></i> Tipo Documento
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="cboTipoDoc" required>
                                <option value="">Seleccione...</option>
                                <option value="1">DNI</option>
                                <option value="6">RUC</option>
                            </select>
                            <div class="invalid-feedback">Seleccione el tipo</div>
                        </div>
                        
                        <!-- Número de Documento -->
                        <div class="col-md-5">
                            <label class="form-label">
                                <i class="fas fa-hashtag"></i> Número Documento
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="txtDocumento" 
                                       placeholder="Ingrese documento" required maxlength="11">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="consultarReniec()" id="btnConsultar">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                            </div>
                            <div class="invalid-feedback">Ingrese el documento</div>
                            <small class="form-text text-muted">
                                DNI: 8 dígitos | RUC: 11 dígitos
                            </small>
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-toggle-on"></i> Estado
                            </label>
                            <select class="form-select" id="cboEstado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        
                        <!-- Nombre/Razón Social -->
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Nombre / Razón Social
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="txtNombre" 
                                   placeholder="Ingrese nombre completo o razón social" 
                                   required maxlength="100">
                            <div class="invalid-feedback">Ingrese el nombre</div>
                        </div>
                        
                        <!-- Placa -->
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-car"></i> Placa del Vehículo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase" id="txtPlaca" 
                                   placeholder="ABC-123" required maxlength="20" 
                                   data-mask="placa">
                            <div class="invalid-feedback">Ingrese la placa</div>
                        </div>
                        
                        <!-- Licencia -->
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-id-card-alt"></i> Licencia de Conducir
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase" id="txtLicencia" 
                                   placeholder="Q10234567" required maxlength="20">
                            <div class="invalid-feedback">Ingrese la licencia</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Los campos marcados con (*) son obligatorios.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="guardarTransportista()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = [];

$inlineScript = "
var tableTransportistas;
var modalTransportista;

$(document).ready(function() {
    // Inicializar modal
    modalTransportista = new bootstrap.Modal(document.getElementById('modalTransportista'));
    
    // Inicializar tabla
    cargarTablaTransportistas();
    
    // Evento cambio tipo documento
    $('#cboTipoDoc').on('change', function() {
        var tipo = $(this).val();
        if (tipo === '1') {
            $('#txtDocumento').attr('maxlength', 8).attr('placeholder', '12345678');
        } else if (tipo === '6') {
            $('#txtDocumento').attr('maxlength', 11).attr('placeholder', '20123456789');
        }
        $('#txtDocumento').val('');
    });
    
    // Búsqueda en tiempo real
    $('#txtBuscar').on('keyup', function(e) {
        if (e.keyCode === 13) {
            buscarTransportistas();
        }
    });
});

/**
 * Cargar tabla de transportistas
 */
function cargarTablaTransportistas() {
    tableTransportistas = $('#tableTransportistas').DataTable({
        ajax: {
            url: '../api/listar_transportistas.php',
            type: 'POST',
            data: function(d) {
                d.buscar = $('#txtBuscar').val();
                d.estado = $('#filtroEstado').val();
            },
            dataSrc: function(json) {
                $('#badgeTotal').text(json.data.length + ' registros');
                return json.data;
            }
        },
        columns: [
            { data: 'ID', className: 'text-center' },
            { 
                data: 'DOCUMENTO',
                render: function(data, type, row) {
                    return data.length === 11 ? 'RUC' : 'DNI';
                }
            },
            { data: 'DOCUMENTO' },
            { data: 'NOMBRE' },
            { 
                data: 'PLACA',
                render: function(data) {
                    return data ? '<span class=\"badge bg-dark\">' + data + '</span>' : '-';
                }
            },
            { 
                data: 'LICENCIA',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'ESTADO',
                className: 'text-center',
                render: function(data) {
                    if (data == 1) {
                        return '<span class=\"badge bg-success\"><i class=\"fas fa-check\"></i> Activo</span>';
                    } else {
                        return '<span class=\"badge bg-danger\"><i class=\"fas fa-times\"></i> Inactivo</span>';
                    }
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <button class=\"btn btn-sm btn-warning\" onclick=\"editarTransportista(${row.ID})\" 
                                title=\"Editar\" data-bs-toggle=\"tooltip\">
                            <i class=\"fas fa-edit\"></i>
                        </button>
                        <button class=\"btn btn-sm btn-danger\" onclick=\"eliminarTransportista(${row.ID})\" 
                                title=\"Eliminar\" data-bs-toggle=\"tooltip\">
                            <i class=\"fas fa-trash\"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        drawCallback: function() {
            initTooltips();
        }
    });
}

/**
 * Buscar transportistas
 */
function buscarTransportistas() {
    tableTransportistas.ajax.reload();
}

/**
 * Abrir modal para nuevo transportista
 */
function abrirModalNuevo() {
    $('#formTransportista')[0].reset();
    $('#formTransportista').removeClass('was-validated');
    $('#txtId').val('');
    $('#modalTitleText').text('Nuevo Transportista');
    $('#cboEstado').val('1');
    modalTransportista.show();
}

/**
 * Editar transportista
 */
function editarTransportista(id) {
    mostrarLoading('Cargando datos...');
    
    $.ajax({
        url: '../api/obtener_transportista.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                var data = response.data;
                
                $('#txtId').val(data.ID);
                $('#cboTipoDoc').val(data.DOCUMENTO.length === 11 ? '6' : '1');
                $('#txtDocumento').val(data.DOCUMENTO);
                $('#txtNombre').val(data.NOMBRE);
                $('#txtPlaca').val(data.PLACA);
                $('#txtLicencia').val(data.LICENCIA);
                $('#cboEstado').val(data.ESTADO);
                
                $('#modalTitleText').text('Editar Transportista');
                modalTransportista.show();
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al cargar los datos');
        }
    });
}

/**
 * Guardar transportista
 */
function guardarTransportista() {
    var form = document.getElementById('formTransportista');
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        mostrarAdvertencia('Por favor complete todos los campos requeridos');
        return;
    }
    
    // Validar documento
    var tipoDoc = $('#cboTipoDoc').val();
    var documento = $('#txtDocumento').val();
    
    if (tipoDoc === '1' && !validarDNI(documento)) {
        mostrarError('El DNI debe tener 8 dígitos');
        return;
    }
    
    if (tipoDoc === '6' && !validarRUC(documento)) {
        mostrarError('El RUC no es válido');
        return;
    }
    
    mostrarLoading('Guardando...');
    
    var formData = {
        id: $('#txtId').val(),
        documento: documento,
        nombre: $('#txtNombre').val(),
        placa: $('#txtPlaca').val().toUpperCase(),
        licencia: $('#txtLicencia').val().toUpperCase(),
        estado: $('#cboEstado').val()
    };
    
    $.ajax({
        url: '../api/guardar_transportista.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                mostrarExito(response.message);
                modalTransportista.hide();
                tableTransportistas.ajax.reload(null, false);
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al guardar');
        }
    });
}

/**
 * Eliminar transportista
 */
function eliminarTransportista(id) {
    mostrarConfirmacion(
        '¿Está seguro de eliminar este transportista? Esta acción no se puede deshacer.',
        function() {
            mostrarLoading('Eliminando...');
            
            $.ajax({
                url: '../api/eliminar_transportista.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    ocultarLoading();
                    
                    if (response.success) {
                        mostrarExito(response.message);
                        tableTransportistas.ajax.reload(null, false);
                    } else {
                        mostrarError(response.message);
                    }
                },
                error: function() {
                    ocultarLoading();
                    mostrarError('Error al eliminar');
                }
            });
        }
    );
}

/**
 * Consultar RENIEC/SUNAT (simulado)
 */
function consultarReniec() {
    var tipoDoc = $('#cboTipoDoc').val();
    var documento = $('#txtDocumento').val();
    
    if (!tipoDoc) {
        mostrarAdvertencia('Seleccione el tipo de documento');
        return;
    }
    
    if (!documento) {
        mostrarAdvertencia('Ingrese el número de documento');
        return;
    }
    
    mostrarAdvertencia('Funcionalidad de consulta API RENIEC/SUNAT en desarrollo');
}

/**
 * Exportar transportistas
 */
function exportarTransportistas() {
    var params = new URLSearchParams({
        buscar: $('#txtBuscar').val(),
        estado: $('#filtroEstado').val()
    });
    
    window.open('../api/exportar_transportistas_excel.php?' + params.toString(), '_blank');
    mostrarToast('Generando archivo Excel...', 'info');
}
";

include __DIR__ . '/../includes/footer.php';
?>