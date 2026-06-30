<?php
require_once __DIR__ .'/../auth.php';
require_once __DIR__ .'/../connection.php';
// require_once __DIR__ .'/../loginaction.php';
// require_once 'record.php';
/**
 * Định dạng số:
 *  - Nếu là số nguyên (ví dụ 16.0000) → in “16”
 *  - Nếu có phần thập phân khác 0 → chỉ giữ tối đa 4 chữ số thập phân rồi xoá 0 thừa
 *  - Nếu kết quả trống hoặc “.” → in “0”
 */
// echo '<pre>'; print_r($_SESSION); echo '</pre>';
function fmtNum($val) {
    // chuyển sang float để loại bỏ các ký tự lạ
    $f = floatval($val);
    // định dạng cố định 4 chữ số thập phân
    $s = number_format($f, 4, '.', '');
    // xoá số 0 ở cuối
    $s = rtrim($s, '0');
    // nếu còn dấu chấm ở cuối, xoá luôn
    $s = rtrim($s, '.');
    // nếu chuỗi rỗng, gán về “0”
    return $s === '' ? '0' : $s;
}

// select user name to lookup
$from_date = $_GET['from-date'] ?? date('Y-m-01');
$to_date   = $_GET['to-date']   ?? date('Y-m-t');
$empId = $_GET['emp-type'] ?? '';
$userid = $_SESSION['Member_ID'];
$fullname = $_SESSION['full_name'];

function getEmployeeList($conn, $userid){
  $list =[];
  $sql = "{CALL sp_ASPHRGetEmployeeList}";
  $stmt = sqlsrv_query($conn, $sql);
  if ($stmt){
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
      $list[] = $row;
    }
    sqlsrv_free_stmt($stmt);
  }
  return $list;
}
//Sub table (sp2)
function getStaffInfo($conn, $empId){
  $data = null;
  if ($empId){
    $year = date('Y');
    $sql = "{CALL sp_ASPGetHRAbsenceStaffInfoV2 (?, ?)}";
    $params = [[$empId, SQLSRV_PARAM_IN],
              [$year, SQLSRV_PARAM_IN]  
  ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt){
      $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
      sqlsrv_free_stmt($stmt);
    }
  }
  return $data;
}
// main table (sp3)
function getAbsenceList($conn, $from_date, $to_date, $empId, $userid){
  $rows = [];
  if ($empId){
    $sql = "{CALL sp_ASPViewHRAbsenceStaffV2 (?, ?, ?, ?)}";
    $params = [[$from_date, SQLSRV_PARAM_IN], [$to_date, SQLSRV_PARAM_IN], [$empId, SQLSRV_PARAM_IN], [$userid, SQLSRV_PARAM_IN]];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt){
      while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $rows []= $row;
      }
      sqlsrv_free_stmt($stmt);
    }
  }
  return $rows;
}

//Query data
$employees = getEmployeeList($conn, $userid);
$staffInfo = getStaffInfo($conn, $empId);
$absenceList = getAbsenceList($conn, $from_date, $to_date, $empId, $userid);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Tra cứu phép</title>
    <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://kit.fontawesome.com/ee3f99b602.js" crossorigin="anonymous"></script>
</head>
<body class="p-6 bg-gray-100">
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

      Tra cứu phép
    </h1>
  </div>
</header>
  <!-- Container cha để vị trí relative (dùng absolute positioning cho search nếu cần) -->
<div class="relative mb-4 flex items-center">
  <!-- 1. Bộ lọc Từ ngày – Đến ngày + nút Lọc -->
  <form method="get">
  <div class="flex items-center space-x-2">
    <!-- Bộ lọc -->
        <label for="from-date" class="text-gray-700"><b>Từ ngày:</b></label>
        <input
          type="date"
          id="from-date"
          name="from-date"
          value="<?= htmlspecialchars($from_date) ?>"
          class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200"
        />
        <label for="to-date" class="text-gray-700"><b>Đến ngày:</b></label>
        <input
          type="date"
          id="to-date"
          name="to-date"
          value="<?= htmlspecialchars($to_date) ?>"
          class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200"
        />
       <label for="emp-type" class="text-gray-700"><b>Mã nhân viên:</b></label>
        <select id="emp-type" name="emp-type"
        class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200 w-64">
        <option value="">--Chọn mã nhân viên--</option>
        <?php foreach ($employees as $emp): ?>
          <?php $selected = ($empId === $emp['Ma_CbNv']) ? 'selected' : ''; ?>
          <option value="<?= htmlspecialchars($emp['Ma_CbNv']) ?>" <?= $selected ?>>
            <?= htmlspecialchars($emp['Ma_CbNv'].' - '.$emp['Ten_CbNv']) ?>
          </option>
        <?php endforeach; ?>
      </select>

    <!-- Các nút thao tác -->
    <div class="flex items-center gap-2">
      <button
        id="btn-filter"
        title="Lọc"
        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
      >
        <i class="fa-solid fa-filter"></i>
      </button>

      <a href="/dashboard"
        class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700"
        title="Menu">
        <i class="fa-solid fa-clipboard-list"></i>
      </a>
      <a href="tracking_absence.php"
           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" title="Quản lý">
           <i class="fa-solid fa-people-roof"></i>
        </a>
    </div>
  </div>
</form>
<div class="ml-auto flex items-center space-x-2">
       <a href="logout"
       class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
      <i class="fa-solid fa-right-from-bracket" style="color: #000000;" title="Logout"></i>
    </a>
    </div>
</div>
<script>
  $(document).ready(function() {
    $('#emp-type').select2({
      placeholder: "--Chọn mã nhân viên--",
      allowClear: true,
      width: 'resolve'
    });
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const now       = new Date();
    const firstDay  = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay   = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    const fmt = d => d.toISOString().split('T')[0];

    const fromInput = document.getElementById('from-date');
    const toInput   = document.getElementById('to-date');

    if (!fromInput.value) fromInput.value = fmt(firstDay);
    if (!toInput.value)   toInput.value   = fmt(lastDay);
  });
</script>
  </div>
  <!-- Staff info-->

    <div class="flex-1 border border-red-500 rounded-lg p-4 bg-white">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
      <div>
        <p><span class="font-semibold">Bộ phận:</span> <?= htmlspecialchars($staffInfo['Ten_Bp'] ?? '--') ?></p>
        <p><span class="font-semibold">Chức vụ:</span> <?= htmlspecialchars($staffInfo['Ten_ChucVu'] ?? '--') ?></p>
        <p><span class="font-semibold">Ngày nhận việc:</span> <?= htmlspecialchars($staffInfo['Ngay_ChinhThuc'] ?? '--') ?></p>
        <p><span class="font-semibold">Trạng thái:</span> <?= htmlspecialchars($staffInfo['Status_CBNV'] ?? '--') ?></p>
      </div>
      <div>
        <p><span class="font-semibold">Tổng phép năm tích lũy:</span> <i class="text-red-600 font-semibold"><?= htmlspecialchars($staffInfo['Phep_Chuan'] ?? '--') ?></i></p>
        <p><span class="font-semibold">Tổng phép năm:</span> <?= htmlspecialchars($staffInfo['Phep_ThuongNien'] ?? '--') ?></p>
        <p><span class="font-semibold">Tổng phép thưởng:</span> <?= htmlspecialchars($staffInfo['Phep_Thuong'] ?? '--') ?></p>
        <p><span class="font-semibold">Tổng phép tồn năm trước:</span> <?= htmlspecialchars($staffInfo['Phep_Ton'] ?? '--') ?></p>
      </div>
    </div>
    <p class="text-red-600 font-semibold mt-2">(*) Đã khấu trừ nghỉ Tết âm lịch: <?= htmlspecialchars($staffInfo['KhauTruTet'] ?? '--') ?></p>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto bg-white shadow rounded">
    <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
      <th class="border px-2 py-1">Thời gian nghỉ</th>
      <th class="border px-2 py-1">Số ngày nghỉ</th>
      <th class="border px-2 py-1">Loại phép</th>
      <th class="border px-2 py-1">Lý do xin nghỉ</th>
      <th class="border px-2 py-1">Tổng phép còn lại</th>
      <th class="border px-2 py-1">Tổng phép năm còn lại</th>
      <th class="border px-2 py-1">Tổng phép thưởng còn lại</th>
      <th class="border px-2 py-1">Phép tồn năm trước còn lại</th>
      <th class="border px-2 py-1">TBP Duyệt</th>
      <th class="border px-2 py-1">BOD Duyệt</th>
      <th class="border px-2 py-1">BP. HCNS Duyệt</th>
      <th class="border px-2 py-1">Sử dụng phép tồn</th>
    </tr>

      </thead>
      <tbody class="bg-white divide-y divide-gray-100">
        <?php if (!empty($absenceList)): ?>
          <?php foreach($absenceList as $row): ?>
      <tr>
      <td class="px-3 py-2 col-date text-center">
        <?= $row['TimeOff'] 
              ? $row['TimeOff']->format('d-m-Y') 
              : '' ?>
      </td>
      <td class="px-3 py-2 col-days text-center"><?= fmtNum($row['NumDateOff']) ?></td>
      <td class="px-3 py-2 col-type"><?= htmlspecialchars($row['TypeOfAbsence']) ?></td>
      <td class="px-3 py-2 col-reason"><?= htmlspecialchars(string: $row['ReasonOfAbsence']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($row['SumRemainAbsense']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($row['SumAnnualLeaves']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($row['SumBonusLeaves']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($row['SumRemainLeaves']) ?></td>
      <!-- HOD -->
      <td class="text-center">
        <input type="checkbox"
               class="cb-approve HOD"
               <?= $row['AHDStatus'] ? 'checked' : '' ?>
               disabled />
      </td>

      <!-- BOD -->
      <td class="text-center">
        <input type="checkbox"
               class="cb-approve BOD"
               <?= $row['ABODStatus'] ? 'checked' : '' ?>
               disabled />
      </td>

      <!-- HR -->
      <td class="text-center">
        <input type="checkbox"
               class="cb-approve HR"
               <?= $row['HRStatus'] ? 'checked' : '' ?>
               disabled />
      </td>

      <!-- Sử dụng phép tồn -->
      <td class="px-3 py-2 text-center">
        <input type="checkbox"
               class="cb-remainleave remainleave"
               <?= $row['IsUseRemainLeave'] ? 'checked' : '' ?>
               disabled />
      </td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>
</div>
</body>
</html>