<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/fcm_v1.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$nama_penerima = $input['nama_penerima'] ?? '';
$no_telepon = $input['no_telepon'] ?? '';
$golongan = $input['golongan'] ?? '';
$rumah_sakit = $input['rumah_sakit'] ?? '';

if (empty($nama_penerima) || empty($no_telepon) || empty($golongan)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama, no telepon, dan golongan wajib diisi']);
    exit;
}

// Simpan request
$stmt = $conn->prepare("INSERT INTO request_darah (nama_penerima, no_telepon, golongan_darah, rumah_sakit) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nama_penerima, $no_telepon, $golongan, $rumah_sakit);
$stmt->execute();
$request_id = $stmt->insert_id;

// Cari pendonor dengan golongan yang sama & status aktif
$query = "SELECT id, nama, fcm_token FROM pendonor WHERE golongan_darah = ? AND status_aktif = 'aktif' AND is_verified = TRUE";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $golongan);
$stmt->execute();
$result = $stmt->get_result();

$pendonor_list = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['fcm_token'])) {
        $pendonor_list[] = $row;
    }
}

// Kirim FCM broadcast
$fcm_result = sendBroadcastToDonors($pendonor_list, $golongan, $request_id, $rumah_sakit);

echo json_encode([
    'status' => 'success',
    'message' => 'Request darah berhasil dikirim',
    'data' => [
        'request_id' => $request_id,
        'golongan' => $golongan,
        'jumlah_pendonor' => count($pendonor_list),
        'fcm_status' => $fcm_result
    ]
]);

$conn->close();
?>