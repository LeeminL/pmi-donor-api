<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$broadcast_id = $input['broadcast_id'] ?? 0;
$pendonor_id = $input['pendonor_id'] ?? 0;

if (empty($broadcast_id) || empty($pendonor_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Broadcast ID dan Pendonor ID wajib diisi']);
    exit;
}

// Cek apakah broadcast_id ada
$check = $conn->prepare("SELECT id FROM broadcast_log WHERE id = ?");
$check->bind_param("i", $broadcast_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Broadcast tidak ditemukan']);
    exit;
}
$check->close();

// Cek apakah sudah pernah merespon
$check_respon = $conn->prepare("SELECT id FROM donor_response WHERE broadcast_id = ? AND pendonor_id = ?");
$check_respon->bind_param("ii", $broadcast_id, $pendonor_id);
$check_respon->execute();
$check_respon->store_result();

if ($check_respon->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Anda sudah merespon broadcast ini']);
    exit;
}
$check_respon->close();

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
    echo json_encode(['status' => 'error', 'message' => 'Detail broadcast tidak ditemukan']);
    exit;
}

$request = $result->fetch_assoc();
$stmt->close();

// Simpan response dengan status 'menunggu'
$insert = $conn->prepare("INSERT INTO donor_response (broadcast_id, pendonor_id, no_telepon_penerima, status) VALUES (?, ?, ?, 'menunggu')");
$insert->bind_param("iis", $broadcast_id, $pendonor_id, $request['no_telepon_penerima']);

if (!$insert->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal simpan: ' . $insert->error]);
    exit;
}

$response_id = $insert->insert_id;
$insert->close();

// Format nomor telepon
$no_telepon = preg_replace('/[^0-9]/', '', $request['no_telepon_penerima']);
if (substr($no_telepon, 0, 1) === '0') {
    $no_telepon = '62' . substr($no_telepon, 1);
}
if (substr($no_telepon, 0, 1) === '8') {
    $no_telepon = '62' . $no_telepon;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Terima kasih! Hubungi penerima melalui WhatsApp.',
    'data' => [
        'response_id' => $response_id,
        'no_telepon_penerima' => $request['no_telepon_penerima'],
        'nama_penerima' => $request['nama_penerima'],
        'rumah_sakit' => $request['rumah_sakit'],
        'golongan' => $request['golongan_darah'],
        'wa_link' => 'https://wa.me/' . $no_telepon
    ]
]);

$conn->close();
?>