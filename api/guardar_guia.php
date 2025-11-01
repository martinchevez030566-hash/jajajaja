<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/api/guardar_guia.php
 * Descripción: API para guardar nueva guía de remisión en la BD
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - ../librerias/Database.class.php
 * 
 * Método: POST
 * Parámetros: (todos requeridos)
 * - codigo_almacen
 * - tipo_doc_rel, serie_doc_rel, numero_doc_rel
 * - tipo_doc_destinatario, num_doc_destinatario, razon_social_destinatario
 * - ubigeo_llegada, direccion_llegada
 * - motivo_traslado, descripcion_motivo (opcional)
 * - peso_total, num_bultos
 * - id_transportista, fecha_inicio_traslado
 * - codigo_vendedor (opcional)
 * - detalle (JSON array)
 * - enviar_sunat (opcional: true/false)
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
    
    // Validar parámetros requeridos
    $required = [
        'codigo_almacen', 'tipo_doc_destinatario', 'num_doc_destinatario',
        'razon_social_destinatario', 'ubigeo_llegada', 'direccion_llegada',
        'motivo_traslado', 'peso_total', 'num_bultos', 'id_transportista',
        'fecha_inicio_traslado', 'detalle'
    ];
    
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Obtener datos
    $codigoAlmacen = trim($_POST['codigo_almacen']);
    $tipoDocRel = isset($_POST['tipo_doc_rel']) ? trim($_POST['tipo_doc_rel']) : null;
    $serieDocRel = isset($_POST['serie_doc_rel']) ? trim($_POST['serie_doc_rel']) : null;
    $numeroDocRel = isset($_POST['numero_doc_rel']) ? trim($_POST['numero_doc_rel']) : null;
    $tipoDocDest = trim($_POST['tipo_doc_destinatario']);
    $numDocDest = trim($_POST['num_doc_destinatario']);
    $razonSocialDest = trim($_POST['razon_social_destinatario']);
    $ubigeoLlegada = trim($_POST['ubigeo_llegada']);
    $direccionLlegada = trim($_POST['direccion_llegada']);
    $motivoTraslado = trim($_POST['motivo_traslado']);
    $descripcionMotivo = isset($_POST['descripcion_motivo']) ? trim($_POST['descripcion_motivo']) : null;
    $pesoTotal = floatval($_POST['peso_total']);
    $numBultos = intval($_POST['num_bultos']);
    $idTransportista = intval($_POST['id_transportista']);
    $fechaInicioTraslado = trim($_POST['fecha_inicio_traslado']);
    $codigoVendedor = isset($_POST['codigo_vendedor']) ? trim($_POST['codigo_vendedor']) : null;
    $detalle = json_decode($_POST['detalle'], true);
    $enviarSunat = isset($_POST['enviar_sunat']) && $_POST['enviar_sunat'] === 'true';
    
    // Validaciones
    if (empty($detalle) || !is_array($detalle)) {
        throw new Exception('El detalle de productos es requerido');
    }
    
    if ($pesoTotal <= 0) {
        throw new Exception('El peso total debe ser mayor a cero');
    }
    
    if ($numBultos <= 0) {
        throw new Exception('El número de bultos debe ser mayor a cero');
    }
    
    // Validar ubigeo
    $sqlUbigeo = "SELECT COUNT(*) AS TOTAL FROM RSFACCAR.dbo.TB_UBIGEOS WHERE ubigeo_inei = ?";
    $resultUbigeo = $db->selectOne($sqlUbigeo, [$ubigeoLlegada]);
    if (!$resultUbigeo || $resultUbigeo['TOTAL'] == 0) {
        throw new Exception('Ubigeo de llegada no válido');
    }
    
    // Validar transportista
    $sqlTransp = "SELECT COUNT(*) AS TOTAL FROM MPCL.dbo.TBL_TRANSPORTISTA WHERE ID = ? AND ESTADO = 1";
    $resultTransp = $db->selectOne($sqlTransp, [$idTransportista]);
    if (!$resultTransp || $resultTransp['TOTAL'] == 0) {
        throw new Exception('Transportista no válido');
    }
    
    // Obtener serie y siguiente correlativo del almacén
    $sqlAlmacen = "SELECT 
                   G.SERIE_GRE AS SERIE,
                   A.A1_NNUMGUI AS CORRELATIVO
                   FROM RSFACCAR.dbo.AL0005ALMA A
                   INNER JOIN MPCL.dbo.TBL_ALMACENES_GRE G ON A.A1_CALMA = G.CODIGO_ALMACEN
                   WHERE A.A1_CALMA = ? AND G.ESTADO = 1";
    
    $almacen = $db->selectOne($sqlAlmacen, [$codigoAlmacen]);
    
    if (!$almacen) {
        throw new Exception('Almacén no configurado para GRE');
    }
    
    $serie = $almacen['SERIE'];
    $numero = $almacen['CORRELATIVO'];
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Insertar cabecera
        $sqlCab = "INSERT INTO MPCL.dbo.TBL_GUIAS_CAB (
                    CODIGO_ALMACEN, SERIE, NUMERO, 
                    FECHA_EMISION, HORA_EMISION,
                    TIPO_DOC_REL, SERIE_DOC_REL, NUMERO_DOC_REL,
                    TIPO_DOC_DESTINATARIO, NUM_DOC_DESTINATARIO, RAZON_SOCIAL_DESTINATARIO,
                    UBIGEO_LLEGADA, DIRECCION_LLEGADA,
                    MOTIVO_TRASLADO, DESCRIPCION_MOTIVO,
                    PESO_TOTAL, UNIDAD_PESO, NUM_BULTOS,
                    ID_TRANSPORTISTA, FECHA_INICIO_TRASLADO,
                    CODIGO_VENDEDOR,
                    ESTADO_SUNAT, USUARIO_CREACION, FECHA_CREACION
                   ) VALUES (
                    ?, ?, ?,
                    GETDATE(), CONVERT(TIME, GETDATE()),
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, 'KGM', ?,
                    ?, ?,
                    ?,
                    'PENDIENTE', ?, GETDATE()
                   )";
        
        $paramsCab = [
            $codigoAlmacen, $serie, $numero,
            $tipoDocRel, $serieDocRel, $numeroDocRel,
            $tipoDocDest, $numDocDest, $razonSocialDest,
            $ubigeoLlegada, $direccionLlegada,
            $motivoTraslado, $descripcionMotivo,
            $pesoTotal, $numBultos,
            $idTransportista, $fechaInicioTraslado,
            $codigoVendedor,
            $_SESSION['usuario']
        ];
        
        $idGuia = $db->execute($sqlCab, $paramsCab);
        
        if (!$idGuia) {
            throw new Exception('Error al insertar cabecera de guía');
        }
        
        // Insertar detalle
        $sqlDet = "INSERT INTO MPCL.dbo.TBL_GUIAS_DET (
                   ID_GUIA_CAB, ITEM, CODIGO_PRODUCTO, DESCRIPCION, CANTIDAD, UNIDAD_MEDIDA
                   ) VALUES (?, ?, ?, ?, ?, ?)";
        
        foreach ($detalle as $item) {
            $paramsDet = [
                $idGuia,
                $item['item'],
                $item['codigo'],
                $item['descripcion'],
                $item['cantidad'],
                $item['unidad']
            ];
            
            if (!$db->execute($sqlDet, $paramsDet)) {
                throw new Exception('Error al insertar detalle de guía');
            }
        }
        
        // Actualizar correlativo en tabla de almacenes
        $sqlUpdateCorr = "UPDATE RSFACCAR.dbo.AL0005ALMA 
                          SET A1_NNUMGUI = A1_NNUMGUI + 1 
                          WHERE A1_CALMA = ?";
        $db->execute($sqlUpdateCorr, [$codigoAlmacen]);
        
        // Registrar log
        $db->registrarLog(
            $idGuia,
            'CREACION',
            null,
            'PENDIENTE',
            'Guía creada desde formulario web',
            null,
            null
        );
        
        // Confirmar transacción
        $db->commit();
        
        $nroGuiaCompleto = $serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
        
        $response = [
            'success' => true,
            'id_guia' => $idGuia,
            'serie' => $serie,
            'numero' => $numero,
            'nro_guia' => $nroGuiaCompleto,
            'message' => "Guía $nroGuiaCompleto guardada correctamente"
        ];
        
        // Si se solicita enviar a SUNAT inmediatamente
        if ($enviarSunat) {
            $response['enviar_sunat'] = true;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>