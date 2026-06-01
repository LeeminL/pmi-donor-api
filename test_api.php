<?php
// test_api.php
$base_url = "https://pmi-donorlink.kesug.com/api/";

echo "<h1>Test API PMI Donor Link</h1>";
echo "<p>Base URL: $base_url</p>";

function testEndpoint($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['http_code' => $httpCode, 'response' => json_decode($response, true)];
}

// Test 1: GET Stok
echo "<h3>1. GET Stok Darah</h3>";
$result = testEndpoint($base_url . "stok.php");
echo "<pre>";
print_r($result);
echo "</pre>";

// Test 2: POST Request (jika perlu)
echo "<h3>2. POST Request Darurat</h3>";
$result = testEndpoint($base_url . "request.php", 'POST', [
    'nama_penerima' => 'Test Pasien',
    'no_telepon' => '628123456789',
    'golongan' => 'B',
    'rumah_sakit' => 'RS Test'
]);
echo "<pre>";
print_r($result);
echo "</pre>";

echo "<hr>";
echo "<p style='color:green'>✅ Testing selesai. Jika sukses, API siap digunakan.</p>";
?>