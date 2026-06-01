<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$no_telepon = $input['no_telepon'] ?? '';
$otp = $input['otp'] ?? '';

if (empty($no_telepon) || empty($otp)) {
    sendResponse('error', 'Nomor telepon dan OTP wajib diisi', null, 400);
}

$no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
if (substr($no_telepon, 0, 1) === '0') $no_telepon = '62' . substr($no_telepon, 1);

$stmt = $conn->prepare("SELECT id, otp_code, otp_expired, is_verified FROM pendonor WHERE no_telepon = ?");
$stmt->bind_param("s", $no_telepon);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse('error', 'Nomor telepon tidak terdaftar', null, 404);
}

$pendonor = $result->fetch_assoc();

if ($pendonor['is_verified']) {
    sendResponse('error', 'Akun sudah terverifikasi', null, 400);
}

if ($pendonor['otp_code'] !== $otp) {
    sendResponse('error', 'Kode OTP salah', null, 400);
}

if (strtotime($pendonor['otp_expired']) < time()) {
    sendResponse('error', 'Kode OTP kadaluarsa. Silakan registrasi ulang.', null, 400);
}

$update = $conn->prepare("UPDATE pendonor SET is_verified = TRUE, otp_code = NULL, otp_expired = NULL WHERE id = ?");
$update->bind_param("i", $pendonor['id']);
$update->execute();

sendResponse('success', 'Verifikasi berhasil. Silakan login.', ['pendonor_id' => $pendonor['id']]);
?>