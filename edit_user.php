<?php
session_start();
include 'db.php';

$update_success = false; // Default value for success flag

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
}

if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $student_email = $_POST['student_email'];
    $new_password = $_POST['password'];

    // ตรวจสอบว่าผู้ใช้พิมพ์รหัสผ่านใหม่หรือไม่
    if (!empty($new_password)) {
        $password_update = "password = '$new_password',"; // ใช้รหัสผ่านใหม่
    } else {
        $password_update = ""; // ไม่เปลี่ยนรหัสผ่าน
    }

    // อัปเดตข้อมูล
    $conn->query("UPDATE users SET username='$username', $password_update role='$role', student_email='$student_email' WHERE id=$id");
    
    header("Location: admin.php?success=1"); // Redirect with success parameter
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="admin.php">Admin Panel</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">แก้ไขข้อมูลผู้ใช้</div>
                <div class="card-body">
                    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                        <div class="alert alert-success" role="alert">
                            อัปเดตข้อมูลสำเร็จแล้ว!
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" name="username" value="<?= $user['username'] ?>" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">รหัสผ่าน (ถ้าไม่ต้องการเปลี่ยน ให้เว้นว่าง)</label>
                            <input type="text" name="password" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">บทบาท</label>
                            <select name="role" class="form-select">
                                <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>นักเรียน</option>
                                <option value="teacher" <?= $user['role'] == 'teacher' ? 'selected' : '' ?>>อาจารย์</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">อีเมลนักเรียน/อาจารย์</label>
                            <input type="email" name="student_email" value="<?= $user['student_email'] ?>" class="form-control">
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="update_user" class="btn btn-warning">อัปเดตข้อมูล</button>
                            <a href="admin.php" class="btn btn-secondary mt-2">กลับ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
