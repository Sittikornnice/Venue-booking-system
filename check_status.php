<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

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
    <title>เช็คสถานะนัดหมาย</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>เช็คสถานะนัดหมาย</h1>
            <nav>
                <ul>
                    <li><a href="logout.php">ออกจากระบบ</a></li>
                    <li><a href="book.php">จองนัดหมาย</a></li>
                    <li><a href="check_status.php">เช็คสถานะ</a></li>
                    <?php if ($_SESSION['role'] == 'teacher') { ?>
                        <li><a href="appointments.php">ดูนัดหมาย</a></li>
                    <?php } ?>
                </ul>
            </nav>
        </header>

        <div class="dashboard">
            <h2>สถานะนัดหมายของคุณ</h2>
            <?php if (count($appointments) > 0) { ?>
                <ul>
                    <?php foreach ($appointments as $appointment) { ?>
                        <li>สถานะ: <?php echo htmlspecialchars($appointment['status']); ?></li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p>ยังไม่มีนัดหมาย</p>
            <?php } ?>
        </div>
    </div>
</body>
</html>
