<?php
function testQuery($msg) {
    echo "========================================\n";
    echo "TESTING QUERY: '$msg'\n";
    echo "========================================\n";

    $ch = curl_init('http://localhost/care/api/chat_handler.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $msg]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $httpCode\n";
    echo "Response JSON:\n$response\n\n";
}

testQuery('Severe chest pain and tightness');
testQuery('Stomach acidity and pain after eating');
testQuery('Skin rash and acne treatments');
testQuery('How do I book an appointment on CARE Nexus?');
