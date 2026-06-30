<?php
require_once __DIR__ .'/../auth.php';
require_once __DIR__ .'/../connection.php';
require_once 'record.php';

if (!empty($_SESSION['success'])) {
    echo '<script>alert("'. addslashes($_SESSION['success']) .'");</script>';
    unset($_SESSION['success']);
  }
if (!empty($_SESSION['error'])) {
    echo '<script>alert("'. addslashes($_SESSION['error']) .'");</script>';
    unset($_SESSION['error']);
}


//lay danh sach thiet bi

$stmt = sqlsrv_query($conn, "SELECT * FROM ASPEmpDevices ORDER BY Department ASC");
$devices = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
  $devices[] = $r;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Quản lý Thiết bị</title>
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
      Quản lý thiết bị
    </h1>
  </div>
</header>


  <!-- Toolbar -->
   
  <div class="relative mb-4 flex items-center">
  <!-- Left: Add/Edit/Delete/Export/Import/Chuyển trang -->
  <div class="flex items-center space-x-2">
    <button id="btn-add"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" title="Thêm">
      <i class="fa-solid fa-plus" style="color: #ffffff;"></i>
    </button>
    
    <button id="btn-edit"
            class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600" title="Sửa">
      <i class="fa-solid fa-pen-to-square" style="color: #ffffff;"></i>
    </button>
    <button id="btn-delete"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" title="Xóa">
      <i class="fa-solid fa-trash-can" style="color: #ffffff;"></i>
    </button>
    <button id="btn-export"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" title="Export Excel">
      <i class="fa-solid fas fa-download" style="color: #ffffff;"></i>
    </button>
    <button id="btn-import"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700" title="Import Excel">
      <i class="fa-solid fas fa-upload" style="color: #ffffff;"></i>
    </button>
    <a href="/dashboard"
      class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700" title="Menu">
      <i class="fa-solid fa-clipboard-list" style="color: #ffffff;"></i>
    </a>
    <!-- Chuyển trang, size giống Import -->
    <div class="relative">
  <button id="dropdown-toggle"
          class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
    Chuyển trang <i class="fas fa-caret-square-down" style="color: #ffffff;"></i>
  </button>
  <ul id="dropdown-menu"
      class="absolute right-0 mt-2 w-48 bg-white border rounded shadow-lg hidden">
    <li>
      <a href="manage_camera.php"
         class="block px-4 py-2 hover:bg-gray-100">
        Quản lý Camera
      </a>
    </li>
    <li>
      <a href="manage_printer.php"
         class="block px-4 py-2 hover:bg-gray-100">
        Quản lý máy in
      </a>
    </li>
    <li>
      <a href="manage_other_it.php"
         class="block px-4 py-2 hover:bg-gray-100">
        Quản lý CNTT khác
      </a>
    </li>
  </ul>
</div>

  </div>
<!-- Middle: Search với icon -->
  <div class="absolute left-1/2 transform -translate-x-1/2 w-full max-w-md px-4">
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
  </div>
  <!-- Right: Dashboard -->
     <div class="ml-auto flex items-center space-x-2">
       <a href="logout"
       class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
      <i class="fa-solid fa-right-from-bracket" style="color: #000000;"></i>
    </a>
    </div>
  </div>
  <!-- Table -->
   <form id="deviceForm" method="post" action="">
  <div class="overflow-x-auto bg-white shadow rounded">
    <table id="datatable" class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-cyan-600 text-white">
      <tr>
        <th class="px-3 py-2 w-10"><input id="selectAll" type="checkbox" class="form-checkbox"/></th>
        <th class="px-3 py-2 w-8 text-center">STT</th>
        <th class="px-3 py-2 w-24">Mã CCDC</th>
        <th class="px-3 py-2 w-24">Mã NV</th>
        <th class="px-3 py-2 w-40">Họ & Tên</th>
        <th class="px-3 py-2 w-32">Phòng ban</th>
        <th class="px-3 py-2 w-32">Nhà máy</th>
        <th class="px-3 py-2 w-24">Loại NV</th>
        <th class="px-3 py-2 w-32">Thiết bị</th>
        <th class="px-3 py-2 w-24">Trạng thái</th>
        <th class="px-3 py-2 w-32">Cấu hình</th>
        <th class="px-3 py-2 w-28">Ngày cấp</th>
        <th class="px-3 py-2 w-28">Ngày BT</th>
        <th class="px-3 py-2">Ghi chú</th>
      </tr>
      </thead>
      <tbody id="deviceTbody" class="bg-white divide-y divide-gray-100">
      <?php foreach($devices as $i => $d): ?>
        <tr class="<?= $i % 2 ? 'bg-gray-50' : '' ?>">
          <td class="px-3 py-2 text-center">
            <input type="checkbox"
                           name="selected"
                           class="row-checkbox form-checkbox"
                           value="<?= (int)$d['AutoID'] ?>"
              data-toolid="<?= htmlspecialchars($d['Tool_ID']) ?>"
              data-empid="<?= htmlspecialchars($d['EmpID']) ?>"
              data-empname="<?= htmlspecialchars($d['EmpName']) ?>"
              data-department="<?= htmlspecialchars($d['Department']) ?>"
              data-factory="<?= htmlspecialchars($d['Factory']) ?>"
              data-employeetype="<?= htmlspecialchars($d['EmployeeType']) ?>"
              data-devicetype="<?= htmlspecialchars($d['DevicesType']) ?>"
              data-status="<?= htmlspecialchars($d['Status']) ?>"
              data-configuration="<?= htmlspecialchars($d['Configuration']) ?>"
              data-issuedate="<?= $d['IssueDate']? $d['IssueDate']->format('Y-m-d'):'' ?>"
              data-maintenance="<?= $d['MaintenanceDay']? $d['MaintenanceDay']->format('Y-m-d'):'' ?>"
              data-note="<?= htmlspecialchars($d['Note']) ?>">

          </td>
        <td class="px-3 py-2 text-center"><?= $i+1 ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['Tool_ID'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['EmpID'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['EmpName'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['Department'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['Factory'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['EmployeeType'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['DevicesType'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['Status'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['Configuration'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-3 py-2 text-center">
          <?= $d['IssueDate']? $d['IssueDate']->format('d-m-Y'): '' ?>
        </td>
        <td class="px-3 py-2 text-center">
          <?= $d['MaintenanceDay']? $d['MaintenanceDay']->format('d-m-Y'): '' ?>
        </td>
        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['Note'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</form>
    <div id="pagination" class="flex items-center justify-center mt-4 space-x-1"></div>
  </div>
<script>
document.getElementById('btn-export').addEventListener('click', async () => {
  // 1) Xuất file Excel client‐side
  const wb = XLSX.utils.table_to_book(
    document.getElementById('datatable'),
    { sheet: 'Devices' }
  );
  const ws =wb.Sheets['Devices'];
  ws['!rows']=[];
  XLSX.writeFile(wb, 'Devices.xlsx');

  // 2) Fire-and-forget ghi log
  try {
    const desc = `${USER_ID} đã tải file dữ liệu quản lý thiết bị`;
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
  <!-- Backdrop chung -->
  <div id="modalBackdrop"
       class="fixed inset-0 bg-black bg-opacity-50 hidden"></div>
<!-- Modal Thêm Thiết Bị -->
<div id="addModal"
     class="fixed inset-0 flex items-center justify-center p-4 hidden bg-black/30 backdrop-blur-sm z-50">
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-4xl overflow-auto">
    
    <!-- Header -->
    <div class="px-6 py-3 border-b flex justify-between items-center">
      <h2 class="text-xl font-semibold">Thêm Thiết Bị</h2>
      <button type="button" onclick="closeModal('addModal')" class="text-gray-500 hover:text-gray-800 text-lg">✖</button>
    </div>

    <!-- Form -->
    <form id="addForm" action="add" method="post" class="px-6 py-5 space-y-4">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- ========== CỘT TRÁI ========== -->
        <div class="space-y-3">

          <!-- Mã sinh tự động -->
          <div>
            <label for="donvi" class="block text-sm font-medium mb-1">Đơn vị</label>
            <select id="donvi" name="donvi" class="border px-2 py-1 rounded w-full">
              <option value="">--Chọn đơn vị--</option>
              <option value="ASM1">ASM1</option>
              <option value="ASM2">ASM2</option>
              <option value="ASM3">ASM3</option>
            </select>
          </div>

          <div>
            <label for="loai" class="block text-sm font-medium mb-1">Loại thiết bị (Sinh mã)</label>
            <select id="loai" name="loai" class="border px-2 py-1 rounded w-full">
              <option value="">--Chọn loại--</option>
              <option value="LT">Laptop</option>
              <option value="BPC">Bộ PC</option>
              <option value="MTB">Tablet</option>
              <option value="DTB">Điện thoại bàn</option>
              <option value="DTDD">Điện thoại di động</option>
              <option value="TV">Tivi</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Mã CCDC</label>
            <input type="text" id="Tool_ID" name="Tool_ID" readonly
                   class="w-full border px-2 py-1 rounded bg-gray-100 text-gray-700">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Họ & Tên</label>
            <input type="text" name="EmpName" required
                   class="w-full border px-2 py-1 rounded">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Nhà máy</label>
            <select name="Factory" class="w-full border px-2 py-1 rounded">
              <option value="ASM1">ASM1</option>
              <option value="ASM2">ASM2</option>
              <option value="ASM3">ASM3</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Loại thiết bị</label>
            <select name="DevicesType" class="w-full border px-2 py-1 rounded">
              <option value="PC">BỘ PC</option>
              <option value="Laptop">Laptop</option>
              <option value="Tablet">Tablet</option>
              <option value="Điện thoại bàn">Điện thoại bàn</option>
              <option value="Điện thoại di động">Điện thoại di động</option>
              <option value="TV">Tivi</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Cấu hình</label>
            <input type="text" name="Configuration" required
                   class="w-full border px-2 py-1 rounded">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Ngày bảo trì</label>
            <input type="date" name="MaintenanceDay"
                   class="w-full border px-2 py-1 rounded">
          </div>

        </div>

        <!-- ========== CỘT PHẢI ========== -->
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium mb-1">Mã NV</label>
            <input type="text" name="EmpID" required
                   class="w-full border px-2 py-1 rounded">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Phòng ban</label>
            <select name="Department" class="w-full border px-2 py-1 rounded">
              <option value="BOD">BOD</option>
              <option value="ACC">ACC</option>
              <option value="MCD">MCD</option>
              <option value="HRGA">HRGA</option>
              <option value="IT">IT</option>
              <option value="QA">QA</option>
              <option value="PROD">PROD</option>
              <option value="ENG">ENG</option>
              <option value="SRC">SOURCING</option>
              <option value="LOG">LOG</option>
              <option value="CS">CS</option>
              <option value="SALES">SALES</option>
              <option value="PU">PU</option>
              <option value="WH">WH</option>
              <option value="PLANNING">PLANNING</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Loại NV</label>
            <input type="text" name="EmployeeType" required
                   class="w-full border px-2 py-1 rounded">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select name="Status" required
                    class="w-full border px-2 py-1 rounded">
              <option value="Stock">Stock</option>
              <option value="Tốt">Tốt</option>
              <option value="Hư">Hư</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Ngày cấp</label>
            <input type="date" name="IssueDate"
                   class="w-full border px-2 py-1 rounded">
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Ghi chú</label>
            <textarea name="Note" rows="5" class="w-full border px-2 py-1 rounded"></textarea>
          </div>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-3 pt-4 border-t mt-4">
        <button type="button" onclick="closeModal('addModal')"
                class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
          Hủy
        </button>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Lưu
        </button>
      </div>

    </form>
  </div>
</div>

<!-- Script sinh mã tự động -->
<script>
document.querySelectorAll('#donvi, #loai').forEach(select => {
  select.addEventListener('change', async () => {
    const donvi = document.getElementById('donvi').value;
    const loai = document.getElementById('loai').value;
    if (donvi && loai) {
      try {
        const res = await fetch(`./get_next_numb.php?donvi=${encodeURIComponent(donvi)}&loai=${encodeURIComponent(loai)}`);
        const data = await res.json();
        document.getElementById('Tool_ID').value = data.next_tool_id || '';
      } catch (e) {
        console.error('Lỗi lấy mã:', e);
        document.getElementById('Tool_ID').value = '';
      }
    } else {
      document.getElementById('Tool_ID').value = '';
    }
  });
});
</script>

<!-- Modal Edit -->
<div id="editModal"
       class="fixed inset-0 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl overflow-auto">
      <div class="px-6 py-4 border-b">
        <h2 class="text-xl font-bold">Sửa Thiết bị</h2>
      </div>
      <form id="editForm" method="post" class="px-6 py-4 space-y-3">
        <!-- sẽ set action động bằng JS -->

        <!-- Grid 2 cột giống Add -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Mã CCDC</label>
            <input id="editTool_ID" type="text" name="Tool_ID" required
                   class="w-full border px-2 py-1 rounded">
          </div>
          <!-- Mã NV -->
          <div>
            <label class="block text-sm font-medium mb-1">Mã NV</label>
            <input id="editEmpID" type="text" name="EmpID" required
                   class="w-full border px-2 py-1 rounded">
          </div>
          <!-- Họ & Tên -->
          <div>
            <label class="block text-sm font-medium mb-1">Họ & Tên</label>
            <input id="editEmpName" type="text" name="EmpName" required
                   class="w-full border px-2 py-1 rounded">
          </div>
          <!-- Phòng ban -->
          <div>
            <label class="block text-sm font-medium mb-1">Phòng ban</label>
            <select id="editDepartment" type="text" name="Department" 
                   class="w-full border px-2 py-1 rounded focus:ring focus:ring-blue-200">
                      <option value="BOD">BOD</option>
                      <option value="ACC">ACC</option>
                      <option value="MCD">MCD</option>
                      <option value="HRGA">HRGA</option>
                      <option value="IT">IT</option>
                      <option value="QA">QA</option>
                      <option value="PROD">PROD</option>
                      <option value="ENG">ENG</option>
                      <option value="SRC">SOURCING</option>
                      <option value="LOG">LOG</option>
                      <option value="CS">CS</option>
                      <option value="SALES">SALES</option>
                      <option value="PU">PU</option>
                      <option value="WH">WH</option>
                      <option value="PLANNING">PLANNING</option>
            </select>
          </div>
          <!-- Nhà máy -->
          <div>
            <label class="block text-sm font-medium mb-1">Nhà máy</label>
            <select id="editFactory" type="text" name="Factory" 
                   class="w-full border px-2 py-1 rounded focus:ring focus:ring-blue-200">
                      <option value="ASM1">ASM1</option>
                      <option value="ASM2">ASM2</option>
            </Select>
          </div>
          <!-- Loại NV -->
          <div>
            <label class="block text-sm font-medium mb-1">Loại NV</label>
            <input id="editEmployeeType" type="text" name="EmployeeType" required
                   class="w-full border px-2 py-1 rounded">
          </div>
          <!-- Loại thiết bị -->
          <div>
            <label class="block text-sm font-medium mb-1">Loại thiết bị</label>
            <select id="editDevicesType" type="text" name="DevicesType" 
                   class="w-full border px-2 py-1 rounded focus:ring focus:ring-blue-200">
                        <option value="PC">BỘ PC</option>
                        <option value="Laptop">Laptop</option>
                        <option value="Tablet">Tablet</option>
                        <option value="Điện thoại bàn">Điện thoại bàn</option>
                        <option value="Điện thoại di động">Điện thoại di động</option>
            </select>
          </div>
          <!-- Trạng thái -->
          <div>
            <label class="block text-sm font-medium mb-1">Trạng thái</label>
            <select id="editStatus" type="text" name="Status" required
                   class="w-full border px-2 py-1 rounded focus:ring focus:ring-blue-200">
                        <option value="Stock">Stock</option>
                        <option value="Tốt">Tốt</option>
                        <option value="Hư">Hư</option>
          </select>
          </div>

          <!-- Cột phải -->
          <div>
            <label class="block text-sm font-medium mb-1">Cấu hình</label>
            <input id="editConfiguration" type="text" name="Configuration" required
                   class="w-full border px-2 py-1 rounded">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Ngày cấp</label>
            <input id="editIssueDate" type="date" name="IssueDate" 
                   class="w-full border px-2 py-1 rounded">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Ngày bảo trì</label>
            <input id="editMaintenanceDay" type="date" name="MaintenanceDay" 
                   class="w-full border px-2 py-1 rounded">
          </div>
        </div>

        <!-- Ghi chú full-width -->
        <div>
          <label class="block text-sm font-medium mb-1">Ghi chú</label>
          <textarea id="editNote" name="Note" rows="3"
                    class="w-full border px-2 py-1 rounded"></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end space-x-2 mt-4">
          <button type="button" onclick="closeModal('editModal')"
                  class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
            Hủy
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
            Cập nhật
          </button>
        </div>
      </form>
    </div>
  </div>
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

    // Lấy radio đang chọn
/**
 * Trả về mảng các <input name="selected"> đang được check
 */
    function getSelectedRows() {
      return [
        ...document.querySelectorAll('input[name="selected"]:checked')
      ];
    }

    // BTN Add
    document.getElementById('btn-add').onclick = () => {
      document.querySelector('#addModal form').reset();
      openModal('addModal');
    };

    // BTN Import
    document.getElementById('btn-import').onclick = () => {
      openModal('importModal');
    };

    // BTN Edit
document.getElementById('btn-edit').onclick = () => {
  const selected = getSelectedRows();

  if (selected.length !== 1) {
    return alert('Chọn đúng 1 dòng để sửa');
  }

  const r = selected[0];
  const form = document.getElementById('editForm');

  // set action
  form.action = 'edit?id=' + encodeURIComponent(r.value);

  // prefill
  document.getElementById('editTool_ID').value         = r.dataset.toolid;
  document.getElementById('editEmpID').value          = r.dataset.empid;
  document.getElementById('editEmpName').value        = r.dataset.empname;
  document.getElementById('editDepartment').value     = r.dataset.department;
  document.getElementById('editFactory').value        = r.dataset.factory;
  document.getElementById('editEmployeeType').value   = r.dataset.employeetype;
  document.getElementById('editDevicesType').value    = r.dataset.devicetype;
  document.getElementById('editStatus').value         = r.dataset.status;
  document.getElementById('editConfiguration').value  = r.dataset.configuration;
  document.getElementById('editIssueDate').value      = r.dataset.issuedate;
  document.getElementById('editMaintenanceDay').value = r.dataset.maintenance;
  document.getElementById('editNote').value           = r.dataset.note;

  openModal('editModal');
}

    // BTN Delete
document.getElementById('btn-delete').onclick = () => {
  const selected = getSelectedRows();

  if (selected.length === 0) {
    return alert('Chọn ít nhất 1 dòng để xóa');
  }
  if (!confirm('Dữ liệu sẽ mất VĨNH VIỄN sau khi xóa, bạn có muốn tiếp tục?')) {
    return;
  }

  // Xây chuỗi params: id[]=1&id[]=2…
  const params = selected
    .map(cb => 'id[]=' + encodeURIComponent(cb.value))
    .join('&');

  // Chuyển trang GET, hoặc bạn có thể chuyển thành form POST
  window.location = 'del?' + params;
};
  </script>
  <script>
// 1. SEARCH: filter mọi ô trong mỗi row
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

// 2. DROPDOWN toggle
const ddToggle = document.getElementById('dropdown-toggle');
const ddMenu   = document.getElementById('dropdown-menu');
ddToggle.addEventListener('click', () => {
  ddMenu.classList.toggle('hidden');
});
// ẩn dropdown khi click ngoài
document.addEventListener('click', e => {
  if (!ddToggle.contains(e.target)) {
    ddMenu.classList.add('hidden');
  }
});

// 3. PAGINATION
document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector('#datatable tbody');
  // 1. Lấy mảng row gốc
  const allRows = Array.from(tbody.querySelectorAll('tr'));
  let filteredRows = allRows.slice();  // copy

  const rowsPerPage = 20;
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
    // prev
    const prev = document.createElement('button');
    prev.textContent = '‹';
    prev.disabled = currentPage === 1;
    prev.className = 'px-3 py-1 mx-1 rounded ' +
                     (prev.disabled
                       ? 'bg-gray-200 text-gray-400'
                       : 'bg-gray-100 hover:bg-gray-200');
    prev.onclick = () => changePage(currentPage - 1);
    pag.appendChild(prev);

    // pages
    for (let i = 1; i <= total; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = 'px-3 py-1 mx-1 rounded ' +
                       (i === currentPage
                         ? 'bg-blue-600 text-white'
                         : 'bg-gray-100 hover:bg-gray-200');
      btn.onclick = () => changePage(i);
      pag.appendChild(btn);
    }

    // next
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
    const selectAllCb = document.getElementById('selectAll');
    const tbody       = document.getElementById('deviceTbody');
    let lastChecked  = null;

    // Hàm cập nhật highlight và đồng bộ checkbox "Chọn tất cả"
    function updateState() {
      const allCbs   = Array.from(tbody.querySelectorAll('.row-checkbox'));
      const checkedCbs = allCbs.filter(cb => cb.checked);

      allCbs.forEach(cb => {
        cb.closest('tr').classList.toggle('bg-blue-100', cb.checked);
      });

      selectAllCb.checked = checkedCbs.length === allCbs.length;
      selectAllCb.indeterminate = checkedCbs.length > 0 && checkedCbs.length < allCbs.length;
    }

    // Sự kiện cho checkbox "Chọn tất cả"
    selectAllCb.addEventListener('change', () => {
      const isChecked = selectAllCb.checked;
      tbody.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = isChecked;
      });
      updateState();
    });

    // Gán sự kiện riêng cho từng checkbox dòng
    tbody.querySelectorAll('.row-checkbox').forEach(cb => {
      cb.addEventListener('click', (e) => {
        // Shift+click để chọn liên tiếp
        if (e.shiftKey && lastChecked && lastChecked !== cb) {
          const checkboxes = Array.from(tbody.querySelectorAll('.row-checkbox'));
          const start = checkboxes.indexOf(lastChecked);
          const end   = checkboxes.indexOf(cb);
          const [from, to] = start < end ? [start, end] : [end, start];

          checkboxes.slice(from, to + 1).forEach(c => c.checked = true);
        }

        lastChecked = cb;
        updateState();

        // Ngăn sự kiện click "đánh row" trùng với click checkbox
        e.stopPropagation();
      });
    });

    // Khi click vào bất kỳ ô nào của <tr> (ngoại trừ chính checkbox)
    tbody.addEventListener('click', (e) => {
      const tr = e.target.closest('tr');
      if (!tr) return;

      const cb = tr.querySelector('.row-checkbox');
      if (!cb || e.target === cb) return;

      cb.checked = !cb.checked;
      lastChecked = cb;
      updateState();
    });
  });
</script>
</body>
</html>