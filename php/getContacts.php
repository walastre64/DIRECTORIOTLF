<?php 
require_once 'conexion.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT id, gerencia, nombre, extension, orden FROM directorio";
if (!empty($search)) {
    $sql .= " WHERE gerencia LIKE ? OR nombre LIKE ? OR extension LIKE ? ";
    $params = array("%$search%", "%$search%", "%$search%");
} else {
    $params = array();
}

// Agregar ORDER BY para ordenar por el campo 'orden' de forma ascendente
$sql .= " ORDER BY orden ASC";

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$contacts = array();
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $contacts[] = $row;
}

header('Content-Type: application/json');
echo json_encode($contacts);
?>