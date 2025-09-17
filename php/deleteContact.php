<?php
header('Content-Type: application/json');
require_once 'conexion.php';

session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logged']) || !$_SESSION['admin_logged']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$input = json_decode(file_get_contents('php://input'), true);

// Validar ID
if (!isset($input['id']) || !is_numeric($input['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de contacto inválido',
        'received_data' => $input // Para depuración
    ]);
    exit;
}

$id = (int)$input['id'];

try {
    // 1. Verificar que el contacto existe
    $checkSql = "SELECT id FROM directorio WHERE id = ?";
    $checkStmt = sqlsrv_query($conn, $checkSql, array($id));
    
    if ($checkStmt === false) {
        throw new Exception('Error al verificar el contacto');
    }
    
    if (!sqlsrv_has_rows($checkStmt)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'El contacto no existe'
        ]);
        exit;
    }
    
    // 2. Eliminar el contacto
    $deleteSql = "DELETE FROM directorio WHERE id = ?";
    $deleteStmt = sqlsrv_query($conn, $deleteSql, array($id));
    
    if ($deleteStmt === false) {
        throw new Exception('Error al ejecutar la eliminación');
    }
    
    // 3. Verificar eliminación (alternativa a sqlsrv_rows_affected)
    $verifySql = "SELECT id FROM directorio WHERE id = ?";
    $verifyStmt = sqlsrv_query($conn, $verifySql, array($id));
    
    if (sqlsrv_has_rows($verifyStmt)) {
        throw new Exception('El contacto no fue eliminado');
    }
    
    // Éxito
    echo json_encode([
        'success' => true,
        'message' => 'Contacto eliminado correctamente',
        'id' => $id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => sqlsrv_errors()
    ]);
} finally {
    // Liberar recursos
    if (isset($checkStmt)) sqlsrv_free_stmt($checkStmt);
    if (isset($deleteStmt)) sqlsrv_free_stmt($deleteStmt);
    if (isset($verifyStmt)) sqlsrv_free_stmt($verifyStmt);
    if ($conn) sqlsrv_close($conn);
}
?>