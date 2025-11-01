<?php
$contraseña = "Sapo2021!";
$usuario = "usermpcl";
$nombreBaseDeDatos = "RSFACCAR";
$rutaServidor = "SERVERMPCLW";
try {
    $base_de_datos = new PDO("sqlsrv:server=$rutaServidor;database=$nombreBaseDeDatos", $usuario, $contraseña);
    $base_de_datos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,);
	$base_de_datos->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);	
} catch (Exception $e) {
    echo "Ocurrió un error con la base de datos: " . $e->getMessage();
}
?>


