<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/buscar_ubigeo.php
 * Descripción: API para autocompletado de ubigeos (jQuery Autocomplete)
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - ../librerias/Database.class.php
 * 
 * Método: GET
 * Parámetros:
 * - term: Texto de búsqueda (mínimo 3 caracteres)
 * 
 * Respuesta JSON Array:
 * [
 *   {
 *     "value": "150101",
 *     "label": "LIMA - LIMA - LIMA",
 *     "ubigeo": "150101",
 *     "departamento": "LIMA",
 *     "provincia": "LIMA",
 *     "distrito": "LIMA"
 *   }
 * ]
 * 
 * =======================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../librerias/Database.class.php';

try {
    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    if (strlen($term) < 3) {
        echo json_encode([]);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Buscar ubigeos
    $sql = "SELECT TOP 20
            ubigeo_inei AS UBIGEO,
            departamento_inei AS DEPARTAMENTO,
            provincia_inei AS PROVINCIA,
            distrito AS DISTRITO,
            departamento_inei + ' - ' + provincia_inei + ' - ' + distrito AS DESCRIPCION
            FROM RSFACCAR.dbo.TB_UBIGEOS
            WHERE departamento_inei LIKE ? 
            OR provincia_inei LIKE ?
            OR distrito LIKE ?
            ORDER BY departamento_inei, provincia_inei, distrito";
    
    $busqueda = "%$term%";
    $resultados = $db->select($sql, [$busqueda, $busqueda, $busqueda]);
    
    $response = [];
    
    foreach ($resultados as $row) {
        $response[] = [
            'value' => trim($row['UBIGEO']),
            'label' => trim($row['DESCRIPCION']),
            'ubigeo' => trim($row['UBIGEO']),
            'departamento' => trim($row['DEPARTAMENTO']),
            'provincia' => trim($row['PROVINCIA']),
            'distrito' => trim($row['DISTRITO'])
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>