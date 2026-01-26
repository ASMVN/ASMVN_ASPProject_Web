<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ .'/../auth.php';
require 'record.php';

// 3) Nếu form POST lên mới xử lý insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3.1) Trim đầu vào
    $p = array_map('trim', $_POST);

    // 3.2) Chuẩn bị câu INSERT
    $sql = "INSERT INTO ASPEmpDevices (Tool_ID,
        EmpID, EmpName, Department, Factory, EmployeeType,
        DevicesType, Status, Configuration,
        IssueDate, MaintenanceDay, Note
      ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
      )
    ";
    $params = [
      $p['Tool_ID'],
      $p['EmpID'], $p['EmpName'], $p['Department'], $p['Factory'], 
      $p['EmployeeType'], $p['DevicesType'], $p['Status'], 
      $p['Configuration'],
      $p['IssueDate'], $p['MaintenanceDay'], $p['Note']
    ];

    // 3.3) Thực thi INSERT và bắt lỗi ngay
    $stmtInsert = sqlsrv_query($conn, $sql, $params);
    if ($stmtInsert === false) {
        $err = print_r(sqlsrv_errors(), true);
        error_log("[ADD-ERROR] $err");
        $_SESSION['error'] = "Thêm mới thất bại: lỗi SQL, xem log để biết thêm";
        header('Location: add_pc.php');
        exit;
    }

    // 3.4) Kiểm tra số row vừa ảnh hưởng
    $rowsAffected = sqlsrv_rows_affected($stmtInsert);
    if ($rowsAffected === false) {
        $_SESSION['error'] = "Thêm mới thất bại: không xác định được kết quả.";
        header('Location: add_pc.php');
        exit;
    }
    if ($rowsAffected === 0) {
        $_SESSION['error'] = "Thêm mới thất bại: không có bản ghi nào được thêm.";
        header('Location: add_pc.php');
        exit;
    }

    // 3.5) Lấy ID mới (nếu cần)
    $idRes   = sqlsrv_query($conn, "SELECT CAST(SCOPE_IDENTITY() AS INT) AS NewID");
    $rowId   = sqlsrv_fetch_array($idRes, SQLSRV_FETCH_ASSOC);
    $newID   = $rowId['NewID'] ?? 0;

    // 3.6) Ghi log giống phần sửa: “ASPxxx đã thêm thông tin của ASPyyy: Cột1: '…'; Cột2: '…'…”
    $fields = [
      'Tool_ID'       => 'Mã CCDC',
      'EmpID'         => 'Mã nhân viên',
      'EmpName'       => 'Tên nhân viên',
      'Department'    => 'Phòng ban',
      'Factory'       => 'Nhà máy',
      'EmployeeType'  => 'Loại NV',
      'DevicesType'   => 'Loại thiết bị',
      'Status'        => 'Trạng thái',
      'Configuration' => 'Cấu hình',
      'IssueDate'     => 'Ngày cấp',
      'MaintenanceDay'=> 'Ngày bảo trì',
      'Note'          => 'Ghi chú'
    ];
    $parts = [];
    foreach ($fields as $col => $label) {
        $val = $p[$col] ?? '';
        $parts[] = sprintf("%s: '%s'", $label, $val);
    }
    $desc = sprintf(
      "%s đã thêm thông tin của %s: %s",
      $userid,
      $p['EmpID'],
      implode("; ", $parts)
    );
    logAction($conn, $userid, $desc);

    // 3.7) Redirect về manage_pc
    $_SESSION['success'] = "Thêm mới thành công!";
    header('Location: manage_pc.php');
    exit;
}

// 4) Nếu GET — chỉ hiển thị form (hoặc redirect)
header('Location: manage_pc.php');
exit;