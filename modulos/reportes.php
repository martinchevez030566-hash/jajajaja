<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/modulos/reportes.php
 * Descripción: Módulo de reportes y consultas
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

$pageTitle = 'Reportes y Consultas';
$currentPage = 'reportes';
$breadcrumb = [
    ['text' => 'Reportes', 'url' => '#']
];

$db = Database::getInstance();

// Obtener estadísticas para el dashboard
$sqlEstadisticas = "SELECT 
                    COUNT(*) AS TOTAL,
                    SUM(CASE WHEN ESTADO_SUNAT = 'ACEPTADO' THEN 1 ELSE 0 END) AS ACEPTADAS,
                    SUM(CASE WHEN ESTADO_SUNAT = 'PENDIENTE' THEN 1 ELSE 0 END) AS PENDIENTES,
                    SUM(CASE WHEN ESTADO_SUNAT = 'RECHAZADO' THEN 1 ELSE 0 END) AS RECHAZADAS,
                    SUM(CASE WHEN ESTADO_ANULACION IS NOT NULL THEN 1 ELSE 0 END) AS ANULADAS
                    FROM MPCL.dbo.TBL_GUIAS_CAB";
$stats = $db->selectOne($sqlEstadisticas);

include __DIR__ . '/../includes/header.php';
?>

<!-- TÍTULO -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-chart-bar"></i> Reportes y Consultas</h2>
        <p class="text-muted">Genere reportes y consultas personalizadas del sistema</p>
    </div>
</div>

<!-- ESTADÍSTICAS GENERALES -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chart-pie"></i> Estadísticas Generales
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="stat-value text-primary"><?= number_format($stats['TOTAL']) ?></div>
                        <div class="stat-label">Total Guías</div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-value text-success"><?= number_format($stats['ACEPTADAS']) ?></div>
                        <div class="stat-label">Aceptadas</div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-value text-warning"><?= number_format($stats['PENDIENTES']) ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-value text-danger"><?= number_format($stats['RECHAZADAS']) ?></div>
                        <div class="stat-label">Rechazadas</div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-value text-secondary"><?= number_format($stats['ANULADAS']) ?></div>
                        <div class="stat-label">Anuladas</div>
                    </div>
                    <div class="col-md-2">
                        <?php
                        $porcentajeExito = $stats['TOTAL'] > 0 ? 
                            round(($stats['ACEPTADAS'] / $stats['TOTAL']) * 100, 1) : 0;
                        ?>
                        <div class="stat-value text-info"><?= $porcentajeExito ?>%</div>
<div class="stat-label">Tasa de Éxito</div>
</div>
</div>
</div>
</div>
</div>
</div>
<!-- REPORTES PREDEFINIDOS -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-alt"></i> Reportes Predefinidos
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-day fa-3x text-primary mb-3"></i>
                                <h5>Guías del Día</h5>
                                <p class="text-muted">Reporte de guías emitidas hoy</p>
                                <button class="btn btn-primary" onclick="reporteDelDia()">
                                    <i class="fas fa-file-excel"></i> Generar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-week fa-3x text-success mb-3"></i>
                                <h5>Guías de la Semana</h5>
                                <p class="text-muted">Reporte semanal de guías</p>
                                <button class="btn btn-success" onclick="reporteDeLaSemana()">
                                    <i class="fas fa-file-excel"></i> Generar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-warning mb-3"></i>
                                <h5>Guías del Mes</h5>
                                <p class="text-muted">Reporte mensual de guías</p>
                                <button class="btn btn-warning" onclick="reporteDelMes()">
                                    <i class="fas fa-file-excel"></i> Generar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie fa-3x text-info mb-3"></i>
                                <h5>Por Vendedor</h5>
                                <p class="text-muted">Guías agrupadas por vendedor</p>
                                <button class="btn btn-info" onclick="reportePorVendedor()">
                                    <i class="fas fa-file-pdf"></i> Generar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-truck fa-3x text-secondary mb-3"></i>
                                <h5>Por Transportista</h5>
                                <p class="text-muted">Guías agrupadas por transportista</p>
                                <button class="btn btn-secondary" onclick="reportePorTransportista()">
                                    <i class="fas fa-file-pdf"></i> Generar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-map-marker-alt fa-3x text-danger mb-3"></i>
                                <h5>Por Destino</h5>
                                <p class="text-muted">Guías agrupadas por ubigeo</p>
                                <button class="btn btn-danger" onclick="reportePorDestino()">
                                    <i class="fas fa-file-pdf"></i> Generar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- CONSULTA PERSONALIZADA -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-search"></i> Consulta Personalizada
            </div>
            <div class="card-body">
                <form id="formConsulta">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-calendar"></i> Fecha Desde
                            </label>
                            <input type="date" class="form-control" id="fechaDesde">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-calendar"></i> Fecha Hasta
                            </label>
                            <input type="date" class="form-control" id="fechaHasta">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-filter"></i> Estado SUNAT
                            </label>
                            <select class="form-select" id="filtroEstadoReporte">
                                <option value="">Todos</option>
                                <option value="ACEPTADO">Aceptado</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="RECHAZADO">Rechazado</option>
                                <option value="ANULADO">Anulado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-user-tie"></i> Vendedor
                            </label>
                            <select class="form-select select2" id="filtroVendedor">
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
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-truck"></i> Transportista
                            </label>
                            <select class="form-select select2" id="filtroTransportista">
                                <option value="">Todos</option>
                                <?php
                                $sqlTransp = "SELECT ID, NOMBRE FROM MPCL.dbo.TBL_TRANSPORTISTA WHERE ESTADO = 1 ORDER BY NOMBRE";
                                $transportistas = $db->select($sqlTransp);
                                foreach ($transportistas as $transp):
                                ?>
                                <option value="<?= $transp['ID'] ?>">
                                    <?= htmlspecialchars($transp['NOMBRE']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-file"></i> Formato
                            </label>
                            <select class="form-select" id="formatoReporte">
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="pdf">PDF (.pdf)</option>
                                <option value="csv">CSV (.csv)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="fas fa-sort"></i> Agrupar Por
                            </label>
                            <select class="form-select" id="agruparPor">
                                <option value="">Sin agrupar</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="transportista">Transportista</option>
                                <option value="destino">Destino</option>
                                <option value="estado">Estado</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12 text-end">
                            <button type="button" class="btn btn-secondary" onclick="limpiarConsulta()">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="generarReportePersonalizado()">
                                <i class="fas fa-download"></i> Generar Reporte
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- GRÁFICOS ESTADÍSTICOS -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Guías por Mes (Últimos 6 meses)
            </div>
            <div class="card-body">
                <canvas id="chartGuiasMes" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Distribución por Estado
            </div>
            <div class="card-body">
                <canvas id="chartEstados" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
<?php
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

$inlineScript = "
$(document).ready(function() {
    // Configurar fechas por defecto (último mes)
    var hoy = new Date();
    var hace30dias = new Date();
    hace30dias.setDate(hoy.getDate() - 30);
    
    $('#fechaDesde').val(hace30dias.toISOString().split('T')[0]);
    $('#fechaHasta').val(hoy.toISOString().split('T')[0]);
    
    // Inicializar gráficos
    inicializarGraficos();
});

/**
 * Inicializar gráficos con Chart.js
 */
function inicializarGraficos() {
    // Gráfico de guías por mes
    var ctxMes = document.getElementById('chartGuiasMes').getContext('2d');
    new Chart(ctxMes, {
        type: 'line',
        data: {
            labels: ['Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre'],
            datasets: [{
                label: 'Guías Emitidas',
                data: [12, 19, 15, 25, 22, 30],
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });
    
    // Gráfico de estados
    var ctxEstados = document.getElementById('chartEstados').getContext('2d');
    new Chart(ctxEstados, {
        type: 'doughnut',
        data: {
            labels: ['Aceptadas', 'Pendientes', 'Rechazadas', 'Anuladas'],
            datasets: [{
                data: [<?= $stats['ACEPTADAS'] ?>, <?= $stats['PENDIENTES'] ?>, 
                   <?= $stats['RECHAZADAS'] ?>, <?= $stats['ANULADAS'] ?>],
            backgroundColor: [
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)',
                'rgb(100, 116, 139)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
}
/**

Reporte del día
*/
function reporteDelDia() {
var hoy = new Date().toISOString().split('T')[0];
generarReporte(hoy, hoy, '', 'excel');
}

/**

Reporte de la semana
*/
function reporteDeLaSemana() {
var hoy = new Date();
var hace7dias = new Date();
hace7dias.setDate(hoy.getDate() - 7);
generarReporte(
hace7dias.toISOString().split('T')[0],
hoy.toISOString().split('T')[0],
'',
'excel'
);
}

/**

Reporte del mes
*/
function reporteDelMes() {
var hoy = new Date();
var primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
generarReporte(
primerDia.toISOString().split('T')[0],
hoy.toISOString().split('T')[0],
'',
'excel'
);
}

/**

Reporte por vendedor
*/
function reportePorVendedor() {
Swal.fire({
title: 'Reporte por Vendedor',
text: 'Seleccione el período',
html:          <div class=\"mb-3\">              <label class=\"form-label\">Desde:</label>              <input type=\"date\" id=\"swal-desde\" class=\"form-control\">          </div>          <div class=\"mb-3\">              <label class=\"form-label\">Hasta:</label>              <input type=\"date\" id=\"swal-hasta\" class=\"form-control\">          </div>     ,
showCancelButton: true,
confirmButtonText: 'Generar',
preConfirm: () => {
return {
desde: document.getElementById('swal-desde').value,
hasta: document.getElementById('swal-hasta').value
};
}
}).then((result) => {
if (result.isConfirmed) {
generarReporte(result.value.desde, result.value.hasta, 'vendedor', 'pdf');
}
});
}

/**

Reporte por transportista
*/
function reportePorTransportista() {
var hoy = new Date();
var hace30dias = new Date();
hace30dias.setDate(hoy.getDate() - 30);
generarReporte(
hace30dias.toISOString().split('T')[0],
hoy.toISOString().split('T')[0],
'transportista',
'pdf'
);
}

/**

Reporte por destino
*/
function reportePorDestino() {
var hoy = new Date();
var hace30dias = new Date();
hace30dias.setDate(hoy.getDate() - 30);
generarReporte(
hace30dias.toISOString().split('T')[0],
hoy.toISOString().split('T')[0],
'destino',
'pdf'
);
}

/**

Generar reporte personalizado
*/
function generarReportePersonalizado() {
var fechaDesde = $('#fechaDesde').val();
var fechaHasta = $('#fechaHasta').val();
var formato = $('#formatoReporte').val();
var agrupar = $('#agruparPor').val();
if (!fechaDesde || !fechaHasta) {
mostrarAdvertencia('Debe seleccionar el rango de fechas');
return;
}
var params = new URLSearchParams({
fecha_desde: fechaDesde,
fecha_hasta: fechaHasta,
estado: $('#filtroEstadoReporte').val(),
vendedor: $('#filtroVendedor').val(),
transportista: $('#filtroTransportista').val(),
formato: formato,
agrupar: agrupar
});
var url = '../api/generar_reporte.php?' + params.toString();
window.open(url, '_blank');
mostrarToast('Generando reporte...', 'info');
}

/**

Función auxiliar para generar reportes
*/
function generarReporte(desde, hasta, agrupar, formato) {
var params = new URLSearchParams({
fecha_desde: desde,
fecha_hasta: hasta,
agrupar: agrupar,
formato: formato
});
var url = '../api/generar_reporte.php?' + params.toString();
window.open(url, '_blank');
mostrarToast('Generando reporte...', 'info');
}

/**

Limpiar consulta
*/
function limpiarConsulta() {
$('#formConsulta')[0].reset();
// Restablecer fechas
var hoy = new Date();
var hace30dias = new Date();
hace30dias.setDate(hoy.getDate() - 30);
$('#fechaDesde').val(hace30dias.toISOString().split('T')[0]);
$('#fechaHasta').val(hoy.toISOString().split('T')[0]);
$('.select2').val('').trigger('change');
}
";

include DIR . '/../includes/footer.php';
?>