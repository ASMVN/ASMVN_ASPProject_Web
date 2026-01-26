<?php
require_once __DIR__ .'/../connection.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: request_absence.php');
    exit;
}

// 1. Lay va chuan hoa du lieu tu $_POST
date_default_timezone_set('Asia/Ho_Chi_Minh');
$timestamp = date("Y-m-d\TH:i:sP"); 
$userid = trim($_POST['Member_ID'] ?? '');
$fullname = trim($_POST['full_name'] ?? '');
$raw_timeoff = trim($_POST['timeoff'] ?? '');
$numdateoff = (float) ($_POST['numdateoff'] ?? 0);
$typeofabsence = trim($_POST['typeofabsence'] ?? '');
$reasonofabsence = trim($_POST['reasonofabsence'] ?? '');
$managermail =trim($_POST['managermail'] ?? '');

// Convert dinh dang ngay thang YYYY-MM-DD
$dt = DateTime::createFromFormat('Y-m-d', $raw_timeoff);
$timeoff = $dt->format('d-m-Y');
if ($dt== false){
  die ("Invalid date format: $raw_timeoff");
}

//2. Chuan bi cau lenh INSERT voi tham so (positional placeholder)
$sql = "INSERT INTO ASPHRAbsenceMng ([Timestamp], EmpID
      ,EmpName
      ,TimeOff
      ,NumDateOff
      ,TypeOfAbsence
      ,ReasonOfAbsence)
      VALUES ( ?, ?, ?, ?, ?, ?, ?)";

$params = [$timestamp, $userid, $fullname, $raw_timeoff, $numdateoff, $typeofabsence, $reasonofabsence];

$stmt = sqlsrv_query($conn, $sql, $params);

//3. Thuc thi va kiem tra ket qua

if ($stmt === false) {
    $errors = print_r(sqlsrv_errors(), true);
    $feedback = "Save data failed!" . $errors;
    $alertCls = "bg-red-100 text-red-800";
}else{
    $message = 
        "Mã số nhân viên: $userid\n" .
        "Họ và tên: $fullname\n" .
        "Xin nghỉ ngày: $timeoff\n".
        "Số ngày nghỉ: $numdateoff\n" .
        "Loại phép: $typeofabsence\n" .
        "Lý do xin nghỉ: $reasonofabsence\n\n".
        "Thank you and best regard!";

        
        try {
          $mail = new PHPMailer(true);

          $mail-> isSMTP();
          $mail-> Host = 'pro57.emailserver.vn';
          $mail-> SMTPAuth = true;
          $mail-> Username = 'hronleave@airspeedmfg.com.vn';
          $mail-> Password = 'asp123@hronleave';
          $mail-> SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
          $mail-> Port = 587;
          //Vietnamese
          $mail-> CharSet = 'UTF-8';
          $mail-> Encoding = 'base64';
          //sender $ recipient
          $mail-> setFrom('hronleave@airspeedmfg.com.vn', 'AirspeedMFG - Đơn xin nghỉ phép');
          $mail-> addAddress('it2@airspeedmfgvn.com');
          $ccList = [
                      // 'hr1@airspeedmfgvn.com',
                      // 'hr2@airspeedmfgvn.com',
                      // 'hr3@airspeedmfgvn.com',
                      // 'ga2@airspeedmfgvn.com'
                      'makleythien94@gmail.com'
                    ];
          foreach ($ccList as $cc) {
              $mail->addCC($cc);
          }
          //title
          $mail->Subject = '[Quản lý phép Online] - Đơn xin nghỉ phép từ ' . $fullname;
          // Content
          $mail-> isHTML(false);
          $mail-> Body = $message;
          // Send mail

          $mail-> send();
          $feedback = "Lưu dữ liệu thành công, hệ thống đã gửi email đến quản lý của bạn!";
          $alertCls = "bg-green-100 text-green-800";
          } catch (Exception $e){
            $feedback = "Lưu dữ liệu thành công nhưng không thể gửi email: ". $mail-> ErrorInfo;
            $alertCls = "bg-yellow-100 text-yellow-800";
          }
      }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Result</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="max-w-lg w-full">
    <div id="alert" class="<?php echo $alertCls ?> px-4 py-3 rounded-lg text-center mb-4">
      <?php echo nl2br(htmlspecialchars($feedback)); ?>
    </div>
    <div class="text-center">
      <a href="request_absence.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
        Trở lại trang đăng ký
      </a>
    </div>
  </div>
</body>
</html>