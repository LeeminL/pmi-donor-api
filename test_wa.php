<?php

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo "ERROR: " . curl_error($ch);
} else {
    echo "BERHASIL";
}

curl_close($ch);