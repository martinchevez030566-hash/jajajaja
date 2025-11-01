<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/modulos/almacenes.php
 * Descripción: Gestión de almacenes y series GRE
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

$pageTitle = 'Gestión de Almacenes y Series';
$currentPage = 'almacenes';
$breadcrumb = [
    ['text' => 'Gestión', 'url' => '#'],
    ['text' => 'Almacenes y Series', 'url' => '#']
];

$db = Database::getInstance();

// Obtener almacenes existentes
$sqlAlmacenes = "SELECT 
                 A.A1_CALMA AS CODIGO,
                 A.A1_CDESCRI AS NOMBRE,
                 A.A1_CDIRECC AS DIRECCION,
                 A.A1_CCODUBI AS UBIGEO,
                 U.departamento_inei + ' - ' + U.provincia_inei + ' - ' + U.distrito AS UBIGEO_DESC,
                 A.A1_NNUMGUI AS CORRELATIVO,
                 G.SERIE_GRE,
                 G.ESTADO
                 FROM RSFACCAR.dbo.AL0005ALMA A
                 LEFT JOIN MPCL.dbo.TBL_ALMACENES_GRE G ON A.A1_CALMA = G.CODIGO_ALMACEN
                 LEFT JOIN RSFACCAR.dbo.TB_UBIGEOS U ON A.A1_CCODUBI = U.ubigeo_inei
                 ORDER BY A.A1_CDESCRI";

$almacenes = $db->select($sqlAlmacenes);

include __DIR__ . '/../includes/header.php';
?>

<!-- TÍTULO -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-warehouse"></i> Gestión de Almacenes y Series GRE</h2>
        <p class="text-muted">Configure las series de guías de remisión para cada almacén</p>
    </div>
</div>

<!-- ALERT INFORMATIVO -->
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Información:</strong> Los almacenes se gestionan desde el sistema principal. 
    Aquí solo puede configurar las series GRE para emitir guías de remisión.
</div>

<!-- TABLA DE ALMACENES -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-building"></i> Almacenes y Configuración de Series
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th width="80">CÓDIGO</th>
                        <th>NOMBRE</th>
                        <th>DIRECCIÓN</th>
                        <th>UBIGEO</th>
                        <th width="100">SERIE GRE</th>
                        <th width="120">CORRELATIVO</th>
                        <th width="100">ESTADO</th>
                        <th width="120">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($almacenes as $alm): ?>
                    <tr>
                        <td class="text-center">
                            <strong><?= htmlspecialchars($alm['CODIGO']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($alm['NOMBRE']) ?></td>
                        <td><small><?= htmlspecialchars($alm['DIRECCION']) ?></small></td>
                        <td><small><?= htmlspecialchars($alm['UBIGEO_DESC'] ?: $alm['UBIGEO']) ?></small></td>
                        <td class="text-center">
                            <?php if ($alm['SERIE_GRE']): ?>
                                <span class="badge bg-primary"><?= htmlspecialchars($alm['SERIE_GRE']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <strong><?= number_format($alm['CORRELATIVO']) ?></strong>
                        </td>
                        <td class="text-center">
                            <?php if ($alm['SERIE_GRE']): ?>
                                <?php if ($alm['ESTADO'] == 1): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">Sin configurar</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($alm['SERIE_GRE']): ?>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="editarSerie('<?= htmlspecialchars($alm['CODIGO']) ?>', '<?= htmlspecialchars($alm['SERIE_GRE']) ?>', <?= $alm['ESTADO'] ?>)"
                                        title="Editar" data-bs-toggle="tooltip">
                                    <i class="fas fa-edit"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" 
                                        onclick="configurarSerie('<?= htmlspecialchars($alm['CODIGO']) ?>')"
                                        title="Configurar" data-bs-toggle="tooltip">
                                    <i class="fas fa-cog"></i> Configurar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- INFORMACIÓN ADICIONAL -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="fas fa-lightbulb"></i> Información de Series
            </div>
            <div class="card-body">
                <h6>Formato de Series:</h6>
                <ul>
                    <li><strong>Serie:</strong> 4 caracteres alfanuméricos (ej: T001, V001, G001)</li>
                    <li><strong>Numeración:</strong> Correlativa automática desde el campo A1_NNUMGUI</li>
                    <li><strong>Formato final:</strong> SERIE-00000001 (8 dígitos)</li>
                </ul>
                
                <hr>
                
                <h6>Recomendaciones:</h6>
                <ul>
                    <li>Use series diferentes para cada almacén</li>
                    <li>Series comunes: T### para transporte, V### para ventas</li>
                    <li>No modifique series ya en uso</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <i class="fas fa-exclamation-triangle"></i> Advertencias
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Importante:</strong>
                    <ul class="mb-0">
                        <li>No elimine series con guías emitidas</li>
                        <li>El correlativo se actualiza automáticamente</li>
                        <li>Series inactivas no aparecen en emisión</li>
                        <li>Contacte a sistemas para cambios críticos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONFIGURAR/EDITAR SERIE -->
<div class="modal fade" id="modalSerie" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-barcode"></i> <span id="modalTitleSerie">Configurar Serie GRE</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formSerie" novalidate>
                    <input type="hidden" id="txtCodigoAlmacen">
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-building"></i> Almacén
                        </label>
                        <input type="text" class="form-control" id="txtNombreAlmacen" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-barcode"></i> Serie GRE
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control text-uppercase" id="txtSerie" 
                               placeholder="T001" required maxlength="4" pattern="[A-Z0-9]{4}">darSerie()">
<i class="fas fa-save"></i> Guardar
</button>
</div>
</div>
</div>
</div><?php
$additionalJS = [];$inlineScript = "
var modalSerie;$(document).ready(function() {
// Inicializar modal
modalSerie = new bootstrap.Modal(document.getElementById('modalSerie'));// Inicializar tooltips
initTooltips();// Convertir input a mayúsculas
$('#txtSerie').on('input', function() {
    $(this).val($(this).val().toUpperCase());
});
});/**

Configurar nueva serie para almacén
*/
function configurarSerie(codigoAlmacen) {
$('#formSerie')[0].reset();
$('#formSerie').removeClass('was-validated');
$('#txtCodigoAlmacen').val(codigoAlmacen);// Obtener nombre del almacén
var nombreAlmacen = '';
$('table tbody tr').each(function() {
    var codigo = $(this).find('td:first strong').text().trim();
    if (codigo === codigoAlmacen) {
        nombreAlmacen = $(this).find('td:nth-child(2)').text().trim();
        return false;
    }
});$('#txtNombreAlmacen').val(codigoAlmacen + ' - ' + nombreAlmacen);
$('#txtSerie').val('');
$('#cboEstadoSerie').val('1');
$('#modalTitleSerie').text('Configurar Serie GRE');modalSerie.show();
}/**

Editar serie existente
*/
function editarSerie(codigoAlmacen, serieActual, estadoActual) {
$('#formSerie')[0].reset();
$('#formSerie').removeClass('was-validated');
$('#txtCodigoAlmacen').val(codigoAlmacen);// Obtener nombre del almacén
var nombreAlmacen = '';
$('table tbody tr').each(function() {
    var codigo = $(this).find('td:first strong').text().trim();
    if (codigo === codigoAlmacen) {
        nombreAlmacen = $(this).find('td:nth-child(2)').text().trim();
        return false;
    }
});$('#txtNombreAlmacen').val(codigoAlmacen + ' - ' + nombreAlmacen);
$('#txtSerie').val(serieActual);
$('#cboEstadoSerie').val(estadoActual);
$('#modalTitleSerie').text('Editar Serie GRE');modalSerie.show();
}/**

Guardar serie
*/
function guardarSerie() {
var form = document.getElementById('formSerie');
if (!form.checkValidity()) {
    form.classList.add('was-validated');
    mostrarAdvertencia('Por favor complete todos los campos correctamente');
    return;
}var codigoAlmacen = $('#txtCodigoAlmacen').val();
var serie = $('#txtSerie').val().toUpperCase();
var estado = $('#cboEstadoSerie').val();// Validar formato de serie
if (!/^[A-Z0-9]{4}$/.test(serie)) {
    mostrarError('La serie debe tener exactamente 4 caracteres alfanuméricos');
    return;
}mostrarConfirmacion(
    '¿Está seguro de guardar esta configuración?',
    function() {
        mostrarLoading('Guardando configuración...');        $.ajax({
            url: '../api/guardar_serie.php',
            type: 'POST',
            data: {
                codigo_almacen: codigoAlmacen,
                serie: serie,
                estado: estado
            },
            dataType: 'json',
            success: function(response) {
                ocultarLoading();                if (response.success) {
                    mostrarExito(response.message);
                    modalSerie.hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    mostrarError(response.message);
                }
            },
            error: function() {
                ocultarLoading();
                mostrarError('Error al guardar la configuración');
            }
        });
    }
);
}
";include DIR . '/../includes/footer.php';
?>