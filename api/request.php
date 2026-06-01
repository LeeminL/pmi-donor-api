<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$nama_penerima = $input['nama_penerima'] ?? '';
$no_telepon = $input['no_telepon'] ?? '';
$golongan = $input['golongan'] ?? '';
$rumah_sakit = $input['rumah_sakit'] ?? '';

if (empty($nama_penerima) || empty($no_telepon) || empty($golongan)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama, no telepon, dan golongan wajib diisi']);
    exit;
}

// Format nomor telepon
$no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
if (substr($no_telepon, 0, 1) === '0') {
    $no_telepon = '62' . substr($no_telepon, 1);
}
if (substr($no_telepon, 0, 1) === '8') {
    $no_telepon = '62' . $no_telepon;
}

$stmt = $conn->prepare("INSERT INTO request_darah (nama_penerima, no_telepon, golongan_darah, rumah_sakit) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nama_penerima, $no_telepon, $golongan, $rumah_sakit);

if ($stmt->execute()) {
    $request_id = $stmt->insert_id;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Request darah berhasil dikirim',
        'data' => [
            'request_id' => $request_id,
            'golongan' => $golongan
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>