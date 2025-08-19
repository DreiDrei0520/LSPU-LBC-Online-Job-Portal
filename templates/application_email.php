<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4361ee; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 10px; font-size: 12px; color: #777; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Application Received</h2>
        </div>
        <div class="content">
            <p>Dear <?= htmlspecialchars($first_name) ?>,</p>
            <p>Thank you for applying for the <strong><?= htmlspecialchars($position) ?></strong> position at our organization.</p>
            
            <p>We have successfully received your application with the following details:</p>
            
            <ul>
                <li><strong>Name:</strong> <?= htmlspecialchars($first_name . ' ' . $middle_name . ' ' . $last_name) ?></li>
                <li><strong>Email:</strong> <?= htmlspecialchars($email) ?></li>
                <li><strong>Phone:</strong> <?= htmlspecialchars($phone) ?></li>
                <li><strong>Position Applied:</strong> <?= htmlspecialchars($position) ?></li>
                <li><strong>Application Date:</strong> <?= date('F j, Y') ?></li>
            </ul>
            
            <p>Our hiring team will review your application and we will contact you if your qualifications match our requirements. This process may take up to 2-3 weeks.</p>
            
            <p>You can check the status of your application by logging into your account on our job portal.</p>
            
            <p>If you have any questions, please don't hesitate to reply to this email.</p>
            
            <p>Best regards,<br>
            Human Resources Department<br>
            <?= htmlspecialchars($_ENV['SMTP_FROM_NAME'] ?? 'Job Portal System') ?></p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply directly to this email.</p>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($_ENV['SMTP_FROM_NAME'] ?? 'Job Portal System') ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>