<?php
session_start();
require 'connection.php';

if (isset($_POST['logbtn'])){
    //Lay input
    $userid = strtoupper(trim($_POST['Member_ID'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    if ($userid === '' || $password === '') {
    die("<script>alert('Bạn phải nhập cả User ID và Password');window.history.back();</script>");
}


    //Chuan bi va thuc thi truy van voiw placeholder

    $sql = "SELECT Member_Name, CONVERT(VARCHAR(MAX),
        dbo.fn_Decrypt(CONVERT(VARBINARY(MAX), CheckPass))
    ) AS clear_password
    FROM L00MEMBERASP
    WHERE Member_ID = CAST(? AS NVARCHAR)
    ";
    $params = [ $userid ];
    $fullname = $_POST['full_name'];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt == false){
        die("Data query error: \n" . print_r(sqlsrv_errors(), true));
    }
    // Lay dong du lieu
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row || !isset($row['clear_password'])){
        die("<script>alert('User ID không tồn tại');window.history.back();</script>");
    }
    // 6. So sánh mật khẩu
    $dbPassword = trim($row['clear_password']);
    if ($password == $dbPassword) {
        // Đăng nhập thành công
        session_start();
        $_SESSION['Member_ID']   = $userid;
        $_SESSION['full_name'] = $row['Member_Name'];

        header("Location: dashboard");
        exit();
    } else {
        // Đăng nhập thất bại
        echo "<script>alert('Sai tên tài khoản hoặc mật khẩu'); window.history.back();</script>";
        exit();
    }
}