<?php
require_once __DIR__ . '/../connection.php'; // file kết nối SQL Server bằng sqlsrv_connect()


if (!isset($_GET['donvi']) || !isset($_GET['loai'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$donvi = $_GET['donvi'];
$loai = $_GET['loai'];

$sql = "SELECT TOP 1 Tool_ID 
        FROM ASPEmpDevices
        WHERE Tool_ID LIKE ? 
        ORDER BY Tool_ID DESC";
$params = ["{$donvi}-{$loai}-%"];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $last_tool_id = $row['Tool_ID'];
    $parts = explode('-', $last_tool_id);
    $number = intval(end($parts)) + 1;
    $next_tool_id = "{$donvi}-{$loai}-" . str_pad($number, 3, '0', STR_PAD_LEFT);
} else {
    $next_tool_id = "{$donvi}-{$loai}-001";
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['next_tool_id' => $next_tool_id]);
exit;