<?php
$url = 'http://localhost/care/api/news_feedback.php';
$payload = json_encode(['id' => 1, 'type' => 'like']);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
echo "Response for ID 1 LIKE: " . $response . "\n";
