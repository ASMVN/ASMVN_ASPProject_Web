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

// Nếu chưa có giá trị GET, dùng đầu và cuối tháng hiện tại
$from_date = $_GET['from-date'] ?? date('Y-m-01');
$to_date   = $_GET['to-date']   ?? date('Y-m-t');
$AHDStatus = 0;
$userid = $_SESSION['Member_ID'];
$typeEmp = ($_GET['emp-type'] ?? 'office') == 'office' ? 0 : 1;

$sql = "{CALL sp_ASPGetHRAbsenceDepartmentV3(?, ?, ?, ?, ?)}";
$params = [
  [$from_date,SQLSRV_PARAM_IN],
  [$to_date, SQLSRV_PARAM_IN],
  [$AHDStatus, SQLSRV_PARAM_IN],
  [$userid, SQLSRV_PARAM_IN],
  [$typeEmp, SQLSRV_PARAM_IN]
];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die('SQL Error: ' . print_r(sqlsrv_errors(), true));
}
// Lấy toàn bộ hàng trả về
$absences = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $absences[] = $row;
}
sqlsrv_free_stmt($stmt);


?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Quản lý phép</title>
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
    <h1
      class="text-[4rem] text-2xl font-bold text-center text-blue-800 mb-6"
    >
      Quản lý phép
    </h1>
  </div>
</header>


  <!-- Toolbar -->
   
  <!-- Container cha để vị trí relative (dùng absolute positioning cho search nếu cần) -->
<div class="relative mb-4 flex items-center">
  <!-- 1. Bộ lọc Từ ngày – Đến ngày + nút Lọc -->
  <form method="get">
  <div class="flex items-center space-x-2">
  <label for="from-date" class="text-gray-700 focus:ring focus:ring-blue-200"><b>Từ ngày:</b></label>
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

  <label for="emp-type" class="text-gray-700"><b>Loại nhân viên:</b></label>
  <select
    id="emp-type"
    name="emp-type"
    class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200"
    title="Chọn loại nhân viên"
  >
    <option value="office">Văn phòng</option>
    <option value="worker">Công nhân</option>
  </select>

  <button
    id="btn-filter"
    title="Lọc"
    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
  >
    <i class="fa-solid fa-filter"></i>
  </button>&ensp;
</div>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const now       = new Date();
    const firstDay  = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay   = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    const fmt = d => d.toISOString().split('T')[0];

    // Chỉ gán nếu input chưa có giá trị sẵn
    const fromInput = document.getElementById('from-date');
    const toInput   = document.getElementById('to-date');

    if (!fromInput.value) fromInput.value = fmt(firstDay);
    if (!toInput.value)   toInput.value   = fmt(lastDay);
  });
</script>
</form>

  <!-- 2. Nút Sửa phép và Hủy phép (chỉ enable khi có dòng được chọn) -->
      <button id="editBtn" title="Sửa phép"
              class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
        <i class="fa-solid fa-pen-to-square"></i>
      </button>&ensp;
      <button id="cancelBtn" title="Hủy phép"
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
        <i class="fa-solid fa-trash-can"></i>
      </button>&ensp;
      <a href="/dashboard"
      class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700" title="Menu">
      <i class="fa-solid fa-clipboard-list" style="color: #ffffff;"></i>
    </a>&ensp;
    <button id="denyBtn" title="Gửi mail từ chối"
              class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
        <i class="fa-solid fa-ban"></i>
      </button>&ensp;
    <div class="relative">
      <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
        <!-- icon search (Heroicons) -->
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-5 w-5"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 16a8 8 0 1116 0 8 8 0 01-16 0z"/>
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-4.35-4.35"/>
        </svg>
      </span>

      <input id="search-input"
             type="text"
             placeholder="Tìm kiếm..."
             class="w-full pl-10 pr-4 py-2 border rounded focus:outline-none focus:ring"
      />
    </div>
  <!-- Right: Dashboard -->
     <div class="ml-auto flex items-center space-x-2">
          <a href="lookup_absence"
       class="px-4 py-2 bg-green-200 text-gray-800 rounded hover:bg-green-300">
      <i class="fa-solid fa-magnifying-glass"></i>
    </a>
       <a href="logout"
       class="px-4 py-2 bg-gray-200 text-green-800 rounded hover:bg-gray-300">
      <i class="fa-solid fa-right-from-bracket" style="color: #000000;"></i>
    </a>
    </div>
  </div>
  <!-- Table -->
  <div class="overflow-x-auto bg-white shadow rounded">
    <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
      <th class="border px-2 py-1">Chọn dòng</th>
      <th class="border px-2 py-1">Thời gian tạo</th>
      <th class="border px-2 py-1">Mã NV</th>
      <th class="border px-2 py-1">Tên nhân viên</th>
      <th class="border px-2 py-1">Chức vụ</th>
      <th class="border px-2 py-1">Phòng ban</th>
      <th class="border px-2 py-1">Ngày nghỉ</th>
      <th class="border px-2 py-1">Số ngày nghỉ</th>
      <th class="border px-2 py-1">Loại phép</th>
      <th class="border px-2 py-1">Lý do</th>
      <th class="border px-2 py-1">Phép tồn</th>
      <th class="border px-2 py-1">HOD</th>
      <th class="border px-2 py-1">BOD</th>
      <th class="border px-2 py-1">HR</th>
      <th class="border px-2 py-1">Tổng phép</th>
      <th class="border px-2 py-1">Tổng phép còn lại</th>
      <th class="border px-2 py-1">Tổng phép năm còn lại</th>
      <th class="border px-2 py-1">Tổng phép thưởng còn lại</th>
      <th class="border px-2 py-1">Phép tồn năm trước còn lại</th>
    </tr>

      </thead>
      <tbody class="bg-white divide-y divide-gray-100">
  <?php foreach($absences as $i => $a): ?>
    <?php $locked = ($a['AHDStatus']== 1 || $a['ABODStatus'] == 1 || $a['HRStatus'] == 1); ?>
    <tr data-id="<?= $a['AutoID'] ?>" data-locked="<?= $locked?1:0?>">
      <td class="px-2 py-1 text-center">
        <input type="radio" name="selected_absence" class="row-radio" value="<?= $a['AutoID'] ?>">
      </td>

      <td class="px-3 py-2 text-center">
        <?= $a['Timestamp'] 
              ? $a['Timestamp']->format('d-m-Y H:i:s') 
              : '' ?>
      </td>
      <td class="px-3 py-2 text-center"><?= htmlspecialchars($a['EmpID']) ?></td>
      <td class="px-3 py-2"><?= htmlspecialchars($a['EmpName']) ?></td>
      <td class="px-3 py-2"><?= htmlspecialchars($a['Position']) ?></td>
      <td class="px-3 py-2"><?= htmlspecialchars($a['DeptName']) ?></td>
      <td class="px-3 py-2 col-date text-center">
        <?= $a['TimeOff'] 
              ? $a['TimeOff']->format('d-m-Y') 
              : '' ?>
      </td>
      <td class="px-3 py-2 col-days text-center"><?= fmtNum($a['NumDateOff']) ?></td>
      <td class="px-3 py-2 col-type"><?= htmlspecialchars($a['TypeOfAbsence']) ?></td>
      <td class="px-3 py-2 col-reason"><?= htmlspecialchars($a['ReasonOfAbsence']) ?></td>
      <td class="px-3 py-2 text-center">
        <?php
          $checked  = $a['IsUseRemainLeave'] ? 'checked'  : '';
          $disabled = !$a['IsUseRemainLeave'] ? 'disabled' : '';
        ?>
        <input
        type="checkbox"
        class="cb-remainleave remainleave"
        data-id="<?= $a['AutoID'] ?>"
        name="remain_leave[<?= $a['AutoID'] ?>]"
        <?= $checked ?> <?= $disabled ?>
      />
    </td>
<?php
$hodChecked = $a['AHDStatus'] ? 'checked' : '';
$bodChecked = $a['ABODStatus'] ? 'checked' : '';
$hrChecked  = $a['HRStatus'] ? 'checked' : '';

// ------------------------------
// HOD hoặc user có quyền IsHRAbsenceMngEmp
// ------------------------------
$hodDisabled = 'disabled';
if ($a['EmpID'] !== 'ASP1687') {
    if ($_SESSION['IsHRAbsenceMng'] == 1 || $_SESSION['IsHRAbsenceMngEmp'] == 1) {
        // HOD có quyền tick nếu HR chưa duyệt
        $hodDisabled = ($a['HRStatus'] == 1) ? 'disabled' : '';
    }
}

// ------------------------------
// BOD (ASP1687 duyệt đơn của HOD)
// ------------------------------
$bodDisabled = 'disabled';
if ($a['EmpID'] !== 'ASP1687') {
    if ($_SESSION['Member_ID'] === 'ASP1687' && $_SESSION['IsHRAbsenceMng'] == 1) {
        // BOD có quyền tick nếu HR chưa duyệt
        $bodDisabled = ($a['HRStatus'] == 1) ? 'disabled' : '';
    }
}

// ------------------------------
// HR (chỉ được duyệt sau khi HOD hoặc BOD đã duyệt)
// ------------------------------
$hrDisabled = 'disabled';
if ($_SESSION['IsHRAdmin'] == 1) {
    if ($a['EmpID'] !== 'ASP1687' && $a['AHDStatus'] == 1) {
        $hrDisabled = '';
    }
    elseif ($a['EmpID'] === 'ASP1687' && $a['ABODStatus'] == 1) {
        $hrDisabled = '';
    }
}
?>

<!-- Cột duyệt HOD -->
<td class="text-center">
  <input
    type="checkbox"
    class="cb-approve HOD"
    data-id="<?= $a['AutoID'] ?>"
    <?= $hodChecked ?> <?= $hodDisabled ?>
  />
</td>

<!-- Cột duyệt BOD -->
<td class="text-center">
  <input
    type="checkbox"
    class="cb-approve BOD"
    data-id="<?= $a['AutoID'] ?>"
    <?= $bodChecked ?> <?= $bodDisabled ?>
  />
</td>

<!-- Cột duyệt HR -->
<td class="text-center">
  <input
    type="checkbox"
    class="cb-approve HR"
    data-id="<?= $a['AutoID'] ?>"
    <?= $hrChecked ?> <?= $hrDisabled ?>
  />
</td>

      <td class="px-3 py-2 text-center"><?= fmtNum($a['CumulativeTotal']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($a['SumRemainAbsense']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($a['SumAnnualLeaves']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($a['SumBonusLeaves']) ?></td>
      <td class="px-3 py-2 text-center"><?= fmtNum($a['SumPreRemainLeaves']) ?></td>
    </tr>
  <?php endforeach; ?>
</tbody>
    </table>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-lg w-96 p-6">
    <h2 class="text-xl mb-4">Chỉnh sửa đơn nghỉ phép</h2>
    <form id="editForm" class="space-y-3">
      <input type="hidden" name="id" id="modal-id">
      <div>
        <label for="TimeOff" class="block mb-1">Ngày nghỉ</label>
        <input type="date" id="TimeOff" name="date" class="w-full border px-2 py-1" required>
      </div>
      <div>
        <label for="numdateoff" class="block mb-1">Số ngày nghỉ</label>
        <input type="number" id="numdateoff" name="days" class="w-full border px-2 py-1" min="0.001" step="0.001"required>
      </div>
      <div>
        <label for="typeofabsence" class="block mb-1">Loại phép</label>
        <select id="typeofabsence" name="type" class="w-full border px-2 py-1" required>
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
      <div>
        <label for="reasonofabsence" class="block mb-1">Lý do</label>
        <textarea id="reasonofabsence" name="reason" class="w-full border px-2 py-1" rows="2"></textarea>
      </div>
      <div class="text-right space-x-2">
        <button type="button" id="cancel" class="px-4 py-1 bg-gray-300 rounded">Đóng</button>
        <button type="submit" class="px-4 py-1 bg-green-600 text-white rounded">Lưu</button>
      </div>
    </form>
  </div>
</div>
<div id="denyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white rounded-lg w-96 p-6">
    <h2 class="text-xl mb-4">Từ chối đơn nghỉ phép</h2>
    <form id="denyForm" class="space-y-3">
      <input type="hidden" name="id" id="deny-id">
      <div>
        <label for="denyReason" class="block mb-1">Lý do từ chối</label>
        <textarea id="denyReason" name="reason" class="w-full border px-2 py-1" rows="3" required></textarea>
      </div>
      <div class="text-right space-x-2">
        <button type="button" id="denyCancel" class="px-4 py-1 bg-gray-300 rounded">Đóng</button>
        <button type="submit" class="px-4 py-1 bg-red-600 text-white rounded">Xác nhận từ chối</button>
      </div>
    </form>
  </div>
</div>


    <div id="pagination" class="flex items-center justify-center mt-4 space-x-1"></div>
  </div>
    <!-- Pagination -->
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
  // 1. Lấy mảng row gốc
  const allRows = Array.from(tbody.querySelectorAll('tr'));
  let filteredRows = allRows.slice();  // copy

  const rowsPerPage = 10;
  let currentPage = 1;

  // 2. Hiển thị page
  function renderPage(rows, page) {
    const start = (page - 1) * rowsPerPage;
    const end   = start + rowsPerPage;
    rows.forEach((tr, i) => {
      tr.style.display = (i >= start && i < end) ? '' : 'none';
    });
  }

  // 3. Vẽ control pagination
  function renderPaginationControls(rows) {
  const pag = document.getElementById('pagination');
  pag.innerHTML = '';
  const total = Math.ceil(rows.length / rowsPerPage);

  // Prev button
  const prev = document.createElement('button');
  prev.textContent = '‹';
  prev.disabled = currentPage === 1;
  prev.className = 'px-3 py-1 mx-1 rounded ' +
                   (prev.disabled
                     ? 'bg-gray-200 text-gray-400'
                     : 'bg-gray-100 hover:bg-gray-200');
  prev.onclick = () => changePage(currentPage - 1);
  pag.appendChild(prev);

  // build danh sách page + ellipsis
  const delta = 2;       // hiển thị currentPage ±2
  const pages = [];
  let   last = 0;

  for (let i = 1; i <= total; i++) {
    if (
      i === 1 ||
      i === total ||
      (i >= currentPage - delta && i <= currentPage + delta)
    ) {
      if (i - last > 1) {
        pages.push('gap');
      }
      pages.push(i);
      last = i;
    }
  }

  // render pages
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
                       (p === currentPage
                         ? 'bg-blue-600 text-white'
                         : 'bg-gray-100 hover:bg-gray-200');
      btn.onclick = () => changePage(p);
      pag.appendChild(btn);
    }
  });

  // Next button
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
  // 4. Thay đổi trang
  function changePage(page) {
    currentPage = page;
    renderPage(filteredRows, currentPage);
    renderPaginationControls(filteredRows);
  }

  // 5. Search & filter
  document.getElementById('search-input')
    .addEventListener('input', e => {
      const term = e.target.value.trim().toLowerCase();
      currentPage = 1;           // reset page về 1 mỗi khi search / clear
      if (!term) {
        filteredRows = allRows;
      } else {
        filteredRows = allRows.filter(tr => {
          return Array.from(tr.cells)
                      .some(td => td.textContent.toLowerCase()
                                     .includes(term));
        });
      }
      changePage(currentPage);
    });

  // 6. Khởi tạo lần đầu
  changePage(1);
});

// khởi khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
  setupPagination('datatable', 'pagination', 10);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tbody     = document.querySelector('#datatable tbody');
  const editBtn   = document.getElementById('editBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const modal     = document.getElementById('editModal');
  const form      = document.getElementById('editForm');
  const cancel    = document.getElementById('cancel');

  // Khi chọn radio → lưu id vào nút
  document.querySelectorAll(".row-radio").forEach(r => {
    r.addEventListener("change", function() {
      let tr = this.closest("tr");
      let id = tr.dataset.id;
      let locked = tr.dataset.locked;

      editBtn.dataset.id   = id;
      cancelBtn.dataset.id = id;
      editBtn.dataset.locked   = locked;
      cancelBtn.dataset.locked = locked;
    });
  });

  // --- Sửa phép ---
  editBtn.addEventListener("click", function() {
    let id = this.dataset.id;
    if (!id) return alert("Chưa chọn đơn phép");

    let tr = document.querySelector(`tr[data-id="${id}"]`);
    if (!tr) return alert("Không tìm thấy dòng dữ liệu");

    let locked = tr.dataset.locked; // "0" hoặc "1"
    if (locked === "1") {
      alert("Đơn đã được duyệt, không thể sửa.");
      return;
    }

    // Fill modal
    form['id'].value     = id;
    form['date'].value   = tr.querySelector('.col-date').innerText.trim();
    form['days'].value   = tr.querySelector('.col-days').innerText.trim();
    form['type'].value   = tr.querySelector('.col-type').innerText.trim();
    form['reason'].value = tr.querySelector('.col-reason').innerText.trim();

    modal.classList.remove('hidden');
  });

  // --- Hủy phép ---
  cancelBtn.addEventListener("click", function() {
    let id = this.dataset.id;
    if (!id) return alert("Chưa chọn đơn phép");

    let tr = document.querySelector(`tr[data-id="${id}"]`);
    if (!tr) return alert("Không tìm thấy dòng dữ liệu");

    if (tr.dataset.locked == "1") {
      alert("Đơn đã được duyệt, không thể hủy.");
      return;
    }

    if (!confirm("Xác nhận hủy phép?")) return;

    fetch("cancel_absence.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: `id=${id}`
    })
    .then(r => r.json())
    .then(res => {
      if (!res.success){
        alert('Hủy phép thất bại!');
      } else {
        alert('Hủy phép thành công!');
      }
      location.reload();
    });
  });

  // Đóng modal
  cancel.addEventListener('click', () => {
    modal.classList.add('hidden');
  });

  // Submit modal → AJAX edit → reload
  form.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(form);
    fetch('update_absence.php', {
      method: 'POST',
      body: fd
    })
    .then(r => r.json())
    .then(res => {
      if (!res.success){
        alert('Sửa phép thất bại');
      }else{
        alert('Sửa phép thành công');
      }
      location.reload();
    })
    .catch(()=>alert('Lỗi kết nối'));
  });
});
</script>
<script>
// Bắt sự kiện nút từ chối
document.getElementById('denyBtn').addEventListener('click', function () {
  // tìm radio được chọn
  const checked = document.querySelector('input[name="selected_absence"]:checked');

  if (!checked) {
    alert("Hãy chọn 1 đơn");
    return;
  }

  const id = checked.value; // AutoID của đơn
  document.getElementById('deny-id').value = id;
  document.getElementById('denyReason').value = "";
  document.getElementById('denyModal').classList.remove('hidden');
});
document.getElementById('denyForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const id = document.getElementById('deny-id').value;
  const reason = document.getElementById('denyReason').value.trim();

  if (!reason) {
    alert("Vui lòng nhập lý do từ chối.");
    return;
  }

  fetch('approve_absence.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      id: id,
      level: 'HR',      // vì chỉ HR được quyền từ chối
      action: 'deny',
      reason: reason
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Đã từ chối đơn phép.");
      location.reload();
    } else {
      alert(data.message);
    }
  })
  .catch(err => console.error(err));
});
// nút Đóng trong modal
document.getElementById('denyCancel').addEventListener('click', function() {
  document.getElementById('denyModal').classList.add('hidden');
});

// click ra ngoài modal cũng đóng
// document.getElementById('denyModal').addEventListener('click', function(e) {
//   if (e.target.id === 'denyModal') {
//     document.getElementById('denyModal').classList.add('hidden');
//   }
// });
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.cb-approve').forEach(cb => {
    cb.addEventListener('click', async (e) => {
      const checkbox  = e.target;
      const isChecked = checkbox.checked;                 // true = duyệt, false = bỏ duyệt
      const id        = checkbox.dataset.id;
      let level       = 'HR';
      if (checkbox.classList.contains('HOD')) level = 'HOD';
      if (checkbox.classList.contains('BOD')) level = 'BOD';

      const msg = isChecked
        ? `Xác nhận DUYỆT (${level})?`
        : `Xác nhận BỎ DUYỆT (${level})?`;

      if (!confirm(msg)) {
        checkbox.checked = !isChecked;
        return;
      }

      try {
        const body = new URLSearchParams({
          id,
          level,
          action: 'approve',      // luôn là approve (kể cả bỏ duyệt)
          status: isChecked ? '1' : '0' // 1: duyệt, 0: bỏ duyệt
        });

        const res = await fetch('approve_absence.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: body.toString()
        });

        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e){ throw new Error('JSON parse error: '+text); }

        if (!data.success) {
          alert('Thao tác thất bại: ' + (data.message || 'Lỗi không xác định'));
          checkbox.checked = !isChecked; // rollback
          return;
        }
        alert(isChecked ? 'Đã duyệt.' : 'Đã bỏ duyệt.');
        location.reload();
      } catch(err) {
        alert('Lỗi kết nối: ' + err.message);
        checkbox.checked = !isChecked; // rollback
      }
    });
  });
});
</script>
</body>
</html>