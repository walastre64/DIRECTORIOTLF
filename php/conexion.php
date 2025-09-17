<?php
// conexion.php
$serverName = "localhost";
$connectionOptions = array(
    "Database" => "DIRECTORIO",
    "Uid" => "sa",
    "PWD" => ""
);

// Establecer conexión
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
