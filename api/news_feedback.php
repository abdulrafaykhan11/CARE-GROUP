<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$newsId = isset($input['id']) ? (int)$input['id'] : 0;
$type = isset($input['type']) ? $input['type'] : '';

if ($newsId <= 0 || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit;
}

// Prepare statement
if ($type === 'like') {
    $q = "UPDATE medical_news SET likes = likes + 1 WHERE news_id = ?";
} else {
    $q = "UPDATE medical_news SET dislikes = dislikes + 1 WHERE news_id = ?";
}

$stmt = $conn->prepare($q);
if ($stmt) {
    $stmt->bind_param("i", $newsId);
    if ($stmt->execute()) {
        // Fetch new counts
        $res = $conn->query("SELECT likes, dislikes FROM medical_news WHERE news_id = $newsId");
        if ($res && $row = $res->fetch_assoc()) {
            echo json_encode([
                'status' => 'success',
                'new_likes' => (int)$row['likes'],
                'new_dislikes' => (int)$row['dislikes']
            ]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Failed to update feedback.']);
