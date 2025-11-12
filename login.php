<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // รับรหัสผ่านที่ผู้ใช้ป้อน

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // ตรวจสอบรหัสผ่านแบบปกติ (ไม่ใช้ password_verify)
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<div class='alert alert-danger d-flex justify-content-center align-items-center' role='alert'>ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</div>";
    }    
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header text-center">
                        <h4>เข้าสู่ระบบ</h4>
                    </div>
                    <div class="card-body">
                        <form action="login.php" method="POST">
                            <div class="form-group">
                                <label for="username">ชื่อผู้ใช้:</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="password">รหัสผ่าน:</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>ยังไม่มีบัญชีผู้ใช้? <a href="register.php">ลงทะเบียน</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- รวม JavaScript ของ Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
