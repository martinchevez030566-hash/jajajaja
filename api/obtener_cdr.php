<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/obtener_cdr.php
 * Descripción: API para descargar CDR (Constancia de Recepción) de SUNAT
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: GET
 * Parámetros:
 * - id: ID de la guía
 * 
 * =======================================================
 */

session_start();

if (!isset($_SESSION['usuario'])) {
    die('Sesión no válida');
}

require_once __DIR__ . '/../librerias/Database.class.php';
require_once __DIR__ . '/../librerias/SunatGRE.class.php';

try {
    $idGuia = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($idGuia <= 0) {
        throw new Exception('ID no válido');
    }
    
    $db = Database::getInstance();
    
    // Verificar si tiene CDR guardado
    $sql = "SELECT CDR_SUNAT, SERIE, NUMERO FROM MPCL.dbo.TBL_GUIAS_CAB WHERE ID = ?";
    $guia = $db->selectOne($sql, [$idGuia]);
    
    if (!$guia) {
        throw new Exception('Guía no encontrada');
    }
    
    if (!empty($guia['CDR_SUNAT'])) {
        // Descargar CDR guardado
        $cdrData = $guia['CDR_SUNAT'];
        $nombreArchivo = 'R-' . $guia['SERIE'] . '-' . str_pad($guia['NUMERO'], 8, '0', STR_PAD_LEFT) . '.zip';
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($cdrData));
        
        echo $cdrData;
    } else {
        // Intentar descargar de SUNAT
        $sunat = new SunatGRE();
        $cdr = $sunat->descargarCDR($idGuia);
        
        if ($cdr) {
            $nombreArchivo = 'R-' . $guia['SERIE'] . '-' . str_pad($guia['NUMERO'], 8, '0', STR_PAD_LEFT) . '.zip';
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($cdr));
            
            echo $cdr;
        } else {
            throw new Exception('No se pudo obtener el CDR');
        }
    }
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>