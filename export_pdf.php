<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ตรวจสอบว่าเป็นผู้ใช้ที่มีสิทธิ์หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// รับค่าช่วงวันที่จาก URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// คิวรีข้อมูลการนัดหมาย
$query = "SELECT 
            students.username AS student_name, 
            teachers.username AS teacher_name, 
            appointments.appointment_date, 
            appointments.appointment_time, 
            appointments.status 
          FROM appointments 
          JOIN users AS students ON appointments.student_id = students.id 
          JOIN users AS teachers ON appointments.teacher_id = teachers.id 
          WHERE appointments.appointment_date BETWEEN ? AND ? 
          ORDER BY appointments.appointment_date, appointments.appointment_time";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// ตั้งค่า DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isFontSubsettingEnabled', true);

// ตั้งค่าฟอนต์
$fontDir = __DIR__ . '/storage/fonts/';
$options->set('fontDir', $fontDir);
$options->set('fontCache', $fontDir);
$options->set('chroot', $fontDir);
$options->set('defaultFont', 'Sarabun');

$dompdf = new Dompdf($options);

// ใส่ HTML ใน PDF
$html = '<style>
    body {
        font-family: "Sarabun", sans-serif;
        font-size: 12pt;
    }

    h2 {
        font-family: "Sarabun", sans-serif;
        text-align: center;
        margin-bottom: 20px;
    }

    p {
        font-family: "Sarabun", sans-serif;
        text-align: center;
        margin-bottom: 30px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        border: 1px solid black;
        padding: 12px;
        text-align: center;
        font-family: "Sarabun", sans-serif;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    td {
        background-color: #ffffff;
    }

    thead th {
        text-align: center; /* ทำให้หัวข้ออยู่ตรงกลาง */
    }

    tbody td {
        text-align: center; /* ทำให้ข้อมูลในแถวอยู่ตรงกลาง */
    }
</style>';

$html .= '<p>สรุปรายงานการนัดหมาย</p>';
$html .= '<p>ช่วงวันที่ ' . htmlspecialchars($start_date) . ' ถึง ' . htmlspecialchars($end_date) . '</p>';
$html .= '<table>
            <thead>
                <tr>
                    <td>ชื่อนักศึกษา</td>
                    <td>อาจารย์</td>
                    <td>วันที่</td>
                    <td>เวลา</td>
                    <td>สถานะ</td>
                </tr>
            </thead>
            <tbody>';

foreach ($appointments as $appointment) {
    $html .= '<tr>
                <td><span>' . htmlspecialchars($appointment['student_name']) . '</span></td>
                <td><span>' . htmlspecialchars($appointment['teacher_name']) . '</span></td>
                <td><span>' . htmlspecialchars($appointment['appointment_date']) . '</span></td>
                <td><span>' . htmlspecialchars($appointment['appointment_time']) . '</span></td>
                <td><span>' . htmlspecialchars($appointment['status']) . '</span></td>
              </tr>';
}

$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// แสดง PDF ใน browser
$dompdf->stream("report.pdf", array("Attachment" => 1));  // เปลี่ยน Attachment เป็น 1 เพื่อดาวน์โหลดไฟล์ PDF

exit;
