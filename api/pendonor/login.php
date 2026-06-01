<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$no_telepon = $input['no_telepon'] ?? '';
$password = $input['password'] ?? '';

if (empty($no_telepon) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nomor telepon dan password wajib diisi']);
    exit;
}

$no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
if (substr($no_telepon, 0, 1) === '0') {
    $no_telepon = '62' . substr($no_telepon, 1);
}
if (substr($no_telepon, 0, 1) === '8') {
    $no_telepon = '62' . $no_telepon;
}

$stmt = $conn->prepare("SELECT id, nama, password, golongan_darah, status_aktif FROM pendonor WHERE no_telepon = ?");
$stmt->bind_param("s", $no_telepon);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor telepon atau password salah']);
    exit;
}

$pendonor = $result->fetch_assoc();

if (!password_verify($password, $pendonor['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor telepon atau password salah']);
    exit;
}

$token = bin2hex(random_bytes(32));
$_SESSION['pendonor_' . $pendonor['id']] = $token;

echo json_encode([
    'status' => 'success',
    'message' => 'Login berhasil',
    'data' => [
        'pendonor_id' => $pendonor['id'],
        'nama' => $pendonor['nama'],
        'golongan_darah' => $pendonor['golongan_darah'],
        'status_aktif' => $pendonor['status_aktif'],
        'token' => $token
    ]
]);

$stmt->close();
$conn->close();
?>