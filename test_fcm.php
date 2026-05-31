<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/fcm_v1.php';

// GANTI DENGAN FCM TOKEN DARI ANDROID NANTI
$test_token = "YOUR_ANDROID_FCM_TOKEN_HERE";

echo "<h1>Test FCM v1</h1>";

// Cek file credential
$credentialFile = __DIR__ . '/config/firebase-credentials.json';
if (file_exists($credentialFile)) {
    echo "<p style='color:green'>✅ File credential ditemukan</p>";
} else {
    echo "<p style='color:red'>❌ File credential TIDAK ditemukan di: " . $credentialFile . "</p>";
}

// Test kirim notifikasi
if ($test_token != "YOUR_ANDROID_FCM_TOKEN_HERE") {
    echo "<h3>Mengirim test notifikasi...</h3>";
    $result = testFCM($test_token, "Test dari PMI", "Jika Anda menerima ini, FCM berhasil!");
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} else {
    echo "<p style='color:orange'>⚠️ Masukkan FCM token Android terlebih dahulu!</p>";
    echo "<p>Token bisa didapat dari logcat Android setelah install Firebase.</p>";
}
?>