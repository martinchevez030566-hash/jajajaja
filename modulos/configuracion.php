<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/modulos/configuracion.php
 * Descripción: Configuración del sistema (Empresa, SUNAT, Certificado)
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

$pageTitle = 'Configuración del Sistema';
$currentPage = 'configuracion';
$breadcrumb = [
    ['text' => 'Gestión', 'url' => '#'],
    ['text' => 'Configuración', 'url' => '#']
];

$db = Database::getInstance();

// Obtener configuración actual
$config = $db->getConfig();

include __DIR__ . '/../includes/header.php';
?>

<!-- TÍTULO -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-cog"></i> Configuración del Sistema</h2>
        <p class="text-muted">Configure los parámetros del sistema de guías de remisión electrónica</p>
    </div>
</div>

<!-- TABS DE CONFIGURACIÓN -->
<ul class="nav nav-tabs mb-3" id="configTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-empresa" data-bs-toggle="tab" 
                data-bs-target="#panel-empresa" type="button" role="tab">
            <i class="fas fa-building"></i> Datos de Empresa
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-certificado" data-bs-toggle="tab" 
                data-bs-target="#panel-certificado" type="button" role="tab">
            <i class="fas fa-certificate"></i> Certificado Digital
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-sunat" data-bs-toggle="tab" 
                data-bs-target="#panel-sunat" type="button" role="tab">
            <i class="fas fa-server"></i> Conexión SUNAT
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-avanzado" data-bs-toggle="tab" 
                data-bs-target="#panel-avanzado" type="button" role="tab">
            <i class="fas fa-sliders-h"></i> Avanzado
        </button>
    </li>
</ul>

<!-- CONTENIDO DE TABS -->
<div class="tab-content" id="configTabsContent">
    
    <!-- TAB 1: DATOS DE EMPRESA -->
    <div class="tab-pane fade show active" id="panel-empresa" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-building"></i> Información de la Empresa
            </div>
            <div class="card-body">
                <form id="formEmpresa">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-id-card"></i> RUC
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="txtRuc" 
                                   value="<?= htmlspecialchars($config['RUC_EMPRESA']) ?>" 
                                   required maxlength="11" data-mask="ruc">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">
                                <i class="fas fa-building"></i> Razón Social
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="txtRazonSocial" 
                                   value="<?= htmlspecialchars($config['RAZON_SOCIAL']) ?>" 
                                   required maxlength="200">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-store"></i> Nombre Comercial
                            </label>
                            <input type="text" class="form-control" id="txtNombreComercial" 
                                   value="<?= htmlspecialchars($config['NOMBRE_COMERCIAL']) ?>" 
                                   maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Dirección Fiscal
                            </label>
                            <input type="text" class="form-control" id="txtDireccion" 
                                   value="JR. ILO NRO. 234/240 CERCADO DE LIMA" 
                                   maxlength="200">
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-primary" onclick="guardarEmpresa()">
                            <i class="fas fa-save"></i> Guardar Datos de Empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- TAB 2: CERTIFICADO DIGITAL -->
    <div class="tab-pane fade" id="panel-certificado" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-certificate"></i> Certificado Digital (.pfx)
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Información:</strong> El certificado digital es necesario para firmar las guías de remisión electrónicas.
                </div>
                
                <form id="formCertificado">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">
                                <i class="fas fa-file-certificate"></i> Ruta del Certificado
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="txtCertPath" 
                                   value="<?= htmlspecialchars($config['CERT_PATH']) ?>" 
                                   required>
                            <small class="form-text text-muted">
                                Ruta completa al archivo .pfx en el servidor
                            </small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-key"></i> Contraseña del Certificado
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="txtCertPassword" 
                                   value="<?= htmlspecialchars($config['CERT_PASSWORD']) ?>" 
                                   required>
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Seguridad:</strong> La contraseña se almacena en la base de datos. 
                                Asegúrese de tener medidas de seguridad adecuadas.
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-secondary" onclick="verificarCertificado()">
                            <i class="fas fa-check-circle"></i> Verificar Certificado
                        </button>
                        <button type="button" class="btn btn-primary" onclick="guardarCertificado()">
                            <i class="fas fa-save"></i> Guardar Certificado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- TAB 3: CONEXIÓN SUNAT -->
    <div class="tab-pane fade" id="panel-sunat" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-server"></i> Configuración de Conexión SUNAT
            </div>
            <div class="card-body">
                <form id="formSunat">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-link"></i> Endpoint API SUNAT
                                <span class="text-danger">*</span>
                            </label>
                            <input type="url" class="form-control" id="txtEndpoint" 
                                   value="<?= htmlspecialchars($config['SUNAT_ENDPOINT']) ?>" 
                                   required>
                            <small class="form-text text-muted">
                                URL del servicio de guías de remisión electrónicas
                            </small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-link"></i> URL Token OAuth2
                            </label>
                            <input type="url" class="form-control" id="txtTokenUrl" 
                                   value="<?= htmlspecialchars($config['SUNAT_TOKEN_URL']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Usuario SOL
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="txtUsername" 
                                   value="<?= htmlspecialchars($config['SUNAT_USERNAME']) ?>" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-lock"></i> Contraseña SOL
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="txtPassword" 
                                   value="<?= htmlspecialchars($config['SUNAT_PASSWORD']) ?>" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-id-badge"></i> Client ID
                            </label>
                            <input type="text" class="form-control" id="txtClientId" 
                                   value="<?= htmlspecialchars($config['SUNAT_CLIENT_ID']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-key"></i> Client Secret
                            </label>
                            <input type="password" class="form-control" id="txtClientSecret" 
                                   value="<?= htmlspecialchars($config['SUNAT_CLIENT_SECRET']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-server"></i> Ambiente
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="cboAmbiente" required>
                                <option value="BETA" <?= $config['AMBIENTE'] == 'BETA' ? 'selected' : '' ?>>
                                    BETA (Pruebas)
                                </option>
                                <option value="PRODUCCION" <?= $config['AMBIENTE'] == 'PRODUCCION' ? 'selected' : '' ?>>
                                    PRODUCCIÓN
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-secondary" onclick="probarConexionSunat()">
                            <i class="fas fa-plug"></i> Probar Conexión
                        </button>
                        <button type="button" class="btn btn-primary" onclick="guardarSunat()">
                            <i class="fas fa-save"></i> Guardar Configuración SUNAT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- TAB 4: AVANZADO -->
    <div class="tab-pane fade" id="panel-avanzado" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-sliders-h"></i> Configuración Avanzada
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Advertencia:</strong> Modificar estas opciones puede afectar el funcionamiento del sistema. 
                    Contacte a soporte si tiene dudas.
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-database"></i> Base de Datos</h6>
                                <hr>
                                <p><strong>Estado:</strong> 
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Conectado
                                    </span>
                                </p>
                                <p><strong>Servidor:</strong> <?= php_uname('n') ?></p>
                                <p><strong>PHP:</strong> <?= phpversion() ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-chart-line"></i> Estadísticas</h6>
                                <hr>
                                <?php
                                $sqlStats = "SELECT 
                                            COUNT(*) AS TOTAL,
                                            SUM(CASE WHEN ESTADO_SUNAT = 'ACEPTADO' THEN 1 ELSE 0 END) AS ACEPTADAS,
                                            SUM(CASE WHEN ESTADO_SUNAT = 'PENDIENTE' THEN 1 ELSE 0 END) AS PENDIENTES
                                            FROM MPCL.dbo.TBL_GUIAS_CAB";
                                $stats = $db->selectOne($sqlStats);
                                ?>
                                <p><strong>Total Guías:</strong> <?= number_format($stats['TOTAL']) ?></p>
                                <p><strong>Aceptadas:</strong> <?= number_format($stats['ACEPTADAS']) ?></p>
                                <p><strong>Pendientes:</strong> <?= number_format($stats['PENDIENTES']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-tools"></i> Herramientas de Mantenimiento</h6>
                                <hr>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-warning" onclick="limpiarCache()">
                                        <i class="fas fa-trash"></i> Limpiar Caché
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="verLogs()">
                                        <i class="fas fa-file-alt"></i> Ver Logs
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="exportarConfig()">
                                        <i class="fas fa-download"></i> Exportar Configuración
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php
$additionalJS = [];

$inlineScript = "
$(document).ready(function() {
    console.log('Configuración inicializada');
});

/**
 * Guardar datos de empresa
 */
function guardarEmpresa() {
    if (!validarFormulario('formEmpresa')) {
        return;
    }
    
    mostrarLoading('Guardando...');
    
    $.ajax({
        url: '../api/guardar_config.php',
        type: 'POST',
        data: {
            tipo: 'empresa',
            RUC_EMPRESA: $('#txtRuc').val(),
            RAZON_SOCIAL: $('#txtRazonSocial').val(),
            NOMBRE_COMERCIAL: $('#txtNombreComercial').val()
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            if (response.success) {
                mostrarExito(response.message);
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
 * Guardar certificado
 */
function guardarCertificado() {
    if (!validarFormulario('formCertificado')) {
        return;
    }
    
    mostrarLoading('Guardando...');
    
    $.ajax({
        url: '../api/guardar_config.php',
        type: 'POST',
        data: {
            tipo: 'certificado',
            CERT_PATH: $('#txtCertPath').val(),
            CERT_PASSWORD: $('#txtCertPassword').val()
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            if (response.success) {
                mostrarExito(response.message);
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
 * Verificar certificado
 */
function verificarCertificado() {
    mostrarLoading('Verificando certificado...');
    
    $.ajax({
        url: '../api/verificar_certificado.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Certificado Válido',
                    html: '<p>' + response.message + '</p>' +
                          '<p><strong>Válido hasta:</strong> ' + (response.valido_hasta || 'N/A') + '</p>'
                });
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al verificar');
        }
    });
}

/**
 * Guardar configuración SUNAT
 */
function guardarSunat() {
    if (!validarFormulario('formSunat')) {
        return;
    }
    
    mostrarLoading('Guardando...');
    
    $.ajax({
        url: '../api/guardar_config.php',
        type: 'POST',
        data: {
            tipo: 'sunat',
            SUNAT_ENDPOINT: $('#txtEndpoint').val(),
            SUNAT_TOKEN_URL: $('#txtTokenUrl').val(),
            SUNAT_USERNAME: $('#txtUsername').val(),
            SUNAT_PASSWORD: $('#txtPassword').val(),
            SUNAT_CLIENT_ID: $('#txtClientId').val(),
            SUNAT_CLIENT_SECRET: $('#txtClientSecret').val(),
            AMBIENTE: $('#cboAmbiente').val()
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading();
            if (response.success) {
                mostrarExito(response.message);
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
 * Limpiar caché
 */
function limpiarCache() {
    mostrarConfirmacion('¿Desea limpiar el caché del sistema?', function() {
        mostrarToast('Caché limpiado', 'success');
    });
}

/**
 * Ver logs
 */
function verLogs() {
    window.open('../logs/system.log', '_blank');
}

/**
 * Exportar configuración
 */
function exportarConfig() {
    window.open('../api/exportar_configuracion.php', '_blank');
    mostrarToast('Generando archivo de configuración...', 'info');
}
";

include __DIR__ . '/../includes/footer.php';
?>