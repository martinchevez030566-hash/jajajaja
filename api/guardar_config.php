<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/guardar_config.php
 * Descripción: API para guardar configuración del sistema
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * Parámetros:
 * - tipo: empresa|certificado|sunat
 * - [parámetros según tipo]
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
    $db = Database::getInstance();
    
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    
    if (empty($tipo)) {
        throw new Exception('Tipo de configuración no especificado');
    }
    
    $parametros = [];
    
    switch ($tipo) {
        case 'empresa':
            $parametros = [
                'RUC_EMPRESA' => $_POST['RUC_EMPRESA'] ?? '',
                'RAZON_SOCIAL' => $_POST['RAZON_SOCIAL'] ?? '',
                'NOMBRE_COMERCIAL' => $_POST['NOMBRE_COMERCIAL'] ?? ''
            ];
            break;
            
        case 'certificado':
            $parametros = [
                'CERT_PATH' => $_POST['CERT_PATH'] ?? '',
                'CERT_PASSWORD' => $_POST['CERT_PASSWORD'] ?? ''
            ];
            break;
            
        case 'sunat':
            $parametros = [
                'SUNAT_ENDPOINT' => $_POST['SUNAT_ENDPOINT'] ?? '',
                'SUNAT_TOKEN_URL' => $_POST['SUNAT_TOKEN_URL'] ?? '',
                'SUNAT_USERNAME' => $_POST['SUNAT_USERNAME'] ?? '',
                'SUNAT_PASSWORD' => $_POST['SUNAT_PASSWORD'] ?? '',
                'SUNAT_CLIENT_ID' => $_POST['SUNAT_CLIENT_ID'] ?? '',
                'SUNAT_CLIENT_SECRET' => $_POST['SUNAT_CLIENT_SECRET'] ?? '',
                'AMBIENTE' => $_POST['AMBIENTE'] ?? 'BETA'
            ];
            break;
            
        default:
            throw new Exception('Tipo de configuración no válido');
    }
    
    // Actualizar cada parámetro
    $actualizados = 0;
    foreach ($parametros as $parametro => $valor) {
        if ($db->setConfig($parametro, $valor)) {
            $actualizados++;
        }
    }
    
    if ($actualizados > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuración guardada correctamente'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('No se pudo actualizar la configuración');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>