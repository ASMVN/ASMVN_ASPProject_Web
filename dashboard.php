<?php
session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/function.php';

$userid = $_SESSION['Member_ID'] ?? 0;

// Phân quyền người dùng
$canDevice         = hasPermission($conn, $userid, 'DMng_U'); // quyền vào trang quản lí thiết bị
$canAbsence        = hasPermission($conn, $userid, 'WPO_U');  // quyền vào trang quản lý phép
$canAbsenceWorker  = hasPermission($conn, $userid, 'WPOW_U'); // quyền vào trang quản lý phép công nhân
$canASP    = hasPermission($conn, $userid, 'ASP_D'); // quyền tải ứng dụng
$canLinkQ   = hasPermission($conn, $userid, 'LinkQ_D'); // quyền tải ứng dụng
$canContract       = hasPermission($conn, $userid, 'HD_U'); // quyền vào trang hợp đồng
$canBCC            = hasPermission($conn, $userid, 'BCC'); // quyền vào trang hợp đồng

$department = $_GET['dept'] ?? 'HR';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Dashboard Quản trị</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function noPermission() {
      alert("Bạn không có quyền truy cập");
    }
  </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center py-10">
  <header class="my-8">
  <div class="container mx-auto flex items-center justify-center space-x-6">
    <!-- Logo -->
    <img
      src="https://res.cloudinary.com/dhhvufcd7/image/upload/v1752831374/asplogo128x128_ys1s9t.png"
      alt="AIRSPEED Logo"
      class="h-24 w-auto"
    />

    <!-- Tiêu đề -->
    <h1 class="text-6xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
      Bảng điều khiển quản trị
    </h1>
  </div>
  </header>

    <!-- Bộ phận chọn -->
    <div class="flex justify-center gap-4 mb-10 bg-white/60 backdrop-blur-md rounded-2xl p-3 shadow-sm">
  <a href="?dept=HR"
     class="px-6 py-2.5 rounded-xl text-sm font-medium transition 
            <?= $department === 'HR' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
     Phòng HR
  </a>
  <a href="?dept=IT"
     class="px-6 py-2.5 rounded-xl text-sm font-medium transition 
            <?= $department === 'IT' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
     Phòng IT
  </a>
    <a href="?dept=PROD"
     class="px-6 py-2.5 rounded-xl text-sm font-medium transition 
            <?= $department === 'PROD' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
     BP. Sản xuất
  </a>
</div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

      <?php if ($department === 'HR'): ?>

        <!-- Quản lý thiết bị -->
        <button
          onclick="<?= $canDevice ? "location.href='manage/manage_pc'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canDevice ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Laptop + Cog -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 4.5h18v12H3v-12zm9 12v3m-6 0h12" />
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15.75 9a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM13.5 9a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM11.25 9a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Quản lý thiết bị</h2>
          <p class="text-gray-600 text-sm">Xem, thêm, sửa, xoá thông tin thiết bị.</p>
        </button>

        <!-- Quản lý phép -->
        <button
          onclick="<?= $canAbsence ? "location.href='absence/request_absence'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canAbsence ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Calendar Check -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 9.75V19.5A2.25 2.25 0 006.75 21.75h10.5a2.25 2.25 0 002.25-2.25V9.75H4.5z" />
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9.75 13.5l2.25 2.25L15.75 12" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Quản lý phép</h2>
          <p class="text-gray-600 text-sm">Tạo đơn nghỉ phép, xem lịch sử và duyệt đơn.</p>
        </button>

        <!-- Bảng chấm công -->
         <button
          onclick="<?= $canBCC ? "location.href='absence/timesheet'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canBCC ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Document signature -->
          <svg xmlns="http://www.w3.org/2000/svg"
          fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-12 h-12 mx-auto text-cyan-500 mb-4">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Bảng chấm công tháng</h2>
          <p class="text-gray-600 text-sm">Kiểm tra và xác nhận công tháng</p>
        </button>

        <!-- Hợp đồng -->
         <button
          onclick="<?= $canContract ? "location.href='contract/fetch_contract'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canContract ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Document signature -->
          <svg xmlns="http://www.w3.org/2000/svg"
     class="w-12 h-12 mx-auto text-purple-500 mb-4"
     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 2h6a2 2 0 012 2v1h1a2 2 0 012 2v13a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h1V4a2 2 0 012-2zm3 10l-2 2 4 4 6-6" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Hợp đồng</h2>
          <p class="text-gray-600 text-sm">Xem, tạo mới hợp đồng</p>
        </button>

        <!-- IT -->
      <?php elseif ($department === 'IT'): ?>

        <!-- Tải ứng dụng -->
        <button
          onclick="<?= $canASP ? "location.href='ASPapk'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canASP ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Cloud Download -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 15.75a4.5 4.5 0 014.5-4.5h1.008a3 3 0 015.736 0H15a4.5 4.5 0 014.5 4.5m-7.5-3v6m0 0l-2.25-2.25M12 18.75l2.25-2.25" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Tải ứng dụng ASPProject</h2>
          <p class="text-gray-600 text-sm">Tải xuống ứng dụng.</p>
        </button>
        <button
          onclick="<?= $canlinkQ ? "location.href='linkQapk'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canlinkQ ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Cloud Download -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 15.75a4.5 4.5 0 014.5-4.5h1.008a3 3 0 015.736 0H15a4.5 4.5 0 014.5 4.5m-7.5-3v6m0 0l-2.25-2.25M12 18.75l2.25-2.25" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Tải ứng dụng LinkQ</h2>
          <p class="text-gray-600 text-sm">Tải xuống ứng dụng.</p>
        </button>

    
      <?php elseif ($department === 'PROD'): ?>

        <!-- Đăng kí phép công nhân -->
        <button
          onclick="<?= $canAbsenceWorker ? "location.href='absence/request_worker'" : "noPermission()" ?>"
          class="bg-white rounded-2xl shadow p-8 text-center transition 
                 <?= $canAbsenceWorker ? 'hover:shadow-lg hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' ?>">
          <!-- Icon: Clipboard List -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 3.75h6A2.25 2.25 0 0117.25 6v12A2.25 2.25 0 0115 20.25H9A2.25 2.25 0 016.75 18V6A2.25 2.25 0 019 3.75zM9 9h6M9 12h6M9 15h3" />
          </svg>
          <h2 class="text-xl font-semibold mb-2">Đăng kí phép công nhân</h2>
          <p class="text-gray-600 text-sm">Truy cập trang đăng kí phép khối trực tiếp sản xuất.</p>
        </button>


      <?php endif; ?>
    </div>
  </div>
  </div>
</body>
</html>
