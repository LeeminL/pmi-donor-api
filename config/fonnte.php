<?php
$fonnte_api_key = '2j1BdF4redHk4nnHDujz';

function sendWA($phone, $message) {
    global $fonnte_api_key;
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
    if (substr($phone, 0, 1) === '8') $phone = '62' . $phone;
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['target' => $phone, 'message' => $message]),
        CURLOPT_HTTPHEADER => ['Authorization: ' . $fonnte_api_key]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    // LOG untuk debugging
    error_log("Fonnte Response: " . $response);
    error_log("HTTP Code: " . $httpCode);
    if ($error) error_log("CURL Error: " . $error);
    
    return [
        'success' => $httpCode == 200,
        'response' => json_decode($response, true),
        'http_code' => $httpCode,
        'error' => $error
    ];
}

function sendOTP($phone, $otp, $nama) {
    $message = "*VERIFIKASI OTP - PMI Donor Darah*\n\n";
    $message .= "Halo $nama,\n\n";
    $message .= "Kode verifikasi Anda: *$otp*\n";
    $message .= "Berlaku selama 5 menit.\n\n";
    $message .= "Jangan berikan kode ini ke siapapun.\n\n";
    $message .= "_PMI - Selamatkan Nyawa, Donor Darah!_";
    
    return sendWA($phone, $message);
}
?>