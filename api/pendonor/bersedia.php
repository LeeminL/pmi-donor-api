<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$broadcast_id = $input['broadcast_id'] ?? '';
$pendonor_id = $input['pendonor_id'] ?? '';

if (empty($broadcast_id) || empty($pendonor_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Broadcast ID dan Pendonor ID wajib diisi']);
    exit;
}

// Ambil nomor telepon penerima
$query = "
    SELECT r.no_telepon as no_telepon_penerima, r.nama_penerima, r.rumah_sakit, r.golongan_darah
    FROM broadcast_log b
    JOIN request_darah r ON b.request_id = r.id
    WHERE b.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $broadcast_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Broadcast tidak ditemukan']);
    exit;
}

$request = $result->fetch_assoc();

// Simpan response
$insert = $conn->prepare("INSERT INTO donor_response (broadcast_id, pendonor_id, no_telepon_penerima) VALUES (?, ?, ?)");
$insert->bind_param("iis", $broadcast_id, $pendonor_id, $request['no_telepon_penerima']);
$insert->execute();

echo json_encode([
    'status' => 'success',
    'message' => 'Terima kasih! Hubungi penerima melalui WhatsApp.',
    'data' => [
        'no_telepon_penerima' => $request['no_telepon_penerima'],
        'nama_penerima' => $request['nama_penerima'],
        'rumah_sakit' => $request['rumah_sakit'],
        'golongan' => $request['golongan_darah'],
        'wa_link' => 'https://wa.me/' . $request['no_telepon_penerima']
    ]
]);

$stmt->close();
$conn->close();
?>