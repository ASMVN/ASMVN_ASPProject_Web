<?php
// timesheet.php
// Full file: xử lý ajax update + hiển thị bảng + js selectAll + ajax submit
require_once __DIR__ .'/auth.php';
require_once __DIR__ .'/connection.php'; // phải cung cấp $conn (sqlsrv_connect)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// --------------------
// 1) XỬ LÝ AJAX UPDATE
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajaxUpdate'])) {
    header('Content-Type: application/json; charset=utf-8');

    $maNV = $_POST['MaNV'] ?? '';
    $thang = (int)($_POST['Thang'] ?? 0);
    $nam = (int)($_POST['Nam'] ?? 0);
    $loai = (int)($_POST['Loai'] ?? 0);
    $xacNhan = isset($_POST['XacNhan']) ? (int)$_POST['XacNhan'] : 0;
    $ghiChu = $_POST['GhiChu'] ?? '';

    if ($maNV === '' || $thang <= 0 || $nam <= 0 || ($loai !== 1 && $loai !== 2)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu tham số bắt buộc.']);
        exit;
    }

    $sql = "{CALL sp_UpdateBangChamCong(?, ?, ?, ?, ?, ?)}";
    $params = [
        [$maNV, SQLSRV_PARAM_IN],
        [$thang, SQLSRV_PARAM_IN],
        [$nam, SQLSRV_PARAM_IN],
        [$loai, SQLSRV_PARAM_IN],
        [$xacNhan, SQLSRV_PARAM_IN],
        [$ghiChu, SQLSRV_PARAM_IN],
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        $errs = sqlsrv_errors();
        $msg = is_array($errs) && isset($errs[0]['message']) ? $errs[0]['message'] : 'Lỗi SQL không xác định.';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    // nếu SP có RAISERROR thì sqlsrv_query trả false ở trên, nên đến đây là ok
    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công.']);
    exit;
}

// --------------------
// 2) GỌI SP LẤY DỮ LIỆU (chỉ 1 lần)
// --------------------
$month = isset($_GET['month']) && (int)$_GET['month'] > 0 ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  && (int)$_GET['year']  > 0 ? (int)$_GET['year']  : (int)date('Y');
$mabp = isset($_GET['mabp']) ? trim($_GET['mabp']) : '';
$manhamay = isset($_GET['Ma_NhaMay']) ? trim($_GET['Ma_NhaMay']) : '';

$sql = "{CALL sp_rptBangChamCong(?, ?, ?, ?)}";
$params = [
    [$month, SQLSRV_PARAM_IN],
    [$year, SQLSRV_PARAM_IN],
    [$mabp, SQLSRV_PARAM_IN],
    [$manhamay, SQLSRV_PARAM_IN],
];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die('SQL Error: ' . print_r(sqlsrv_errors(), true));
}

$rows = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $r;
}
sqlsrv_free_stmt($stmt);

// --------------------
// 3) TÍNH KHUNG NGÀY CHO PHẦN ENABLE/DISABLE UI
// --------------------
$today = new DateTime();
$start_mid = new DateTime(sprintf('%04d-%02d-15', $year, $month));
$end_mid   = new DateTime(sprintf('%04d-%02d-18', $year, $month));

$start_end = new DateTime(sprintf('%04d-%02d-30', $year, $month));
$end_end   = (clone $start_end)->modify('+1 month')->setDate((clone $start_end)->format('Y'), (clone $start_end)->format('m'), 2); // unused
// simpler:
$end_end = new DateTime((clone $start_end)->modify('+1 month')->format('Y-m') . '-02');

$allow_mid = ($today >= $start_mid && $today <= $end_mid);
$allow_end = ($today >= $start_end || $today <= $end_end);

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Bảng công</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/ee3f99b602.js" crossorigin="anonymous"></script>
  <style> /* nhỏ */ .row-checked { background:#e6f7ff; }</style>
</head>
<body class="p-6 bg-gray-100">
<header class="my-8">
  <div class="container mx-auto flex items-center justify-center space-x-6">
    <img src="https://res.cloudinary.com/dhhvufcd7/image/upload/v1752831374/asplogo128x128_ys1s9t.png" alt="logo" class="h-24"/>
    <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">BẢNG CÔNG</h1>
  </div>
</header>

<!-- Filters (GET) -->
<div class="relative mb-4 flex items-center">
  <form method="get" class="flex items-center space-x-2">
    <label><b>Chọn tháng:</b></label>
    <select id="month" name="month" class="border rounded px-2 py-1">
      <?php for ($m=1;$m<=12;$m++): ?>
        <option value="<?= $m ?>" <?= ($month==$m?'selected':'') ?>><?= $m ?></option>
      <?php endfor; ?>
    </select>
    <label><b>Chọn năm:</b></label>
    <select id="year" name="year" class="border rounded px-2 py-1">
      <?php for ($y=2017;$y<=date('Y');$y++): ?>
        <option value="<?= $y ?>" <?= ($year==$y?'selected':'') ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <label><b>Bộ phận:</b></label>
    <select id="mabp" name="mabp" class="border rounded px-2 py-1">
      <option value="">--Tất cả--</option>
      <option value="1100" <?= $mabp==='1100' ? 'selected' : '' ?>>Bộ phận Hành Chính Nhân Sự</option>
      <!-- thêm option nếu cần -->
    </select>
    <label><b>Nhà máy:</b></label>
    <select id="Ma_NhaMay" name="Ma_NhaMay" class="border rounded px-2 py-1">
      <option value="">Tất cả</option>
      <option value="ASM1" <?= $manhamay==='ASM1' ? 'selected' : '' ?>>ASM1</option>
      <option value="ASM2" <?= $manhamay==='ASM2' ? 'selected' : '' ?>>ASM2</option>
    </select>
    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"><i class="fa-solid fa-filter"></i></button>
  </form>

  <!-- nút xác nhận (AJAX) ở form GET nhưng là type=button tránh submit -->
  <div class="ml-auto">
    <button id="btn-xacnhan" type="button" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" title="Xác nhận">
      <i class="fa-solid fa-circle-check"></i>
    </button>
  </div>
</div>

<!-- Table -->
<div class="overflow-x-auto bg-white shadow rounded">
  <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
        <th class="px-3 py-2 w-10 text-center"><input id="selectAll" type="checkbox" class="form-checkbox"/></th>
        <th class="px-3 py-2">Mã NV</th>
        <th class="px-3 py-2">Tên nhân viên</th>
        <th class="px-3 py-2">Ngày công thực tế</th>
        <th class="px-3 py-2">Chi tiết</th>
        <th class="px-3 py-2">Xác nhận giữa tháng</th>
        <th class="px-3 py-2">Ghi chú giữa tháng</th>
        <th class="px-3 py-2">Xác nhận cuối tháng</th>
        <th class="px-3 py-2">Ghi chú cuối tháng</th>
      </tr>
    </thead>
    <tbody id="datatable-body" class="bg-white divide-y divide-gray-100">
    <?php foreach ($rows as $r): 
        $ma = htmlspecialchars($r['MaNhanVien']);
        $ten = htmlspecialchars($r['TenNhanVien']);
        $ngay = (int)$r['NgayCongThucTe'];
        $ghi1 = htmlspecialchars($r['Ghi_chu1'] ?? '');
        $ghi2 = htmlspecialchars($r['Ghi_chu2'] ?? '');
        $xn1 = !empty($r['Xac_nhan1']) ? 'checked' : '';
        $xn2 = !empty($r['Xac_nhan2']) ? 'checked' : '';
    ?>
      <tr data-manv="<?= $ma ?>">
        <td class="px-3 py-2 text-center">
          <input type="checkbox" class="row-checkbox form-checkbox h-4 w-4">
          <input type="hidden" name="manv[]" value="<?= $ma ?>">
        </td>
        <td class="px-3 py-2"><?= $ma ?></td>
        <td class="px-3 py-2"><?= $ten ?></td>
        <td class="px-3 py-2 text-center"><?= $ngay ?></td>
        <td class="px-3 py-2 text-center">
            <button type="button" class="bg-blue-500 text-white rounded px-2 py-1 hover:bg-blue-600" onclick='showCalendarModal(<?= json_encode($ten) ?>, <?= json_encode($r['ChiTietNgayCong']) ?>, <?= json_encode($month) ?>, <?= json_encode($year) ?>)'>
              <i class="fa-solid fa-circle-info"></i>
            </button>
        </td>

        <!-- giữa tháng -->
        <td class="px-3 py-2 text-center <?= $allow_mid ? 'bg-yellow-50' : 'bg-gray-100' ?>">
          <input type="checkbox" class="xacnhan1" name="Xac_nhan1[<?= $ma ?>]" <?= $xn1 ?> <?= $allow_mid ? '' : 'disabled' ?>>
        </td>
        <td class="px-3 py-2">
          <input type="text" class="ghichu1 w-full border rounded px-2 py-1" name="Ghi_chu1[<?= $ma ?>]" value="<?= $ghi1 ?>" <?= $allow_mid ? '' : 'readonly' ?>>
        </td>

        <!-- cuối tháng -->
        <td class="px-3 py-2 text-center <?= $allow_end ? 'bg-yellow-50' : 'bg-gray-100' ?>">
          <input type="checkbox" class="xacnhan2" name="Xac_nhan2[<?= $ma ?>]" <?= $xn2 ?> <?= $allow_end ? '' : 'disabled' ?>>
        </td>
        <td class="px-3 py-2">
          <input type="text" class="ghichu2 w-full border rounded px-2 py-1" name="Ghi_chu2[<?= $ma ?>]" value="<?= $ghi2 ?>" <?= $allow_end ? '' : 'readonly' ?>>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal (unchanged) -->
<div id="calendarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-lg p-6 w-[700px] max-h-[90vh] overflow-auto">
    <h3 id="calendarTitle" class="text-xl font-bold text-center text-blue-600 mb-4"></h3>
    <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm"></div>
    <div class="text-center mt-6">
      <button onclick="closeModal()" class="px-5 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">Đóng</button>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById('datatable-body');
  const selectAll = document.getElementById('selectAll');

  // Select all / row checkbox logic + highlight + shift-select
  let lastChecked = null;
  function updateState() {
    const all = Array.from(tbody.querySelectorAll('.row-checkbox'));
    const checked = all.filter(c => c.checked);
    all.forEach(cb => cb.closest('tr').classList.toggle('row-checked', cb.checked));
    selectAll.checked = checked.length === all.length && all.length>0;
    selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
  }

  selectAll.addEventListener('change', () => {
    const is = selectAll.checked;
    tbody.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = is);
    updateState();
  });

  tbody.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('click', (e) => {
      if (e.shiftKey && lastChecked) {
        const checkboxes = Array.from(tbody.querySelectorAll('.row-checkbox'));
        const start = checkboxes.indexOf(lastChecked);
        const end = checkboxes.indexOf(cb);
        const [from, to] = start < end ? [start, end] : [end, start];
        checkboxes.slice(from, to+1).forEach(c => c.checked = true);
      }
      lastChecked = cb;
      updateState();
      e.stopPropagation();
    });
  });

  tbody.addEventListener('click', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    const cb = tr.querySelector('.row-checkbox');
    if (!cb || e.target === cb) return;
    cb.checked = !cb.checked;
    lastChecked = cb;
    updateState();
  });

  // Button Xác nhận (AJAX): gửi cho từng row có thay đổi
  document.getElementById('btn-xacnhan').addEventListener('click', async () => {
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    let successCount = 0;
    let errorMessages = [];

    if (rows.length === 0) { alert('Không có dòng để cập nhật'); return; }

    // iterate rows
    for (const row of rows) {
      const maNV = row.dataset.manv;
      const x1 = row.querySelector('.xacnhan1')?.checked ? 1 : 0;
      const g1 = row.querySelector('.ghichu1')?.value || '';
      const x2 = row.querySelector('.xacnhan2')?.checked ? 1 : 0;
      const g2 = row.querySelector('.ghichu2')?.value || '';

      // gửi 1: giữa tháng (nếu có thay đổi)
      if (x1 || g1.trim() !== '') {
        const fd = new FormData();
        fd.append('ajaxUpdate', 1);
        fd.append('MaNV', maNV);
        fd.append('Thang', month);
        fd.append('Nam', year);
        fd.append('Loai', 1);
        fd.append('XacNhan', x1);
        fd.append('GhiChu', g1);

        try {
          const res = await fetch(window.location.href, { method: 'POST', body: fd });
          const json = await res.json();
          if (json.success) successCount++; else errorMessages.push(maNV + ' (gian thang): ' + json.message);
        } catch (e) {
          errorMessages.push(maNV + ' (gian thang): ' + e.message);
        }
      }

      // gửi 2: cuối tháng
      if (x2 || g2.trim() !== '') {
        const fd2 = new FormData();
        fd2.append('ajaxUpdate', 1);
        fd2.append('MaNV', maNV);
        fd2.append('Thang', month);
        fd2.append('Nam', year);
        fd2.append('Loai', 2);
        fd2.append('XacNhan', x2);
        fd2.append('GhiChu', g2);

        try {
          const res2 = await fetch(window.location.href, { method: 'POST', body: fd2 });
          const json2 = await res2.json();
          if (json2.success) successCount++; else errorMessages.push(maNV + ' (cuoi thang): ' + json2.message);
        } catch (e) {
          errorMessages.push(maNV + ' (cuoi thang): ' + e.message);
        }
      }
    } // end for

    let msg = `Đã cập nhật ${successCount} bản ghi thành công.`;
    if (errorMessages.length) msg += '\nLỗi: ' + errorMessages.slice(0,5).join('; ');
    alert(msg);
    window.location.reload();
  });

}); // end DOMContentLoaded

// Modal helper (unchanged from your code)
function showCalendarModal(empName, chiTiet, month, year) {
  const modal = document.getElementById('calendarModal');
  const grid = document.getElementById('calendarGrid');
  const title = document.getElementById('calendarTitle');
  title.innerText = `BẢNG CHẤM CÔNG ${empName} - THÁNG ${month}/${year}`;
  grid.innerHTML = '';

  // parse chiTiet like "01-X;02-P;..."
  const statusMap = {};
  const regex = /(\d{1,2})-([^\;]+)/gi;
  let match;
  while ((match = regex.exec(chiTiet)) !== null) {
    statusMap[parseInt(match[1],10)] = match[2];
  }

  const firstDay = new Date(year, month - 1, 1);
  const daysInMonth = new Date(year, month, 0).getDate();
  const startDay = (firstDay.getDay() + 6) % 7;
  const weekdays = ['T2','T3','T4','T5','T6','T7','CN'];
  weekdays.forEach(d => {
    const h = document.createElement('div'); h.className='font-semibold text-blue-600'; h.innerText=d; grid.appendChild(h);
  });
  for (let i=0;i<startDay;i++) grid.appendChild(document.createElement('div'));

  for (let day=1;day<=daysInMonth;day++) {
    const cell = document.createElement('div');
    let status = statusMap[day] || '';
    let bg = 'bg-white border-gray-300';
    if (/^X/i.test(status)) bg = 'bg-green-300 border-green-500';
    else if (/^P/i.test(status)) bg = 'bg-yellow-300 border-yellow-500';
    else if (/^H/i.test(status)) bg = 'bg-blue-300 border-blue-500';
    else if (/^A/i.test(status)) bg = 'bg-red-300 border-red-500';
    else if (status.trim() === '--') bg = 'bg-gray-300 border-gray-500';
    const d = new Date(year, month-1, day).getDay();
    if (d===0) bg += ' bg-violet-200 border-violet-400';
    cell.className = `border rounded-lg p-2 ${bg} transition hover:scale-105 hover:shadow-md`;
    cell.innerHTML = `<div class="font-bold">${day}</div><div class="text-sm">${status}</div>`;
    grid.appendChild(cell);
  }
  modal.classList.remove('hidden'); modal.classList.add('flex');
}
function closeModal(){ const modal=document.getElementById('calendarModal'); modal.classList.remove('flex'); modal.classList.add('hidden'); }
</script>
</body>
</html>
