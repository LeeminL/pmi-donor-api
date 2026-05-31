<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    sendResponse('error', 'Username dan password wajib diisi', null, 400);
}

$stmt = $conn->prepare("SELECT id, username, password, nama FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse('error', 'Username atau password salah', null, 401);
}

$admin = $result->fetch_assoc();

if (!password_verify($password, $admin['password'])) {
    sendResponse('error', 'Username atau password salah', null, 401);
}

$token = bin2hex(random_bytes(32));
$_SESSION['admin_token'] = $token;
$_SESSION['admin_id'] = $admin['id'];

sendResponse('success', 'Login berhasil', [
    'admin_id' => $admin['id'],
    'nama' => $admin['nama'],
    'token' => $token
]);
?>