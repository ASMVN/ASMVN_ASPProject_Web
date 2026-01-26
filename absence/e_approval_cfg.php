<?php
require_once __DIR__ .'/../connection.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
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
        }