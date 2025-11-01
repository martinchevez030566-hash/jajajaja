<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/ver_xml.php
 * Descripción: API para obtener el XML firmado de una guía
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * Parámetros:
 * - id_guia: ID de la guía
 * 
 * =======================================================
 */

session_start();

if (!isset($_SESSION['usuario'])) {
    echo 'Sesión no válida';
    exit;
}

require_once __DIR__ . '/../librerias/Database.class.php';

try {
    $idGuia = isset($_POST['id_guia']) ? intval($_POST['id_guia']) : 0;
    
    if ($idGuia <= 0) {
        throw new Exception('ID de guía no válido');
    }
    
    $db = Database::getInstance();
    
    $sql = "SELECT XML_FIRMADO, XML_GENERADO FROM MPCL.dbo.TBL_GUIAS_CAB WHERE ID = ?";
    $guia = $db->selectOne($sql, [$idGuia]);
    
    if (!$guia) {
        throw new Exception('Guía no encontrada');
    }
    
    if (!empty($guia['XML_FIRMADO'])) {
        // Formatear XML para mejor visualización
        $xml = new DOMDocument();
        $xml->loadXML($guia['XML_FIRMADO']);
        $xml->formatOutput = true;
        echo $xml->saveXML();
    } elseif (!empty($guia['XML_GENERADO'])) {
        $xml = new DOMDocument();
        $xml->loadXML($guia['XML_GENERADO']);
        $xml->formatOutput = true;
        echo $xml->saveXML();
    } else {
        throw new Exception('No hay XML generado para esta guía');
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>