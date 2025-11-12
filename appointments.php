<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// เช็คสิทธิ์การเข้าใช้งาน ต้องเป็น 'teacher' เท่านั้น
if ($_SESSION['role'] !== 'teacher') {
    sendAccessDeniedEmail();
    die("รายละเอียดได้ถูกส่งไปยังผู้ดูแลระบบ");
}

// ฟังก์ชันส่งอีเมลแจ้งเตือนหากพบการเข้าถึงผิดพลาด
function sendAccessDeniedEmail() {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sittikorn.nice@gmail.com'; // เปลี่ยนเป็นอีเมลต้นทางที่ต้องการ
        $mail->Password = 'hiqr qfpz erju xmvu'; // ใส่รหัสแอป (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $admin_email = 'sittikorn.nice@gmail.com'; // ใช้ email เดียวกัน
        $mail->setFrom($admin_email, 'ระบบการนัดหมาย'); // ตั้งค่า email ผู้ส่งและชื่อที่แสดง
        $mail->addAddress($admin_email); // ส่งไปยังผู้ดูแลระบบ (admin)

        $mail->isHTML(true);
        $mail->Subject = '❌ มีความพยายามเข้าถึงระบบที่ไม่ได้รับอนุญาต';
        $mail->Body    = "<p>มีผู้ใช้พยายามเข้าถึงหน้าการจัดการนัดหมายโดยไม่มีสิทธิ์</p><p>โปรดตรวจสอบความปลอดภัยของระบบ</p>";
    
        $mail->send();
    } catch (Exception $e) {
        echo "❌ ไม่สามารถส่งอีเมลแจ้งเตือนได้: {$mail->ErrorInfo}";
    }
}

// ดึงข้อมูลนัดหมายที่อาจารย์ต้องจัดการ
$teacher_id = $_SESSION['user_id'];
$query = "
    SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.note, u.student_email
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.teacher_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การนัดหมายของอาจารย์</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>รายการการนัดหมาย</h2>
    <?php while ($appointment = $appointments->fetch_assoc()) { ?>
        <p>
            <?php 
            // แสดงข้อมูลนัดหมายและสถานะ
            echo "นัดหมาย: " . $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];

            if ($appointment['status'] == 'ยกเลิก') {
                echo " <strong>สถานะ: ถูกปฏิเสธ</strong><br>";
                echo "เหตุผล: " . $appointment['note'];
            } elseif ($appointment['status'] == 'เสร็จสมบูรณ์' || $appointment['status'] == 'กำลังดำเนินการ') {
                echo " <strong>สถานะ: " . $appointment['status'] . "</strong>";
            } else {
                echo " <a href='approve.php?id=" . $appointment['id'] . "&email=" . urlencode($appointment['student_email']) . "'>อนุมัติ</a> 
                <a href='reject.php?id=" . $appointment['id'] . "&email=" . urlencode($appointment['student_email']) . "'>ปฏิเสธ</a>";
            }
            ?>
        </p>
    <?php } ?>
</body>
</html>
