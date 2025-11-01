<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/obtener_transportista.php
 * Descripción: API para obtener datos de un transportista
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
    
    $sql = "SELECT * FROM MPCL.dbo.TBL_TRANSPORTISTA WHERE ID = ?";
    $transportista = $db->selectOne($sql, [$id]);
    
    if (!$transportista) {
        throw new Exception('Transportista no encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transportista
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>