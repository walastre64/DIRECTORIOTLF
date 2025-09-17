<?php
header('Content-Type: application/json');
require_once 'conexion.php';

session_start();

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos recibidos
if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos']);
    exit;
}

$username = trim($input['username']);
$password = trim($input['password']);

// Consulta SQL con parámetros
$sql = "SELECT id, username, password FROM usuarios WHERE username = ? AND activo = 1";
$params = array($username);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos',
        'errors' => sqlsrv_errors()
    ]);
    exit;
}

$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Verificación directa de contraseña (sin hash)
if (!$user || $user['password'] !== $password) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Credenciales incorrectas'
    ]);
    exit;
}

// Configurar sesión
$_SESSION['admin_logged'] = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'message' => 'Autenticación exitosa',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username']
    ]
]);

// Liberar recursos
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>