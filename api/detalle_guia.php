<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/detalle_guia.php
 * Descripción: API para obtener detalle expandible de una guíaReintentarClaude aún no tiene la capacidad de ejecutar el código que genera.MContinuar
Autor: Sistema GRE
Fecha: 2025-10-26

Dependencias:


../librerias/Database.class.php



Método: POST
Parámetros:


id: ID de la guía



Respuesta: HTML formateado

=======================================================
*/

session_start();
if (!isset($_SESSION['usuario'])) {
echo '<div class="alert alert-danger">Sesión no válida</div>';
exit;
}
require_once DIR . '/../librerias/Database.class.php';
try {
    idGuia=isset(idGuia = isset(
idGuia=isset(_POST['id']) ? intval($_POST['id']) : 0;if ($idGuia <= 0) {
if ($idGuia <= 0) {
    throw new Exception('ID de guía no válido');
}

$db = Database::getInstance();

// Obtener datos completos de la guía
$sql = "SELECT 
        G.*,
        A.A1_CDESCRI AS ALMACEN_NOMBRE,
        A.A1_CDIRECC AS ALMACEN_DIRECCION,
        U1.departamento_inei + ' - ' + U1.provincia_inei + ' - ' + U1.distrito AS UBIGEO_PARTIDA_DESC,
        U2.departamento_inei + ' - ' + U2.provincia_inei + ' - ' + U2.distrito AS UBIGEO_LLEGADA_DESC,
        T.NOMBRE AS TRANSPORTISTA_NOMBRE,
        T.DOCUMENTO AS TRANSPORTISTA_DOC,
        T.PLACA,
        T.LICENCIA,
        M.DESCRIPCION AS MOTIVO_DESC,
        V.VE_CNOMBRE AS VENDEDOR_NOMBRE
        FROM MPCL.dbo.TBL_GUIAS_CAB G
        LEFT JOIN RSFACCAR.dbo.AL0005ALMA A ON G.CODIGO_ALMACEN = A.A1_CALMA
        LEFT JOIN RSFACCAR.dbo.TB_UBIGEOS U1 ON A.A1_CCODUBI = U1.ubigeo_inei
        LEFT JOIN RSFACCAR.dbo.TB_UBIGEOS U2 ON G.UBIGEO_LLEGADA = U2.ubigeo_inei
        LEFT JOIN MPCL.dbo.TBL_TRANSPORTISTA T ON G.ID_TRANSPORTISTA = T.ID
        LEFT JOIN MPCL.dbo.TBL_MOTIVOS_TRASLADO M ON G.MOTIVO_TRASLADO = M.CODIGO
        LEFT JOIN RSFACCAR.dbo.FT0005VEND V ON G.CODIGO_VENDEDOR = V.VE_CCODIGO
        WHERE G.ID = ?";

$guia = $db->selectOne($sql, [$idGuia]);

if (!$guia) {
    throw new Exception('Guía no encontrada');
}

// Obtener detalle de productos
$sqlDet = "SELECT * FROM MPCL.dbo.TBL_GUIAS_DET WHERE ID_GUIA_CAB = ? ORDER BY ITEM";
$detalle = $db->select($sqlDet, [$idGuia]);

// Formatear fechas
$fechaEmision = $guia['FECHA_EMISION'] ? date('d/m/Y H:i', strtotime($guia['FECHA_EMISION'])) : '-';
$fechaTraslado = $guia['FECHA_INICIO_TRASLADO'] ? date('d/m/Y', strtotime($guia['FECHA_INICIO_TRASLADO'])) : '-';
$fechaEnvio = $guia['FECHA_ENVIO_SUNAT'] ? date('d/m/Y H:i', strtotime($guia['FECHA_ENVIO_SUNAT'])) : '-';

// Generar HTML
?>

<div class="row g-3">
    <!-- COLUMNA IZQUIERDA -->
    <div class="col-md-6">
        
        <!-- PRODUCTOS -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-box"></i> PRODUCTOS A TRASLADAR
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>CÓDIGO</th>
                                <th>DESCRIPCIÓN</th>
                                <th width="80">CANT</th>
                                <th width="60">UND</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalle as $item): ?>
                            <tr>
                                <td class="text-center"><?= $item['ITEM'] ?></td>
                                <td><small><?= htmlspecialchars($item['CODIGO_PRODUCTO']) ?></small></td>
                                <td><?= htmlspecialchars($item['DESCRIPCION']) ?></td>
                                <td class="text-end"><?= number_format($item['CANTIDAD'], 2) ?></td>
                                <td class="text-center"><?= htmlspecialchars($item['UNIDAD_MEDIDA']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-6">
                        <strong>Peso Total:</strong> 
                        <?= number_format($guia['PESO_TOTAL'], 2) ?> <?= $guia['UNIDAD_PESO'] ?>
                    </div>
                    <div class="col-6">
                        <strong>Bultos:</strong> <?= $guia['NUM_BULTOS'] ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- DESTINATARIO -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="fas fa-user"></i> DESTINATARIO
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td width="120"><strong>Documento:</strong></td>
                        <td><?= htmlspecialchars($guia['NUM_DOC_DESTINATARIO']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Razón Social:</strong></td>
                        <td><?= htmlspecialchars($guia['RAZON_SOCIAL_DESTINATARIO']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- COLUMNA DERECHA -->
    <div class="col-md-6">
        
        <!-- TRANSPORTE -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="fas fa-truck"></i> DATOS DEL TRANSPORTE
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td width="120"><strong>Transportista:</strong></td>
                        <td><?= htmlspecialchars($guia['TRANSPORTISTA_NOMBRE']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>RUC/DNI:</strong></td>
                        <td><?= htmlspecialchars($guia['TRANSPORTISTA_DOC']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Placa:</strong></td>
                        <td><?= htmlspecialchars($guia['PLACA']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Licencia:</strong></td>
                        <td><?= htmlspecialchars($guia['LICENCIA'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Fecha Traslado:</strong></td>
                        <td><?= $fechaTraslado ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- DIRECCIONES -->
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <i class="fas fa-map-marker-alt"></i> PUNTOS DE TRASLADO
            </div>
            <div class="card-body">
                <div class="alert alert-success mb-2">
                    <strong><i class="fas fa-arrow-up"></i> PARTIDA:</strong><br>
                    <?= htmlspecialchars($guia['ALMACEN_DIRECCION']) ?><br>
                    <small class="text-muted"><?= htmlspecialchars($guia['UBIGEO_PARTIDA_DESC']) ?></small>
                </div>
                <div class="alert alert-danger mb-0">
                    <strong><i class="fas fa-arrow-down"></i> LLEGADA:</strong><br>
                    <?= htmlspecialchars($guia['DIRECCION_LLEGADA']) ?><br>
                    <small class="text-muted"><?= htmlspecialchars($guia['UBIGEO_LLEGADA_DESC']) ?></small>
                </div>
            </div>
        </div>
        
        <!-- INFORMACIÓN ADICIONAL -->
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-info-circle"></i> INFORMACIÓN ADICIONAL
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td width="140"><strong>Motivo Traslado:</strong></td>
                        <td><?= htmlspecialchars($guia['MOTIVO_DESC']) ?></td>
                    </tr>
                    <?php if ($guia['VENDEDOR_NOMBRE']): ?>
                    <tr>
                        <td><strong>Vendedor:</strong></td>
                        <td><?= htmlspecialchars($guia['VENDEDOR_NOMBRE']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Fecha Emisión:</strong></td>
                        <td><?= $fechaEmision ?></td>
                    </tr>
                    <?php if ($guia['FECHA_ENVIO_SUNAT']): ?>
                    <tr>
                        <td><strong>Enviado SUNAT:</strong></td>
                        <td><?= $fechaEnvio ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($guia['MENSAJE_SUNAT']): ?>
                    <tr>
                        <td><strong>Mensaje SUNAT:</strong></td>
                        <td><small><?= htmlspecialchars($guia['MENSAJE_SUNAT']) ?></small></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($guia['CODIGO_HASH']): ?>
                    <tr>
                        <td><strong>Hash:</strong></td>
                        <td><small class="text-muted"><?= htmlspecialchars(substr($guia['CODIGO_HASH'], 0, 40)) ?>...</small></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
    </div>
</div>

<!-- BOTONES DE ACCIÓN -->
<div class="row mt-3">
    <div class="col-12">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" onclick="imprimirGuia(<?= $guia['ID'] ?>)">
                <i class="fas fa-print"></i> Imprimir
            </button>
            
            <?php if ($guia['ESTADO_SUNAT'] == 'PENDIENTE'): ?>
            <button type="button" class="btn btn-success" onclick="reenviarSunat(<?= $guia['ID'] ?>)">
                <i class="fas fa-paper-plane"></i> Enviar a SUNAT
            </button>
            <?php endif; ?>
            
            <?php if ($guia['ESTADO_SUNAT'] == 'ACEPTADO' && !$guia['ESTADO_ANULACION']): ?>
            <button type="button" class="btn btn-danger" onclick="anularGuia(<?= $guia['ID'] ?>)">
                <i class="fas fa-ban"></i> Anular
            </button>
            <?php endif; ?>
            
            <?php if ($guia['XML_FIRMADO']): ?>
            <button type="button" class="btn btn-secondary" onclick="verXML(<?= $guia['ID'] ?>)">
                <i class="fas fa-code"></i> Ver XML
            </button>
            <?php endif; ?>
            
            <?php if ($guia['CDR_SUNAT']): ?>
            <button type="button" class="btn btn-info" onclick="descargarCDR(<?= $guia['ID'] ?>)">
                <i class="fas fa-download"></i> Descargar CDR
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
} catch (Exception $e) {
echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>