<?php
    require_once __DIR__ .'/../connection.php';

function fmtNum($val) {
    // đảm bảo là string
    $s = (string)$val;

    // nếu không có dấu chấm thì trả luôn
    if (strpos($s, '.') === false) {
        return $s;
    }

    // xoá số 0 phía sau
    $s = rtrim($s, '0');

    // xoá dấu . nếu còn ở cuối
    $s = rtrim($s, '.');

    // nếu dạng ".xxx" → thêm 0 phía trước
    if (strpos($s, '.') === 0) {
        $s = '0' . $s;
    }

    return $s;
}


$from_date = date('Y-m-d', strtotime($_GET['from-date'] ?? date('Y-m-01')));
$to_date   = date('Y-m-d', strtotime($_GET['to-date'] ?? date('Y-m-t')));


$sql = "{CALL sp_Guest_SearchBOM(?, ?)}";
$params = [
  [date_create($from_date), SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME],
  [date_create($to_date),   SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME]
];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die('SQL Error: ' . print_r(sqlsrv_errors(), true));
}
$bom = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $bom[] = $row;
}
sqlsrv_free_stmt($stmt);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Search BOM</title>
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
     SEARCH BOM
    </h1>
  </div>
</header>
  <!-- Toolbar -->
<div class="relative mb-4 flex items-center">
  <!-- 1. Bộ lọc Từ ngày – Đến ngày + nút Lọc -->
  <form method="get">
  <div class="flex items-center space-x-2">
  <label for="from-date" class="text-gray-700 focus:ring focus:ring-blue-200"><b>From date:</b></label>
  <input
    type="date"
    id="from-date"
    name="from-date"
    value="<?= date('Y-m-d', strtotime($from_date)) ?>"
    class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200"
  />

  <label for="to-date" class="text-gray-700"><b>To date:</b></label>
  <input
    type="date"
    id="to-date"
    name="to-date"
    value="<?= date('Y-m-d', strtotime($to_date)) ?>"
    class="border rounded px-2 py-1 bg-white focus:ring focus:ring-blue-200"
  />
    <button
    id="btn-filter"
    title="Filter"
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
             placeholder="Search anything..."
             class="w-full pl-10 pr-80 py-2 border rounded focus:outline-none focus:ring"
      />
    </div>
  <!-- Right: Dashboard -->
     <div class="ml-auto flex items-center space-x-2">
       <a href="logout"
       class="px-4 py-2 bg-gray-200 text-green-800 rounded hover:bg-gray-300" title="Logout">
      <i class="fa-solid fa-right-from-bracket" style="color: #000000;"></i>
    </a>
    </div>
  </div>
  <!-- Table -->
  <div class="overflow-x-auto bg-white shadow rounded">
    <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
      <th class="border px-2 py-1">Product Code</th>
      <th class="border px-2 py-1">Materials Code</th>
      <th class="border px-2 py-1">Materials Name</th>
      <th class="border px-2 py-1">Suplier Name</th>
      <th class="border px-2 py-1">Lead Time</th>
      <th class="border px-2 py-1">MOQ</th>
      <th class="border px-2 py-1">Quantity</th>
      <th class="border px-2 py-1">Unit</th>
      <!-- <th class="border px-2 py-1">Price (USD)</th> -->
      <th class="border px-2 py-1">In Use</th>
      <th class="border px-2 py-1">Replacement Group Code</th>
      <th class="border px-2 py-1">Is Semi Product</th>
    </tr>

      </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach($bom as $i => $a): ?>
                <tr>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Ma_Sp']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Ma_Vt']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Ten_Vt_Del']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Ma_Dt_Kh']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Lead_Time']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['MOQ']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['So_Luong']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Dvt']) ?></td>
                <!-- <td class="px-3 py-2"><?= htmlspecialchars($a['TGia_Sau_Thue_Usd']) ?></td> -->
                <td class="px-3 py-2"><?= htmlspecialchars($a['Is_Used']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['Ma_Vt_Thay_The']) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($a['IS_BTP']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div id="pagination" class="flex items-center justify-center mt-4 space-x-1"></div>
  </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {

  const tbody = document.querySelector('#datatable tbody');
  const searchInput = document.getElementById('search-input');

  const allRows = Array.from(tbody.querySelectorAll('tr'));
  let filteredRows = [...allRows];

  const rowsPerPage = 50;
  let currentPage = 1;

  // ===== Render table theo page =====
  function renderPage(rows, page) {
    const start = (page - 1) * rowsPerPage;
    const end   = start + rowsPerPage;

    allRows.forEach(tr => tr.style.display = 'none'); // ẩn hết

    rows.slice(start, end).forEach(tr => {
      tr.style.display = '';
    });
  }

  // ===== Pagination =====
function renderPaginationControls(rows) {
  const pag = document.getElementById('pagination');
  pag.innerHTML = '';

  const total = Math.ceil(rows.length / rowsPerPage);

  // Prev
  const prev = document.createElement('button');
  prev.textContent = '‹';
  prev.disabled = currentPage === 1;
  prev.className = 'px-3 py-1 mx-1 rounded ' +
    (prev.disabled ? 'bg-gray-200 text-gray-400' : 'bg-gray-100 hover:bg-gray-200');
  prev.onclick = () => changePage(currentPage - 1);
  pag.appendChild(prev);

  const delta = 2;
  const pages = [];
  let last = 0;

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

  pages.forEach(p => {
    if (p === 'gap') {
      const span = document.createElement('span');
      span.textContent = '...';
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

  // Next
  const next = document.createElement('button');
  next.textContent = '›';
  next.disabled = currentPage === total;
  next.className = 'px-3 py-1 mx-1 rounded ' +
    (next.disabled ? 'bg-gray-200 text-gray-400' : 'bg-gray-100 hover:bg-gray-200');
  next.onclick = () => changePage(currentPage + 1);
  pag.appendChild(next);
}

  // ===== Change page =====
  function changePage(page) {
    currentPage = page;
    renderPage(filteredRows, currentPage);
    renderPaginationControls(filteredRows);
  }

  // ===== SEARCH khi bấm Enter =====
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();

      const term = this.value.trim().toLowerCase();
      currentPage = 1;

      if (!term) {
        filteredRows = [...allRows];
      } else {
        filteredRows = allRows.filter(tr =>
          Array.from(tr.cells).some(td =>
            td.textContent.toLowerCase().includes(term)
          )
        );
      }

      changePage(1);
    }
  });

  // ===== INIT =====
  changePage(1);
});
</script>
