<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../config/fonnte.php';  // ← Tambahkan ini

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$nama = $input['nama'] ?? '';
$no_telepon = $input['no_telepon'] ?? '';
$golongan = $input['golongan'] ?? '';
$password = $input['password'] ?? '';

if (empty($nama) || empty($no_telepon) || empty($golongan) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']);
    exit;
}

// Format nomor telepon
$no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
if (substr($no_telepon, 0, 1) === '0') {
    $no_telepon = '62' . substr($no_telepon, 1);
}
if (substr($no_telepon, 0, 1) === '8') {
    $no_telepon = '62' . $no_telepon;
}

// Cek duplikat
$check = $conn->prepare("SELECT id FROM pendonor WHERE no_telepon = ?");
$check->bind_param("s", $no_telepon);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor telepon sudah terdaftar']);
    exit;
}
$check->close();

// Generate OTP
$otp_code = sprintf("%06d", mt_rand(1, 999999));
$otp_expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert ke database (is_verified = FALSE)
$stmt = $conn->prepare("INSERT INTO pendonor (nama, no_telepon, golongan_darah, password, otp_code, otp_expired, is_verified) VALUES (?, ?, ?, ?, ?, ?, FALSE)");
$stmt->bind_param("ssssss", $nama, $no_telepon, $golongan, $hashed_password, $otp_code, $otp_expired);

if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal registrasi: ' . $stmt->error]);
    exit;
}

$pendonor_id = $stmt->insert_id;
$stmt->close();

// Kirim OTP via WhatsApp
$wa_result = sendOTP($no_telepon, $otp_code, $nama);

if ($wa_result['success']) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Registrasi berhasil! Cek WhatsApp untuk kode OTP.',
        'data' => [
            'pendonor_id' => $pendonor_id,
            'no_telepon' => $no_telepon
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Registrasi berhasil, tetapi gagal mengirim OTP. Silakan hubungi admin.',
        'data' => [
            'pendonor_id' => $pendonor_id,
            'no_telepon' => $no_telepon
        ]
    ]);
}

$conn->close();
?>