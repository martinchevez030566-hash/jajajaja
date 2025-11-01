<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/emitir_guia.php
 * Descripción: Formulario de emisión de guías de remisión
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - includes/header.php
 * - includes/footer.php
 * - librerias/Database.class.php
 * 
 * =======================================================
 */

session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/librerias/Database.class.php';

$pageTitle = 'Emitir Guía de Remisión';
$currentPage = 'emitir';
$breadcrumb = [
    ['text' => 'Emisión de Guías', 'url' => '#']
];

$db = Database::getInstance();

// Obtener almacenes disponibles
$sqlAlmacenes = "SELECT 
                 A.A1_CALMA AS CODIGO,
                 A.A1_CDESCRI AS NOMBRE,
                 G.SERIE_GRE,
                 A.A1_NNUMGUI AS CORRELATIVO
                 FROM RSFACCAR.dbo.AL0005ALMA A
                 INNER JOIN MPCL.dbo.TBL_ALMACENES_GRE G ON A.A1_CALMA = G.CODIGO_ALMACEN
                 WHERE G.ESTADO = 1
                 ORDER BY A.A1_CDESCRI";
$almacenes = $db->select($sqlAlmacenes);

// Obtener transportistas activos
$sqlTransportistas = "SELECT ID, DOCUMENTO, NOMBRE, PLACA, LICENCIA 
                      FROM MPCL.dbo.TBL_TRANSPORTISTA 
                      WHERE ESTADO = 1 
                      ORDER BY NOMBRE";
$transportistas = $db->select($sqlTransportistas);

// Obtener motivos de traslado
$sqlMotivos = "SELECT CODIGO, DESCRIPCION 
               FROM MPCL.dbo.TBL_MOTIVOS_TRASLADO 
               WHERE ESTADO = 1 
               ORDER BY CODIGO";
$motivos = $db->select($sqlMotivos);

include 'includes/header.php';
?>

<!-- TÍTULO Y AYUDA -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Instrucciones:</strong> 
            Ingrese el número de factura o boleta para cargar los datos automáticamente. 
            Luego complete la información del traslado y presione "Emitir y Enviar a SUNAT".
        </div>
    </div>
</div>

<!-- FORMULARIO DE EMISIÓN -->
<form id="formEmitirGuia" novalidate>
    
    <!-- PASO 1: BUSCAR FACTURA -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-search"></i> PASO 1: Buscar Factura o Boleta
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-hashtag"></i> Serie
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="txtSerie" required>
                        <option value="">Seleccione...</option>
                        <option value="F001">F001 - FACTURA</option>
                        <option value="F002">F002 - FACTURA</option>
                        <option value="B001">B001 - BOLETA</option>
                        <option value="B002">B002 - BOLETA</option>
                        <option value="B003">B003 - BOLETA</option>
                        <option value="B004">B004 - BOLETA</option>
                        <option value="B005">B005 - BOLETA</option>
                    </select>
                    <div class="invalid-feedback">La serie es requerida</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-sort-numeric-up"></i> Número
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="txtNumero" 
                           placeholder="Ej: 0027744" required maxlength="7">
                    <div class="invalid-feedback">El número es requerido</div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-success w-100" onclick="buscarFactura()">
                        <i class="fas fa-search"></i> BUSCAR DOCUMENTO
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary w-100" onclick="limpiarFormulario()">
                        <i class="fas fa-broom"></i> LIMPIAR
                    </button>
                </div>
            </div>
            
            <!-- Información del documento encontrado -->
            <div id="infoDocumento" class="mt-3" style="display: none;">
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> Documento Encontrado</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Tipo:</strong> <span id="infoTipoDoc"></span><br>
                            <strong>Número:</strong> <span id="infoNumDoc"></span><br>
                            <strong>Fecha:</strong> <span id="infoFechaDoc"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Cliente:</strong> <span id="infoNombreCliente"></span><br>
                            <strong>Documento:</strong> <span id="infoDocCliente"></span><br>
                            <strong>Items:</strong> <span id="infoTotalItems"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASO 2: DATOS DEL DESTINATARIO -->
    <div class="card mb-4" id="seccionDestinatario" style="display: none;">
        <div class="card-header bg-info text-white">
            <i class="fas fa-user"></i> PASO 2: Destinatario
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Tipo Doc</label>
                    <input type="text" class="form-control" id="txtTipoDocDest" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Número Documento</label>
                    <input type="text" class="form-control" id="txtNumDocDest" readonly>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Razón Social / Nombre</label>
                    <input type="text" class="form-control" id="txtNombreDest" readonly>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASO 3: PUNTO DE LLEGADA -->
    <div class="card mb-4" id="seccionLlegada" style="display: none;">
        <div class="card-header bg-warning">
            <i class="fas fa-map-marker-alt"></i> PASO 3: Punto de Llegada
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-map"></i> Ubigeo (Departamento - Provincia - Distrito)
                        <span class="text-danger">*</span>
                    </label>
                    <input type="hidden" id="txtUbigeoLlegada" required>
                    <input type="text" class="form-control" id="txtUbigeoLlegadaDesc" 
                           placeholder="Buscar ubigeo..." required>
                    <small class="form-text text-muted">
                        Escriba al menos 3 caracteres para buscar
                    </small>
                    <div class="invalid-feedback">El ubigeo es requerido</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-home"></i> Dirección de Entrega
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="txtDireccionLlegada" 
                           placeholder="Dirección completa" required maxlength="200">
                    <div class="invalid-feedback">La dirección es requerida</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASO 4: DATOS DEL TRASLADO -->
    <div class="card mb-4" id="seccionTraslado" style="display: none;">
        <div class="card-header bg-success text-white">
            <i class="fas fa-dolly"></i> PASO 4: Datos del Traslado
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-clipboard-list"></i> Motivo de Traslado
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="cboMotivoTraslado" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($motivos as $motivo): ?>
                        <option value="<?= htmlspecialchars($motivo['CODIGO']) ?>">
                            <?= htmlspecialchars($motivo['CODIGO'] . ' - ' . $motivo['DESCRIPCION']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Seleccione el motivo</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-weight"></i> Peso Total (KG)
                        <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" id="txtPesoTotal" 
                           placeholder="0.00" step="0.01" min="0.01" required>
                    <div class="invalid-feedback">Ingrese el peso</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-box"></i> Nro Bultos
                        <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" id="txtNumBultos" 
                           placeholder="1" min="1" required>
                    <div class="invalid-feedback">Ingrese los bultos</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i> Fecha Inicio Traslado
                        <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" id="txtFechaTraslado" required>
                    <div class="invalid-feedback">Ingrese la fecha</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASO 5: TRANSPORTISTA -->
    <div class="card mb-4" id="seccionTransportista" style="display: none;">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-truck"></i> PASO 5: Transportista
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user-tie"></i> Seleccionar Transportista
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select select2" id="cboTransportista" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($transportistas as $transp): ?>
                        <option value="<?= $transp['ID'] ?>" 
                                data-documento="<?= htmlspecialchars($transp['DOCUMENTO']) ?>"
                                data-placa="<?= htmlspecialchars($transp['PLACA']) ?>"
                                data-licencia="<?= htmlspecialchars($transp['LICENCIA']) ?>">
                            <?= htmlspecialchars($transp['DOCUMENTO'] . ' - ' . $transp['NOMBRE']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Seleccione el transportista</div>
                    <small class="form-text text-muted">
                        <a href="modulos/transportistas.php" target="_blank">
                            <i class="fas fa-plus"></i> Agregar nuevo transportista
                        </a>
                    </small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-car"></i> Placa
                    </label>
                    <input type="text" class="form-control" id="txtPlaca" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> Licencia
                    </label>
                    <input type="text" class="form-control" id="txtLicencia" readonly>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASO 6: ALMACÉN Y SERIE -->
    <div class="card mb-4" id="seccionAlmacen" style="display: none;">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-warehouse"></i> PASO 6: Almacén de Salida
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-building"></i> Almacén / Punto de Partida
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="cboAlmacen" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($almacenes as $alm): ?>
                        <option value="<?= htmlspecialchars($alm['CODIGO']) ?>"
                                data-serie="<?= htmlspecialchars($alm['SERIE_GRE']) ?>"
                                data-correlativo="<?= $alm['CORRELATIVO'] ?>">
                            <?= htmlspecialchars($alm['CODIGO'] . ' - ' . $alm['NOMBRE']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Seleccione el almacén</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-barcode"></i> Serie GRE
                    </label>
                    <input type="text" class="form-control fw-bold" id="txtSerieGRE" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-sort-numeric-up"></i> Próximo Número
                    </label>
                    <input type="text" class="form-control fw-bold" id="txtNumeroGRE" readonly>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PASO 7: DETALLE DE PRODUCTOS -->
    <div class="card mb-4" id="seccionDetalle" style="display: none;">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-boxes"></i> PASO 7: Detalle de Productos
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tableDetalle">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th>CÓDIGO</th>
                            <th>DESCRIPCIÓN</th>
                            <th width="100">CANTIDAD</th>
                            <th width="80">UNIDAD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                <i class="fas fa-inbox"></i> No hay productos cargados
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- BOTONES DE ACCIÓN -->
    <div class="card" id="seccionBotones" style="display: none;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 text-end">
                    <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.href='index.php'">
                        <i class="fas fa-times"></i> CANCELAR
                    </button>
                    <button type="button" class="btn btn-warning btn-lg" onclick="guardarBorrador()">
                        <i class="fas fa-save"></i> GUARDAR BORRADOR
                    </button>
                    <button type="button" class="btn btn-success btn-lg" onclick="emitirYEnviarSunat()">
                        <i class="fas fa-paper-plane"></i> EMITIR Y ENVIAR A SUNAT
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Campos ocultos para almacenar datos -->
    <input type="hidden" id="hiddenTipoDocRel">
    <input type="hidden" id="hiddenSerieDocRel">
    <input type="hidden" id="hiddenNumeroDocRel">
    <input type="hidden" id="hiddenTipoDocDest">
    <input type="hidden" id="hiddenCodigoVendedor">
    <input type="hidden" id="hiddenDetalleJSON">
    
</form>
<?php
$additionalJS = [
    'assets/js/emitir-guia.js'
];$inlineScript = "
// Configurar fecha de traslado por defecto (hoy)
$(document).ready(function() {
var hoy = new Date().toISOString().split('T')[0];
$('#txtFechaTraslado').val(hoy);// Inicializar autocomplete de ubigeo
initUbigeoAutocomplete();// Evento cambio de transportista
$('#cboTransportista').on('change', function() {
    var selected = $(this).find(':selected');
    $('#txtPlaca').val(selected.data('placa') || '');
    $('#txtLicencia').val(selected.data('licencia') || '');
});// Evento cambio de almacén
$('#cboAlmacen').on('change', function() {
    var selected = $(this).find(':selected');
    var serie = selected.data('serie') || '';
    var correlativo = selected.data('correlativo') || 0;    $('#txtSerieGRE').val(serie);
    $('#txtNumeroGRE').val(String(correlativo).padStart(8, '0'));
});
});
";include 'includes/footer.php';
?>