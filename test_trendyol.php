<?php
// Trendyol API test script'i
$apiKey = 'CVn4MItx2ORADdD5VLZI';
$apiSecret = 'btLhur2HrPmhKjXC0Fz9';
$supplierId = '113278';

// Basic Auth header
$basic = base64_encode($apiKey . ':' . $apiSecret);

// Headers
$headers = [
    'Accept: application/json',
    'Authorization: Basic ' . $basic,
    'User-Agent: YeniPazarYeri-SelfIntegration/1.0',
    'Content-Type: application/json'
];

// URL
$url = "https://api.trendyol.com/sapigw/suppliers/{$supplierId}/orders";

echo "Testing URL: {$url}\n";
echo "Basic Auth: Basic {$basic}\n";
echo "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n\n";

// cURL ile test
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "cURL Error: " . ($error ?: 'None') . "\n";
echo "Response:\n{$response}\n";
