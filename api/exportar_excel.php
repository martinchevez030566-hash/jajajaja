<?php
/**
 * API para exportar datos a Excel
 * Genera archivos Excel con los datos de productos o ventas
 */

// Configuración de headers para descarga de archivo Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Iniciar sesión y verificar autenticación
session_start();

// Incluir archivo de conexión
require_once '../config/conexion.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    die('Acceso denegado. Debe iniciar sesión.');
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'productos';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Función para limpiar datos para Excel
function limpiarDato($dato) {
    $dato = str_replace('"', '""', $dato);
    return '"' . $dato . '"';
}

// Generar reporte según tipo
if ($tipo === 'productos') {
    exportarProductos($conexion);
} elseif ($tipo === 'ventas') {
    exportarVentas($conexion, $fecha_inicio, $fecha_fin);
} elseif ($tipo === 'clientes') {
    exportarClientes($conexion);
} elseif ($tipo === 'inventario') {
    exportarInventario($conexion);
} else {
    die('Tipo de reporte no válido.');
}

/**
 * Exportar listado de productos
 */
function exportarProductos($conexion) {
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th colspan='8' style='text-align:center; font-size:16px; font-weight:bold;'>";
    echo "REPORTE DE PRODUCTOS - " . date('d/m/Y H:i:s');
    echo "</th>";
    echo "</tr>";
    echo "<tr style='background-color:#4CAF50; color:white; font-weight:bold;'>";
    echo "<th>ID</th>";
    echo "<th>Código</th>";
    echo "<th>Nombre</th>";
    echo "<th>Categoría</th>";
    echo "<th>Precio Compra</th>";
    echo "<th>Precio Venta</th>";
    echo "<th>Stock</th>";
    echo "<th>Estado</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $sql = "SELECT p.id, p.codigo, p.nombre, c.nombre as categoria, 
            p.precio_compra, p.precio_venta, p.stock, p.estado
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            ORDER BY p.id DESC";
    
    $resultado = $conexion->query($sql);
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $fila['id'] . "</td>";
            echo "<td>" . limpiarDato($fila['codigo']) . "</td>";
            echo "<td>" . limpiarDato($fila['nombre']) . "</td>";
            echo "<td>" . limpiarDato($fila['categoria']) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['precio_compra'], 2) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['precio_venta'], 2) . "</td>";
            echo "<td>" . $fila['stock'] . "</td>";
            echo "<td>" . ($fila['estado'] == 1 ? 'Activo' : 'Inactivo') . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center;'>No hay productos registrados</td></tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
}

/**
 * Exportar reporte de ventas
 */
function exportarVentas($conexion, $fecha_inicio, $fecha_fin) {
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th colspan='9' style='text-align:center; font-size:16px; font-weight:bold;'>";
    echo "REPORTE DE VENTAS";
    if ($fecha_inicio && $fecha_fin) {
        echo " - Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
    }
    echo " - Generado: " . date('d/m/Y H:i:s');
    echo "</th>";
    echo "</tr>";
    echo "<tr style='background-color:#2196F3; color:white; font-weight:bold;'>";
    echo "<th>N° Venta</th>";
    echo "<th>Fecha</th>";
    echo "<th>Cliente</th>";
    echo "<th>Vendedor</th>";
    echo "<th>Subtotal</th>";
    echo "<th>Descuento</th>";
    echo "<th>IGV</th>";
    echo "<th>Total</th>";
    echo "<th>Estado</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $sql = "SELECT v.id, v.numero_venta, v.fecha, 
            CONCAT(c.nombres, ' ', c.apellidos) as cliente,
            CONCAT(u.nombres, ' ', u.apellidos) as vendedor,
            v.subtotal, v.descuento, v.igv, v.total, v.estado
            FROM ventas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            WHERE 1=1";
    
    if ($fecha_inicio && $fecha_fin) {
        $sql .= " AND DATE(v.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    
    $sql .= " ORDER BY v.id DESC";
    
    $resultado = $conexion->query($sql);
    
    $total_subtotal = 0;
    $total_descuento = 0;
    $total_igv = 0;
    $total_general = 0;
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . limpiarDato($fila['numero_venta']) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($fila['fecha'])) . "</td>";
            echo "<td>" . limpiarDato($fila['cliente']) . "</td>";
            echo "<td>" . limpiarDato($fila['vendedor']) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['subtotal'], 2) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['descuento'], 2) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['igv'], 2) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['total'], 2) . "</td>";
            echo "<td>" . ($fila['estado'] == 1 ? 'Completada' : 'Anulada') . "</td>";
            echo "</tr>";
            
            if ($fila['estado'] == 1) {
                $total_subtotal += $fila['subtotal'];
                $total_descuento += $fila['descuento'];
                $total_igv += $fila['igv'];
                $total_general += $fila['total'];
            }
        }
        
        // Fila de totales
        echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
        echo "<td colspan='4' style='text-align:right;'>TOTALES:</td>";
        echo "<td style='mso-number-format:\"0.00\"'>" . number_format($total_subtotal, 2) . "</td>";
        echo "<td style='mso-number-format:\"0.00\"'>" . number_format($total_descuento, 2) . "</td>";
        echo "<td style='mso-number-format:\"0.00\"'>" . number_format($total_igv, 2) . "</td>";
        echo "<td style='mso-number-format:\"0.00\"'>" . number_format($total_general, 2) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    } else {
        echo "<tr><td colspan='9' style='text-align:center;'>No hay ventas en el período seleccionado</td></tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
}

/**
 * Exportar listado de clientes
 */
function exportarClientes($conexion) {
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th colspan='7' style='text-align:center; font-size:16px; font-weight:bold;'>";
    echo "REPORTE DE CLIENTES - " . date('d/m/Y H:i:s');
    echo "</th>";
    echo "</tr>";
    echo "<tr style='background-color:#FF9800; color:white; font-weight:bold;'>";
    echo "<th>ID</th>";
    echo "<th>Tipo Doc.</th>";
    echo "<th>N° Documento</th>";
    echo "<th>Nombres</th>";
    echo "<th>Apellidos</th>";
    echo "<th>Teléfono</th>";
    echo "<th>Email</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $sql = "SELECT id, tipo_documento, numero_documento, nombres, apellidos, 
            telefono, email
            FROM clientes
            WHERE estado = 1
            ORDER BY nombres, apellidos";
    
    $resultado = $conexion->query($sql);
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $fila['id'] . "</td>";
            echo "<td>" . limpiarDato($fila['tipo_documento']) . "</td>";
            echo "<td>" . limpiarDato($fila['numero_documento']) . "</td>";
            echo "<td>" . limpiarDato($fila['nombres']) . "</td>";
            echo "<td>" . limpiarDato($fila['apellidos']) . "</td>";
            echo "<td>" . limpiarDato($fila['telefono']) . "</td>";
            echo "<td>" . limpiarDato($fila['email']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No hay clientes registrados</td></tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
}

/**
 * Exportar reporte de inventario
 */
function exportarInventario($conexion) {
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th colspan='9' style='text-align:center; font-size:16px; font-weight:bold;'>";
    echo "REPORTE DE INVENTARIO - " . date('d/m/Y H:i:s');
    echo "</th>";
    echo "</tr>";
    echo "<tr style='background-color:#9C27B0; color:white; font-weight:bold;'>";
    echo "<th>Código</th>";
    echo "<th>Producto</th>";
    echo "<th>Categoría</th>";
    echo "<th>Stock Actual</th>";
    echo "<th>Stock Mínimo</th>";
    echo "<th>Precio Compra</th>";
    echo "<th>Precio Venta</th>";
    echo "<th>Valor Inventario</th>";
    echo "<th>Estado Stock</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    $sql = "SELECT p.codigo, p.nombre, c.nombre as categoria, 
            p.stock, p.stock_minimo, p.precio_compra, p.precio_venta,
            (p.stock * p.precio_compra) as valor_inventario
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.estado = 1
            ORDER BY p.nombre";
    
    $resultado = $conexion->query($sql);
    
    $valor_total = 0;
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $estado_stock = '';
            $color = '';
            
            if ($fila['stock'] <= 0) {
                $estado_stock = 'SIN STOCK';
                $color = 'background-color:#f44336; color:white;';
            } elseif ($fila['stock'] <= $fila['stock_minimo']) {
                $estado_stock = 'STOCK BAJO';
                $color = 'background-color:#FF9800; color:white;';
            } else {
                $estado_stock = 'NORMAL';
                $color = '';
            }
            
            echo "<tr>";
            echo "<td>" . limpiarDato($fila['codigo']) . "</td>";
            echo "<td>" . limpiarDato($fila['nombre']) . "</td>";
            echo "<td>" . limpiarDato($fila['categoria']) . "</td>";
            echo "<td>" . $fila['stock'] . "</td>";
            echo "<td>" . $fila['stock_minimo'] . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['precio_compra'], 2) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['precio_venta'], 2) . "</td>";
            echo "<td style='mso-number-format:\"0.00\"'>" . number_format($fila['valor_inventario'], 2) . "</td>";
            echo "<td style='$color'>" . $estado_stock . "</td>";
            echo "</tr>";
            
            $valor_total += $fila['valor_inventario'];
        }
        
        // Fila de total
        echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
        echo "<td colspan='7' style='text-align:right;'>VALOR TOTAL INVENTARIO:</td>";
        echo "<td style='mso-number-format:\"0.00\"'>" . number_format($valor_total, 2) . "</td>";
        echo "<td></td>";
        echo "</tr>";
    } else {
        echo "<tr><td colspan='9' style='text-align:center;'>No hay productos en inventario</td></tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
}

// Cerrar conexión
$conexion->close();
?>