<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// ตรวจสอบค่าที่ส่งมาจากฟอร์ม
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d'); // กำหนดให้ default เป็นวันนี้

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// คิวรีข้อมูลการนัดหมายโดยกรองเฉพาะวันที่ที่เลือก
$query = "SELECT 
            students.username AS student_name, 
            teachers.username AS teacher_name, 
            appointments.appointment_date, 
            appointments.appointment_time, 
            appointments.status 
          FROM appointments 
          JOIN users AS students ON appointments.student_id = students.id 
          JOIN users AS teachers ON appointments.teacher_id = teachers.id 
          WHERE appointments.appointment_date = ? 
          ORDER BY appointments.appointment_date, appointments.appointment_time";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $start_date);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปรายงานการนัดหมาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">สรุปรายงานการนัดหมาย</h1>
        
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">จากวันที่:</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">กรอง</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered text-center">
                <thead class="table-dark">
                    <tr>
                        <th>ชื่อนักศึกษา</th>
                        <th>อาจารย์</th>
                        <th>วันที่</th>
                        <th>เวลา</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0) { ?>
                        <?php foreach ($appointments as $appointment) { ?>
                            <tr>
                                <td><?= htmlspecialchars($appointment['student_name']); ?></td>
                                <td><?= htmlspecialchars($appointment['teacher_name']); ?></td>
                                <td><?= htmlspecialchars($appointment['appointment_date']); ?></td>
                                <td><?= htmlspecialchars($appointment['appointment_time']); ?></td>
                                <td><span class="badge bg-<?php 
                                    echo ($appointment['status'] == 'approved') ? 'success' : 
                                         (($appointment['status'] == 'pending') ? 'warning' : 'danger'); 
                                ?>">
                                    <?= htmlspecialchars($appointment['status']); ?>
                                </span></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" class="text-muted">ไม่มีข้อมูลในวันที่เลือก</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="export_pdf.php?start_date=<?= urlencode($start_date); ?>" class="btn btn-danger">Export PDF</a>
            <a href="dashboard.php" class="btn btn-secondary">กลับหน้าหลัก</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
