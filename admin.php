<?php
session_start();
include 'db.php';

// ถ้าไม่มีการเข้าสู่ระบบให้กลับไปหน้า login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// การออกจากระบบ
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}

// เพิ่มข้อมูลผู้ใช้
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // รับรหัสผ่านตรง ๆ (ตามที่ผู้ใช้พิมพ์)
    $role = $_POST['role'];
    $student_email = $_POST['student_email']; // ถ้าจะใช้ในข้อมูลนี้ให้นำไปใส่ในฟิลด์ของ table อื่น เช่น email

    $sql = "INSERT INTO users (username, password, role, student_email) 
            VALUES ('$username', '$password', '$role', '$student_email')";

    if ($conn->query($sql)) {
        $_SESSION['success'] = "เพิ่มข้อมูลผู้ใช้สำเร็จแล้ว!";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มข้อมูล!";
    }
    header("Location: admin.php");
    exit();
}

// ลบข้อมูลผู้ใช้
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($conn->query("DELETE FROM users WHERE id = $id")) {
        $_SESSION['success'] = "ลบข้อมูลผู้ใช้สำเร็จแล้ว!";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล!";
    }
    header("Location: admin.php");
    exit();
}

// โหลดค่าปัจจุบันจาก config.php
$config = include('config.php');
$current_location = $config['location'];

// ตรวจสอบว่ามีการส่งฟอร์มแก้ไขสถานที่หรือไม่
if (isset($_POST['update_location'])) {
    $new_location = trim($_POST['location']);

    // อัปเดตไฟล์ config.php
    $config_content = "<?php\nreturn [\n    'location' => '" . addslashes($new_location) . "',\n];\n";
    file_put_contents('config.php', $config_content);

    $_SESSION['success'] = "อัปเดตสถานที่สำเร็จ!";
    header("Location: admin.php");
    exit();
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$result_users = $conn->query("SELECT * FROM users");

// ดึงข้อมูล appointment
$result_appointments = $conn->query("SELECT * FROM appointments");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Admin Panel</a>
        <a href="admin.php?logout=true" class="btn btn-danger">ออกจากระบบ</a>
    </div>
</nav>

<div class="container mt-4">
    <!-- แสดงข้อความแจ้งเตือน -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <!-- Form เพิ่มผู้ใช้ -->
            <div class="card">
                <div class="card-header bg-success text-white">เพิ่มผู้ใช้ใหม่</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รหัสผ่าน</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">บทบาท</label>
                            <select name="role" class="form-select">
                                <option value="student">นักเรียน</option>
                                <option value="teacher">อาจารย์</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">อีเมลนักเรียน/อาจารย์</label>
                            <input type="email" name="student_email" class="form-control">
                        </div>
                        <button type="submit" name="add_user" class="btn btn-success w-100">เพิ่มผู้ใช้</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- ตารางรายชื่อผู้ใช้ -->
            <div class="card">
                <div class="card-header bg-primary text-white">รายชื่อผู้ใช้</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>บทบาท</th>
                                    <th>รหัสผ่าน</th> <!-- เปลี่ยนจาก "อีเมลนักเรียน/อาจารย์" เป็น "รหัสผ่าน" -->
                                    <th>อีเมลนักเรียน/อาจารย์</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= $row['username'] ?></td>
                                    <td><?= $row['role'] ?></td>
                                    <td><?= $row['password'] ?></td> <!-- แสดงรหัสผ่านจากฐานข้อมูล -->
                                    <td><?= $row['student_email'] ?></td> <!-- แสดงรหัสผ่านจากฐานข้อมูล -->
                                    <td>
                                        <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">แก้ไข</a>
                                        <a href="admin.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                           onclick="return confirm('ต้องการลบใช่หรือไม่?')">ลบ</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>  
                </div>
            </div>

           <!-- ตาราง Appointment Report -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white">รายงานการนัดหมาย</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>นักเรียน</th>
                        <th>อาจารย์</th>
                        <th>วันนัดหมาย</th>
                        <th>เวลา</th>
                        <th>สถานะ</th>
                        <th>สถานที่</th>
                        <th>หมายเหตุ</th>
                        <th>การจัดการ</th> <!-- คอลัมน์สำหรับปุ่มลบ -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ✅ ดึงข้อมูลพร้อมชื่อของนักเรียนและอาจารย์
                    $query = "
                        SELECT a.id, s.username AS student_name, t.username AS teacher_name, 
                               a.appointment_date, a.appointment_time, a.status, 
                               a.location, a.note 
                        FROM appointments a
                        LEFT JOIN users s ON a.student_id = s.id
                        LEFT JOIN users t ON a.teacher_id = t.id
                    ";
                    $result_appointments = $conn->query($query);

                    while ($row = $result_appointments->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['student_name'] ?></td>
                        <td><?= $row['teacher_name'] ?></td>
                        <td><?= $row['appointment_date'] ?></td>
                        <td><?= $row['appointment_time'] ?></td>
                        <td>
                            <?php 
                            // แสดงสถานะด้วยสีที่แตกต่าง
                            if ($row['status'] == 'รอดำเนินการ') {
                                echo '<span class="badge bg-warning text-dark">รอดำเนินการ</span>';
                            } elseif ($row['status'] == 'กำลังดำเนินการ') {
                                echo '<span class="badge bg-info text-white">กำลังดำเนินการ</span>';
                            } elseif ($row['status'] == 'เสร็จสมบูรณ์') {
                                echo '<span class="badge bg-success text-white">เสร็จสมบูรณ์</span>';
                            } else {
                                echo '<span class="badge bg-danger text-white">ยกเลิก</span>';
                            }
                            ?>
                        </td>
                        <td><?= $row['location'] ?></td>
                        <td><?= $row['note'] ?></td>
                        <td>
                            <!-- ปุ่มลบ -->
                            <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบนัดหมายนี้?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>  
    </div>
</div>

<?php
// ✅ ตรวจสอบว่ามีการส่งค่าลบเข้ามาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    // ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<script>alert('✅ ลบนัดหมายสำเร็จ'); window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('❌ ไม่สามารถลบนัดหมายได้');</script>";
    }
    $stmt->close();
}
?>


<div class="card mt-4">
    <div class="card-header bg-primary text-white"><i class="bi bi-geo-alt-fill"></i> อัปเดตสถานที่</div>
    <div class="card-body">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="location" class="form-label fw-bold">สถานที่:</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($current_location, ENT_QUOTES, 'UTF-8') ?>" class="form-control border-primary shadow-sm">
            </div>
            <button type="submit" name="update_location" class="btn btn-success w-100">
                <i class="bi bi-save"></i> บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
