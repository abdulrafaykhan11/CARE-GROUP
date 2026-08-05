<?php
require_once __DIR__ . '/config/mail.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Send email
        $subject = "CARE Nexus Newsletter Subscription";
        $body = "<html><body><h3>Thank you for subscribing!</h3><p>You have successfully subscribed to the CARE Nexus Newsletter. We will keep you updated with the latest health articles and medical news.</p></body></html>";
        sendEmail($email, $subject, $body);
        echo "Success";
    }
}
?>