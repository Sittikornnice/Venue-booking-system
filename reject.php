<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// ตรวจสอบว่าส่งค่า id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ ไม่พบนัดหมาย");
}

$appointment_id = $_GET['id'];

// ✅ ใช้ prepared statement เพื่อดึงข้อมูลนัดหมายและอีเมลนักศึกษา
$stmt = $conn->prepare("
    SELECT a.appointment_date, a.appointment_time, u.student_email 
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

// ✅ ตรวจสอบว่าพบข้อมูลหรือไม่
if (!$appointment) {
    die("❌ ไม่พบนัดหมายในระบบ");
}

$recipient_email = $appointment['student_email'];
$appointment_date = $appointment['appointment_date'];
$appointment_time = $appointment['appointment_time'];

// ✅ ตรวจสอบว่าอีเมลไม่เป็นค่าว่างหรือ NULL
if (empty($recipient_email)) {
    die("❌ ไม่พบอีเมลของนักศึกษา");
}

// หากฟอร์มถูกส่งมา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = $_POST['note'];

    // ✅ อัปเดตสถานะเป็น "ยกเลิก" และเก็บหมายเหตุ
    $stmt = $conn->prepare("UPDATE appointments SET status = 'ยกเลิก', note = ? WHERE id = ?");
    $stmt->bind_param("si", $note, $appointment_id);
    $stmt->execute();

    // ✅ ส่งอีเมลแจ้งเตือน
    sendRejectionEmail($recipient_email, $appointment_date, $appointment_time, $note);

    echo "✅ การปฏิเสธเสร็จสมบูรณ์";
    header("refresh:2; url=dashboard.php");
}

// ✅ ฟังก์ชันส่งอีเมลแจ้งเตือน
function sendRejectionEmail($to, $date, $time, $note) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sittikorn.nice@gmail.com'; // เปลี่ยนเป็นอีเมลต้นทางที่ต้องการ
        $mail->Password = 'hiqr qfpz erju xmvu'; // ใส่รหัสแอป (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ✅ ตรวจสอบอีเมลก่อนเพิ่มเข้า PHPMailer
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("❌ อีเมลไม่ถูกต้อง: $to");
        }
          // ✅ กำหนดการเข้ารหัสข้อความให้เป็น UTF-8
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('sittikorn.nice@gmail.com', 'ระบบการนัดหมาย'); // ตั้งค่าอีเมลผู้ส่ง
        $mail->addAddress($to); // ✅ ส่งไปยังอีเมลของนักศึกษา

        $mail->isHTML(true);
        $mail->Subject = '❌ การนัดหมายถูกปฏิเสธ';
        $mail->Body    = "<p>การนัดหมายของคุณในวันที่ <b>$date</b> เวลา <b>$time</b> ถูกปฏิเสธ</p><p><b>เหตุผล:</b> $note</p>";

        $mail->send();
    } catch (Exception $e) {
        echo "❌ Mailer Error: " . $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิเสธการนัดหมาย</title>
    <!-- เพิ่มการเชื่อมโยงกับ Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJx3XnRpXZA3t8Gk5KjuRkWbZJ6L5TbO2O8Ol8YyY/2FQOZmdz/mJDRXlsn7" crossorigin="anonymous">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        textarea {
            width: 100%;
            height: 150px;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #dc3545;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
        }
        button:hover {
            background-color: #c82333;
        }
        h2 {
    text-align: center;
}

         
    </style>
</head>
<body class="d-flex justify-content-center align-items-center" style="height: 100vh; background-color: #f0f2f5;">
    <div class="container">
        <div class="text-center mb-4">
            <h2>กรุณากรอกหมายเหตุการปฏิเสธ</h2>
        </div>
        <div class="form-container">
            <form method="POST">
                <div class="mb-3">
                    <label for="note" class="form-label">กรุณากรอกเหตุผลที่ปฎิเสธ</label>
                    <textarea id="note" name="note" placeholder="กรอกเหตุผลที่ปฏิเสธ" required></textarea>
                </div>
                <button type="submit" class="btn btn-danger">ปฏิเสธการนัดหมาย</button>
            </form>
        </div>
    </div>

    <!-- เพิ่ม JavaScript สำหรับ Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7S6VQeYu7SsjIjQpK6Q5fN1TcVrENnV0shF9FkvEv4pZbm1Hq3exb5t6mymB6I" crossorigin="anonymous"></script>
</body>
</html>
