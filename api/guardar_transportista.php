<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/guardar_transportista.php
 * Descripción: API para guardar/actualizar transportista
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Método: POST
 * Parámetros:
 * - id: ID del transportista (vacío para nuevo)
 * - documento: Número de documento
 * - nombre: Nombre/Razón social
 * - placa: Placa del vehículo
 * - licencia: Licencia de conducir
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
    
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $documento = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $placa = isset($_POST['placa']) ? trim($_POST['placa']) : '';
    $licencia = isset($_POST['licencia']) ? trim($_POST['licencia']) : '';
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
    
    // Validaciones
    if (empty($documento)) {
        throw new Exception('El documento es requerido');
    }
    
    if (empty($nombre)) {
        throw new Exception('El nombre es requerido');
    }
    
    if (empty($placa)) {
        throw new Exception('La placa es requerida');
    }
    
    if (empty($licencia)) {
        throw new Exception('La licencia es requerida');
    }
    
    // Verificar si ya existe otro transportista con el mismo documento
    if (empty($id)) {
        $sqlVerif = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_TRANSPORTISTA WHERE DOCUMENTO = ?";
        $existe = $db->selectOne($sqlVerif, [$documento]);
        
        if ($existe && $existe['TOTAL'] > 0) {
            throw new Exception('Ya existe un transportista con ese documento');
        }
    } else {
        $sqlVerif = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_TRANSPORTISTA 
                     WHERE DOCUMENTO = ? AND ID != ?";
        $existe = $db->selectOne($sqlVerif, [$documento, $id]);
        
        if ($existe && $existe['TOTAL'] > 0) {
            throw new Exception('Ya existe otro transportista con ese documento');
        }
    }
    
    if (empty($id)) {
        // Insertar nuevo
        $sql = "INSERT INTO MPCL.dbo.TBL_TRANSPORTISTA 
                (USUARIO, ESTADO, NOMBRE, DOCUMENTO, PLACA, LICENCIA)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $result = $db->execute($sql, [
            $_SESSION['usuario'],
            $estado,
            $nombre,
            $documento,
            $placa,
            $licencia
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Transportista registrado correctamente'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Error al registrar el transportista');
        }
    } else {
        // Actualizar existente
        $sql = "UPDATE MPCL.dbo.TBL_TRANSPORTISTA SET
                NOMBRE = ?,
                DOCUMENTO = ?,
                PLACA = ?,
                LICENCIA = ?,
                ESTADO = ?,
                USUARIO = ?
                WHERE ID = ?";
        
        $result = $db->execute($sql, [
            $nombre,
            $documento,
            $placa,
            $licencia,
            $estado,
            $_SESSION['usuario'],
            $id
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Transportista actualizado correctamente'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('Error al actualizar el transportista');
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>