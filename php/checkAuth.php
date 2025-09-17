<?php
header('Content-Type: application/json');
session_start();

// Verificar si la sesión está activa y el usuario autenticado
if (!isset($_SESSION['admin_logged']) {
    echo json_encode(['authenticated' => false]);
    exit;
}

// Conexión a la base de datos para verificar datos adicionales (opcional)
require_once 'conexion.php';

try {
    $sql = "SELECT id, username, nombre FROM usuarios WHERE id = ?";
    $params = array($_SESSION['user_id']);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        throw new Exception('Error al verificar usuario en la base de datos');
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user) {
        session_unset();
        session_destroy();
        echo json_encode(['authenticated' => false]);
        exit;
    }

    // Usuario autenticado y válido en la base de datos
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nombre' => $user['nombre']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'authenticated' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) sqlsrv_free_stmt($stmt);
    if (isset($conn)) sqlsrv_close($conn);
}
?>