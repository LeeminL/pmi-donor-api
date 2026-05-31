<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$no_telepon = $input['no_telepon'] ?? '';
$password = $input['password'] ?? '';

if (empty($no_telepon) || empty($password)) {
    sendResponse('error', 'Nomor telepon dan password wajib diisi', null, 400);
}

$no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
if (substr($no_telepon, 0, 1) === '0') $no_telepon = '62' . substr($no_telepon, 1);

$stmt = $conn->prepare("SELECT id, nama, password, is_verified, status_aktif, fcm_token FROM pendonor WHERE no_telepon = ?");
$stmt->bind_param("s", $no_telepon);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse('error', 'Nomor telepon atau password salah', null, 401);
}

$pendonor = $result->fetch_assoc();

if (!password_verify($password, $pendonor['password'])) {
    sendResponse('error', 'Nomor telepon atau password salah', null, 401);
}

if (!$pendonor['is_verified']) {
    sendResponse('error', 'Akun belum diverifikasi. Cek WhatsApp untuk OTP.', null, 403);
}

// Update last_login
$update = $conn->prepare("UPDATE pendonor SET last_login = NOW() WHERE id = ?");
$update->bind_param("i", $pendonor['id']);
$update->execute();

$token = bin2hex(random_bytes(32));
$_SESSION['pendonor_' . $pendonor['id']] = $token;

sendResponse('success', 'Login berhasil', [
    'pendonor_id' => $pendonor['id'],
    'nama' => $pendonor['nama'],
    'status_aktif' => $pendonor['status_aktif'],
    'token' => $token
]);
?>  