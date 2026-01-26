<?php
require_once __DIR__ . '/../connection.php';
header('Content-Type: application/json; charset=utf-8');

$AutoID = intval($_POST['id'] ?? 0);
if (!$AutoID) exit(json_encode(['success'=>false]));


$checkSql  = "SELECT AHDStatus, ABODStatus, HRStatus 
              FROM ASPHRAbsenceMng WHERE AutoID=?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$AutoID]);
$a       = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if (!$a) {
    echo json_encode(['success'=>false,'message'=>'Không tìm thấy đơn phép']);
    exit;
}

// 🚫 Nếu đơn đã được duyệt ở bất kỳ cấp nào thì KHÔNG cho hủy
if ($a['AHDStatus']==1 || $a['ABODStatus']==1 || $a['HRStatus']==1) {
    echo json_encode(['success'=>false,'message'=>'Đơn đã được duyệt, không thể hủy']);
    exit;
}

$sql    = "{CALL sp_ASPUpdateCancelHRAbsenceV2(?)}";
$params = [ [$AutoID, SQLSRV_PARAM_IN] ];
$stmt   = sqlsrv_query($conn, $sql, $params);


echo json_encode(['success' => $stmt!==false]);