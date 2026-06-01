<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi']);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, nama FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username atau password salah']);
    exit;
}

$admin = $result->fetch_assoc();

if (!password_verify($password, $admin['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Username atau password salah']);
    exit;
}

$token = bin2hex(random_bytes(32));
$_SESSION['admin_token'] = $token;
$_SESSION['admin_id'] = $admin['id'];

echo json_encode([
    'status' => 'success',
    'message' => 'Login berhasil',
    'data' => [
        'admin_id' => $admin['id'],
        'nama' => $admin['nama'],
        'token' => $token
    ]
]);

$stmt->close();
$conn->close();
?>