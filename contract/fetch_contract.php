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

// Nhận dữ liệu lọc từ form
// $maBp = $_GET['maBp'] ?? '';
// $maCbNv = $_GET['maCbNv'] ?? '';
// $soHd = $_GET['soHd'] ?? '';
// $trangThai = $_GET['trang_thai'] ?? '';
// $typeId = $_GET['typeId'] ?? '';
// $isQuanLy = isset($_GET['isQuanLy']) ? (int)$_GET['isQuanLy'] : 0;
// $isVanPhong = isset($_GET['isVanPhong']) ? (int)$_GET['isVanPhong'] : 0;
// $isCongNhan = isset($_GET['isCongNhan']) ? (int)$_GET['isCongNhan'] : 0;
// $maNhaMay = $_GET['maNhaMay'] ?? '';
$LoaiNV = isset($_GET['loainv']) ? trim($_GET['loainv']) : '';
$TTHD = isset($_GET['trang_thai']) ? trim($_GET['trang_thai']) : '';

$stmt = null;
if (isset($_GET['btnFind'])) {
    $sql = "{CALL sp_GET_HDLD_V2(?, ?)}";
    $params = [$TTHD, $LoaiNV];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        echo "<p class='text-red-600 font-bold'>Lỗi truy vấn:</p>";
        die(print_r(sqlsrv_errors(), true));
    }
}

function formatDate($date) {
    if ($date === null || $date === '' || !($date instanceof DateTime) && !is_array($date)) {
        return '';
    }

    // Nếu dữ liệu SQL Server trả về là mảng ['date' => DateTime]
    if (is_array($date) && isset($date['date'])) {
        $date = $date['date'];
    }

    // Nếu là đối tượng DateTime
    if ($date instanceof DateTime) {
        return $date->format('d/m/Y');
    }

    // Nếu là kiểu chuỗi
    return date('d/m/Y', strtotime($date));
}

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Hợp đồng</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script>
  const USER_ID = <?= json_encode($_SESSION['Member_ID']) ?>;
</script>
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
      Quản lý hợp đồng
    </h1>
  </div>
</header>
<form method="get" class="flex flex-wrap items-center gap-3 mb-2">
   
  <!-- Container cha để vị trí relative (dùng absolute positioning cho search nếu cần) -->
  <div class="flex flex-wrap items-center gap-3 mb-2">
    <!-- Bộ phận -->
    <div class="flex items-center space-x-2">
      <label for="maBp" class="font-medium text-gray-700 w-20"><b>Bộ phận</b></label>
      <input type="text" id="maBp" name="maBp"
             class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
    </div>

    <!-- Nhân viên -->
    <div class="flex items-center space-x-2">
      <label for="maCbNv" class="font-medium text-gray-700 w-20"><b>Nhân viên</b></label>
      <input type="text" id="maCbNv" name="maCbNv"
             class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
    </div>

    <!-- Trạng thái -->
    <div class="flex items-center space-x-2">
      <label for="trang_thai" class="font-medium text-gray-700 w-20"><b>Trạng thái</b></label>
      <select id="trang_thai" name="trang_thai"
              class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
        <option value="">--Chọn trạng thái--</option>
        <option value="0" <?= $TTHD=='0'?'selected':'' ?>>Đang hiệu lực</option>
        <option value="1" <?= $TTHD=='1'?'selected':'' ?>>Hết hiệu lực</option>
      </select>
    </div>

    <!-- Nút Tìm -->
  <button id="btnFind" name="btnFind" type="submit"
          class="px-4 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700">
    <i class="fa-solid fa-magnifying-glass"></i>
  </button>

    <!-- Hợp đồng -->
    <div class="flex items-center space-x-2">
      <select id="type_id"
              class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
        <option value="HD_CN">Hợp đồng lao động - công nhân</option>
        <option value="QDBN">Quyết định bổ nhiệm</option>
        <option value="QDDC">Quyết định điều chuyển</option>
        <option value="QDNV">Quyết định nghỉ việc</option>
        <option value="HDTV-30N">Hợp đồng thử việc</option>
        <option value="HD_VP">Hợp đồng lao động - văn phòng</option>
        <option value="PL_CN">Phụ lục hợp đồng lao động - công nhân</option>
        <option value="PL_VP">Phụ lục hợp đồng lao động - văn phòng</option>
        <option value="PL_TV">Phụ lục hợp đồng lao động - thử việc</option>
        <option value="HD_QL">Hợp đồng lao động - quản lý</option>
        <option value="PL_QL">Phụ lục hợp đồng lao động - quản lý</option>
      </select>

      <!-- Nút Gia hạn -->
      <button id="btn-filter" name="btn-filter" type="submit"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
      <i class="fa-solid fa-filter"></i> Lọc
  </button>

      <!-- Nút In -->
      <button type="button"
              class="px-3 py-1.5 bg-gray-400 text-white rounded hover:bg-gray-500 flex items-center gap-1">
        <i class="fa-solid fa-print"></i>
      </button>

      <!-- Nút Lọc -->
      <button type="button"
              class="px-3 py-1.5 bg-gray-400 text-white rounded hover:bg-gray-500 flex items-center gap-1">
        <i class="fa-solid fa-eye"></i>
      </button>
    </div>
  </div>

  <!-- Dòng 2 -->
  <div class="flex flex-wrap items-center gap-3">
    <!-- Số hợp đồng -->
    <div class="flex items-center space-x-2">
      <label for="contractNo" class="font-medium text-gray-700 w-20"><b>Số hợp đồng</b></label>
      <input type="text" id="contractNo" name="contractNo"
             class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
    </div>

    <!-- Loại hợp đồng -->
    <div class="flex items-center space-x-2">
      <label for="contractType2" class="font-medium text-gray-700 w-20"><b>Loại hợp đồng</b></label>
      <input type="text" id="contractType2" name="contractType2"
             class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
    </div>

    <!-- Select -->
    <div class="flex items-center space-x-2">
      <label for="loainv" class="font-medium text-gray-700 w-20"><b>Loại nhân viên</b></label>
         <select id="loainv" name="loainv"
              class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200">
                <option value="">-- Loại nhân viên --</option>
                <option value="1" <?= $LoaiNV=='1'?'selected':'' ?>>Quản lý</option>
                <option value="2" <?= $LoaiNV=='2'?'selected':'' ?>>Văn phòng</option>
                <option value="3" <?= $LoaiNV=='3'?'selected':'' ?>>Công nhân</option>
        </select>
    </div>

    <!-- Nút Update_TDCVPB -->
    <button type="button"
            class="px-3 py-1.5 bg-blue-200 text-blue-800 rounded hover:bg-blue-300 flex items-center gap-1">
      <i class="fa-solid fa-circle-plus"></i> Update_TDCVPB
    </button>

    <!-- Nút Update_Luong -->
    <button type="button"
            class="px-3 py-1.5 bg-blue-200 text-blue-800 rounded hover:bg-blue-300 flex items-center gap-1">
      <i class="fa-solid fa-circle-plus"></i> Update_Luong
    </button>
  </div>
</form>
  <!-- Table -->
  <div class="overflow-x-auto bg-white shadow rounded">
<div class="overflow-x-auto bg-white shadow rounded mt-4">
  <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
        <th class="border px-2 py-1">Chọn dòng</th>
        <th class="border px-2 py-1">Mã nhân viên</th>
        <th class="border px-2 py-1">Tên nhân viên</th>
        <th class="border px-2 py-1">Số hợp đồng</th>
        <th class="border px-2 py-1">Nội dung</th>
        <th class="border px-2 py-1">Ngày ký</th>
        <th class="border px-2 py-1">Mã loại</th>
        <th class="border px-2 py-1">Người ký</th>
        <th class="border px-2 py-1">Ngày bắt đầu</th>
        <th class="border px-2 py-1">Ngày kết thúc</th>
        <th class="border px-2 py-1">Ngày thử việc</th>
        <th class="border px-2 py-1">Ngày chính thức</th>
        <th class="border px-2 py-1">Lý do</th>
        <th class="border px-2 py-1">Đã chấm dứt</th>
        <th class="border px-2 py-1">Loại nhân viên</th>
        <th class="border px-2 py-1">Ngày gia hạn</th>
        <th class="border px-2 py-1">Mã ngạch lương</th>
        <th class="border px-2 py-1">Mã bậc lương</th>
        <th class="border px-2 py-1">Lương cơ bản</th>
        <th class="border px-2 py-1">Tên hợp đồng</th>
        <th class="border px-2 py-1">Phụ cấp ăn trưa</th>
        <th class="border px-2 py-1">Phụ cấp xăng xe</th>
        <th class="border px-2 py-1">Phụ cấp nhà ở</th>
      </tr>
    </thead>

    <tbody class="bg-white divide-y divide-gray-100">
  <?php if ($stmt && sqlsrv_has_rows($stmt)) : ?>
    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) : ?>
      <tr class="hover:bg-gray-100">
        <td class="border px-2 py-1 text-center"><input type="checkbox"></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ma_CbNv'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ten_CbNv'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['So_Hd'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Noi_Dung'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['Ngay_Ky'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Type_Id'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Nguoi_Ky'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['Ngay_Bd'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['Ngay_Kt'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['Ngay_Thu_Viec'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['Ngay_Chinh_Thuc'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ly_Do'] ?? '') ?></td>
        <td class="border px-2 py-1 text-center"><?= ($row['Da_Cham_Dut'] ?? 0) ? '✔' : '' ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Type_Id_LoaiNV'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['Ngay_Gia_Han'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ma_NgachLuong'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ma_BacLuong'] ?? '') ?></td>
        <td class="border px-2 py-1 text-right"><?= number_format($row['Luong_Cb'] ?? 0) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ten_LoaiHDONG'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ma_Tn1'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ma_Tn2'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['Ma_Tn3'] ?? '') ?></td>
      </tr>
    <?php endwhile; ?>
<?php elseif (isset($_GET['btnFind'])) : ?>
        <tr><td colspan="24" class="text-center py-4 text-gray-500">Không có dữ liệu phù hợp</td></tr>
      <?php else : ?>
        <tr><td colspan="24" class="text-center py-4 text-gray-400">Vui lòng chọn điều kiện và bấm "Tìm"</td></tr>
      <?php endif; ?>
    </tbody>

  </table>
    <div id="pagination" class="flex items-center justify-center mt-4 space-x-1"></div>
  </div>
<script>
// 3. PAGINATION
document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector('#datatable tbody');
  const allRows = Array.from(tbody.querySelectorAll('tr'));
  let filteredRows = allRows.slice();

  const rowsPerPage = 20;
  let currentPage = 1;

  function renderPage(rows, page) {
    const start = (page - 1) * rowsPerPage;
    const end   = start + rowsPerPage;
    rows.forEach((tr, i) => {
      tr.style.display = (i >= start && i < end) ? '' : 'none';
    });
  }

  function renderPaginationControls(rows) {
  const pag = document.getElementById('pagination');
  pag.innerHTML = '';
  const total = Math.ceil(rows.length / rowsPerPage);
  if (total <= 1) return; // Không cần phân trang nếu chỉ có 1 trang

  const maxButtons = 5; // số nút hiển thị quanh trang hiện tại
  let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
  let end = Math.min(total, start + maxButtons - 1);

  // nếu ở cuối bảng thì dịch ngược lại cho đủ maxButtons
  if (end - start + 1 < maxButtons) {
    start = Math.max(1, end - maxButtons + 1);
  }

  // nút prev
  const prev = document.createElement('button');
  prev.textContent = '‹';
  prev.disabled = currentPage === 1;
  prev.className = 'px-3 py-1 mx-1 rounded ' +
                   (prev.disabled
                     ? 'bg-gray-200 text-gray-400'
                     : 'bg-gray-100 hover:bg-gray-200');
  prev.onclick = () => changePage(currentPage - 1);
  pag.appendChild(prev);

  // nếu không ở trang đầu thì hiện dấu ...
  if (start > 1) {
    const dots = document.createElement('span');
    dots.textContent = '...';
    dots.className = 'mx-1 text-gray-500';
    pag.appendChild(dots);
  }

  // các nút số trang
  for (let i = start; i <= end; i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    btn.className = 'px-3 py-1 mx-1 rounded ' +
                     (i === currentPage
                       ? 'bg-blue-600 text-white'
                       : 'bg-gray-100 hover:bg-gray-200');
    btn.onclick = () => changePage(i);
    pag.appendChild(btn);
  }

  // nếu chưa đến cuối thì hiện dấu ...
  if (end < total) {
    const dots = document.createElement('span');
    dots.textContent = '...';
    dots.className = 'mx-1 text-gray-500';
    pag.appendChild(dots);
  }

  // nút next
  const next = document.createElement('button');
  next.textContent = '›';
  next.disabled = currentPage === total;
  next.className = 'px-3 py-1 mx-1 rounded ' +
                   (next.disabled
                     ? 'bg-gray-200 text-gray-400'
                     : 'bg-gray-100 hover:bg-gray-200');
  next.onclick = () => changePage(currentPage + 1);
  pag.appendChild(next);
}


  function changePage(page) {
    currentPage = page;
    renderPage(filteredRows, currentPage);
    renderPaginationControls(filteredRows);
  }

  // Tìm kiếm
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    searchInput.addEventListener('input', e => {
      const term = e.target.value.trim().toLowerCase();
      currentPage = 1;
      filteredRows = term
        ? allRows.filter(tr =>
            Array.from(tr.cells).some(td =>
              td.textContent.toLowerCase().includes(term)
            )
          )
        : allRows;
      changePage(currentPage);
    });
  }

  // ✅ Khởi tạo lần đầu
  changePage(1);
});
</script>

</body>
</html>