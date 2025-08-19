<?php
session_start();
require 'vendor/autoload.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    // Get form data and sanitize
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $subject = trim(htmlspecialchars($_POST['subject'] ?? ''));
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['message_error'] = "All fields are required.";
        header("Location: contact.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message_error'] = "Invalid email format.";
        header("Location: contact.php");
        exit();
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'jadesupremo0@gmail.com'; // Your Gmail address
        $mail->Password = 'lfns yegc vqba ywbq'; // Use App Password if 2FA is enabled
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port = 465; // Gmail SMTP port for SSL
        
        // Important settings
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        $mail->Timeout = 30;
        $mail->CharSet = 'UTF-8';
        
        // Disable SSL certificate verification (for local testing only)
        // $mail->SMTPOptions = [
        //     'ssl' => [
        //         'verify_peer' => false,
        //         'verify_peer_name' => false,
        //         'allow_self_signed' => true
        //     ]
        // ];

        // Recipients
        $mail->setFrom('jadesupremo0@gmail.com', 'Job Application System'); // Must match your Gmail address
        $mail->addAddress('jbdmnnln@gmail.com', 'Admin'); // Admin email from your database
        $mail->addReplyTo($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Contact Form: $subject";
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4361ee; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9em; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>New Contact Form Submission</h1>
                    </div>
                    <div class='content'>
                        <p><strong>Name:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Subject:</strong> $subject</p>
                        <p><strong>Message:</strong></p>
                        <p>" . nl2br($message) . "</p>
                    </div>
                    <div class='footer'>
                        <p>This message was sent from the contact form on your website.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

        $mail->send();
        
        // Store success message in session
        $_SESSION['message_sent'] = true;
        header("Location: contact.php");
        exit();
    } catch (Exception $e) {
        // Store error message in session
        $_SESSION['message_error'] = "Message could not be sent. Error: " . $e->getMessage();
        error_log('Mailer Error: ' . $e->getMessage());
        header("Location: contact.php");
        exit();
    }
} else {
    // If not a POST request, redirect to contact page
    header("Location: contact.php");
    exit();
}