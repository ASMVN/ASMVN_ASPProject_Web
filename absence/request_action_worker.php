<?php
    require_once __DIR__ .'/../connection.php';
    require __DIR__ . '/vendor/autoload.php';

    //khai báo thư viện PHPMailer
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: request_worker.php');
    exit;
}
    // Lấy và chuẩn hóa dữ liệu $_POST
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $timestamp = date('Y-m-d\TH:i:sP');
    $workerid = trim($_POST['Worker_ID'] ?? '');
    $userid = '';
    $fullname = '';

        if (!empty($workerid)) {
            // Tách EmpID và EmpName bằng dấu |
            list($userid, $fullname) = explode('|', $workerid, 2);
        }

    $reasonofabsence = trim($_POST['reasonofabsence']);
    $supemail = trim($_POST['supemail']);
    
    //Dữ liệu đợt xin nghỉ

    $timeoffs = $_POST['timeoff'] ?? [];
    $numdateoffs = $_POST['numdateoff'] ?? [];
    $typeofabsences = $_POST['typeofabsence'] ?? [];

    $feedbackList = [];
    // Duyệt từng đợt
    for ($i = 0; $i < count($timeoffs); $i++){
        $date = trim($timeoffs[$i] ?? '');
        $days = (float) ($numdateoffs[$i] ?? '');
        $type = trim($typeofabsences[$i] ?? '');

        //Nếu trống thì bỏ qua
        if (empty($date) || $days <= 0){
            continue;
        }
   // Chuẩn hóa ngày
   $dt = DateTime::createFromFormat('Y-m-d', $date);
   if ($dt === false){
    $feedbackList[] = "Đợt " . ($i+1) . ": Ngày không hợp lệ ($date)";
    continue;
   }

    $timeoff = $dt->format('d-m-Y');
    // Lưu vào DB
    $sql = "INSERT INTO ASPHRAbsenceMng ([Timestamp], EmpID
      ,EmpName
      ,TimeOff
      ,NumDateOff
      ,TypeOfAbsence
      ,ReasonOfAbsence, TypeEmp)
      VALUES ( ?, ?, ?, ?, ?, ?, ?, 1)";
    
    $params = [$timestamp, $userid , $fullname, $date, $days, $type, $reasonofabsence];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $errors = print_r(sqlsrv_errors(), true);
        $feedbackList[] = "Đợt ". ($i+1) . ": Lưu dữ liệu thất bại! $errors";
        $alertCls = "bg-red-100 text-red-800";
        continue;
    }
    //Soạn email
    $message = 
        "Mã số nhân viên: $userid\n" .
        "Họ và tên: $fullname\n".
        "Xin nghỉ ngày: $timeoff\n".
        "Số ngày nghỉ: $days\n".
        "Loại phép: $type\n".
        "Lý do xin nghỉ: $reasonofabsence\n\n".
        "Thank you and best regards!";

    try {
        $mail = new PHPMailer(true);
        $mail -> isSMTP();
        $mail -> Host = 'pro57.emailserver.vn';
        $mail -> SMTPAuth = true;
        $mail -> Username = 'hronleave@airspeedmfg.com.vn';
        $mail -> Password = 'asp123@hronleave';
        $mail -> SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail -> Port = 587;

        $mail -> CharSet ='UTF-8';
        $mail -> Encoding = 'base64';

        $mail -> setFrom('hronleave@airspeedmfg.com.vn', 'AirspeedMFG - Đơn xin nghỉ phép');
        $mail -> addAddress('it2@airspeedmfgvn.com');

        // cc email
        $ccList = [
            'makleythien94@gmail.com'
        ];
        foreach ($ccList as $cc){
            $mail -> addCC($cc);
        }
        $mail -> Subject = "[Quản lý phép Online] -  Đơn xin nghỉ phép (Đợt". ($i+1) .") từ $fullname";
        $mail -> isHTML(false);
        $mail -> Body = $message;

        $mail -> send();
        $feedbackList[] = "Đợt " . ($i+1) . ": Lưu dữ liệu & gửi email thành công!";
        $alertCls = "bg-green-100 text-green-800";
    } catch (Exception $e){
        $feedbackList[] = "Đợt " . ($i+1) . ": Lưu dữ liệu thành công nhưng không gửi được email: " . $mail ->ErrorInfo;
        $alertCls = "bg-yellow-100 text-yellow-800";
    }
}
if (empty($feedbackList)){
    $feedbackList[] = "Không có đợt nào hợp lệ để xử lý.";
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Kết quả xin nghỉ phép</title>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
  <div class="max-w-lg w-full">
    <div id="alert" class="<?php echo $alertCls ?> px-4 py-3 rounded-lg text-center mb-4">
      <?php foreach ($feedbackList as $fb): ?>
        <p class="mb-2"> <?php echo htmlspecialchars($fb); ?></p>
      <?php endforeach; ?>
    </div>
    <div class="text-center">
      <a href="request_worker.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
        Trở lại trang đăng ký
      </a>
    </div>
  </div>
</body>
</html>
