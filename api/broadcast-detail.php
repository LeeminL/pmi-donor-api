<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID broadcast wajib diisi']);
    exit;
}

$query = "
    SELECT r.id, r.nama_penerima, r.no_telepon, r.golongan_darah, r.rumah_sakit, r.status, r.created_at
    FROM broadcast_log b
    JOIN request_darah r ON b.request_id = r.id
    WHERE b.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Broadcast tidak ditemukan']);
    exit;
}

$detail = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'data' => $detail
]);

$conn->close();
?>