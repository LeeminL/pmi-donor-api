<?php
require_once '../../config/database.php';

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

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO pendonor (nama, no_telepon, golongan_darah, password, is_verified) VALUES (?, ?, ?, ?, TRUE)");
$stmt->bind_param("ssss", $nama, $no_telepon, $golongan, $hashed_password);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Registrasi berhasil. Silakan login.',
        'data' => [
            'pendonor_id' => $stmt->insert_id,
            'no_telepon' => $no_telepon
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal registrasi: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>