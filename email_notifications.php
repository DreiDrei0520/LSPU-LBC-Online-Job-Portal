<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Path to PHPMailer autoload

function sendStatusEmail($email, $status, $additionalInfo = '') {
    $mail = new PHPMailer(true);
    
    // Email subject and message based on status
    $statusMessages = [
        'Applied' => [
            'subject' => 'Application Received',
            'message' => 'Thank you for submitting your application. We have received it and it is now pending review.'
        ],
        'Under Review' => [
            'subject' => 'Application Under Review',
            'message' => 'Your application is currently being reviewed by our team.'
        ],
        'Interview Scheduled' => [
            'subject' => 'Interview Scheduled',
            'message' => 'An interview has been scheduled for your application. ' . $additionalInfo
        ],
        'Interviewed' => [
            'subject' => 'Interview Completed',
            'message' => 'Thank you for completing the interview. We will review your performance and get back to you soon.'
        ],
        'Hired' => [
            'subject' => 'Congratulations! You\'re Hired!',
            'message' => 'We are pleased to inform you that your application has been successful! ' . $additionalInfo
        ],
        'Rejected' => [
            'subject' => 'Application Status Update',
            'message' => 'After careful consideration, we regret to inform you that your application has not been successful this time.'
        ]
    ];

    if (!isset($statusMessages[$status])) {
        return false;
    }

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jadesupremo0@gmail.com'; // Your Gmail address
        $mail->Password = 'lfns yegc vqba ywbq'; // Your Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jadesupremo0@gmail.com', 'Job Portal System');
        $mail->addAddress($email);

        $mail->isHTML(false);
        $mail->Subject = $statusMessages[$status]['subject'];
        $mail->Body = $statusMessages[$status]['message'];

        return $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>