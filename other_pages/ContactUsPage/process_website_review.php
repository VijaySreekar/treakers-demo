<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Demo mode: don't attempt to send email from a serverless deployment.
    $_SESSION['message'] = 'Thanks for the review! (Demo mode — submissions are not stored.)';
    $_SESSION['alert_type'] = 'success';

    // Redirect back to the contact page or any other appropriate page
    header("Location: contactus.php");
    exit();
} else {
    // Redirect back to the contact page
    header("Location: contactus.php");
    exit();
}
?>
