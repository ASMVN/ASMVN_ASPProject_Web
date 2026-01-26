<?php
require_once __DIR__ .'/../auth.php';
require_once __DIR__ .'/../connection.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$noteEditEnabled = 0;
$IsHRAdmin = $_SESSION['IsHRAdmin'] ?? 0;
$NOTE_MODE = $_SESSION['NOTE_MODE'] ?? 0;


$sql = "{CALL sp_GetNoteEditStatus}";
$stmt = sqlsrv_query($conn, $sql);

if($stmt && ($row =sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))){
  $noteEditEnabled =(int)$row['ConfigValue'];
}
// Đồng bộ session
  $_SESSION['NOTE_MODE'] = $noteEditEnabled;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggleNote'])) {
  header('Content-Type: application/json; charset=utf-8');

  if (!$IsHRAdmin) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
  }

  $enabled = (int)($_POST['enabled'] ?? 0);

  $sql = "{CALL sp_SetNoteEditStatus (?)}";
  sqlsrv_query($conn, $sql, [[$enabled, SQLSRV_PARAM_IN]]);

  // 🔥 CỰC KỲ QUAN TRỌNG
  $_SESSION['NOTE_MODE'] = $enabled;

  echo json_encode([
    'success' => true,
    'enabled' => $enabled
  ]);
  exit;
}

$mabp_user = $_SESSION['Ma_Bp'] ?? '';

        // Nếu là HRAdmin, dùng giá trị người chọn (có thể rỗng = tất cả)
// Nếu không phải HRAdmin → ép luôn về bộ phận của user
if ($IsHRAdmin == 1) {
    $mabp = isset($_GET['mabp']) ? trim($_GET['mabp']) : '';
} else {
    $mabp = $mabp_user; // <- ép luôn bộ phận theo session
}
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
$username = $_SESSION['Member_ID'];
$manhamay = isset($_GET['Ma_NhaMay']) ? trim($_GET['Ma_NhaMay']) : '';

$sql = "{CALL sp_rptBangChamCong(?, ?, ?, ?, ?)}";
$params = [
    [$month, SQLSRV_PARAM_IN],
    [$year, SQLSRV_PARAM_IN],
    [$username, SQLSRV_PARAM_IN],
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
// $today = new DateTime();
// $start_mid = new DateTime(sprintf('%04d-%02d-15', $year, $month));
// $end_mid   = new DateTime(sprintf('%04d-%02d-18', $year, $month));

// $start_end = new DateTime(sprintf('%04d-%02d-30', $year, $month));
// $end_end   = (clone $start_end)->modify('+1 month')->setDate((clone $start_end)->format('Y'), (clone $start_end)->format('m'), 2); // unused
// // simpler:
// $end_end = new DateTime((clone $start_end)->modify('+1 month')->format('Y-m') . '-02');

// $allow_mid = ($today >= $start_mid && $today <= $end_mid);
// $allow_end = ($today >= $start_end && $today <= $end_end);

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
    <select id="month" name="month" class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
      <?php for ($m=1;$m<=12;$m++): ?>
        <option value="<?= $m ?>" <?= ($month==$m?'selected':'') ?>><?= $m ?></option>
      <?php endfor; ?>
    </select>
    <label><b>Chọn năm:</b></label>
    <select id="year" name="year" class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
      <?php for ($y=2017;$y<=date('Y');$y++): ?>
        <option value="<?= $y ?>" <?= ($year==$y?'selected':'') ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <?php if($IsHRAdmin): ?>
    <label for="mabp" class="text-gray-700 focus:ring focus:ring-blue-200"><b>Mã bộ phận</b></label>
    <select id="mabp" name="mabp" class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
      <option value="">--Tất cả--</option>
      <option value="1000" <?= $mabp==='1000' ? 'selected' : '' ?>>Ban Tổng Giám Đốc</option>
      <option value="1100" <?= $mabp==='1100' ? 'selected' : '' ?>>Bộ phận Hành Chính Nhân Sự</option>
      <option value="1200" <?= $mabp==='1200' ? 'selected' : '' ?>>Phòng Tài Chính - Kế Toán</option>
      <option value="1701" <?= $mabp==='1701' ? 'selected' : '' ?>>Phòng Thu Mua</option>
      <option value="1300" <?= $mabp==='1300' ? 'selected' : '' ?>>Bộ Phận Kho</option>
      <option value="1702" <?= $mabp==='1702' ? 'selected' : '' ?>>Bộ Phận Kế Hoạch</option>
      <option value="1404" <?= $mabp==='1404' ? 'selected' : '' ?>>Bộ Phận Sản Xuất</option>
      <option value="1500" <?= $mabp==='1500' ? 'selected' : '' ?>>Bộ Phận Kỹ Thuật</option>
      <option value="1501" <?= $mabp==='1501' ? 'selected' : '' ?>>Bộ Phận Tooling</option>
      <option value="1704" <?= $mabp==='1704' ? 'selected' : '' ?>>Bộ Phận Xuất Nhập Khẩu</option>
      <option value="1800" <?= $mabp==='1800' ? 'selected' : '' ?>>Bộ Phận Chất Lượng</option>
      <option value="1900" <?= $mabp==='1900' ? 'selected' : '' ?>>Bộ Phận Kinh Doanh</option>
      <option value="1703" <?= $mabp==='1703' ? 'selected' : '' ?>>Bộ Phận Chăm Sóc Khách Hàng</option>
    </select>
    <?php else: ?>
      <input type="hidden" name="mabp" value="<?= htmlspecialchars($mabp_user) ?>">
    <?php endif; ?>
    <label><b>Nhà máy:</b></label>
    <select id="Ma_NhaMay" name="Ma_NhaMay" class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
      <option value="">Tất cả</option>
      <option value="ASM1" <?= $manhamay==='ASM1' ? 'selected' : '' ?>>ASM1</option>
      <option value="ASM2" <?= $manhamay==='ASM2' ? 'selected' : '' ?>>ASM2</option>
    </select>
    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"><i class="fa-solid fa-filter"></i></button>
  </form>
  &ensp;
      <a href="/web/phep/PhepSQLSRV/dashboard"
      class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700" title="Menu">
      <i class="fa-solid fa-clipboard-list" style="color: #ffffff;"></i>
    </a>&ensp;
    <?php if ($IsHRAdmin == 1): ?>
    <button id="btnToggleNote"
            class="px-4 py-2 rounded bg-gray-600 text-white text-white rounded hover:bg-gray-700 <?= $noteEditEnabled ? 'bg-green-600' : 'bg-gray-600'  ?>">
            <?= $noteEditEnabled ? 'Tắt ghi chú':'Mở ghi chú' ?>
    </button>&ensp;
    <?php endif; ?>

      <button id="btn-xacnhan" type="button" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" title="Xác nhận">
        <i class="fa-solid fa-circle-check" style="color: #ffffff;"></i>
      </button>&ensp;
    <input id="search-input"
             type="text"
             placeholder="Tìm kiếm..."
             class=" pl-10 px-4 py-2 border rounded focus:outline-none focus:ring"
      />
      <div class="ml-auto">
        <a href="logout"
          class="px-4 py-2 bg-gray-200 text-green-800 rounded hover:bg-gray-300">
          <i class="fa-solid fa-right-from-bracket" style="color: #000000;"></i>
        </a>
    </div>
  <!-- nút xác nhận (AJAX) ở form GET nhưng là type=button tránh submit -->
  
</div>

<!-- Table -->
<div class="overflow-x-auto bg-white shadow rounded">
  <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
        <!-- <th class="px-3 py-2 w-10 text-center"><input id="selectAll" type="checkbox" class="form-checkbox"/></th> -->
        <th class="px-3 py-2">Mã NV</th>
        <th class="px-3 py-2">Tên nhân viên</th>
        <th class="px-3 py-2">Ngày công thực tế</th>
        <th class="px-3 py-2">Chi tiết</th>
        <!-- <th class="px-3 py-2">Xác nhận giữa tháng</th> -->
        <th class="px-3 py-2">Ghi chú giữa tháng</th>
        <!-- <th class="px-3 py-2">Xác nhận cuối tháng</th> -->
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
        // $xn1 = !empty($r['Xac_nhan1']) ? 'checked' : '';
        // $xn2 = !empty($r['Xac_nhan2']) ? 'checked' : '';
    ?>
      <tr data-manv="<?= $ma ?>">
        <td class="px-3 py-2"><?= $ma ?></td>
        <td class="px-3 py-2"><?= $ten ?></td>
        <td class="px-3 py-2 text-center"><?= $ngay ?></td>
        <td class="px-3 py-2 text-center">
            <button type="button" class="bg-blue-500 text-white rounded px-2 py-1 hover:bg-blue-600"
            onclick='showCalendarModal(
            <?= json_encode($ma) ?>,
            <?= json_encode($ten) ?>,
            <?= json_encode($r["ChiTietNgayCong"]) ?>,
            <?= json_encode($month) ?>,
            <?= json_encode($year)?>
            )'>
              <i class="fa-solid fa-circle-info"></i>
            </button>
        </td>

        <!-- giữa tháng -->
        <!-- <td class="px-3 py-2 text-center <?= $allow_mid ? 'bg-yellow-50' : 'bg-gray-100' ?>">
          <input type="checkbox" class="xacnhan1" name="Xac_nhan1[<?= $ma ?>]" <?= $xn1 ?>>
        </td> -->
        <td class="px-3 py-2">
          <input type="text" class="ghichu1 w-full border rounded px-2 py-1 " name="Ghi_chu1[<?= $ma ?>]" value="<?= $ghi1 ?>" disabled>
        </td>

        <!-- cuối tháng -->
        <!-- <td class="px-3 py-2 text-center <?= $allow_end ? 'bg-yellow-50' : 'bg-gray-100' ?>">
          <input type="checkbox" class="xacnhan2" name="Xac_nhan2[<?= $ma ?>]" <?= $xn2 ?>>
        </td> -->
        <td class="px-3 py-2">
          <input type="text" class="ghichu2 w-full border rounded px-2 py-1" name="Ghi_chu2[<?= $ma ?>]" value="<?= $ghi2 ?>" disabled>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div id="pagination" class="flex justify-center mt-4"></div>
</div>

<!-- Modal (unchanged) -->
<div id="calendarModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-lg p-6 w-[1000px] max-h-[200vh] overflow-auto">
    
    <!-- Tiêu đề -->
    <h3 id="calendarTitle" class="text-xl font-bold text-center text-blue-600 mb-4"></h3>

    <!-- Lưới lịch -->
    <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm"></div>

    <!-- Chú thích màu -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-6 text-sm">
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-green-300 border border-green-500 rounded"></span> 
        <span>Đủ công</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-yellow-300 border border-yellow-500 rounded"></span> 
        <span>Thiếu công</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-orange-300 border border-orange-500 rounded"></span> 
        <span>Tăng ca</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-cyan-300 border border-cyan-500 rounded"></span> 
        <span>Phép được hưởng lương</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-pink-300 border border-pink-500 rounded"></span> 
        <span>Phép U, S</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-violet-300 border border-violet-500 rounded"></span> 
        <span>Phép thai sản</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-4 h-4 bg-gray-400 border border-gray-700 rounded"></span> 
        <span>Chủ nhật</span>
      </div>
    </div>
    <!-- Nút đóng -->
    <div class="text-center mt-6">
      <button onclick="closeModal()" 
              class="px-5 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
        Đóng
      </button>
    </div>
  </div>
</div>
<!-- Mini Modal -->
 <!-- SUB MODAL GHI CHÚ -->
<div id="noteModal"
     class="hidden fixed inset-0 bg-black bg-opacity-40 items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-5 w-80">
      <h3 class="text-lg font-bold mb-2">Ghi chú ngày <span id="noteDay"></span></h3>

      <div class="mb-3">
          <label class="block font-semibold">Trạng thái:</label>
          <div id="noteStatus" class="text-blue-600 font-semibold"></div>
      </div>

      <textarea id="noteText"
                class="w-full border rounded p-2"
                rows="3"
                placeholder="Nhập ghi chú..."></textarea>

      <div class="flex justify-end gap-2 mt-3">
          <button onclick="closeNoteModal()" class="px-3 py-1 bg-gray-300 rounded">Hủy</button>
          <button onclick="saveNote()" class="px-3 py-1 bg-blue-600 text-white rounded">Lưu</button>
      </div>
  </div>
</div>

<!-- Input để submit về server -->
<input type="hidden" id="notesInput" name="notes">

<!-- SCRIPTS ON/ OFF-->
 
<script>
let NOTE_MODE = <?= ($_SESSION['NOTE_MODE'] ?? 0) ? 'true' : 'false' ?>;
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btnToggleNote');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('toggleNote', 1);
    fd.append('enabled', NOTE_MODE ? 0 : 1);

    try {
      const res = await fetch(window.location.href, {
        method: 'POST',
        body: fd
      });

      const json = await res.json();

      if (json.success) {
        alert('Đã cập nhật. Trang sẽ reload.');
        location.reload(); // reload để lấy NOTE_MODE mới từ PHP
      } else {
        alert(json.message || 'Lỗi cập nhật');
      }
    } catch (e) {
      alert('Lỗi kết nối server');
    }
  });
});
</script>
<!-- SCRIPTS -->
<script>
const notesMap = {};    // tạm lưu ghi chú theo ngày (trong giao diện)
let currentNoteDay = null;

// ===========================
//  MINI NOTE MODAL
// ===========================
function openNoteModal(day, status, oldNote) {
    currentNoteDay = day;
    document.getElementById('noteDay').innerText = day;
    document.getElementById('noteStatus').innerText = status;
    document.getElementById('noteText').value = oldNote || "";

    const modal = document.getElementById('noteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeNoteModal() {
    const modal = document.getElementById('noteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ===========================
//  LƯU GHI CHÚ VỀ SERVER
// ===========================
async function saveNote() {
    const note = document.getElementById("noteText").value.trim();
    const day  = currentNoteDay;

    if (!window.currentMaNV) {
        alert("Không xác định được mã nhân viên.");
        return;
    }

    const loai = (day <= 15) ? 2 : 1;

    const fd = new FormData();
    fd.append("ajaxUpdate", 1);
    fd.append("MaNV", window.currentMaNV);
    fd.append("Thang", window.currentMonth);
    fd.append("Nam", window.currentYear);
    fd.append("Loai", loai);
    fd.append("XacNhan", 1);
    fd.append("GhiChu", note);

    try {
        const res  = await fetch(window.location.href, {
            method: "POST",
            body: fd
        });
        const json = await res.json();

        if (!json.success) {
            alert("Lỗi: " + json.message);
            return;
        }

        // =========================
        // ✅ UPDATE INPUT TRÊN BẢNG
        // =========================
        const tr = document.querySelector(
            `tr[data-manv="${window.currentMaNV}"]`
        );

        if (!tr) {
            console.warn("Không tìm thấy dòng nhân viên trên UI");
        } else {
            if (loai === 1) {
                const i1 = tr.querySelector(".ghichu1");
                if (i1) i1.value = note;
            } else {
                const i2 = tr.querySelector(".ghichu2");
                if (i2) i2.value = note;
            }
        }

        // =========================
        // LƯU VÀO notesMap (calendar)
        // =========================
        notesMap[day] = note;

        closeNoteModal();
        alert("Đã lưu ghi chú!");

    } catch (e) {
        alert("Lỗi kết nối: " + e.message);
    }
}

</script>

<script>
  
// ===========================
//  SHOW CALENDAR MODAL
// ===========================

  function showCalendarModal(empId, empName, chiTiet, month, year) {

    // Lưu meta để saveNote() dùng
    window.currentMaNV = empId;
    window.currentEmpName = empName;
    window.currentMonth = month;
    window.currentYear = year;

    // mở modal
    const modal = document.getElementById('calendarModal');
    const grid = document.getElementById('calendarGrid');
    const title = document.getElementById('calendarTitle');
    title.innerText = `BẢNG CHẤM CÔNG ${empName} - THÁNG ${month}/${year}`;
    grid.innerHTML = "";

    // phân tích chuỗi ChiTietNgayCong
    const statusMap = {};
    const regex = /(\d{1,2})-([^;]+)/gi;
    let match;
    while ((match = regex.exec(chiTiet)) !== null) {
        statusMap[parseInt(match[1], 10)] = match[2];
    }

    const firstDay = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    const startDay = (firstDay.getDay() + 6) % 7;
    const weekdays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

    // header
    weekdays.forEach(d => {
        const h = document.createElement('div');
        h.className = 'font-semibold text-blue-600';
        h.innerText = d;
        grid.appendChild(h);
    });

    for (let i = 0; i < startDay; i++) {
        grid.appendChild(document.createElement('div'));
    }

    // vẽ từng ngày
    for (let day = 1; day <= daysInMonth; day++) {
        const cell = document.createElement('div');
        let status = statusMap[day] || "";

        let bg = 'bg-white border-gray-300';
        const dow = new Date(year, month - 1, day).getDay();
        const isSunday = dow === 0;

        if (/THIEU/i.test(status)) {
            bg = isSunday ? 'bg-gray-500 border-gray-700' : 'bg-yellow-300 border-yellow-500';
        } else if (/^X/i.test(status)) {
            bg = 'bg-green-300 border-green-500';
        } else if (/^U|^S/i.test(status)) {
            bg = 'bg-pink-300 border-pink-500';
        } else if (/^H|^A/i.test(status)) {
            bg = 'bg-blue-300 border-blue-500';
        } else if (/^TS/i.test(status)) {
            bg = 'bg-violet-300 border-violet-500';
        } else if (status.trim() === '--') {
            bg = 'bg-gray-300 border-gray-500';
        }

        if (isSunday && !/THIEU/i.test(status)) {
            bg = 'bg-gray-500 border-gray-700';
        }

        cell.className = `border rounded-lg p-2 ${bg} transition hover:scale-105 hover:shadow-md`;
        cell.innerHTML = `
            <div class="font-bold">${day}</div>
            <div class="text-sm">${status}</div>
        `;

        cell.onclick = () => {
            if (!NOTE_MODE) return;
            openNoteModal(day, status, notesMap[day] || "");
        };

        grid.appendChild(cell);
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    const modal = document.getElementById('calendarModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}
</script>
<script>
  document.getElementById('search-input')
  .addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#datatable tbody tr')
      .forEach(tr => {
        const text = Array.from(tr.cells)
                          .map(td => td.textContent.toLowerCase())
                          .join(' ');
        tr.style.display = text.includes(term) ? '' : 'none';
      });
  });
document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector('#datatable tbody');
  if (!tbody) return; // bảo vệ nếu table chưa tồn tại

  // 1. Lấy mảng row gốc
  const allRows = Array.from(tbody.querySelectorAll('tr'));
  let filteredRows = allRows.slice();

  const rowsPerPage = 10;
  let currentPage = 1;

  // 2. Hiển thị page
  function renderPage(rows, page) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    rows.forEach((tr, i) => {
      tr.style.display = (i >= start && i < end) ? '' : 'none';
    });
  }

  // 3. Vẽ control pagination
  function renderPaginationControls(rows) {
    const pag = document.getElementById('pagination');
    if (!pag) return;
    pag.innerHTML = '';
    const total = Math.max(1, Math.ceil(rows.length / rowsPerPage));

    // Prev
    const prev = document.createElement('button');
    prev.textContent = '‹';
    prev.disabled = currentPage === 1;
    prev.className = 'px-3 py-1 mx-1 rounded ' +
                     (prev.disabled ? 'bg-gray-200 text-gray-400' : 'bg-gray-100 hover:bg-gray-200');
    prev.onclick = () => changePage(Math.max(1, currentPage - 1));
    pag.appendChild(prev);

    // pages with gaps
    const delta = 2;
    const pages = [];
    let last = 0;
    for (let i = 1; i <= total; i++) {
      if (i === 1 || i === total || (i >= currentPage - delta && i <= currentPage + delta)) {
        if (i - last > 1) pages.push('gap');
        pages.push(i);
        last = i;
      }
    }

    pages.forEach(p => {
      if (p === 'gap') {
        const span = document.createElement('span');
        span.textContent = '…';
        span.className = 'px-3 py-1 mx-1';
        pag.appendChild(span);
      } else {
        const btn = document.createElement('button');
        btn.textContent = p;
        btn.className = 'px-3 py-1 mx-1 rounded ' +
                         (p === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200');
        btn.onclick = () => changePage(p);
        pag.appendChild(btn);
      }
    });

    // Next
    const next = document.createElement('button');
    next.textContent = '›';
    next.disabled = currentPage === total;
    next.className = 'px-3 py-1 mx-1 rounded ' +
                     (next.disabled ? 'bg-gray-200 text-gray-400' : 'bg-gray-100 hover:bg-gray-200');
    next.onclick = () => changePage(Math.min(total, currentPage + 1));
    pag.appendChild(next);
  }

  // 4. Thay đổi trang
  function changePage(page) {
    currentPage = page;
    renderPage(filteredRows, currentPage);
    renderPaginationControls(filteredRows);
  }

  // 5. Search & filter (kết hợp với pagination)
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('input', e => {
      const term = e.target.value.trim().toLowerCase();
      currentPage = 1; // reset về trang 1 khi search
      if (!term) {
        filteredRows = allRows.slice();
      } else {
        filteredRows = allRows.filter(tr => {
          return Array.from(tr.cells).some(td =>
            td.textContent.toLowerCase().includes(term)
          );
        });
      }
      changePage(currentPage);
    });
  }

  // 6. Khởi tạo lần đầu
  changePage(1);
});
</script>
</body>
</html>