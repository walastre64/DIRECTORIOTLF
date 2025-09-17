<?php
require_once 'conexion.php';

session_start();
if (!isset($_SESSION['admin_logged']) || !$_SESSION['admin_logged']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acceso no autorizado']));
}

$data = json_decode(file_get_contents('php://input'), true);

$sql = "INSERT INTO directorio (gerencia, nombre, extension) VALUES (?, ?, ?)";
$params = array($data['gerencia'], $data['nombre'], $data['extension']);

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    $response = array('success' => false, 'message' => 'Error al agregar contacto');
} else {
    $response = array('success' => true);
}

header('Content-Type: application/json');
echo json_encode($response);
?>