<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$golongan = $_GET['golongan'] ?? '';

if (empty($golongan)) {
    echo json_encode(['status' => 'error', 'message' => 'Golongan wajib diisi']);
    exit;
}

$query = "
    SELECT b.id, b.request_id, b.golongan_target, b.sent_at
    FROM broadcast_log b
    JOIN request_darah r ON b.request_id = r.id
    WHERE b.golongan_target = ?
    ORDER BY b.sent_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $golongan);
$stmt->execute();
$result = $stmt->get_result();

$broadcasts = [];
while ($row = $result->fetch_assoc()) {
    $broadcasts[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $broadcasts
]);

$conn->close();
?>