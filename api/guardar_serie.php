<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/guardar_serie.php
 * Descripción: API para configurar serie GRE de un almacén
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * Parámetros:
 * - codigo_almacen: Código del almacén
 * - serie: Serie GRE (4 caracteres)
 * - estado: Estado (1=activo, 0=inactivo)
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
    
    $codigoAlmacen = isset($_POST['codigo_almacen']) ? trim($_POST['codigo_almacen']) : '';
    $serie = isset($_POST['serie']) ? strtoupper(trim($_POST['serie'])) : '';
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
    
    // Validaciones
    if (empty($codigoAlmacen)) {
        throw new Exception('El código de almacén es requerido');
    }
    
    if (empty($serie)) {
        throw new Exception('La serie es requerida');
    }
    
    if (!preg_match('/^[A-Z0-9]{4}$/', $serie)) {
        throw new Exception('La serie debe tener exactamente 4 caracteres alfanuméricos');
    }
    
    // Verificar si el almacén existe
    $sqlAlmacen = "SELECT COUNT(*) AS TOTAL FROM RSFACCAR.dbo.AL0005ALMA WHERE A1_CALMA = ?";
    $almacenExiste = $db->selectOne($sqlAlmacen, [$codigoAlmacen]);
    
    if (!$almacenExiste || $almacenExiste['TOTAL'] == 0) {
        throw new Exception('El almacén no existe');
    }
    
    // Verificar si ya existe la serie en otro almacén
    $sqlVerifSerie = "SELECT COUNT(*) AS TOTAL 
                      FROM MPCL.dbo.TBL_ALMACENES_GRE 
                      WHERE SERIE_GRE = ? AND CODIGO_ALMACEN != ?";
    
    $serieExiste = $db->selectOne($sqlVerifSerie, [$serie, $codigoAlmacen]);
    
    if ($serieExiste && $serieExiste['TOTAL'] > 0) {
        throw new Exception('La serie ya está asignada a otro almacén');
    }
    
    // Verificar si ya tiene configuración
    $sqlVerif = "SELECT COUNT(*) AS TOTAL 
                 FROM MPCL.dbo.TBL_ALMACENES_GRE 
                 WHERE CODIGO_ALMACEN = ?";
    
    $tieneConfig = $db->selectOne($sqlVerif, [$codigoAlmacen]);
    
    if ($tieneConfig && $tieneConfig['TOTAL'] > 0) {
        // Actualizar
        $sql = "UPDATE MPCL.dbo.TBL_ALMACENES_GRE SET
                SERIE_GRE = ?,
                ESTADO = ?,
                USUARIO_CREACION = ?,
                FECHA_CREACION = GETDATE()
                WHERE CODIGO_ALMACEN = ?";
        
        $result = $db->execute($sql, [$serie, $estado, $_SESSION['usuario'], $codigoAlmacen]);
        $mensaje = 'Serie actualizada correctamente';
    } else {
        // Insertar
        $sql = "INSERT INTO MPCL.dbo.TBL_ALMACENES_GRE 
                (CODIGO_ALMACEN, SERIE_GRE, ESTADO, USUARIO_CREACION, FECHA_CREACION)
                VALUES (?, ?, ?, ?, GETDATE())";
        
        $result = $db->execute($sql, [$codigoAlmacen, $serie, $estado, $_SESSION['usuario']]);
        $mensaje = 'Serie configurada correctamente';
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Error al guardar la configuración');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>