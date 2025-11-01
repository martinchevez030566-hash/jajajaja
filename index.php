<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/index.php
 * Descripción: Panel de control principal - Listado de guías
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

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/librerias/Database.class.php';

$pageTitle = 'Panel de Control';
$currentPage = 'dashboard';
$breadcrumb = [];

// Obtener estadísticas
$db = Database::getInstance();

// Total de guías
$sqlTotal = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_GUIAS_CAB";
$totalGuias = $db->selectOne($sqlTotal);

// Guías aceptadas
$sqlAceptadas = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_GUIAS_CAB WHERE ESTADO_SUNAT = 'ACEPTADO'";
$guiasAceptadas = $db->selectOne($sqlAceptadas);

// Guías pendientes
$sqlPendientes = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_GUIAS_CAB WHERE ESTADO_SUNAT = 'PENDIENTE'";
$guiasPendientes = $db->selectOne($sqlPendientes);

// Guías rechazadas
$sqlRechazadas = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_GUIAS_CAB WHERE ESTADO_SUNAT = 'RECHAZADO'";
$guiasRechazadas = $db->selectOne($sqlRechazadas);

// Guías del mes actual
$sqlMesActual = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_GUIAS_CAB 
                 WHERE MONTH(FECHA_EMISION) = MONTH(GETDATE()) 
                 AND YEAR(FECHA_EMISION) = YEAR(GETDATE())";
$guiasMesActual = $db->selectOne($sqlMesActual);

include 'includes/header.php';
?>

<!-- ESTADÍSTICAS -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stat-card position-relative">
            <i class="fas fa-file-alt stat-icon"></i>
            <div class="stat-value"><?= number_format($totalGuias['TOTAL']) ?></div>
            <div class="stat-label">Total Guías</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-success position-relative">
            <i class="fas fa-check-circle stat-icon"></i>
            <div class="stat-value"><?= number_format($guiasAceptadas['TOTAL']) ?></div>
            <div class="stat-label">Aceptadas</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-warning position-relative">
            <i class="fas fa-clock stat-icon"></i>
            <div class="stat-value"><?= number_format($guiasPendientes['TOTAL']) ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stat-card stat-danger position-relative">
            <i class="fas fa-times-circle stat-icon"></i>
            <div class="stat-value"><?= number_format($guiasRechazadas['TOTAL']) ?></div>
            <div class="stat-label">Rechazadas</div>
        </div>
    </div>
</div>

<!-- ACCIONES RÁPIDAS -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-bolt text-warning"></i> Acciones Rápidas
                </h5>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="emitir_guia.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i> Nueva Guía de Remisión
                    </a>
                    <button type="button" class="btn btn-success btn-lg" onclick="abrirReporteMes()">
                        <i class="fas fa-calendar-alt"></i> Guías del Mes (<?= $guiasMesActual['TOTAL'] ?>)
                    </button>
                    <a href="modulos/transportistas.php" class="btn btn-info btn-lg">
                        <i class="fas fa-truck"></i> Gestionar Transportistas
                    </a>
                    <a href="modulos/configuracion.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTROS DE BÚSQUEDA -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter"></i> Búsqueda Avanzada
    </div>
    <div class="card-body">
        <form id="formFiltros">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> RUC/DNI Cliente
                    </label>
                    <input type="text" class="form-control" id="filtro_documento" placeholder="Buscar...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Nombre Cliente
                    </label>
                    <input type="text" class="form-control" id="filtro_cliente" placeholder="Buscar...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-user-tie"></i> Vendedor
                    </label>
                    <select class="form-select" id="filtro_vendedor">
                        <option value="">Todos</option>
                        <?php
                        $sqlVend = "SELECT VE_CCODIGO, VE_CNOMBRE FROM rsfaccar.dbo.FT0005VEND ORDER BY VE_CNOMBRE";
                        $vendedores = $db->select($sqlVend);
                        foreach ($vendedores as $vend):
                        ?>
                        <option value="<?= htmlspecialchars($vend['VE_CCODIGO']) ?>">
                            <?= htmlspecialchars($vend['VE_CNOMBRE']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-traffic-light"></i> Estado SUNAT
                    </label>
                    <select class="form-select" id="filtro_estado">
                        <option value="">Todos</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="ACEPTADO">ACEPTADO</option>
                        <option value="RECHAZADO">RECHAZADO</option>
                        <option value="ANULADO">ANULADO</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Rango de Fechas
                    </label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="filtro_fecha_desde">
                        <input type="date" class="form-control" id="filtro_fecha_hasta">
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="button" class="btn btn-primary" onclick="buscarGuias()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                    <button type="button" class="btn btn-danger" onclick="exportarPDF()">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TABLA DE GUÍAS -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Listado de Guías de Remisión
        <span class="badge bg-primary float-end" id="badgeTotalGuias">0 guías</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableGuias" class="table table-hover table-striped w-100">
                <thead>
                    <tr>
                        <th width="50"></th>
                        <th>NRO GUÍA</th>
                        <th>FECHA</th>
                        <th>DOC. RELACIONADO</th>
                        <th>CLIENTE</th>
                        <th>DESTINO</th>
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

<?php
$additionalJS = [
    'assets/js/panel-control.js'
];

$inlineScript = "
// Configurar fechas por defecto (últimos 30 días)
$(document).ready(function() {
    var hoy = new Date();
    var hace30dias = new Date();
    hace30dias.setDate(hoy.getDate() - 30);
    
    $('#filtro_fecha_desde').val(hace30dias.toISOString().split('T')[0]);
    $('#filtro_fecha_hasta').val(hoy.toISOString().split('T')[0]);
});
";

include 'includes/footer.php';
?>