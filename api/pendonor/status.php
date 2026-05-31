<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$pendonor_id = $input['pendonor_id'] ?? '';
$status = $input['status'] ?? ''; // 'aktif' atau 'nonaktif'
$fcm_token = $input['fcm_token'] ?? null;

if (empty($pendonor_id) || empty($status)) {
    sendResponse('error', 'Pendonor ID dan status wajib diisi', null, 400);
}

if (!in_array($status, ['aktif', 'nonaktif'])) {
    sendResponse('error', 'Status harus aktif atau nonaktif', null, 400);
}

if ($fcm_token) {
    $stmt = $conn->prepare("UPDATE pendonor SET status_aktif = ?, fcm_token = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $fcm_token, $pendonor_id);
} else {
    $stmt = $conn->prepare("UPDATE pendonor SET status_aktif = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $pendonor_id);
}

if ($stmt->execute()) {
    sendResponse('success', "Status pendonor diubah menjadi $status");
} else {
    sendResponse('error', 'Gagal update status', null, 500);
}
?>