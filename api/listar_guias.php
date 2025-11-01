<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/listar_guias.php
 * Descripción: API para listar guías (DataTables Server-Side)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - ../librerias/Database.class.php
 * 
 * Método: POST
 * Parámetros (filtros opcionales):
 * - filtro_documento
 * - filtro_cliente
 * - filtro_vendedor
 * - filtro_estado
 * - filtro_fecha_desde
 * - filtro_fecha_hasta
 * 
 * Respuesta JSON (formato DataTables):
 * {
 *   "data": [...]
 * }
 * 
 * =======================================================
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['data' => []]);
    exit;
}

require_once __DIR__ . '/../librerias/Database.class.php';

try {
    $db = Database::getInstance();
    
    // Obtener filtros
    $filtroDoc = isset($_POST['filtro_documento']) ? trim($_POST['filtro_documento']) : '';
    $filtroCli = isset($_POST['filtro_cliente']) ? trim($_POST['filtro_cliente']) : '';
    $filtroVen = isset($_POST['filtro_vendedor']) ? trim($_POST['filtro_vendedor']) : '';
    $filtroEst = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtroDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    
    // Construir consulta
    $sql = "SELECT TOP 60
            G.ID,
            G.SERIE,
            G.NUMERO,
            G.FECHA_EMISION,
            G.TIPO_DOC_REL,
            G.SERIE_DOC_REL,
            G.NUMERO_DOC_REL,
            G.NUM_DOC_DESTINATARIO,
            G.RAZON_SOCIAL_DESTINATARIO,
            G.DIRECCION_LLEGADA,
            U.departamento_inei + ' - ' + U.provincia_inei + ' - ' + U.distrito AS DESTINO,
            G.ESTADO_SUNAT,
            G.ESTADO_ANULACION,
            G.CODIGO_HASH,
            G.MENSAJE_SUNAT,
            T.NOMBRE AS TRANSPORTISTA,
            V.VE_CNOMBRE AS VENDEDOR
            FROM MPCL.dbo.TBL_GUIAS_CAB G
            LEFT JOIN RSFACCAR.dbo.TB_UBIGEOS U ON G.UBIGEO_LLEGADA = U.ubigeo_inei
            LEFT JOIN MPCL.dbo.TBL_TRANSPORTISTA T ON G.ID_TRANSPORTISTA = T.ID
            LEFT JOIN RSFACCAR.dbo.FT0005VEND V ON G.CODIGO_VENDEDOR = V.VE_CCODIGO
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtroDoc)) {
        $sql .= " AND G.NUM_DOC_DESTINATARIO LIKE ?";
        $params[] = "%$filtroDoc%";
    }
    
    if (!empty($filtroCli)) {
        $sql .= " AND G.RAZON_SOCIAL_DESTINATARIO LIKE ?";
        $params[] = "%$filtroCli%";
    }
    
    if (!empty($filtroVen)) {
        $sql .= " AND G.CODIGO_VENDEDOR = ?";
        $params[] = $filtroVen;
    }
    
    if (!empty($filtroEst)) {
        $sql .= " AND G.ESTADO_SUNAT = ?";
        $params[] = $filtroEst;
    }
    
    if (!empty($filtroDesde)) {
        $sql .= " AND CAST(G.FECHA_EMISION AS DATE) >= ?";
        $params[] = $filtroDesde;
    }
    
    if (!empty($filtroHasta)) {
        $sql .= " AND CAST(G.FECHA_EMISION AS DATE) <= ?";
        $params[] = $filtroHasta;
    }
    
    $sql .= " ORDER BY G.FECHA_EMISION DESC, G.ID DESC";
    
    $resultados = $db->select($sql, $params);
    
    $data = [];
    
    foreach ($resultados as $row) {
        // Formatear número de guía
        $nroGuia = $row['SERIE'] . '-' . str_pad($row['NUMERO'], 8, '0', STR_PAD_LEFT);
        
        // Formatear documento relacionado
        $docRelacionado = '-';
        if ($row['SERIE_DOC_REL'] && $row['NUMERO_DOC_REL']) {
            $docRelacionado = $row['SERIE_DOC_REL'] . '-' . $row['NUMERO_DOC_REL'];
        }
        
        // Formatear fecha
        $fechaEmision = $row['FECHA_EMISION'] ? 
            date('d/m/Y', strtotime($row['FECHA_EMISION'])) : '-';
        
        $data[] = [
            'ID' => $row['ID'],
            'SERIE' => $row['SERIE'],
            'NUMERO' => $row['NUMERO'],
            'NRO_GUIA' => $nroGuia,
            'FECHA_EMISION' => $fechaEmision,
            'FECHA_EMISION_RAW' => $row['FECHA_EMISION'],
            'TIPO_DOC_REL' => $row['TIPO_DOC_REL'],
            'SERIE_DOC_REL' => $row['SERIE_DOC_REL'],
            'NUMERO_DOC_REL' => $row['NUMERO_DOC_REL'],
            'DOC_RELACIONADO' => $docRelacionado,
            'NUM_DOC_DESTINATARIO' => $row['NUM_DOC_DESTINATARIO'],
            'RAZON_SOCIAL_DESTINATARIO' => $row['RAZON_SOCIAL_DESTINATARIO'],
            'DIRECCION_LLEGADA' => $row['DIRECCION_LLEGADA'],
            'DESTINO' => $row['DESTINO'] ?: $row['DIRECCION_LLEGADA'],
            'ESTADO_SUNAT' => $row['ESTADO_SUNAT'],
            'ESTADO_ANULACION' => $row['ESTADO_ANULACION'],
            'CODIGO_HASH' => $row['CODIGO_HASH'],
            'MENSAJE_SUNAT' => $row['MENSAJE_SUNAT'],
            'TRANSPORTISTA' => $row['TRANSPORTISTA'],
            'VENDEDOR' => $row['VENDEDOR']
        ];
    }
    
    echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>