<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

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

$no_telepon = $input['no_telepon'] ?? '';
$otp_code = $input['otp_code'] ?? '';

if (empty($no_telepon) || empty($otp_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor telepon dan OTP wajib diisi']);
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

// Cek pendonor
$query = "SELECT id, nama, otp_code, otp_expired, is_verified FROM pendonor WHERE no_telepon = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $no_telepon);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor telepon tidak terdaftar']);
    exit;
}

$pendonor = $result->fetch_assoc();
$stmt->close();

// Cek apakah sudah terverifikasi
if ($pendonor['is_verified']) {
    echo json_encode(['status' => 'error', 'message' => 'Akun sudah terverifikasi']);
    exit;
}

// Cek OTP
if ($pendonor['otp_code'] !== $otp_code) {
    echo json_encode(['status' => 'error', 'message' => 'Kode OTP salah']);
    exit;
}

// Cek expired
if (strtotime($pendonor['otp_expired']) < time()) {
    echo json_encode(['status' => 'error', 'message' => 'Kode OTP sudah kadaluarsa. Silakan registrasi ulang.']);
    exit;
}

// Update status verifikasi
$update = $conn->prepare("UPDATE pendonor SET is_verified = TRUE, otp_code = NULL, otp_expired = NULL WHERE id = ?");
$update->bind_param("i", $pendonor['id']);
$update->execute();
$update->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Verifikasi berhasil! Silakan login.',
    'data' => [
        'pendonor_id' => $pendonor['id'],
        'nama' => $pendonor['nama']
    ]
]);

$conn->close();
?>