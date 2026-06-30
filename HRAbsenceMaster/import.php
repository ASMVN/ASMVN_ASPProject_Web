<?php
require_once __DIR__ .'/../auth.php';
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../libs/SimpleXLSX.php';


use Shuchkin\SimpleXLSX;

$userid = $_SESSION['Member_ID'] ?? 0;

// 2. Kiểm file upload
if (empty($_FILES['excelfile']['tmp_name'])
  || $_FILES['excelfile']['error'] !== UPLOAD_ERR_OK
) {
    $_SESSION['error'] = "Chưa chọn file hoặc upload lỗi";
    header('Location: importMaster');
    exit;
}

// 2) Parse Excel
if (! $xlsx = SimpleXLSX::parse($_FILES['excelfile']['tmp_name'])) {
    $_SESSION['error'] = "Lỗi đọc Excel: " . SimpleXLSX::parseError();
    header('Location: importMaster');
    exit;
}

// 3) Lấy nội dung, bỏ header row
$rows = $xlsx->rows();
array_shift($rows);

// 4) Chuẩn bị câu INSERT (11 cột)
$sql      = "INSERT INTO ASPHRAbsenceMaster
    (EmpID,RemainLeaves,BonusLeaves,AnualLeaves,
     DownLeaves,UpLeaves,RemainLeavesExpiredDate,
     LunarYearLeaves,CumulativeTotal,CurrentYear,LastYear, CreatedBy, CreatedDate)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";

$inserted = 0;

// 5) Duyệt tất cả rows
foreach ($rows as $i => $r) {
    // 5.1) Đảm bảo có ít nhất 13 phần tử
    $r = array_slice($r, 1);
    $r = array_pad($r, 11, '');

    // 5.2) Bỏ qua nếu EmpID (cột 0) trống
    if (trim($r[0]) === '') {
        continue;
    }

    // 5.3) Trim từng giá trị
    $params = array_map('trim', $r);
    $params  [] = $userid;

    // 5.4) Thực thi INSERT
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        // Log lỗi chi tiết để debug
        error_log("[IMPORT-ERROR row ".($i+2)."] " . print_r(sqlsrv_errors(), true));
        continue;  // bỏ qua dòng lỗi, tiếp tục các dòng sau
    }

    $inserted++;
}

// // 6) Thông báo status import
$total = count($rows);

if ($inserted > 0) {
    $_SESSION['success'] = "Import thành công {$inserted}/{$total} dòng.";
} else {
    $_SESSION['error'] = "Không import được dòng nào. Vui lòng kiểm tra file Excel.";
}


// 7) Quay về manage_pc.php và hiện flash
header('Location: importMaster');
exit;