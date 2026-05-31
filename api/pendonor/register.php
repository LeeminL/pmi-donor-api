<?php
require_once '../../config/database.php';
require_once '../../config/fonnte.php';
require_once '../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$nama = $input['nama'] ?? '';
$no_telepon = $input['no_telepon'] ?? '';
$golongan = $input['golongan'] ?? '';
$password = $input['password'] ?? '';

if (empty($nama) || empty($no_telepon) || empty($golongan) || empty($password)) {
    sendResponse('error', 'Semua field wajib diisi', null, 400);
}

// Format nomor
$no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
if (substr($no_telepon, 0, 1) === '0') $no_telepon = '62' . substr($no_telepon, 1);
if (substr($no_telepon, 0, 1) === '8') $no_telepon = '62' . $no_telepon;

// Cek duplikat
$check = $conn->prepare("SELECT id FROM pendonor WHERE no_telepon = ?");
$check->bind_param("s", $no_telepon);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    sendResponse('error', 'Nomor telepon sudah terdaftar', null, 409);
}

$otp = sprintf("%06d", mt_rand(1, 999999));
$otp_expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO pendonor (nama, no_telepon, golongan_darah, password, otp_code, otp_expired) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $nama, $no_telepon, $golongan, $hashed_password, $otp, $otp_expired);

if ($stmt->execute()) {
    sendOTP($no_telepon, $otp, $nama);
    sendResponse('success', 'Registrasi berhasil. Cek WhatsApp untuk kode OTP.', [
        'pendonor_id' => $stmt->insert_id,
        'no_telepon' => $no_telepon
    ]);
} else {
    sendResponse('error', 'Gagal registrasi', null, 500);
}
?>