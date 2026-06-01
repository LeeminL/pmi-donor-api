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

// Mulai transaction
$conn->begin_transaction();

try {
    // 1. Simpan ke request_darah
    $stmt = $conn->prepare("INSERT INTO request_darah (nama_penerima, no_telepon, golongan_darah, rumah_sakit) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama_penerima, $no_telepon, $golongan, $rumah_sakit);
    $stmt->execute();
    $request_id = $stmt->insert_id;
    $stmt->close();
    
    // 2. Cari pendonor dengan golongan yang sama & status aktif
    // Cari pendonor dengan golongan yang sama & status aktif
$query = "SELECT id, nama, fcm_token FROM pendonor WHERE golongan_darah = ? AND status_aktif = 'aktif' AND is_verified = TRUE";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $golongan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pendonor_list = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['fcm_token'])) {
            $pendonor_list[] = $row;
        }
    }
    $stmt->close();
    
    // 3. Simpan ke broadcast_log
    $jumlah_pendonor = count($pendonor_list);
    $broadcast_stmt = $conn->prepare("INSERT INTO broadcast_log (request_id, golongan_target, jumlah_pendonor_dikirim) VALUES (?, ?, ?)");
    $broadcast_stmt->bind_param("isi", $request_id, $golongan, $jumlah_pendonor);
    $broadcast_stmt->execute();
    $broadcast_id = $broadcast_stmt->insert_id;
    $broadcast_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // 4. Response sukses
    echo json_encode([
        'status' => 'success',
        'message' => 'Request darah berhasil dikirim',
        'data' => [
            'request_id' => $request_id,
            'broadcast_id' => $broadcast_id,
            'golongan' => $golongan,
            'jumlah_pendonor' => $jumlah_pendonor
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    echo json_encode([
        'status' => 'error', 
        'message' => 'Gagal simpan: ' . $e->getMessage()
    ]);
}

$conn->close();
?>