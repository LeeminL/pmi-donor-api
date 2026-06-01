<?php
session_start();
require_once '../../config/database.php';

function authenticateAdmin() {
    if (!isset($_SESSION['admin_token']) || !isset($_SESSION['admin_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Silakan login.']);
        http_response_code(401);
        exit;
    }
    return $_SESSION['admin_id'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$admin_id = authenticateAdmin();

$input = json_decode(file_get_contents('php://input'), true);

$golongan = $input['golongan'] ?? '';
$jumlah = $input['jumlah'] ?? null;

if (empty($golongan) || $jumlah === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Golongan dan jumlah wajib diisi']);
    exit;
}

$stmt = $conn->prepare("UPDATE stok_darah SET jumlah = ?, updated_by = ? WHERE golongan = ?");
$stmt->bind_param("iis", $jumlah, $admin_id, $golongan);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Stok darah berhasil diupdate',
        'data' => [
            'golongan' => $golongan,
            'jumlah' => $jumlah
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update stok: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>