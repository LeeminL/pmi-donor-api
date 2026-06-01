<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

$broadcast_id = $_GET['broadcast_id'] ?? 0;
$pendonor_id = $_GET['pendonor_id'] ?? 0;

if (empty($broadcast_id) || empty($pendonor_id)) {
    echo json_encode(['status' => 'error', 'message' => 'broadcast_id dan pendonor_id wajib diisi']);
    exit;
}

$query = "SELECT id, status FROM donor_response WHERE broadcast_id = ? AND pendonor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $broadcast_id, $pendonor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'success', 'data' => null]);
    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'data' => [
        'response_id' => $row['id'],
        'status' => $row['status']
    ]
]);

$stmt->close();
$conn->close();
?>