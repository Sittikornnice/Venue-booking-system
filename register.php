<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // รับรหัสผ่านตรง ๆ (ตามที่ผู้ใช้พิมพ์)
    $role = $_POST['role'];
    $student_email = $_POST['student_email'];

    // บันทึกลงฐานข้อมูลโดยตรง (ไม่เข้ารหัส)
    $stmt = $conn->prepare("INSERT INTO users (username, password, role, student_email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $role, $student_email);

    if ($stmt->execute()) {
        echo "<script>alert('ลงทะเบียนสำเร็จ!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด!'); window.location.href='register.php';</script>";
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 500px;
            margin-top: 80px;
        }
        h2 {
            color: #343a40;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h2>ลงทะเบียนผู้ใช้</h2>
            </div>
            <div class="card-body">
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="username">ชื่อ-นามสกุล:</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="password">รหัสผ่าน:</label>
                        <input type="text" id="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="student_email">อีเมล:</label>
                        <input type="email" id="student_email" name="student_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="role">บทบาท:</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="student">นักเรียน</option>
                            <option value="teacher">อาจารย์</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">ลงทะเบียน</button>
                </form>
            </div>
            <div class="card-footer footer">
                <p>มีบัญชีผู้ใช้แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
