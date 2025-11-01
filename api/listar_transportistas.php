<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/listar_transportistas.php
 * Descripción: API para listar transportistas (DataTables)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * Parámetros:
 * - buscar: Texto de búsqueda (opcional)
 * - estado: Estado (1=activo, 0=inactivo, ''=todos)
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
    
    $buscar = isset($_POST['buscar']) ? trim($_POST['buscar']) : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
    
    $sql = "SELECT 
            ID, USUARIO, ESTADO, NOMBRE, DOCUMENTO, PLACA, LICENCIA
            FROM MPCL.dbo.TBL_TRANSPORTISTA
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($buscar)) {
        $sql .= " AND (DOCUMENTO LIKE ? OR NOMBRE LIKE ?)";
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }
    
    if ($estado !== '') {
        $sql .= " AND ESTADO = ?";
        $params[] = $estado;
    }
    
    $sql .= " ORDER BY NOMBRE";
    
    $resultados = $db->select($sql, $params);
    
    echo json_encode(['data' => $resultados], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>