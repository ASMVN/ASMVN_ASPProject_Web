<?php
require_once __DIR__ .'/../connection.php';

$AutoID = intval($_POST['id']);
$TimeOff   = $_POST['date']   ?? '';
$NumDateOff   = floatval($_POST['days'] ?? 0);
$ReasonOfAbsence = $_POST['reason'] ?? '';
$TypeOfAbsence   = $_POST['type']   ?? '';

if (!$AutoID || !$TimeOff || !$NumDateOff || !$TypeOfAbsence) {
    echo json_encode(['success'=>false,'message'=>'Thiếu dữ liệu đầu vào']);
    exit;
}

// 1. Check trạng thái đơn trước
$checkSql = "SELECT AHDStatus, ABODStatus, HRStatus 
             FROM ASPHRAbsenceMng WHERE AutoID=?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$AutoID]);
$a = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if (!$a) {
    echo json_encode(['success'=>false,'message'=>'Không tìm thấy đơn phép']);
    exit;
}

// 🚫 Nếu đơn đã được duyệt ở bất kỳ cấp nào thì KHÔNG cho sửa
if ($a['AHDStatus']==1 || $a['ABODStatus']==1 || $a['HRStatus']==1) {
    echo json_encode(['success'=>false,'message'=>'Đơn đã được duyệt, không thể sửa']);
    exit;
}


// Gọi Stored Procedure
$sql = "{CALL sp_ASPEditHRAbsenceV2 (?, ?, ?, ?, ?)}";
$params = [
    [$AutoID ,SQLSRV_PARAM_IN],
    [$TimeOff,   SQLSRV_PARAM_IN],
    [$NumDateOff,   SQLSRV_PARAM_IN],
    [$ReasonOfAbsence, SQLSRV_PARAM_IN],
    [$TypeOfAbsence,   SQLSRV_PARAM_IN],
];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
  $err = print_r(sqlsrv_errors(), true);
  echo json_encode(['success'=>false, 'message'=>$err]);
  exit;
}
echo json_encode(['success'=>true]);

