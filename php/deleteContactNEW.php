<?php
// deleteContact.php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
if (!isset($_SESSION['admin_logged']) || !$_SESSION['admin_logged']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acceso no autorizado']));
}

// Manejar errores de PHP
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Incluir archivo de conexión
require_once 'conexion.php';

// Verificar conexión a la base de datos
if (!$conn) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos',
        'sql_errors' => sqlsrv_errors()
    ]));
}

try {
    // Obtener el ID del contacto a eliminar
    $id = null;
    
    // Manejar diferentes métodos de envío
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Para DELETE, obtener datos del cuerpo
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $id = $data['id'] ?? null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Para POST, obtener datos del formulario
        $id = $_POST['id'] ?? null;
    } else {
        throw new Exception('Método no permitido');
    }

    // Validar que el ID esté presente y sea válido
    if (!$id || !is_numeric($id)) {
        throw new Exception('ID de contacto inválido o no proporcionado');
    }

    $id = (int)$id;

    // Preparar la consulta SQL para eliminar
    $sql = "DELETE FROM directorio WHERE id = ?";
    $params = array($id);
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);

    // Ejecutar la consulta
    $stmt = sqlsrv_query($conn, $sql, $params, $options);

    if ($stmt === false) {
        throw new Exception('Error al ejecutar la consulta de eliminación');
    }

    // Verificar filas afectadas
    $rows_affected = sqlsrv_rows_affected($stmt);
    
    if ($rows_affected === false) {
        throw new Exception('Error al verificar filas afectadas');
    } elseif ($rows_affected == 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el contacto con el ID proporcionado',
            'id' => $id
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Contacto eliminado correctamente',
            'id' => $id
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => isset($errors) ? $errors : null
    ]);
} finally {
    // Liberar recursos
    if (isset($stmt)) sqlsrv_free_stmt($stmt);
    if ($conn) sqlsrv_close($conn);
}
?>