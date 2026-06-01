<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$pendonor_id = $input['pendonor_id'] ?? '';
$status = $input['status'] ?? '';
$fcm_token = $input['fcm_token'] ?? null;

if (empty($pendonor_id) || empty($status)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Pendonor ID dan status wajib diisi']);
    exit;
}

if (!in_array($status, ['aktif', 'nonaktif'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status harus aktif atau nonaktif']);
    exit;
}

if ($fcm_token) {
    $stmt = $conn->prepare("UPDATE pendonor SET status_aktif = ?, fcm_token = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $fcm_token, $pendonor_id);
} else {
    $stmt = $conn->prepare("UPDATE pendonor SET status_aktif = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $pendonor_id);
}

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => "Status pendonor diubah menjadi $status"
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>