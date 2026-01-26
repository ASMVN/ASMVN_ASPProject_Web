<?php
require_once __DIR__ .'/../auth.php';
require_once __DIR__ .'/../connection.php';

$empId = $_GET['emp-type'] ?? '';
$userid = $_SESSION['Member_ID'];

function getEmployeeList($conn, $empId, $userid){
  $list =[];
  $sql = "{CALL sp_ASPGetEmployeeListByUsername (?, ?)}";
  $params = [
    [$empId, SQLSRV_PARAM_IN],
    [$userid, SQLSRV_PARAM_IN]
  ];
  $stmt = sqlsrv_query($conn, $sql, $params);
  if ($stmt){
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
      $list[] = $row;
    }
    sqlsrv_free_stmt($stmt);
  }
  return $list;
}
function getSupEmail($conn, $userid){
  $listmail = [];
  $sql = "{CALL sp_ASPHRGetSupEmpEmail (?)}";
  $params = [$userid, SQLSRV_PARAM_IN];
  $stmt = sqlsrv_query($conn, $sql, $params);
  if ($stmt){
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
      $listmail[] = $row;
    }
    sqlsrv_free_stmt($stmt);
  }
  return $listmail;
}

$employees = getEmployeeList($conn, $empId, $userid);
$SupEmpEmail = getSupEmail($conn, $userid);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đơn xin nghỉ phép</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://kit.fontawesome.com/ee3f99b602.js" crossorigin="anonymous"></script>
</head>
<body class="p-6">

<!-- Header -->
<header class="my-8">
  <div class="container mx-auto flex items-center justify-center space-x-6">
    <img src="https://res.cloudinary.com/dhhvufcd7/image/upload/v1752831374/asplogo128x128_ys1s9t.png"
         alt="AIRSPEED Logo"
         class="h-24 w-auto"/>
      <h1 class="text-6xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
      Đơn xin nghỉ phép
    </h1>
  </div>
</header>

<!-- Select nhân viên -->
<div class="relative mb-4 flex items-center">
  <form method="post" action="request_action_worker.php" class="flex items-center space-x-2">
    <label for="Worker_ID" class="text-gray-700 font-semibold">Mã nhân viên:</label>
    <select id="Worker_ID" name="Worker_ID"
            class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200 w-72">
      <option value="">--Chọn mã nhân viên--</option>
      <?php foreach ($employees as $emp): ?>
        <?php $selected = ($empId === $emp['EmpID']) ? 'selected' : ''; ?>
        <option value="<?= htmlspecialchars($emp['EmpID'].'|'.$emp['EmpName']) ?>" <?= $selected ?>>
      <?= htmlspecialchars($emp['EmpID'].' - '.$emp['EmpName']) ?>
    </option>
      <?php endforeach; ?>
    </select>

  <div class="ml-auto flex items-center space-x-2">
    <button type="submit" class="bg-blue-600 hover:bg-blue-700
    text-white px-6 py-2
    rounded-lg font-semibold">
    <i class="fa-solid fa-paper-plane" title="Nộp đơn"></i>
    </button>
       <a href="logout"
       class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
      <i class="fa-solid fa-right-from-bracket" style="color: #000000;" title="Logout"></i>
    </a>
  </div>
</div>
<script>
$(document).ready(function() {
  $('#Worker_ID').select2({
    placeholder: "--Chọn mã nhân viên--",
    allowClear: true,
    width: 'resolve'
  });
});
</script>

<!-- Khung xin nghỉ phép -->
<div class="relative border border-red-500 rounded-lg p-6 bg-white w-full mb-4">
  <!-- Title ở góc trái trên -->
  <span class="absolute -top-3 left-4 bg-white px-2 text-red-500 font-semibold text-sm">
    Thời gian nghỉ phép
  </span>

  <!-- Grid 3 cột full ngang -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
    <!-- Đợt 1 -->
    <div class="space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Đợt 1</h3>
      <div>
        <label for="timeoff[]" class="block text-sm font-medium">Thời gian</label>
        <input type="date" id="timeoff[]" name="timeoff[]" class="mt-1 block w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
        <label for="numdateoff[]" class="block text-sm font-medium">Số ngày</label>
        <input type="number" id="numdateoff[]" name="numdateoff[]" step="0.001" class="mt-1 block w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
            <label for="typeofabsence[]" class="block font-medium">Loại phép</label>
            <select id="typeofabsence[]" name="typeofabsence[]" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200">
                    <option value=""></option>
                    <option value="S - Phép bệnh">S - Phép bệnh</option>
                    <option value="A - Phép năm">A - Phép năm</option>
                    <option value="A1 - Phép năm (0.5 ngày)">A1 - Phép năm (0.5 ngày)</option>
                    <option value="A - Phép thưởng">A - Phép thưởng</option>
                    <option value="A1 - Phép thưởng (0.5 ngày)">A1 - Phép thưởng (0.5 ngày)</option>
                    <option value="U - Phép trừ lương">U - Phép trừ lương</option>
                    <option value="U1 - Phép trừ lương (0.5 ngày)">U1 - Phép trừ lương (0.5 ngày)</option>
                    <option value="S - Phép khám thai">S - Phép khám thai</option>
                    <option value="X - Phép tang chế">X - Phép tang chế</option>
                    <option value="X - Phép cưới">X - Phép cưới</option>
                </select> 
        </div>
    </div>

    <!-- Đợt 2 -->
    <div class="space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Đợt 2</h3>
      <div>
        <label for="timeoff[]" class="block text-sm font-medium">Thời gian</label>
        <input type="date" id="timeoff[]" name="timeoff[]" class="mt-1 block w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
        <label for="numdateoff[]" class="block text-sm font-medium">Số ngày</label>
        <input type="number" id="numdateoff[]" name="numdateoff[]" step="0.001" class="mt-1 block w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
            <label for="typeofabsence[]" class="block font-medium">Loại phép</label>
            <select id="typeofabsence[]" name="typeofabsence[]" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200">
                    <option value=""></option>
                    <option value="S - Phép bệnh">S - Phép bệnh</option>
                    <option value="A - Phép năm">A - Phép năm</option>
                    <option value="A1 - Phép năm (0.5 ngày)">A1 - Phép năm (0.5 ngày)</option>
                    <option value="A - Phép thưởng">A - Phép thưởng</option>
                    <option value="A1 - Phép thưởng (0.5 ngày)">A1 - Phép thưởng (0.5 ngày)</option>
                    <option value="U - Phép trừ lương">U - Phép trừ lương</option>
                    <option value="U1 - Phép trừ lương (0.5 ngày)">U1 - Phép trừ lương (0.5 ngày)</option>
                    <option value="S - Phép khám thai">S - Phép khám thai</option>
                    <option value="X - Phép tang chế">X - Phép tang chế</option>
                    <option value="X - Phép cưới">X - Phép cưới</option>
                </select> 
        </div>
    </div>

    <!-- Đợt 3 -->
    <div class="space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Đợt 3</h3>
      <div>
        <label for="timeoff[]" class="block text-sm font-medium">Thời gian</label>
        <input type="date" id="timeoff[]" name="timeoff[]" class="mt-1 block w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
        <label for="numdateoff[]" class="block text-sm font-medium">Số ngày</label>
        <input type="number" id="numdateoff[]" name="numdateoff[]" step="0.001" class="mt-1 block w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
            <label id="typeofabsence[]" class="block font-medium">Loại phép</label>
            <select id="typeofabsence[]" name="typeofabsence[]" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200">
                    <option value=""></option>
                    <option value="S - Phép bệnh">S - Phép bệnh</option>
                    <option value="A - Phép năm">A - Phép năm</option>
                    <option value="A1 - Phép năm (0.5 ngày)">A1 - Phép năm (0.5 ngày)</option>
                    <option value="A - Phép thưởng">A - Phép thưởng</option>
                    <option value="A1 - Phép thưởng (0.5 ngày)">A1 - Phép thưởng (0.5 ngày)</option>
                    <option value="U - Phép trừ lương">U - Phép trừ lương</option>
                    <option value="U1 - Phép trừ lương (0.5 ngày)">U1 - Phép trừ lương (0.5 ngày)</option>
                    <option value="S - Phép khám thai">S - Phép khám thai</option>
                    <option value="X - Phép tang chế">X - Phép tang chế</option>
                    <option value="X - Phép cưới">X - Phép cưới</option>
                </select> 
        </div>
    </div>
  </div>
</div>

<div class="relative border border-red-500 rounded-lg p-6 bg-white w-full">
  <!-- Grid 3 cột full ngang -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <div class="space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Lý do xin nghỉ</h3>
      <div>
          <label for="reasonofabsence" class="block font-medium"></label>
          <textarea type="text" id="reasonofabsence" name="reasonofabsence" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200"> </textarea>
      </div>
    </div>
    <!-- Email quản lý -->
    <div class="space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Email quản lý</h3>
      <div>
        <label for="supemail" class="text-gray-700 font-semibold"></label>
          <select id="supemail" name="supemail"
            class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200 w-72">
      <option value="">--Chọn Email quản lý--</option>
        <?php foreach ($SupEmpEmail as $sup): ?>
        <?php $selected = ($userid === $sup['Email']) ? 'selected' : ''; ?>
        <option value="<?= htmlspecialchars($sup['Email']) ?>" <?= $selected ?>>
          <?= htmlspecialchars($sup['Email'].' - '.$sup['FullName']) ?>
        </option>
      <?php endforeach; ?>
    </select>
      </div>
    </div>
  </div>
</div>
</form>
</body>
</html>
