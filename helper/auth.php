<?php
function authenticateAdmin() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        sendResponse('error', 'Token tidak ditemukan. Silakan login.', null, 401);
    }
    
    // Untuk sementara, token adalah session sederhana
    // Bisa ditingkatkan ke JWT nanti
    if ($token !== $_SESSION['admin_token'] ?? null) {
        sendResponse('error', 'Token tidak valid.', null, 401);
    }
    
    return $_SESSION['admin_id'] ?? null;
}
?>