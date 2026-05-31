<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method tidak diizinkan', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$broadcast_id = $input['broadcast_id'] ?? '';
$pendonor_id = $input['pendonor_id'] ?? '';

if (empty($broadcast_id) || empty($pendonor_id)) {
    sendResponse('error', 'Broadcast ID dan Pendonor ID wajib diisi', null, 400);
}

// Ambil nomor telepon penerima dari request melalui broadcast_log
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
    sendResponse('error', 'Broadcast tidak ditemukan', null, 404);
}

$request = $result->fetch_assoc();

// Simpan response pendonor
$insert = $conn->prepare("INSERT INTO donor_response (broadcast_id, pendonor_id, no_telepon_penerima) VALUES (?, ?, ?)");
$insert->bind_param("iis", $broadcast_id, $pendonor_id, $request['no_telepon_penerima']);
$insert->execute();

// Kirim balik nomor telepon penerima untuk dihubungi via WA
sendResponse('success', 'Terima kasih! Hubungi penerima melalui WhatsApp.', [
    'no_telepon_penerima' => $request['no_telepon_penerima'],
    'nama_penerima' => $request['nama_penerima'],
    'rumah_sakit' => $request['rumah_sakit'],
    'golongan' => $request['golongan_darah'],
    'wa_link' => 'https://wa.me/' . $request['no_telepon_penerima']
]);
?>