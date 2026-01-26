<?php
require_once __DIR__ . '/../connection.php';
/**
 * Ghi log hành động của user vào AuditLog
 *
 * @param resource $conn     Kết nối sqlsrv
 * @param string   $userid  Mã nhân viên ($_SESSION['Member_ID'])
 * @param string   $desc     Mô tả hành động
 */
function logAction($conn, $userid, $desc) {
    // đảm bảo timezone UTC+7
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $now = date('Y-m-d H:i:s');

    $sql    = "INSERT INTO AuditLog (ActionTime, UserID, ActionDesc)
               VALUES (?, ?, ?)";
    $params = [ $now, $userid, $desc ];

    sqlsrv_query($conn, $sql, $params);
}