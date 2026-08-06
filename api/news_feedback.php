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
$prevVote = isset($input['previous_vote']) && in_array($input['previous_vote'], ['like', 'dislike']) ? $input['previous_vote'] : null;
$newVote = isset($input['new_vote']) && in_array($input['new_vote'], ['like', 'dislike']) ? $input['new_vote'] : null;

// Support legacy API format (where only 'type' is passed without previous_vote)
if (isset($input['type']) && !isset($input['new_vote'])) {
    $newVote = in_array($input['type'], ['like', 'dislike']) ? $input['type'] : null;
}

if ($newsId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid news ID.']);
    exit;
}

if ($prevVote === $newVote) {
    // No change in vote state
    $res = $conn->query("SELECT likes, dislikes FROM medical_news WHERE news_id = $newsId");
    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'new_likes' => (int)$row['likes'],
            'new_dislikes' => (int)$row['dislikes'],
            'user_vote' => $newVote
        ]);
        exit;
    }
}

// Compute query based on vote transition
if ($prevVote === null && $newVote === 'like') {
    $q = "UPDATE medical_news SET likes = likes + 1 WHERE news_id = ?";
} elseif ($prevVote === null && $newVote === 'dislike') {
    $q = "UPDATE medical_news SET dislikes = dislikes + 1 WHERE news_id = ?";
} elseif ($prevVote === 'like' && $newVote === null) {
    $q = "UPDATE medical_news SET likes = GREATEST(0, likes - 1) WHERE news_id = ?";
} elseif ($prevVote === 'dislike' && $newVote === null) {
    $q = "UPDATE medical_news SET dislikes = GREATEST(0, dislikes - 1) WHERE news_id = ?";
} elseif ($prevVote === 'like' && $newVote === 'dislike') {
    $q = "UPDATE medical_news SET likes = GREATEST(0, likes - 1), dislikes = dislikes + 1 WHERE news_id = ?";
} elseif ($prevVote === 'dislike' && $newVote === 'like') {
    $q = "UPDATE medical_news SET dislikes = GREATEST(0, dislikes - 1), likes = likes + 1 WHERE news_id = ?";
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vote transition.']);
    exit;
}

$stmt = $conn->prepare($q);
if ($stmt) {
    $stmt->bind_param("i", $newsId);
    if ($stmt->execute()) {
        $res = $conn->query("SELECT likes, dislikes FROM medical_news WHERE news_id = $newsId");
        if ($res && $row = $res->fetch_assoc()) {
            echo json_encode([
                'status' => 'success',
                'new_likes' => (int)$row['likes'],
                'new_dislikes' => (int)$row['dislikes'],
                'user_vote' => $newVote
            ]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Failed to update feedback.']);
