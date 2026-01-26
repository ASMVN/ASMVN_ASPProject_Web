<?php
require_once __DIR__ .'/../connection.php';
require_once __DIR__ .'/../auth.php';

$userid = $_SESSION['Member_ID'];
$fullname = $_SESSION['full_name'];
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Airspeed Việt Nam - Đăng kí nghỉ phép</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/ee3f99b602.js" crossorigin="anonymous"></script>

</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center py-12 px-4">
<div class="max-w-xl w-full bg-white p-8 rounded-2xl shadow-lg">
    <div class="relative mb-4 flex items-center">
        <div class="flex items-center space-x-2">
        <a href="/web/phep/phepSQLSRV/dashboard"
           class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700" title="Menu">
            <i class="fa-solid fa-clipboard-list" style="color: #ffffff;"></i>
        </a>
        <a href="logout"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition" title="Log out">
            <i class="fa-solid fa-right-from-bracket" style="color: #000000;"></i>
        </a>
        </div>
        <div class="absolute left-1/2 transform -translate-x-1/2 w-full max-w-md px-4"></div>
        <div class="ml-auto flex items-center space-x-2">
        <a href="tracking_absence.php"
           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition" title="Quản lý">
           <i class="fa-solid fa-people-roof"></i>
        </a>
      </div>
    </div>
    <div class="col-md-9 col-lg-6 col-xl-5 mx-auto mb-6">
        <img src="https://th.bing.com/th/id/OIP.nfEpFO2ufpleMYdZ8aFwGgHaFS?rs=1&pid=ImgDetMain"
          class="block mx-auto max-w-full h-auto mb-6" alt="Sample image">
      </div>
    <h1 class="text-2xl font-bold text-center text-blue-800 mb-6">Airspeed Việt Nam - Đăng kí nghỉ phép </h1>

    <!-- Animated Feedback -->
    <div id="alert" class="hidden transition transform duration-500 ease-in-out opacity-0 -translate-y-2 text-center text-lg font-medium px-4 py-3 rounded-md"></div>

    <form id="nameForm" class="space-y-4 mt-4" action="request_action.php" method="post">
        <div>
            <label for="Member_ID" class="block font-medium">Mã số nhân viên</label>
            <p class="mt-1 font-medium text-gray-800"><?= htmlspecialchars($userid)?></p>
            <input type="hidden" name="Member_ID" readonly class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200"
            value="<?= htmlspecialchars($userid)?>" />
        </div>

        <div>
            <label for="full_name" class="block font-medium">Họ và tên</label>
            <p class="mt-1 font-medium text-gray-800"><?= htmlspecialchars($fullname)?></p>
            <input type="hidden" name="full_name" readonly class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200"
            value="<?= htmlspecialchars($fullname)?>" />
        </div>
        <div>
            <label for="timeoff" class="block font-medium">Thời gian nghỉ</label>
            <input type="date" name="timeoff" required class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" />
        </div>

        <div>
            <label for="numdateoff" class="block font-medium">Số ngày nghỉ(Có thể nhập số lẻ, ví dụ: 0.125)</label>
            <input type="text" name="numdateoff" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" />
        </div>

        <div>
            <label for="typeofabsence" class="block font-medium">Loại phép</label>
            <select class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" name="typeofabsence">
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
            <label for="reasonofabsence" class="block font-medium">Lý do xin nghỉ</label>
            <input type="text" name="reasonofabsence" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" />
        </div>

        <div>
            <label for="managermail" class="block font-medium">Email người quản lý</label>
            <select class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200" name="managermail">
                    <option value="tonny.bui@airspeedmfgvn.com">Bùi Đức Thọ - Phó tổng giám đốc</option>
                    <option value="oanh.nguyen@airspeedmfg.com">Nguyễn Thị Oanh - Sales Manager</option>
                    <option value="hr1@airspeedmfgvn.com">Phan Võ Thanh Vi - Trưởng Phòng Nhân Sự</option>
                    <option value="hr2@airspeedmfgvn.com">Nguyễn Thị Diễm My - Assistant Manager</option>
                    <option value="it3@airspeedmfgvn.com">Lê Đăng Anh - Mes Software Developers</option>
                    <option value="plan1@airspeedmfgvn.com">Nguyễn Thị Ngọc Oanh - Nhân Viên Kế Hoạch</option>
                    <option value="plan2@airspeedmfgvn.com">Hồ Ngọc Tài - Planning Senior</option>
                    <option value="cs1@airspeedmfgvn.com">Nguyễn Phi Yến - Trưởng Phòng Dịch Vụ Khách Hàng</option>
                    <option value="log1@airspeedmfgvn.com">Nguyễn Hoàng Sang - Trưởng Phòng Xuất Nhập Khẩu</option>
                    <option value="pu2@airspeedmfgvn.com">Trần Ngọc Quang - Trưởng Phòng Thu Mua</option>
                    <option value="fin1@airspeedmfgvn.com">Phạm Thị Kim Thoa - Kế Toán Trưởng</option>
                    <option value="wh1@airspeedmfgvn.com">Trần Tuấn Anh - Trưởng Phòng Kho Vận</option>
                    <option value="pm@airspeedmfgvn.com">Trần Thanh Hải - Head Of Engineer Cum Production Department</option>
                    <option value="trung.nguyen@airspeedmfgvn.com">Nguyễn Thành Trung - Quản Lý Sản Xuất</option>
                    <option value="technician1@airspeedmfgvn.com">Nguyễn Kim Phú - Giám Sát Kỹ Thuật Viên</option>
                    <option value="qa5@airspeedmfgvn.com">Phạm Lưu Đức Hòa - PQA Engineer</option>
                    <option value="qa1@airspeedmfgvn.com">Trần Đức Huy - Quality Manager</option>
                    <option value="qm2@airspeedmfgvn.com">Trần Văn Xuân - Quality Manager</option>
                    <option value="engineer@airspeedmfgvn.com">Lưu Văn Hùng - Trưởng Bộ Phận Kỹ Thuật</option>
                    <option value="engineer7@airspeedmfgvn.com">Nguyễn Trọng Tín - Engineering Assistant Manager</option>
                    
                </select>
            </div>
        <div class="text-center">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
                Đăng kí
            </button>
        </div>
          </div>
  <!-- Right: Dashboard -->
    
    </form>
</div>
</body>
</html>