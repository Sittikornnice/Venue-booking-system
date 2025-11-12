<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sittikorn-sat@rmutp.ac.th';
    $mail->Password = 'zufl kvea nykx fylf'; // ใช้ App Password ของ Google
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // ส่งอีเมลโดยไม่ใช้ addAddress()
    $mail->setFrom('sittikorn.nice@gmail.com', 'Appointment System', false);
    $mail->ClearAllRecipients(); // ล้างอีเมลผู้รับทั้งหมด
    $mail->addTo('sittikorn-sat@rmutp.ac.th'); // ใส่อีเมลผู้รับตรงนี้

    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}
?>
