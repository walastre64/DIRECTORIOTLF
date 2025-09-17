<?php
// updateContact.php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


session_start();
if (!isset($_SESSION['admin_logged']) || !$_SESSION['admin_logged']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acceso no autorizado']));
}

// Configuración de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'conexion.php';

if (!$conn) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos',
        'sql_errors' => sqlsrv_errors()
    ]));
}

try {
    // Obtener los datos del cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Verificar JSON válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error en el formato JSON: ' . json_last_error_msg());
    }

    // Validar campos requeridos
    $required = ['id', 'gerencia', 'nombre', 'extension'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("El campo $field es requerido");
        }
        if (empty(trim($data[$field]))) {
            throw new Exception("El campo $field no puede estar vacío");
        }
    }

    // Sanitizar datos
    $id = (int)$data['id'];
    $gerencia = trim($data['gerencia']);
    $nombre = trim($data['nombre']);
    $extension = trim($data['extension']);

    // Validar ID
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    // 1. Primero verificar que el registro existe
    $checkSql = "SELECT id FROM directorio WHERE id = ?";
    $checkParams = array($id);
    $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);

    if ($checkStmt === false) {
        throw new Exception('Error al verificar existencia del contacto');
    }

    if (!sqlsrv_has_rows($checkStmt)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el contacto con el ID proporcionado',
            'id' => $id
        ]);
        exit;
    }

    // 2. Actualizar el registro
    $updateSql = "UPDATE directorio SET 
                 gerencia = ?, 
                 nombre = ?, 
                 extension = ?, 
                 fecha_actualizacion = GETDATE()
                 WHERE id = ?";

    $updateParams = array($gerencia, $nombre, $extension, $id);
    $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

    if ($updateStmt === false) {
        throw new Exception('Error al ejecutar la actualización');
    }

    // 3. Verificar la actualización
    $verifySql = "SELECT gerencia, nombre, extension FROM directorio WHERE id = ?";
    $verifyStmt = sqlsrv_query($conn, $verifySql, array($id));
    
    if ($verifyStmt === false) {
        throw new Exception('Error al verificar la actualización');
    }

    $updatedData = sqlsrv_fetch_array($verifyStmt, SQLSRV_FETCH_ASSOC);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Contacto actualizado correctamente',
        'contacto' => $updatedData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => isset($errors) ? $errors : null
    ]);
} finally {
    // Liberar recursos de manera segura
    if (isset($checkStmt) && is_resource($checkStmt)) sqlsrv_free_stmt($checkStmt);
    if (isset($updateStmt) && is_resource($updateStmt)) sqlsrv_free_stmt($updateStmt);
    if (isset($verifyStmt) && is_resource($verifyStmt)) sqlsrv_free_stmt($verifyStmt);
    if ($conn) sqlsrv_close($conn);
}
?>