<?php
// conexion.php
$serverName = "DCBAHIA\MSSQLSERVER2014";
$connectionOptions = array(
    "Database" => "DIRECTORIO",
    "Uid" => "sa",
    "PWD" => "Ec14312183.-"
);

// Establecer conexión
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}