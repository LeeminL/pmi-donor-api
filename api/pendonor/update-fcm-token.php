<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$pendonor_id = $input['pendonor_id'] ?? '';
$fcm_token = $input['fcm_token'] ?? '';

if (empty($pendonor_id) || empty($fcm_token)) {
    echo json_encode(['status' => 'error', 'message' => 'Pendonor ID dan FCM token wajib diisi']);
    exit;
}

$stmt = $conn->prepare("UPDATE pendonor SET fcm_token = ? WHERE id = ?");
$stmt->bind_param("si", $fcm_token, $pendonor_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'FCM token berhasil diupdate']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>