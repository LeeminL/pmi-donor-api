<?php
// config/fcm_v1.php
// FCM HTTP v1 API - Pure cURL, tanpa Composer

error_reporting(E_ALL);
ini_set('display_errors', 1);

$credentialPath = __DIR__ . '/firebase-credentials.json';

/**
 * Mendapatkan Access Token dari Service Account JSON
 */
function getFCMAccessToken($credentialPath) {
    if (!file_exists($credentialPath)) {
        return ['error' => true, 'message' => 'File credential tidak ditemukan: ' . $credentialPath];
    }
    
    $credentials = json_decode(file_get_contents($credentialPath), true);
    
    if (!isset($credentials['client_email']) || !isset($credentials['private_key'])) {
        return ['error' => true, 'message' => 'File credential tidak valid'];
    }
    
    // Buat JWT
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claim = base64_encode(json_encode([
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time()
    ]));
    
    $signature = '';
    $privateKey = $credentials['private_key'];
    openssl_sign($header . '.' . $claim, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $jwtSignature = base64_encode($signature);
    
    $jwt = $header . '.' . $claim . '.' . $jwtSignature;
    
    // Tukar JWT dengan Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        return ['error' => true, 'message' => 'Gagal mendapatkan access token: ' . $response];
    }
    
    $result = json_decode($response, true);
    return ['error' => false, 'token' => $result['access_token']];
}

/**
 * Kirim FCM ke satu atau banyak token
 */
function sendFCMv1($tokens, $title, $body, $data = []) {
    global $credentialPath;
    
    // Dapatkan access token
    $auth = getFCMAccessToken($credentialPath);
    if ($auth['error']) {
        return ['success' => false, 'message' => $auth['message']];
    }
    
    $accessToken = $auth['token'];
    
    // Baca project_id dari credential
    $credentials = json_decode(file_get_contents($credentialPath), true);
    $projectId = $credentials['project_id'];
    
    // Pastikan tokens berupa array
    if (!is_array($tokens)) {
        $tokens = [$tokens];
    }
    $tokens = array_filter($tokens);
    
    if (empty($tokens)) {
        return ['success' => false, 'message' => 'Tidak ada token yang valid'];
    }
    
    $successCount = 0;
    $failedTokens = [];
    
    foreach ($tokens as $token) {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => (object) $data,
                'android' => [
                    'priority' => 'high'
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $successCount++;
        } else {
            $failedTokens[] = $token;
            // Log error untuk debugging
            error_log("FCM Error untuk token $token: $response");
        }
    }
    
    return [
        'success' => $successCount > 0,
        'message' => "$successCount notifikasi terkirim dari " . count($tokens),
        'success_count' => $successCount,
        'total' => count($tokens),
        'failed_tokens' => $failedTokens
    ];
}

/**
 * Kirim broadcast ke daftar pendonor
 */
function sendBroadcastToDonors($pendonorList, $golongan, $requestId, $rumahSakit) {
    $tokens = [];
    foreach ($pendonorList as $pendonor) {
        if (!empty($pendonor['fcm_token'])) {
            $tokens[] = $pendonor['fcm_token'];
        }
    }
    
    if (empty($tokens)) {
        return ['success' => false, 'message' => 'Tidak ada FCM token yang valid'];
    }
    
    $title = "❗ Permintaan Darah Darurat";
    $body = "Butuh darah golongan $golongan di $rumahSakit";
    
    $data = [
        'request_id' => (string)$requestId,
        'golongan' => $golongan,
        'rumah_sakit' => $rumahSakit,
        'type' => 'broadcast',
        'click_action' => 'OPEN_BROADCAST_DETAIL'
    ];
    
    return sendFCMv1($tokens, $title, $body, $data);
}

// Fungsi untuk test FCM ke satu token (pakai dari command line atau browser)
function testFCM($token, $title = "Test Notifikasi", $body = "Ini adalah test notifikasi dari PMI") {
    return sendFCMv1($token, $title, $body, ['type' => 'test']);
}
?>