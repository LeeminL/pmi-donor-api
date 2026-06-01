<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/*
|--------------------------------------------------------------------------
| KONFIGURASI DATABASE AWARDSPACE
|--------------------------------------------------------------------------
*/
$host   = "fdb1032.awardspace.net";
$user   = "4763992_pmi";
$pass   = "F]7UiRbW2HnVX/l3";
$dbname = "4763992_pmi";
/*
|--------------------------------------------------------------------------
| KONEKSI DATABASE
|--------------------------------------------------------------------------
*/

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);

    echo json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal',
        'error'   => $conn->connect_error
    ]);

    exit;
}

$conn->set_charset('utf8mb4');

?>