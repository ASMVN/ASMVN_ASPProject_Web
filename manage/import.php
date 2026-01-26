<?php
// manage/import_excel.php
require_once __DIR__ .'/../auth.php';
require_once __DIR__ . '/../connection.php';
require 'record.php';
require_once __DIR__ . '/../libs/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

// 2. Kiểm file upload
if (empty($_FILES['excelfile']['tmp_name'])
  || $_FILES['excelfile']['error'] !== UPLOAD_ERR_OK
) {
    $_SESSION['error'] = "Chưa chọn file hoặc upload lỗi";
    header('Location: manage_pc.php');
    exit;
}

// 2) Parse Excel
if (! $xlsx = SimpleXLSX::parse($_FILES['excelfile']['tmp_name'])) {
    $_SESSION['error'] = "Lỗi đọc Excel: " . SimpleXLSX::parseError();
    header('Location: manage_pc.php');
    exit;
}

// 3) Lấy nội dung, bỏ header row
$rows = $xlsx->rows();
array_shift($rows);

// 4) Chuẩn bị câu INSERT (14 cột)
$sql      = "INSERT INTO ASPEmpDevices
    (Tool_ID,EmpID,EmpName,Department,Factory,EmployeeType,
     DevicesType,Status,Configuration,
     IssueDate,MaintenanceDay,Note)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$params   = [];
$inserted = 0;

// 5) Duyệt tất cả rows
foreach ($rows as $i => $r) {
    // 5.1) Đảm bảo có ít nhất 14 phần tử
    $r = array_slice($r, 1);
    $r = array_pad($r, 14, '');

    // 5.2) Bỏ qua nếu EmpID (cột 0) trống
    if (trim($r[0]) === '') {
        continue;
    }

    // 5.3) Trim từng giá trị
    $params = array_map('trim', $r);

    // 5.4) Thực thi INSERT
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        // Log lỗi chi tiết để debug
        error_log("[IMPORT-ERROR row ".($i+2)."] " . print_r(sqlsrv_errors(), true));
        continue;  // bỏ qua dòng lỗi, tiếp tục các dòng sau
    }

    $inserted++;
}

// 6) Ghi AuditLog nếu có bản ghi mới
error_log("IMPT DEBUG: inserted = $inserted, userid = {$_SESSION['Member_ID']}");


$userid = $_SESSION['Member_ID'] ?? 'unknown';
if ($inserted > 0) {
    $desc = sprintf("%s đã import %d bản ghi từ Excel", $userid, $inserted);
    logAction($conn, $userid, $desc);
    $_SESSION['success'] = "Import thành công {$inserted} bản ghi.";
} else {
    $_SESSION['error'] = "Không có bản ghi nào được import.";
}

// 7) Quay về manage_pc.php và hiện flash
header('Location: manage_pc.php');
exit;
