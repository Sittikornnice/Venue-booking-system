<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ดึงข้อมูลผู้ใช้งาน
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ดึงข้อมูลสถานะของนัดหมายของนักศึกษา
$appointments_stmt = $conn->prepare("SELECT status FROM appointments WHERE student_id = ?");
$appointments_stmt->bind_param("i", $user_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();
$appointments = $appointments_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>ยินดีต้อนรับ, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <nav>
                <ul>
                    <li><a href="logout.php">ออกจากระบบ</a></li>
                    <li><a href="book.php">จองนัดหมาย</a></li>
                    <?php if ($role == 'student') { ?>
                        <li><a href="check_status.php">เช็คสถานะ</a></li>
                    <?php } ?>
                    <?php if ($role == 'teacher') { ?>
                        <li><a href="appointments.php">ดูนัดหมาย</a></li>
                        <li><a href="report.php">สรุปรายงาน</a></li> <!-- เพิ่มปุ่ม "สรุปรายงาน" -->
                    <?php } ?>
                </ul>
            </nav>
        </header>

        <div class="dashboard">
            <?php if ($role == 'student') { ?>
                <h2>นักเรียน</h2>
                <p>คุณสามารถจองนัดหมายกับอาจารย์ได้ที่นี่</p>
                <p>โปรดเลือกอาจารย์และเลือกวันที่ต้องการจอง</p>
                <a href="book.php" class="button">จองนัดหมาย</a>

                <h3>สถานะนัดหมายของคุณ</h3>
                <?php if (count($appointments) > 0) { ?>
                    <ul>
                        <?php foreach ($appointments as $appointment) { ?>
                            <li>สถานะ: <?php echo htmlspecialchars($appointment['status']); ?></li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p>ยังไม่มีนัดหมาย</p>
                <?php } ?>
            <?php } elseif ($role == 'teacher') { ?>
                <h2>อาจารย์</h2>
                <p>ตรวจสอบและจัดการการนัดหมายกับนักเรียนของคุณ</p>
                <a href="appointments.php" class="button">ดูนัดหมาย</a>
            <?php } ?>
        </div>
    </div>
</body>
</html>
