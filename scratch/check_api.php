<?php
require_once __DIR__ . '/../config/gemini.php';

$apiKey = GEMINI_API_KEY;
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = json_decode(curl_exec($ch), true);
curl_close($ch);

$availableGenModels = [];
if (isset($res['models'])) {
    foreach ($res['models'] as $m) {
        if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
            $availableGenModels[] = str_replace('models/', '', $m['name']);
        }
    }
}

echo "Testing all available generateContent models:\n";
foreach ($availableGenModels as $m) {
    $mUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . $apiKey;
    $ch = curl_init($mUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['contents' => [['parts' => [['text' => 'Give short 1 line health tip for drinking water.']]]]]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $responseStr = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Model '$m' -> HTTP Code: $c\n";
    if ($c === 200) {
        $json = json_decode($responseStr, true);
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'NO_TEXT';
        echo "  >>> WORKING! Text: " . trim($text) . "\n";
    }
}
