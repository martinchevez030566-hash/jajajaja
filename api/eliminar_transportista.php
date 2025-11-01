<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/eliminar_transportista.php
 * Descripción: API para eliminar transportista (eliminación lógica)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * Parámetros:
 * - id: ID del transportista
 * 
 * =======================================================
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

require_once __DIR__ . '/../librerias/Database.class.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        throw new Exception('ID no válido');
    }
    
    $db = Database::getInstance();
    
    // Verificar si tiene guías asociadas
    $sqlVerif = "SELECT COUNT(*) AS TOTAL 
                 FROM MPCL.dbo.TBL_GUIAS_CAB 
                 WHERE ID_TRANSPORTISTA = ?";
    
    $tieneGuias = $db->selectOne($sqlVerif, [$id]);
    
    if ($tieneGuias && $tieneGuias['TOTAL'] > 0) {
        throw new Exception('No se puede eliminar. El transportista tiene guías asociadas. Puede desactivarlo en su lugar.');
    }
    
    // Eliminar (físico, ya que no tiene guías)
    $sql = "DELETE FROM MPCL.dbo.TBL_TRANSPORTISTA WHERE ID = ?";
    
    $result = $db->execute($sql, [$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Transportista eliminado correctamente'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Error al eliminar el transportista');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>