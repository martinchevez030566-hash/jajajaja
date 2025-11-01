<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

require_once __DIR__ . '/../librerias/Database.class.php';

try {
    $serie  = $_POST['serie']  ?? '';
    $numero = $_POST['numero'] ?? '';

    if ($serie === '' || $numero === '') {
        throw new Exception('Serie y número son requeridos');
    }

    $numero = str_pad($numero, 7, '0', STR_PAD_LEFT);

    $db = Database::getInstance();

    // Consulta principal
    $sql = "SELECT TOP 1 
            F5.F5_CTD,
            F5.F5_CNUMSER,
            F5.F5_CNUMDOC,
            F5.F5_DFECDOC,
            F5.F5_CCODCLI,
            F5.F5_CNOMBRE,
            F5.F5_CDIRECC,
            F5.F5_CVENDE,
            C.CL_CNOMCLI,
            C.CL_CDIRCLI,
            C.CL_CNUMRUC,
            C.CL_CDOCIDE,
            C.CL_CUBIGEO,
            V.VE_CNOMBRE,
            V.VE_CCODIGO
            FROM rsfaccar.dbo.FT0005FACC F5
            LEFT JOIN rsfaccar.dbo.FT0005CLIE C ON F5.F5_CCODCLI = C.CL_CCODCLI
            LEFT JOIN rsfaccar.dbo.FT0005VEND V ON F5.F5_CVENDE = V.VE_CCODIGO
            WHERE F5.F5_CNUMSER = ? 
            AND F5.F5_CNUMDOC = ?
            AND F5.F5_CESTADO = 'V'";

    $factura = $db->selectOne($sql, [$serie, $numero]);

    if (!$factura) {
        throw new Exception('Documento no encontrado o no válido');
    }

    // Detalle
    $sqlDet = "SELECT 
               F6_CITEM AS ITEM,
               F6_CCODIGO AS CODIGO,
               F6_CDESCRI AS DESCRIPCION,
               F6_NCANTID AS CANTIDAD,
               F6_CUNIDAD AS UNIDAD
               FROM rsfaccar.dbo.FT0005FACD
               WHERE F6_CNUMSER = ? 
               AND F6_CNUMDOC = ?
               ORDER BY F6_CITEM";

    $detalle = $db->select($sqlDet, [$serie, $numero]) ?: [];

    // Formatear detalle
    $detalleFormateado = [];
    foreach ($detalle as $item) {
        $detalleFormateado[] = [
            'item' => (int)trim($item['ITEM']),
            'codigo' => trim($item['CODIGO']),
            'descripcion' => trim($item['DESCRIPCION']),
            'cantidad' => (float)$item['CANTIDAD'],
            'unidad' => trim($item['UNIDAD']),
        ];
    }

    // Ubigeo
    $ubigeo = trim($factura['CL_CUBIGEO'] ?? '');
    $ubigeoDesc = '';
    if ($ubigeo && $ubigeo !== '000000') {
        $rowUbigeo = $db->selectOne(
            "SELECT departamento_inei + ' - ' + provincia_inei + ' - ' + distrito AS DESCRIPCION
             FROM RSFACCAR.dbo.TB_UBIGEOS 
             WHERE ubigeo_inei = ?",
            [$ubigeo]
        );
        $ubigeoDesc = $rowUbigeo['DESCRIPCION'] ?? '';
    }

    // Tipo doc cliente
    $numDocCliente = trim($factura['CL_CNUMRUC'] ?? '');
    $tipoDocCliente = strlen($numDocCliente) === 11 ? '6' : '1';

    // Respuesta
    echo json_encode([
        'success' => true,
        'data' => [
            'tipo_doc' => trim($factura['F5_CTD']),
            'tipo_doc_nombre' => trim($factura['F5_CTD']) === '03' ? 'BOLETA' : 'FACTURA',
            'serie' => trim($factura['F5_CNUMSER']),
            'numero' => trim($factura['F5_CNUMDOC']),
            'fecha' => ($factura['F5_DFECDOC'] instanceof DateTime)
                ? $factura['F5_DFECDOC']->format('Y-m-d')
                : date('Y-m-d'),
            'tipo_doc_cliente' => $tipoDocCliente,
            'num_doc_cliente' => $numDocCliente,
            'nombre_cliente' => trim($factura['CL_CNOMCLI'] ?? $factura['F5_CNOMBRE'] ?? ''),
            'direccion_cliente' => trim($factura['CL_CDIRCLI'] ?? $factura['F5_CDIRECC'] ?? ''),
            'ubigeo_cliente' => $ubigeo,
            'ubigeo_desc' => $ubigeoDesc,
            'codigo_vendedor' => trim($factura['VE_CCODIGO'] ?? ''),
            'nombre_vendedor' => trim($factura['VE_CNOMBRE'] ?? ''),
            'detalle' => $detalleFormateado,
            'total_items' => count($detalleFormateado),
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>