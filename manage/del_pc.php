<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../auth.php';
require 'record.php';

// 1. Lấy mảng ID từ URL (GET) hoặc có thể đổi thành $_POST
$raw = $_GET['id'] ?? [];
$ids = array_filter(
    array_map('intval', (array)$raw),
    fn($v) => $v > 0
);

if (empty($ids)) {
    $_SESSION['error'] = "Bạn phải chọn ít nhất 1 bản ghi hợp lệ để xóa.";
    header('Location: manage_pc.php');
    exit;
}

// 2. Tạo chuỗi placeholder cho IN (…) ví dụ "?, ?, ?"
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// 3. Lấy EmpID của các bản ghi sẽ xóa để ghi log sau
$selectSql = "SELECT AutoID, EmpID
              FROM ASPEmpDevices
              WHERE AutoID IN ($placeholders)";
$selectStmt = sqlsrv_query($conn, $selectSql, $ids);

$rows = [];
while ($r = sqlsrv_fetch_array($selectStmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $r;
}

// 4. Thực thi xóa nhiều bản ghi cùng lúc
$deleteSql = "DELETE FROM ASPEmpDevices
              WHERE AutoID IN ($placeholders)";
$deleteStmt = sqlsrv_query($conn, $deleteSql, $ids);

if ($deleteStmt) {
    // 5. Ghi log cho từng bản ghi đã xóa
    foreach ($rows as $r) {
        $desc = sprintf("%s đã xóa thông tin của %s", $userid, $r['EmpID']);
        logAction($conn, $userid, $desc);
    }

    $_SESSION['success'] = sprintf(
        "Đã xóa thành công %d bản ghi.",
        count($ids)
    );
} else {
    $_SESSION['error'] = "Xóa thất bại. Vui lòng thử lại.";
}

header('Location: manage_pc.php');
exit;
