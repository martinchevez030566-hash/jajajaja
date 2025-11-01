<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/verificar_certificado.php
 * Descripción: API para verificar certificado digital
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

require_once __DIR__ . '/../librerias/Database.class.php';

try {
    $db = Database::getInstance();
    
    $certPath = $db->getConfig('CERT_PATH');
    $certPassword = $db->getConfig('CERT_PASSWORD');
    
    if (empty($certPath)) {
        throw new Exception('No se ha configurado la ruta del certificado');
    }
    
    if (!file_exists($certPath)) {
        throw new Exception('El archivo de certificado no existe en la ruta especificada');
    }
    
    // Intentar leer el certificado
    $pkcs12 = file_get_contents($certPath);
    $certs = [];
    
    if (!openssl_pkcs12_read($pkcs12, $certs, $certPassword)) {
        throw new Exception('No se pudo leer el certificado. Verifique la contraseña.');
    }
    
    // Obtener información del certificado
    $certInfo = openssl_x509_parse($certs['cert']);
    
    $validoDesde = date('d/m/Y', $certInfo['validFrom_time_t']);
    $validoHasta = date('d/m/Y', $certInfo['validTo_time_t']);
    
    // Verificar si está vigente
    $ahora = time();
    if ($ahora < $certInfo['validFrom_time_t']) {
        throw new Exception('El certificado aún no es válido');
    }
    
    if ($ahora > $certInfo['validTo_time_t']) {
        throw new Exception('El certificado ha expirado');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Certificado válido y vigente',
        'valido_desde' => $validoDesde,
        'valido_hasta' => $validoHasta,
        'emisor' => $certInfo['issuer']['CN'] ?? 'N/A',
        'sujeto' => $certInfo['subject']['CN'] ?? 'N/A'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>