<?php
require_once __DIR__ . '/../config/db.php';

$sampleNotifications = [
    [
        'title' => '👁️ Free AI Vision & Eye Screening',
        'message' => 'Check your eyesight instantly with our online AI Vision & Color Blindness Screening test! Takes less than 2 minutes.',
        'action_url' => 'eye-test.php',
        'type' => 'Promotion',
        'icon' => '👁️',
        'status' => 'Approved',
        'target_role' => 'All'
    ],
    [
        'title' => '🩺 Community Free Health Checkup Drive',
        'message' => 'Join our health drive at CARE Central Clinic. Free Blood Pressure & Diabetes checkups available this weekend.',
        'action_url' => 'find_doctor.php',
        'type' => 'Announcement',
        'icon' => '🩺',
        'status' => 'Approved',
        'target_role' => 'All'
    ],
    [
        'title' => '👨‍⚕️ Top Specialist Doctors Available',
        'message' => 'Book instant appointments with verified Cardiologists, Neurologists, and Eye Specialists near you.',
        'action_url' => 'find_doctor.php',
        'type' => 'Alert',
        'icon' => '👨‍⚕️',
        'status' => 'Approved',
        'target_role' => 'Patient'
    ],
    [
        'title' => '🤖 24/7 AI MediBot Instant Assistance',
        'message' => 'Have health questions? Ask CARE MediBot for instant symptom analysis and clinical guidance anytime.',
        'action_url' => 'index.php#medibot',
        'type' => 'Promotion',
        'icon' => '🤖',
        'status' => 'Approved',
        'target_role' => 'All'
    ],
    [
        'title' => '💉 Seasonal Flu Vaccination & Health Care',
        'message' => 'Protect your family this season. Schedule your preventive health checkup and flu vaccination today.',
        'action_url' => 'find_doctor.php',
        'type' => 'Alert',
        'icon' => '💉',
        'status' => 'Approved',
        'target_role' => 'Patient'
    ],
    [
        'title' => '📰 New Medical Breakthrough Insight',
        'message' => 'Read our latest verified medical blog on non-invasive diagnostic technologies and wellness tips.',
        'action_url' => 'news.php',
        'type' => 'News',
        'icon' => '📰',
        'status' => 'Approved',
        'target_role' => 'All'
    ]
];

foreach ($sampleNotifications as $n) {
    $title = $conn->real_escape_string($n['title']);
    $msg = $conn->real_escape_string($n['message']);
    $url = $conn->real_escape_string($n['action_url']);
    $type = $conn->real_escape_string($n['type']);
    $icon = $conn->real_escape_string($n['icon']);
    $status = $conn->real_escape_string($n['status']);
    $target = $conn->real_escape_string($n['target_role']);

    $q = "INSERT INTO notifications (user_id, title, message, action_url, type, icon, status, target_role) 
          VALUES (NULL, '$title', '$msg', '$url', '$type', '$icon', '$status', '$target')";
    if (!$conn->query($q)) {
        echo "Insert error: " . $conn->error . "\n";
    }
}

$res = $conn->query("SELECT * FROM notifications");
echo "Current total rows: " . $res->num_rows . "\n";
while($r = $res->fetch_assoc()) {
    echo "ID: " . $r['notification_id'] . " | Title: " . $r['title'] . " | Status: " . $r['status'] . "\n";
}
