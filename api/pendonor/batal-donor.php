<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

$response_id = $input['response_id'] ?? 0;
$pendonor_id = $input['pendonor_id'] ?? 0;

if (empty($response_id) || empty($pendonor_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Response ID dan Pendonor ID wajib diisi']);
    exit;
}

// Cek apakah response milik pendonor ini
$check = $conn->prepare("SELECT id FROM donor_response WHERE id = ? AND pendonor_id = ?");
$check->bind_param("ii", $response_id, $pendonor_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Response tidak ditemukan atau bukan milik Anda']);
    exit;
}
$check->close();

// Update status menjadi batal
$stmt = $conn->prepare("UPDATE donor_response SET status = 'batal' WHERE id = ?");
$stmt->bind_param("i", $response_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Donor berhasil dibatalkan'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>