<?php
session_start();
require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$admin_id = authenticateAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$golongan = $input['golongan'] ?? '';
$jumlah = $input['jumlah'] ?? null;

if (empty($golongan) || $jumlah === null) {
    sendResponse('error', 'Golongan dan jumlah wajib diisi', null, 400);
}

$stmt = $conn->prepare("UPDATE stok_darah SET jumlah = ?, updated_by = ? WHERE golongan = ?");
$stmt->bind_param("iis", $jumlah, $admin_id, $golongan);

if ($stmt->execute()) {
    sendResponse('success', 'Stok darah berhasil diupdate', [
        'golongan' => $golongan,
        'jumlah' => $jumlah
    ]);
} else {
    sendResponse('error', 'Gagal update stok', null, 500);
}
?>