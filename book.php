<?php 
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// ตรวจสอบว่ามีผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อนทำการจองนัดหมาย");
}
// อัพเดทข้อมูลล่าสุดจากไฟล์ config.php location
$config = include('config.php'); // โหลดค่าที่อัปเดตล่าสุด
$location = $config['location']; // ดึงค่าที่บันทึกล่าสุด
// ดึงอีเมลของนักศึกษาที่ล็อกอิน
$student_id = $_SESSION['user_id'];
$student_email = '';

$stmt = $conn->prepare("SELECT student_email FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($student_email);
$stmt->fetch();
$stmt->close();

// เช็คว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $note = $_POST['note'];
    $location = $_POST['location'];

    // ✅ บันทึกลงฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO appointments (student_id, teacher_id, appointment_date, appointment_time, note, location) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $student_id, $teacher_id, $date, $time, $note, $location);
    $stmt->execute();
    $stmt->close();

    // ✅ ส่งอีเมลแจ้งเตือน
    sendAppointmentEmail($student_email, 'sittikorn.nice@gmail.com', $date, $time, $location, $note);
    
    // ✅ เปลี่ยนเส้นทางไปยังหน้า dashboard
    header('Location: dashboard.php');
    exit;
}

// ✅ ฟังก์ชันส่งอีเมลแจ้งเตือนอาจารย์
function sendAppointmentEmail($from_email, $to_email, $date, $time, $location, $note) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sittikorn.nice@gmail.com'; // เปลี่ยนอีเมลที่ใช้ส่ง
        $mail->Password = 'hiqr qfpz erju xmvu'; // รหัสผ่านแอป (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ✅ กำหนดการเข้ารหัสข้อความให้เป็น UTF-8
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('sittikorn.nice@gmail.com', 'ระบบการนัดหมาย'); 
        $mail->addAddress($to_email); 
        $mail->addReplyTo($from_email); 

        $mail->isHTML(true);
        $mail->Subject = "📅 คำขอนัดหมายใหม่จากนักศึกษา - " . date('d/m/Y', strtotime($date));
        
        // ฟอร์แมตวันที่และเวลาให้เป็นแบบที่ Google Calendar รองรับ
        $start_time = strtotime($date . ' ' . $time);
        $end_time = $start_time + 3600;
        $formatted_start_date = date('Ymd\THis\Z', $start_time);
        $formatted_end_date = date('Ymd\THis\Z', $end_time);

        // สร้างลิงก์ Google Calendar
        $google_calendar_url = "https://www.google.com/calendar/render?action=TEMPLATE&text=คำขอนัดหมายจากนักศึกษา&dates=$formatted_start_date/$formatted_end_date&details=$note&location=$location";
        
        // ✅ ปรับให้รองรับ UTF-8 เต็มที่
        $mail->Body = "
            <h2>นักศึกษาขอนัดหมาย</h2>
            <p><b>อีเมลนักศึกษา:</b> " . htmlspecialchars($from_email, ENT_QUOTES, 'UTF-8') . "</p>
            <p><b>วันที่:</b> " . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . "</p>
            <p><b>เวลา:</b> " . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . "</p>
            <p><b>สถานที่:</b> " . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . "</p>
            <p><b>หมายเหตุ:</b> " . nl2br(htmlspecialchars($note, ENT_QUOTES, 'UTF-8')) . "</p>
            <p>กรุณาตรวจสอบและยืนยันนัดหมาย</p>
            <p><a href='$google_calendar_url' target='_blank'>คลิกที่นี่เพื่อเพิ่มลงใน Google Calendar ของคุณ</a></p>
            <p><a href='http://localhost:8080/อาจารย์อนุมาศ/login.php'>ไปที่ระบบ</a></p>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "❌ ไม่สามารถส่งอีเมลได้: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองนัดหมาย - <?= htmlspecialchars($student_email) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // ฟังก์ชันที่ใช้ตั้งค่า limit วันที่ใน input เป็นวันที่ปัจจุบัน
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date();
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0'); // เดือนจะเริ่มจาก 0
            const year = today.getFullYear();
            const formattedDate = year + '-' + month + '-' + day; // รูปแบบที่ input ต้องการ

            // กำหนดวันที่ใน input ให้ไม่สามารถเลือกวันที่ก่อนปัจจุบันได้
            document.getElementById('date').setAttribute('min', formattedDate);

            // ทำให้วันที่ในอนาคตแสดงเป็นสีเทา
            const dateInput = document.getElementById('date');
            dateInput.addEventListener('input', function () {
                const selectedDate = new Date(this.value);
                const todayDate = new Date();
                if (selectedDate < todayDate) {
                    this.style.color = 'gray'; // เปลี่ยนสีเป็นเทาหากเลือกวันที่เก่า
                } else {
                    this.style.color = 'black'; // ถ้าเลือกวันที่อนาคตหรือปัจจุบันเปลี่ยนเป็นสีดำ
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>จองนัดหมาย</h2>
        <form action="book.php" method="POST">
            <label for="teacher_id">เลือกอาจารย์:</label>
            <select id="teacher_id" name="teacher_id" required>
                <?php
                // ดึงข้อมูลอาจารย์จากฐานข้อมูลที่มีบทบาทเป็น 'teacher'
                $teachers = $conn->query("SELECT id, username FROM users WHERE role = 'teacher'");

                if ($teachers->num_rows > 0) {
                    while ($teacher = $teachers->fetch_assoc()) {
                        echo "<option value='{$teacher['id']}'>{$teacher['username']}</option>";
                    }
                } else {
                    echo "<option value=''>ไม่พบอาจารย์</option>";
                }
                ?>
            </select><br><br>

            <label for="date">วันที่นัดหมาย:</label>
            <input type="date" id="date" name="date" required><br><br>

            <label for="time">เวลานัดหมาย:</label>
            <input type="time" id="time" name="time" required><br><br>

            <label for="location">สถานที่:</label>
            <input type="text" id="location" name="location" value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>" required><br><br>

            <label for="email">อีเมลนักศึกษา:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($student_email) ?>" required readonly><br><br>

            <label for="note">หมายเหตุ (ถ้ามี):</label>
            <textarea id="note" name="note" rows="4" cols="50"></textarea><br><br>

            <button type="submit">จองนัดหมาย</button>
        </form>
    </div>
</body>
</html>
