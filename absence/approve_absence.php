<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../connection.php';

header('Content-Type: application/json; charset=utf-8');

// --- Input ---
$id      = (int)($_POST['id']    ?? 0);
$level   = strtoupper($_POST['level'] ?? '');  // chấp nhận cả hod/bod/hr -> convert lên
$action  = $_POST['action']      ?? '';        // approve | deny
$reason  = trim($_POST['reason'] ?? '');       // lý do từ chối (chỉ khi deny)
$status  = isset($_POST['status']) ? (int)$_POST['status'] : null; // 1=duyệt, 0=bỏ duyệt

if (!$id || !in_array($level, ['HOD','BOD','HR'], true) || !in_array($action, ['approve','deny'], true)) {
  echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], JSON_UNESCAPED_UNICODE);
  exit;
}

// --- Session role ---
$userId             = $_SESSION['Member_ID']         ?? '';
$isHRAdmin          = (int)($_SESSION['IsHRAdmin']   ?? 0);
$isHRAbsenceMng     = (int)($_SESSION['IsHRAbsenceMng']    ?? 0);
$isHRAbsenceMngEmp  = (int)($_SESSION['IsHRAbsenceMngEmp'] ?? 0);

// --- Lấy đơn phép ---
$sql  = "SELECT EmpID, AHDStatus, ABODStatus, HRStatus FROM ASPHRAbsenceMng WHERE AutoID = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);
$row  = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn phép.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$empId      = $row['EmpID'];
$ahdStatus  = (int)$row['AHDStatus'];
$abodStatus = (int)$row['ABODStatus'];
$hrStatus   = (int)$row['HRStatus'];
$BOD_USER   = 'ASP1687';

// --- helper ---
function doUpdate($conn, $colStatus, $colDate, $colBy, $status, $id, $user) {
  $sql = "UPDATE ASPHRAbsenceMng
          SET {$colStatus}=?, {$colDate}=GETDATE(), {$colBy}=?
          WHERE AutoID=?";
  return sqlsrv_query($conn, $sql, [$status, $user, $id]);
}

// -----------------------------
// 1) TỪ CHỐI (HR-only) + gửi mail từ chối
// -----------------------------
if ($action === 'deny') {
    if ($isHRAdmin !== 1) {
        echo json_encode(['success' => false, 'message' => 'Chỉ HR mới có quyền từ chối đơn phép.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($reason === '') {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do từ chối.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sqlDeny = "UPDATE ASPHRAbsenceMng
                SET DeniedReason=?, DeniedBy=?, DeniedDate=GETDATE()
                WHERE AutoID=?";
    $ok = sqlsrv_query($conn, $sqlDeny, [$reason, $userId, $id]);

    if (!$ok) {
        $err = print_r(sqlsrv_errors(), true);
        echo json_encode(['success'=>false,'message'=>$err], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- Lấy thông tin email nhân viên + template từ chối
    $stmtEmp = sqlsrv_query($conn, "{CALL sp_ASPHRAbsenceGetMailEmp(?)}", [$empId]);
    $emp     = $stmtEmp ? sqlsrv_fetch_array($stmtEmp, SQLSRV_FETCH_ASSOC) : null;

    $stmtTpl = sqlsrv_query($conn, "{CALL ASPGetMailTemplate(?)}", ['DenyLeave']);
    $tpl     = $stmtTpl ? sqlsrv_fetch_array($stmtTpl, SQLSRV_FETCH_ASSOC) : null;

    if ($emp && $tpl) {
        $replacements = [
            '{EmpName}'    => $emp['EmpName'],
            '{EmpID}'      => $emp['EmpID'],
            '{DeniedBy}'   => $userId,
            '{DenyReason}' => $reason,
        ];

        $to      = $emp['Email'];
        $ccList  = preg_split('/[;,\s:]+/', $tpl['EmailCC']);
        $subject = strtr($tpl['EmailTitle'],   $replacements);
        $body    = strtr($tpl['EmailContent'], $replacements);

        require __DIR__ . '/vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'pro57.emailserver.vn';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hronleave@airspeedmfg.com.vn';
            $mail->Password   = 'asp123@hronleave';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            $mail->setFrom('hronleave@airspeedmfg.com.vn', 'AirspeedMFG - Quản lý phép');
            $mail->addAddress($to, $emp['EmpName']);
            foreach ($ccList as $cc) {
                $cc = trim($cc);
                if ($cc) $mail->addCC($cc);
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = nl2br($body);
            $mail->send();
        } catch (Exception $e) {
            error_log("Email deny error: " . $mail->ErrorInfo);
        }
    }

    echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------
// 2) DUYỆT / BỎ DUYỆT (approve) – dùng status=1|0
// -----------------------------
if ($action === 'approve' && $status === null) {
    echo json_encode(['success'=>false,'message'=>'Thiếu tham số status (1/0).'], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($level) {
  case 'HOD':
    if (!($isHRAbsenceMng === 1 || $isHRAbsenceMngEmp === 1)) {
      echo json_encode(['success' => false, 'message' => 'Bạn không có quyền HOD.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    if ($empId === $BOD_USER) {
      echo json_encode(['success' => false, 'message' => 'Đơn của BOD phải do BOD duyệt.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    if ($status === 0 && $hrStatus === 1) {
      echo json_encode(['success' => false, 'message' => 'HR đã duyệt. Vui lòng HR bỏ duyệt trước.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $ok = doUpdate($conn, 'AHDStatus','AHDDate','AHDBy',$status,$id,$userId);
    break;

  case 'BOD':
    if (!($userId === $BOD_USER && $isHRAbsenceMng === 1)) {
      echo json_encode(['success' => false, 'message' => 'Chỉ BOD mới được duyệt ở cấp này.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    if ($empId === $BOD_USER) {
      echo json_encode(['success' => false, 'message' => 'BOD không thể tự duyệt đơn của chính mình.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    if ($status === 0 && $hrStatus === 1) {
      echo json_encode(['success' => false, 'message' => 'HR đã duyệt. Vui lòng HR bỏ duyệt trước.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $ok = doUpdate($conn, 'ABODStatus','ABODDate','ABODBy',$status,$id,$userId);
    break;

  case 'HR':
    if ($isHRAdmin !== 1) {
      echo json_encode(['success' => false, 'message' => 'Bạn không có quyền HR.'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    if ($status === 1) {
      if (!($ahdStatus === 1 || $abodStatus === 1)) {
        echo json_encode(['success' => false, 'message' => 'Chưa được duyệt từ cấp trước.'], JSON_UNESCAPED_UNICODE);
        exit;
      }
    }
    $ok = doUpdate($conn, 'HRStatus','HRDate','HRBy',$status,$id,$userId);
    break;
}

if (empty($ok)) {
  $err = print_r(sqlsrv_errors(), true);
  echo json_encode(['success'=>false,'message'=>$err], JSON_UNESCAPED_UNICODE);
  exit;
}

// --- Chỉ gửi mail khi approve và status=1 (tick) ---
if ($action === 'approve' && $status === 1) {
    $stmtEmp = sqlsrv_query($conn, "{CALL sp_ASPHRAbsenceGetMailEmp(?)}", [$empId]);
    $emp     = $stmtEmp ? sqlsrv_fetch_array($stmtEmp, SQLSRV_FETCH_ASSOC) : null;

    $stmtTpl = sqlsrv_query($conn, "{CALL ASPGetMailTemplate(?)}", ['FlowLeave']);
    $tpl     = $stmtTpl ? sqlsrv_fetch_array($stmtTpl, SQLSRV_FETCH_ASSOC) : null;

    if ($emp && $tpl) {
        $flowName = ($level === 'HR') ? 'BP. HCNS'
                  : (($level === 'BOD') ? 'Ban giám đốc' : 'Trưởng bộ phận/ Ban giám đốc');

        $replacements = [
            '{EmpName}'     => $emp['EmpName'],
            '{EmpID}'       => $emp['EmpID'],
            '{LeaveFlowID}' => $flowName,
        ];

        $to      = $emp['Email'];
        $ccList  = preg_split('/[;,\s:]+/', $tpl['EmailCC']);
        $subject = strtr($tpl['EmailTitle'],   $replacements);
        $body    = strtr($tpl['EmailContent'], $replacements);

        require __DIR__ . '/vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'pro57.emailserver.vn';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hronleave@airspeedmfg.com.vn';
            $mail->Password   = 'asp123@hronleave';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            $mail->setFrom('hronleave@airspeedmfg.com.vn', 'AirspeedMFG - Quản lý phép');
            $mail->addAddress($to, $emp['EmpName']);
            foreach ($ccList as $cc) {
                $cc = trim($cc);
                if ($cc) $mail->addCC($cc);
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = nl2br($body);
            $mail->send();
        } catch (Exception $e) {
            error_log("Email approve error: " . $mail->ErrorInfo);
        }
    }
}

echo json_encode(['success'=>true], JSON_UNESCAPED_UNICODE);
exit;
