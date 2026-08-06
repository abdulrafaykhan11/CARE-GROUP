<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$limit = isset($_GET['limit']) ? max(1, min(10, (int)$_GET['limit'])) : 5;
$role = isset($_GET['role']) ? $_GET['role'] : 'All';

$sql = "SELECT notification_id, title, message, action_url, type, icon, created_at 
        FROM notifications 
        WHERE status = 'Approved' AND (target_role = 'All' OR target_role = ?) 
        ORDER BY created_at DESC LIMIT ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("si", $role, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => (int)$row['notification_id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'action_url' => $row['action_url'] ?: '#',
            'type' => $row['type'] ?: 'General',
            'icon' => $row['icon'] ?: '🔔',
            'date' => date('M d, Y', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $notifications]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications.']);
