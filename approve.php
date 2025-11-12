<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// ตรวจสอบสิทธิ์ (ต้องเป็นอาจารย์เท่านั้น)
if ($_SESSION['role'] !== 'teacher') {
    die("Access denied");
}

// ตรวจสอบว่าได้รับ ID นัดหมายหรือไม่ และตรวจสอบว่า ID เป็นตัวเลข
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ไม่พบนัดหมายที่ต้องการ");
}

$appointment_id = (int)$_GET['id']; // แปลงเป็นตัวเลขเพื่อความปลอดภัย

// ✅ ดึงข้อมูลอีเมลของนักศึกษาจากตาราง users โดยใช้ student_id
$stmt = $conn->prepare("
    SELECT u.student_email, a.appointment_date, a.appointment_time, a.status 
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

// เช็คว่าพบข้อมูลหรือไม่
if (!$appointment || empty($appointment['student_email'])) {
    die("❌ ไม่พบอีเมลของผู้รับ หรือไม่มีข้อมูลนัดหมาย");
}

// เช็คสถานะนัดหมายก่อนทำการอนุมัติ
if ($appointment['status'] !== 'รอดำเนินการ') {
    die("❌ สถานะนัดหมายไม่สามารถดำเนินการได้เนื่อจากได้กด อนุมัติไปเรียบร้อยแล้ว");
}

$recipient_email = $appointment['student_email'];
$appointment_date = $appointment['appointment_date'];
$appointment_time = $appointment['appointment_time'];

// ✅ อัปเดตสถานะเป็น "กำลังดำเนินการ"
$stmt = $conn->prepare("UPDATE appointments SET status = 'กำลังดำเนินการ' WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();

// ✅ ส่งอีเมลแจ้งเตือน
sendApprovalEmail($recipient_email, $appointment_date, $appointment_time);

// ✅ อัปเดตสถานะเป็น "เสร็จสมบูรณ์" หลังจากส่งอีเมล
$stmt = $conn->prepare("UPDATE appointments SET status = 'เสร็จสมบูรณ์' WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();

// ✅ รีไดเร็กต์ไปยังหน้า appointments.php พร้อมแจ้งเตือน
echo "<script>
    alert('✅ การยืนยันเสร็จสมบูรณ์');
    window.location.href = 'dashboard.php';
</script>";

// ✅ ฟังก์ชันส่งอีเมลแจ้งเตือน
function sendApprovalEmail($to, $date, $time) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sittikorn.nice@gmail.com'; // เปลี่ยนเป็นอีเมลต้นทางที่ต้องการ
        $mail->Password = 'hiqr qfpz erju xmvu'; // ใส่รหัสผ่านของแอป (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ตรวจสอบอีเมลก่อนเพิ่มเข้า PHPMailer
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("❌ อีเมลไม่ถูกต้อง: $to");
        }
        // ✅ กำหนดการเข้ารหัสข้อความให้เป็น UTF-8
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('sittikorn.nice@gmail.com', 'ระบบการนัดหมาย'); // ตั้งค่าอีเมลผู้ส่ง
        $mail->addAddress($to); // ✅ ส่งไปยังอีเมลของนักศึกษา

        $mail->isHTML(true);
        $mail->Subject = '✅ การนัดหมายได้รับการอนุมัติ';
        $mail->Body    = "
            <h2>การนัดหมายของคุณได้รับการอนุมัติ</h2>
            <p><b>วันที่:</b> $date</p>
            <p><b>เวลา:</b> $time</p>
            <p>ขอบคุณที่ใช้บริการระบบการนัดหมาย</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "❌ Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
