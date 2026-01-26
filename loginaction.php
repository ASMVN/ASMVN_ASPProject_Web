<?php
// require 'auth.php';
require 'connection.php';
session_start();
if (isset($_POST['logbtn'])) {
    // 1. Lấy input
    $userid   = strtoupper(trim($_POST['Member_ID']   ?? ''));
    $password =               trim($_POST['password'] ?? '');

    if ($userid === '' || $password === '') {
        die("<script>
               alert('Bạn phải nhập cả User ID và Password');
               window.history.back();
             </script>");
    }
    // 2. Gọi Stored Procedure sp_ASPLoginV2
    $sql    = "{CALL sp_ASPLoginV2(?)}";
    $params = [ $userid ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die("Query error: " . print_r(sqlsrv_errors(), true));
    }


    // 3. Lấy dữ liệu trả về
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row) {
        die("<script>
               alert('User ID không tồn tại');
               window.history.back();
             </script>");
    }

    // 4. So sánh mật khẩu
    // SP alias cột giải mã là [Password]
    $dbPassword = trim($row['Password'] ?? '');
    if ($password !== $dbPassword) {
        die("<script>
               alert('Sai tên tài khoản hoặc mật khẩu');
               window.history.back();
             </script>");
    }
    // 5. Đăng nhập thành công, set session
    $_SESSION['Member_ID']      = $row['Username'];   // hoặc $userid
    $_SESSION['full_name']      = $row['Ten_CbNv'];   // Ten_CbNv từ SP
    $_SESSION['Ma_CbNv']        = $row['Ma_CbNv'];
    $_SESSION['Ma_Bp']          = $row['Ma_Bp'];
    // Nếu đã mở rộng SP trả về 3 cột quyền:
    if (isset($row['IsHRAbsenceMng'])) {
        $_SESSION['IsHRAbsenceMng']      = (int)$row['IsHRAbsenceMng'];
        $_SESSION['IsHRAdmin']           = (int)$row['IsHRAdmin'];
        $_SESSION['IsHRAbsenceMngEmp']   = (int)$row['IsHRAbsenceMngEmp'];
    }


    header("Location: dashboard");
    exit();
}