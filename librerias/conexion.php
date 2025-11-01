<?php
$contraseña = "Sapo2021!";
$usuario = "usermpcl";
$nombreBaseDeDatos = "RSFACCAR";
$rutaServidor = "SERVERMPCLW";
try {
    $base_de_datos = new PDO("sqlsrv:server=$rutaServidor;database=$nombreBaseDeDatos", $usuario, $contraseña);
    $base_de_datos->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Ocurrió un error con la base de datos: " . $e->getMessage();
}?>