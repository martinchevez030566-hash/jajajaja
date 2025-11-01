<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/anular_guia.php
 * Descripción: API para anular guía de remisión
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - ../librerias/Database.class.php
 * - ../librerias/SunatGRE.class.php
 * 
 * Método: POST
 * Parámetros:
 * - id_guia: ID de la guía
 * - motivo: Motivo de anulación
 * - observacion: Observaciones adicionales (opcional)
 * 
 * Respuesta JSON:
 * {
 *   "success": true/false,
 *   "mensaje": "..."
 * }
 * 
 * =======================================================
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}

require_once __DIR__ . '/../librerias/Database.class.php';
require_once __DIR__ . '/../librerias/SunatGRE.class.php';

try {
    $idGuia = isset($_POST['id_guia']) ? intval($_POST['id_guia']) : 0;
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    $observacion = isset($_POST['observacion']) ? trim($_POST['observacion']) : '';
    
    if ($idGuia <= 0) {
        throw new Exception('ID de guía no válido');
    }
    
    if (empty($motivo)) {
        throw new Exception('Debe especificar el motivo de anulación');
    }
    
    $db = Database::getInstance();
    
    // Verificar estado de la guía
    $sql = "SELECT ESTADO_SUNAT, ESTADO_ANULACION, SERIE, NUMERO 
            FROM MPCL.dbo.TBL_GUIAS_CAB 
            WHERE ID = ?";
    
    $guia = $db->selectOne($sql, [$idGuia]);
    
    if (!$guia) {
        throw new Exception('Guía no encontrada');
    }
    
    if ($guia['ESTADO_SUNAT'] != 'ACEPTADO') {
        throw new Exception('Solo se pueden anular guías ACEPTADAS por SUNAT');
    }
    
    if ($guia['ESTADO_ANULACION']) {
        throw new Exception('Esta guía ya fue anulada');
    }
    
    // Construir motivo completo
    $motivoCompleto = $motivo;
    if (!empty($observacion)) {
        $motivoCompleto .= ' - ' . $observacion;
    }
    
    // Intentar anular en SUNAT
    $sunat = new SunatGRE();
    $resultado = $sunat->anularGuia($idGuia, $motivoCompleto);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'mensaje' => 'Guía anulada correctamente'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Si falla el envío a SUNAT, aún así anular localmente
        $sqlUpdate = "UPDATE MPCL.dbo.TBL_GUIAS_CAB SET
                      ESTADO_ANULACION = 'ANULADO_LOCAL',
                      FECHA_ANULACION = GETDATE(),
                      MOTIVO_ANULACION = ?,
                      USUARIO_ANULACION = ?,
                      ESTADO_SUNAT = 'ANULADO',
                      MENSAJE_SUNAT = 'Anulado localmente. Error en SUNAT: ' + ?
                      WHERE ID = ?";
        
        $db->execute($sqlUpdate, [
            $motivoCompleto,
            $_SESSION['usuario'],
            $resultado['error'],
            $idGuia
        ]);
        
        $db->registrarLog(
            $idGuia,
            'ANULACION_LOCAL',
            'ACEPTADO',
            'ANULADO_LOCAL',
            'Anulación local. Error SUNAT: ' . $resultado['error'],
            null,
            null
        );
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Guía anulada localmente. No se pudo anular en SUNAT: ' . $resultado['error'],
            'warning' => true
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
