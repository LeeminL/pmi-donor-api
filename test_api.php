<?php
// test_api.php

$base_url = "http://pmi-donorlink.atwebpages.com/api/";

echo "<h1>Test API PMI Donor Link</h1>";
echo "<p>Base URL: $base_url</p>";

function testEndpoint($url, $method = 'GET', $data = null)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);

        curl_close($ch);

        return [
            'status' => 'curl_error',
            'message' => $error
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'raw_response' => $response,
        'json_response' => json_decode($response, true)
    ];
}

// =====================
// TEST GET STOK
// =====================

echo "<h3>1. GET Stok Darah</h3>";

$result = testEndpoint($base_url . "stok.php");

echo "<pre>";
print_r($result);
echo "</pre>";

// =====================
// TEST POST REQUEST
// =====================

echo "<h3>2. POST Request Darurat</h3>";

$result = testEndpoint(
    $base_url . "request.php",
    "POST",
    [
        "nama_penerima" => "Test Pasien",
        "no_telepon"    => "628123456789",
        "golongan"      => "B",
        "rumah_sakit"   => "RS Test"
    ]
);

echo "<pre>";
print_r($result);
echo "</pre>";

echo "<hr>";
echo "<p style='color:green'>✅ Testing selesai</p>";
?>