<?php

use Vtiful\Kernel\Format;
require_once __DIR__ .'/../connection.php';
require_once __DIR__ .'/../auth.php';


// if (isset($_SESSION['success'])) {
//     echo '<script>alert("' . $_SESSION['success'] . '");</script>';
//     unset($_SESSION['success']);
// }
// if (isset($_SESSION['error'])) {
//     echo '<script>alert("' . $_SESSION['error'] . '");</script>';
//     unset($_SESSION['error']);
// }


$sql = "SELECT * FROM ASPHRAbsenceMaster
          WHERE CurrentYear = YEAR(GETDATE())";
$stmt = sqlsrv_query($conn, $sql);

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
function formatNumber($num) {
    if ($num === null || $num === '') return '0';

    $num = (float)$num;

    // format 4 số thập phân trước
    $formatted = number_format($num, 4, '.', '');

    // bỏ số 0 dư phía sau
    $formatted = preg_replace('/\.?0+$/', '', $formatted);

    return $formatted;
}

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
      <h1 class="text-6xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
     HRAbsences Master
    </h1>
  </div>
</header>
  <!-- Toolbar -->
   
  <!-- Container cha để vị trí relative (dùng absolute positioning cho search nếu cần) -->
<div class="relative mb-4 flex items-center">
  <!-- 1. Bộ lọc Từ ngày – Đến ngày + nút Lọc -->
  <form method="get">
  <div class="flex items-center space-x-2">
  <label for="" class="text-gray-700"><b>Chọn năm:</b></label>
    <select
      name="year"
      id=""
      class="border rounded px-2 py-1 bg-white focus:ring focú:ring-blue-200"
      title="Chọn năm">
      <?php
        $currentYear = date("Y");
        for ($year = $currentYear -1; $year <= $currentYear +5; $year++){
          echo "<option value=\"$year\">$year</option>";
        }
        ?>
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
      <!-- <button id="editBtn" title="Sửa phép"
              class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
        <i class="fa-solid fa-pen-to-square"></i>
      </button>&ensp;
      <button id="delBtn" title="Xóa phép"
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
        <i class="fa-solid fa-trash-can"></i>
      </button>&ensp; -->
      <button id="btn-export"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" title="Export Excel">
      <i class="fa-solid fas fa-download" style="color: #ffffff;"></i>
    </button>&ensp;
      <button id="btn-import"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" title="Import Excel">
      <i class="fa-solid fas fa-upload" style="color: #ffffff;"></i>
    </button>&ensp;
      <a href="/dashboard"
      class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700" title="Menu">
      <i class="fa-solid fa-clipboard-list" style="color: #ffffff;"></i>
    </a>&ensp;
    <!-- <a href="lookup_absence"
       class="px-4 py-2 bg-green-600 text-gray-800 rounded hover:bg-green-300">
      <i class="fa-solid fa-magnifying-glass"></i>
    </a>&ensp;
    <a href="tracking_absence"
      class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" title="Quản lý">
      <i class="fa-solid fa-people-roof"></i>
    </a>&ensp; -->
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
      <th class="border px-2 py-1">Mã NV</th>
      <th class="border px-2 py-1">Phép tồn</th>
      <th class="border px-2 py-1">Phép thưởng</th>
      <th class="border px-2 py-1">Phép năm</th>
      <th class="border px-2 py-1">Giảm phép</th>
      <th class="border px-2 py-1">Thêm phép</th>
      <th class="border px-2 py-1">Thời gian hết hạn phép tồn</th>
      <th class="border px-2 py-1">Khấu trừ tết</th>
      <th class="border px-2 py-1">Tổng phép năm tích lũy</th>
      <th class="border px-2 py-1">Năm hiện tại</th>
      <th class="border px-2 py-1">Năm ngoái</th>
    </tr>

      </thead>
        <tbody class="bg-white divide-y divide-gray-100">
  <?php if ($stmt && sqlsrv_has_rows($stmt)) : ?>
    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) : ?>
      <tr class="hover:bg-gray-100">
        <td class="border px-2 py-1 text-center"><input type="checkbox"></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['EmpID'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['RemainLeaves'])?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['BonusLeaves'])?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['AnualLeaves']) ?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['DownLeaves']) ?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['UpLeaves']) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars(formatDate($row['RemainLeavesExpiredDate'] ?? null)) ?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['LunarYearLeaves']) ?></td>
        <td class="border px-2 py-1"><?= Formatnumber($row['CumulativeTotal']) ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['CurrentYear'] ?? '') ?></td>
        <td class="border px-2 py-1"><?= htmlspecialchars($row['LastYear'] ?? '') ?></td>
    <?php endwhile; ?>
      <?php endif; ?>
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
<script>
document.getElementById('btn-export').addEventListener('click', async () => {
  // 1) Xuất file Excel client‐side
  const wb = XLSX.utils.table_to_book(
    document.getElementById('datatable'),
    { sheet: 'AbsenceMasterList' }
  );
  const ws =wb.Sheets['AbsenceMasterList'];
  ws['!rows']=[];
  XLSX.writeFile(wb, 'AbsenceMasterList.xlsx');

  // 2) Fire-and-forget ghi log
  try {
    const desc = `${USER_ID} đã tải file dữ liệu Absence Master`;
    const res = await fetch('log_event.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: `description=${encodeURIComponent(desc)}`
    });
    const json = await res.json();
    console.log('log_event response', json);
    if (json.status !== 'ok') {
      console.error('AuditLog error:', json.msg);
    }
  } catch (e) {
    console.error('Fetch error while logging export:', e);
  }
});
</script>
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
  <div id="modalBackdrop"
       class="fixed inset-0 bg-black bg-opacity-50 hidden"></div>
  <!-- Modal Import -->
<div id="importModal"
     class="fixed inset-0 flex items-center justify-center p-4 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
    <div class="px-6 py-4 border-b">
      <h2 class="text-xl font-bold">Import Excel</h2>
    </div>
    <form action="import" method="post"
          enctype="multipart/form-data" class="px-6 py-4 space-y-4">
      <input type="file" name="excelfile" accept=".xlsx">
      <div class="flex justify-end space-x-2">
        <button type="button" onclick="closeModal('importModal')"
                class="px-4 py-2 bg-gray-400 text-white rounded">
          Hủy
        </button>
        <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded">
          Import
        </button>
      </div>
    </form>
  </div>
</div>

  <script>
    // Mở / đóng modal
    function openModal(id) {
      document.getElementById('modalBackdrop').classList.remove('hidden');
      document.getElementById(id).classList.remove('hidden');
    }
    function closeModal(id) {
      document.getElementById('modalBackdrop').classList.add('hidden');
      document.getElementById(id).classList.add('hidden');
    }
        // BTN Import
    document.getElementById('btn-import').onclick = () => {
      openModal('importModal');
    };
    // DelBtn
  //   document.getElementById('delBtn').onclick = () => {
  // const selected = getSelectedRows();

  // if (selected.length === 0) {
  //   return alert('Chọn ít nhất 1 dòng để xóa');
  // }
  // if (!confirm('Dữ liệu sẽ mất VĨNH VIỄN sau khi xóa, bạn có muốn tiếp tục?')) {
  //   return;
  // }

  // Xây chuỗi params: id[]=1&id[]=2…
  // const params = selected
  //   .map(cb => 'id[]=' + encodeURIComponent(cb.value))
  //   .join('&');

  // // Chuyển trang GET, hoặc bạn có thể chuyển thành form POST
  // window.location = 'del?' + params;
// };
  </script>
    
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
</body>
<?php
if (isset($_SESSION['success'])) {
    echo '<script>
        window.onload = function() {
            alert(' . json_encode($_SESSION['success']) . ');
        }
    </script>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<script>
        window.onload = function() {
            alert(' . json_encode($_SESSION['error']) . ');
        }
    </script>';
    unset($_SESSION['error']);
}
?>
</html>