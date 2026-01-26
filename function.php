<?php
// functions.php
include 'connection.php';

/**
 * Kiểm tra xem member có quyền với ProgramID không
 * @param resource $conn   Kết nối sqlsrv
 * @param string      $userid Member_ID
 * @param string   $program Object_ID (ví dụ 'DEVICE_MANAGEMENT', 'ABSENCE_MANAGEMENT')
 * @return bool
 */
function hasPermission($conn, $userid, $program) {
    $sql = "SELECT COUNT(*) AS cnt
      FROM L00MemberASP AS m
      LEFT JOIN L00PermissionASP AS p
      ON p.Member_ID = m.Member_ID AND p.Object_ID=?
      WHERE m.Member_ID = ?
        AND (m.Is_Admin = 1 OR p.Object_ID IS NOT NULL)
    ";
    $params = [ $program, $userid ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        // In lỗi ra log để debug
        error_log("hasPermission SQL error: " . print_r(sqlsrv_errors(), true));
        return false;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $count = $row['cnt'] ?? 0;
    return $count > 0;
}
