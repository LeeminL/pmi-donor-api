<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$result = $conn->query("SELECT golongan, jumlah FROM stok_darah ORDER BY golongan");

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . $conn->error]);
    exit;
}

$stok = [];
while ($row = $result->fetch_assoc()) {
    $stok[] = $row;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Data stok darah',
    'data' => $stok,
    'timestamp' => date('Y-m-d H:i:s')
]);

$conn->close();
?>