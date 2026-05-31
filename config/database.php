<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$host = 'sql310.infinityfree.com';
$user = 'if0_42060877';     // GANTI
$pass = 'BpVxXZSTIKpbjsB';      // GANTI
$dbname = 'if0_42060877_pmi';        // GANTI

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi gagal: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");
?>