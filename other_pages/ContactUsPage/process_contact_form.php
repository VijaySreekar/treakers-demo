<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Demo mode: don't attempt to send email from a serverless deployment.
    $_SESSION['message'] = 'Thanks for your message! (Demo mode — email sending is disabled.)';
    $_SESSION['alert_type'] = 'success';

    // Redirect back to the contact page
    header("Location: contactus.php");
    exit();
}
?>
