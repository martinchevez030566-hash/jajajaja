<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/firmar_enviar.php
 * Descripción: API para firmar XML y enviar guía a SUNAT
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - ../librerias/SunatGRE.class.php
 * 
 * Método: POST
 * Parámetros:
 * - id_guia: ID de la guía a procesar
 * 
 * Respuesta JSON:
 * {
 *   "success": true/false,
 *   "estado": "ACEPTADO|RECHAZADO|ERROR",
 *   "mensaje": "...",
 *   "hash": "...",
 *   "cdr": {...}
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

// Aumentar tiempo de ejecución (el proceso puede tardar)
set_time_limit(120);

require_once __DIR__ . '/../librerias/SunatGRE.class.php';

try {
    $idGuia = isset($_POST['id_guia']) ? intval($_POST['id_guia']) : 0;
    
    if ($idGuia <= 0) {
        throw new Exception('ID de guía no válido');
    }
    
    // Instanciar clase SUNAT
    $sunat = new SunatGRE();
    
    // Procesar guía (generar XML, firmar y enviar)
    $resultado = $sunat->procesarGuia($idGuia);
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'estado' => 'ERROR'
    ], JSON_UNESCAPED_UNICODE);
}
?>
