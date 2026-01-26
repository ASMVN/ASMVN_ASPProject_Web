<?php
// log_event.php

// 1) Start session và include những file cần thiết
require_once __DIR__ . '/../connection.php';
require_once __DIR__ .'/../auth.php';
require 'record.php';


// 2) Lấy userID và description từ POST
$userid = $_SESSION['Member_ID'] ?? '';
$desc   = $_POST['description'] ?? '';

// 3) Validate input
header('Content-Type: application/json; charset=UTF-8');
if (!$userid || !$desc) {
  error_log("[LOG_EVENT] Missing params: userID={$userid}, desc={$desc}");
  http_response_code(400);
  echo json_encode(['status'=>'error','msg'=>'Invalid parameters']);
  exit;
}

// 4) Ghi log
$ok = logAction($conn, $userid, $desc);
if (!$ok) {
  error_log("[LOG_EVENT] logAction returned false for user {$userid}");
  echo json_encode(['status'=>'error','msg'=>'Could not write audit log']);
  exit;
}

// 5) Trả về success
echo json_encode(['status'=>'ok']);
exit;
