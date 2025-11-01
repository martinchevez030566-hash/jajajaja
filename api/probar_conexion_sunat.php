<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/probar_conexion_sunat.php
 * Descripción: API para probar conexión con SUNAT
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * 
 * =======================================================
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

require_once __DIR__ . '/../librerias/SunatGRE.class.php';

try {
    $sunat = new SunatGRE();
    $resultado = $sunat->probarConexion();
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>