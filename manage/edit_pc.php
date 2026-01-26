<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ .'/../auth.php';
require 'record.php';


$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    die('Invalid ID');
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $p = array_map('trim', $_POST);
    // 2. Lấy dữ liệu cũ để so sánh
    $oldStm = sqlsrv_query(
        $conn,
        "SELECT Tool_ID, EmpID, EmpName, Department, Factory, EmployeeType,
                DevicesType, Status, Configuration,
                IssueDate, MaintenanceDay, Note
         FROM ASPEmpDevices
         WHERE AutoID = ?",
        [ $id ]
    );
    $old = sqlsrv_fetch_array($oldStm, SQLSRV_FETCH_ASSOC);

  $sql = "UPDATE ASPEmpDevices SET Tool_ID=?,
      EmpID=?,EmpName=?,Department=?,Factory=?,EmployeeType=?,
      DevicesType=?,Status=?,Configuration=?,
      IssueDate=?,MaintenanceDay=?,Note=?
    WHERE AutoID=?
  ";
  $params = [
    $p['Tool_ID'],
    $p['EmpID'],$p['EmpName'],$p['Department'],$p['Factory'],$p['EmployeeType'],
    $p['DevicesType'],$p['Status'],$p['Configuration'],
    $p['IssueDate'],$p['MaintenanceDay'],
    $p['Note'],$id
  ];
  $updStm = sqlsrv_query($conn, $sql, $params);

  if ($updStm) {
        // map DB column → label
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

        $changes = [];
        foreach ($fields as $col => $label) {
            $oldVal = $old[$col] ?? '';
            // nếu là DateTime → format
            if ($oldVal instanceof DateTime) {
                $oldVal = $oldVal->format('Y-m-d');
            }
            $newVal = $p[$col] ?? '';
            if ((string)$oldVal !== (string)$newVal) {
                $changes[] = sprintf(
                    "%s: '%s' → '%s'",
                    $label,
                    $oldVal,
                    $newVal
                );
            }
        }

        if (!empty($changes)) {
            $desc = sprintf(
              "%s đã sửa thông tin của %s: %s",
              $userID,
              $old['EmpID'],
              implode("; ", $changes)
            );
            logAction($conn, $userid, $desc);
        }

        $_SESSION['success'] = "Cập nhật thành công!";
    } else {
        $_SESSION['error'] = "Cập nhật thất bại!";
    }

    // 4.5. Redirect để reload table
    header('Location: manage_pc.php');
    exit;
}

// 5. Nếu GET, tải dữ liệu để hiển thị table (hoặc form nếu cần)
$stmt = sqlsrv_query($conn, "SELECT * FROM ASPEmpDevices ORDER BY AutoID");
$devices = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $devices[] = $r;
}
