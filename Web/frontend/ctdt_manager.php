<?php
// BƯỚC 1: BẬT BÁO LỖI
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ctdt_manager.php — Quản lý CTĐT (Chương trình cụ thể) VÀ Cấu trúc Khung (Blueprint)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require __DIR__ . '/../auth_php_pack/auth_guard.php'; 
require __DIR__ . '/../backend/db.php';

if (!function_exists('h')) { function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }

$role = $_SESSION['urole'] ?? 'viewer';
$uname = $_SESSION['uname'] ?? 'system';
$canEdit = in_array($role, ['admin','editor'], true);

// =================================================================================
// SỬA LỖI VÀ ĐỊNH NGHĨA THÔNG BÁO CHUYÊN NGHIỆP TẠI ĐÂY
// =================================================================================
$messages = [
    'add_ok' => 'Thêm mới chương trình thành công. Dữ liệu đã được hệ thống ghi nhận.',
    'update_ok' => 'Cập nhật thông tin thành công. Các thay đổi đã được lưu trữ.',
    'delete_ok' => 'Xóa bản ghi thành công. Dữ liệu đã được loại bỏ khỏi hệ thống.',
    'khung_add_ok' => 'Khởi tạo cấu trúc nền thành công. Dữ liệu đang ở trạng thái Nháp.',
];
// SỬA LỖI: Lấy giá trị thông báo thay vì chỉ key
$msg = isset($_GET['msg']) && isset($messages[$_GET['msg']]) ? $messages[$_GET['msg']] : '';
$err = isset($_GET['err']) ? $_GET['err'] : '';
// =================================================================================


$view = $_GET['view'] ?? 'program'; 
$BAC_DAO_TAO = ['Đại học', 'Cao đẳng', 'Thạc sĩ'];

// --- LẤY DANH SÁCH PHỤ TRỢ ---
$nganhList = $conn->query("SELECT id, tenNganh, maNganh FROM nganh ORDER BY tenNganh")->fetch_all(MYSQLI_ASSOC);
$khungList = $conn->query("SELECT id, tenCauTruc, nganh_id, bacDaoTao, khoaTuyen, quyTacTinChi FROM cautruc_khung ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);


// =================================================================================
// 0. LOGIC XỬ LÝ CHUNG (CÁC ACTIONS)
// =================================================================================

// Logic so sánh (Dùng cho cả Xuất CSV và Hiển thị)
$compareData = null;
if (isset($_GET['compare_id1']) && isset($_GET['compare_id2'])) {
    $id1 = (int)$_GET['compare_id1'];
    $id2 = (int)$_GET['compare_id2'];
    $s1 = $conn->query("SELECT c.*, n.tenNganh FROM cautruc_khung c LEFT JOIN nganh n ON c.nganh_id = n.id WHERE c.id=$id1")->fetch_assoc();
    $s2 = $conn->query("SELECT c.*, n.tenNganh FROM cautruc_khung c LEFT JOIN nganh n ON c.nganh_id = n.id WHERE c.id=$id2")->fetch_assoc();
    if ($s1 && $s2) { $compareData = ['s1' => $s1, 's2' => $s2]; }
    $view = 'khung';
}

// --- LOGIC XUẤT CSV (ĐÃ CÓ) ---
if ($compareData && isset($_GET['export_compare']) && $_GET['export_compare'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="so_sanh_cautruc_'.date('Ymd').'.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    $fields = ['khoaTuyen' => 'Khóa tuyển', 'bacDaoTao' => 'Bậc đào tạo', 'tenNganh' => 'Ngành', 'quyTacTinChi' => 'Tổng tín chỉ', 'khoiKienThuc' => 'Các khối mẫu', 'moTa' => 'Mô tả chi tiết'];
    fputcsv($out, ['Thuộc tính', 'Cấu trúc 1 (' . $compareData['s1']['tenCauTruc'] . ')', 'Cấu trúc 2 (' . $compareData['s2']['tenCauTruc'] . ')', 'Có khác biệt?']);
    foreach ($fields as $key => $label) { $val1 = $compareData['s1'][$key]; $val2 = $compareData['s2'][$key]; $isDiff = ($val1 != $val2) ? 'CÓ' : 'KHÔNG'; fputcsv($out, [$label, $val1, $val2, $isDiff]); }
    fclose($out);
    exit;
}


// =================================================================================
// 1. LOGIC XỬ LÝ CTĐT (CHƯƠNG TRÌNH CỤ THỂ)
// =================================================================================
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] == 'add_program' || $_POST['action'] == 'update_program')) {
    $action = $_POST['action']; $id = (int)($_POST['id'] ?? 0); $maCTDT = trim($_POST['maCTDT'] ?? ''); $tenCTDT = trim($_POST['tenCTDT'] ?? ''); $nganh_id = (int)($_POST['nganh_id'] ?? 0); $cautruc_id = (int)($_POST['cautruc_id'] ?? 0); $namBatDau = (int)($_POST['namBatDau'] ?? date('Y')); $tongTinChi = (int)($_POST['tongTinChi'] ?? 0); $moTa = trim($_POST['moTa'] ?? '');

    if ($maCTDT === '' || $tenCTDT === '' || $nganh_id <= 0) { $err = 'Vui lòng nhập Mã CTĐT, Tên CTĐT và chọn Ngành.'; } 
    else {
        if ($action === 'add_program') {
            $chk = $conn->query("SELECT id FROM ctdt WHERE maCTDT='$maCTDT'");
            if($chk->num_rows > 0){ $err = 'Mã CTĐT đã tồn tại.'; } 
            else {
                $st = $conn->prepare("INSERT INTO ctdt (maCTDT, tenCTDT, nganh_id, cautruc_id, namBatDau, tongTinChi, moTa) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $st->bind_param('ssiiiis', $maCTDT, $tenCTDT, $nganh_id, $cautruc_id, $namBatDau, $tongTinChi, $moTa);
                if ($st->execute()) { header("Location: ctdt_manager.php?view=program&msg=add_ok"); exit; }
                else { $err = 'Lỗi thêm: ' . $st->error; }
                $st->close();
            }
        }
        if ($action === 'update_program') {
            $st = $conn->prepare("UPDATE ctdt SET maCTDT=?, tenCTDT=?, nganh_id=?, cautruc_id=?, namBatDau=?, tongTinChi=?, moTa=? WHERE id=?");
            $st->bind_param('ssiiiisi', $maCTDT, $tenCTDT, $nganh_id, $cautruc_id, $namBatDau, $tongTinChi, $moTa, $id);
            if ($st->execute()) { header("Location: ctdt_manager.php?view=program&msg=update_ok"); exit; }
            else { $err = 'Lỗi cập nhật: ' . $st->error; }
            $st->close();
        }
    }
}
if ($canEdit && isset($_GET['delete_program'])) { $id = (int)$_GET['delete_program']; if ($id > 0) { $conn->query("DELETE FROM ctdt WHERE id=$id"); header("Location: ctdt_manager.php?view=program&msg=delete_ok"); exit; } }

$searchTerm = trim($_GET['search'] ?? '');
$whereSQL = $searchTerm !== '' ? "WHERE c.maCTDT LIKE '%$searchTerm%' OR c.tenCTDT LIKE '%$searchTerm%'" : '';
$sql = "SELECT c.*, n.tenNganh, n.maNganh, ck.tenCauTruc FROM ctdt c LEFT JOIN nganh n ON c.nganh_id = n.id LEFT JOIN cautruc_khung ck ON c.cautruc_id = ck.id $whereSQL ORDER BY c.id DESC";
$ctdtList = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$editCTDT = null;
if ($canEdit && isset($_GET['edit_program'])) { $id = (int)$_GET['edit_program']; $rs = $conn->query("SELECT * FROM ctdt WHERE id=$id"); if($rs) $editCTDT = $rs->fetch_assoc(); }


// =================================================================================
// 2. LOGIC XỬ LÝ CẤU TRÚC KHUNG
// =================================================================================
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] == 'save_structure' || $_POST['action'] == 'deactivate' || $_POST['action'] == 'reactivate_khung' || $_POST['action'] == 'delete_khung')) {
    $action = $_POST['action']; $id = (int)($_POST['id'] ?? 0); $view = 'khung';

    if ($action === 'save_structure') {
        $tenCauTruc = trim($_POST['tenCauTruc'] ?? ''); $nganh_id = (int)($_POST['nganh_id'] ?? 0); $bacDaoTao = trim($_POST['bacDaoTao'] ?? ''); $khoaTuyen = trim($_POST['khoaTuyen'] ?? ''); $quyTacTinChi = (int)($_POST['quyTacTinChi'] ?? 0); $khoiKienThuc = trim($_POST['khoiKienThuc'] ?? ''); $moTa = trim($_POST['moTa'] ?? '');

        if ($tenCauTruc === '' || $nganh_id <= 0 || $khoaTuyen === '') { $err = "Vui lòng nhập đủ: Tên, Ngành và Khóa tuyển."; } 
        else {
            if ($id == 0) {
                $chk = $conn->query("SELECT id FROM cautruc_khung WHERE nganh_id=$nganh_id AND khoaTuyen='$khoaTuyen' AND bacDaoTao='$bacDaoTao'");
                if ($chk && $chk->num_rows > 0) { $err = "Cấu trúc cho Ngành này và Khóa này đã tồn tại."; } 
                else {
                    $st = $conn->prepare("INSERT INTO cautruc_khung (tenCauTruc, nganh_id, bacDaoTao, khoaTuyen, moTa, quyTacTinChi, khoiKienThuc, trangThai, nguoiTao) VALUES (?, ?, ?, ?, ?, ?, ?, 'Nháp', ?)");
                    $st->bind_param('sisssiss', $tenCauTruc, $nganh_id, $bacDaoTao, $khoaTuyen, $moTa, $quyTacTinChi, $khoiKienThuc, $uname);
                    if ($st->execute()) { $newId = $st->insert_id; $msg = "Khởi tạo cấu trúc nền thành công (Trạng thái: Nháp)."; $conn->query("INSERT INTO audit_log (table_name, record_id, action, message) VALUES ('cautruc_khung', $newId, 'INIT', 'Khởi tạo cấu trúc nền')"); header("Location: ctdt_manager.php?view=khung&msg=khung_add_ok"); exit; } 
                    else { $err = 'Lỗi: ' . $st->error; }
                }
            } else {
                $checkStatus = $conn->query("SELECT trangThai FROM cautruc_khung WHERE id=$id")->fetch_assoc();
                if ($checkStatus['trangThai'] !== 'Nháp') { $err = "Chỉ được chỉnh sửa khi cấu trúc đang ở trạng thái Nháp."; } 
                else {
                    $st = $conn->prepare("UPDATE cautruc_khung SET tenCauTruc=?, nganh_id=?, bacDaoTao=?, khoaTuyen=?, moTa=?, quyTacTinChi=?, khoiKienThuc=? WHERE id=?");
                    $st->bind_param('sisssisi', $tenCauTruc, $nganh_id, $bacDaoTao, $khoaTuyen, $moTa, $quyTacTinChi, $khoiKienThuc, $id);
                    if ($st->execute()) { $msg = "Cập nhật thông tin chung thành công."; $conn->query("INSERT INTO audit_log (table_name, record_id, action, message) VALUES ('cautruc_khung', $id, 'UPDATE', 'Cập nhật thông tin cấu trúc')"); header("Location: ctdt_manager.php?view=khung&msg=update_ok"); exit; } 
                    else { $err = 'Lỗi cập nhật: ' . $st->error; }
                }
            }
        }
    }
    if ($action === 'deactivate') {
        $row = $conn->query("SELECT trangThai FROM cautruc_khung WHERE id=$id")->fetch_assoc();
        if ($row && $row['trangThai'] === 'Nháp') {
            $conn->query("UPDATE cautruc_khung SET trangThai='NgungHieuLuc' WHERE id=$id");
            header("Location: ctdt_manager.php?view=khung&msg=update_ok"); exit;
        } else { $err = "Không thể ngừng hiệu lực (Cấu trúc không phải Nháp)."; }
    }
    if ($action === 'reactivate_khung') {
        $row = $conn->query("SELECT trangThai FROM cautruc_khung WHERE id=$id")->fetch_assoc();
        if ($row && $row['trangThai'] === 'NgungHieuLuc') {
            $conn->query("UPDATE cautruc_khung SET trangThai='Nháp' WHERE id=$id");
            header("Location: ctdt_manager.php?view=khung&msg=update_ok"); exit;
        } else { $err = "Không thể mở lại cấu trúc này."; }
    }
    if ($action === 'delete_khung') {
        $status = $conn->query("SELECT trangThai FROM cautruc_khung WHERE id=$id")->fetch_assoc()['trangThai'];
        $countCTDT = $conn->query("SELECT COUNT(*) FROM ctdt WHERE cautruc_id=$id")->fetch_row()[0];
        
        if ($countCTDT > 0) { $err = "Không thể xoá: Đã có chương trình cụ thể (CTĐT) liên kết."; }
        elseif ($status === 'HieuLuc') { $err = "Không thể xoá: Cấu trúc đang ở trạng thái Hiệu lực."; }
        else {
            $conn->query("DELETE FROM cautruc_khung WHERE id=$id");
            header("Location: ctdt_manager.php?view=khung&msg=delete_ok"); exit;
        }
    }
}

$sqlKhung = "SELECT c.*, n.tenNganh FROM cautruc_khung c LEFT JOIN nganh n ON c.nganh_id = n.id ORDER BY c.id DESC";
$listStruct = $conn->query($sqlKhung)->fetch_all(MYSQLI_ASSOC);
$editKhung = null;
if (isset($_GET['edit_khung'])) { $id = (int)$_GET['edit_khung']; $editKhung = $conn->query("SELECT * FROM cautruc_khung WHERE id=$id")->fetch_assoc(); $view = 'khung'; }

$vTenKhung = $editKhung['tenCauTruc'] ?? $_POST['tenCauTruc'] ?? ''; $vNganhKhung = $editKhung['nganh_id'] ?? $_POST['nganh_id'] ?? 0; $vBacKhung = $editKhung['bacDaoTao'] ?? $_POST['bacDaoTao'] ?? 'Đại học'; $vKhoaKhung = $editKhung['khoaTuyen'] ?? $_POST['khoaTuyen'] ?? ''; $vTinKhung = $editKhung['quyTacTinChi'] ?? $_POST['quyTacTinChi'] ?? 120; $vKhoiKhung = $editKhung['khoiKienThuc'] ?? $_POST['khoiKienThuc'] ?? ''; $vMoTaKhung = $editKhung['moTa'] ?? $_POST['moTa'] ?? '';

$vMa = $editCTDT['maCTDT'] ?? $_POST['maCTDT'] ?? ''; $vTen = $editCTDT['tenCTDT'] ?? $_POST['tenCTDT'] ?? ''; $vNganh = $editCTDT['nganh_id'] ?? $_POST['nganh_id'] ?? 0; $vCauTruc = $editCTDT['cautruc_id'] ?? $_POST['cautruc_id'] ?? 0; $vNam = $editCTDT['namBatDau'] ?? $_POST['namBatDau'] ?? date('Y'); $vTin = $editCTDT['tongTinChi'] ?? $_POST['tongTinChi'] ?? 0; $vMoTa = $editCTDT['moTa'] ?? $_POST['moTa'] ?? '';

$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/'; $cur = basename($_SERVER['PHP_SELF']); 
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý CTĐT (Cấu trúc & Chương trình)</title>
    <base href="<?= h($base) ?>">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
    .form-box {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }

    .userbox {
        margin-top: 16px;
        border-top: 1px solid #475569;
        padding-top: 12px;
        color: #e5e7eb;
        font-size: 14px
    }

    .userbox a {
        color: #e5e7eb;
        text-decoration: underline
    }

    .badge {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }

    .status-nhap {
        background: #fef3c7;
        color: #92400e;
    }

    .status-hieuluc {
        background: #dcfce7;
        color: #166534;
    }

    .status-ngung {
        background: #fee2e2;
        color: #991b1b;
    }

    .compare-table td {
        vertical-align: top;
    }

    .diff-highlight {
        background-color: #fff7ed;
        font-weight: bold;
        color: #c2410c;
    }

    .tab-switch {
        background: #f1f5f9;
        border-radius: 8px;
        display: inline-flex;
        margin-bottom: 20px;
        padding: 4px;
    }

    .tab-switch a {
        padding: 8px 15px;
        text-decoration: none;
        color: #475569;
        font-weight: 500;
        border-radius: 6px;
        transition: background 0.2s;
        font-size: 14px;
    }

    .tab-switch a.active {
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        color: #0f172a;
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-graduation-cap"></i><span>PHENIKAA UNIVERSITY</span></div>
        <ul>
            <li><a href="index.php" class="<?= $cur==='index.php'?'active':'' ?>"><i
                        class="fa-solid fa-house"></i><span>Trang chủ</span></a></li>
            <li><a href="giangvien.php" class="<?= $cur==='giangvien.php'?'active':'' ?>"><i
                        class="fa-solid fa-chalkboard-user"></i><span>Giảng viên</span></a></li>
            <li><a href="khoa.php" class="<?= $cur==='khoa.php'?'active':'' ?>"><i
                        class="fa-solid fa-building-columns"></i><span>Khoa</span></a></li>
            <li><a href="monhoc.php" class="<?= $cur==='monhoc.php'?'active':'' ?>"><i
                        class="fa-solid fa-book"></i><span>Môn học</span></a></li>
            <li><a href="nganh.php" class="<?= $cur==='nganh.php'?'active':'' ?>"><i
                        class="fa-solid fa-layer-group"></i><span>Ngành</span></a></li>
            <li><a href="lophoc.php" class="<?= $cur==='lophoc.php'?'active':'' ?>"><i
                        class="fa-solid fa-chalkboard"></i><span>Quản lý Khoá học</span></a></li>
            <li><a href="khoikienthuc.php" class="<?= $cur==='khoikienthuc.php'?'active':'' ?>"><i
                        class="fa-solid fa-book"></i><span>Khối Kiến thức</span></a></li>

            <li><a href="ctdt_manager.php" class="<?= $cur==='ctdt_manager.php'?'active':'' ?>"><i
                        class="fa-solid fa-project-diagram"></i><span>Quản lý CTĐT</span></a></li>

            <li><a href="news.php" class="<?= $cur==='news.php'?'active':'' ?>"><i
                        class="fa-regular fa-newspaper"></i><span>Tin tức</span></a></li>
            <li><a href="thanhtoan.php" class="<?= $cur==='thanhtoan.php'?'active':'' ?>"><i
                        class="fa-solid fa-credit-card"></i><span>Thanh toán</span></a></li>
        </ul>
        <div class="userbox">
            <?php if ($uname): ?>
            <div><i class="fa-solid fa-user-shield"></i> <?= h($role) ?>: <b><?= h($uname) ?></b></div>
            <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php">Đăng xuất</a></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <h2>Quản lý Chương trình Đào tạo</h2>

        <?php if ($msg): ?><div
            style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?>
        </div><?php endif; ?>
        <?php if ($err): ?><div
            style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?>
        </div><?php endif; ?>

        <div class="tab-switch">
            <a href="ctdt_manager.php?view=program" class="<?= $view=='program'?'active':'' ?>">Chương trình cụ thể
                (CTĐT)</a>
            <a href="ctdt_manager.php?view=khung" class="<?= $view=='khung'?'active':'' ?>">Cấu trúc Khung
                (Blueprint)</a>
        </div>
        <?php if ($view == 'program'): ?>
        <form method="get" action="ctdt_manager.php" style="margin-bottom:15px;display:flex;gap:10px">
            <input type="hidden" name="view" value="program">
            <input type="text" name="search" value="<?= h($_GET['search'] ?? '') ?>"
                placeholder="Tìm kiếm theo Mã hoặc Tên CTĐT..."
                style="padding:8px;width:300px;border:1px solid #ccc;border-radius:4px">
            <button class="tab" style="height:36px">Tìm kiếm</button>
            <?php if(isset($_GET['search'])): ?><a href="ctdt_manager.php?view=program" class="tab"
                style="background:#eee;color:#333;text-decoration:none;height:36px;line-height:36px">Xoá
                lọc</a><?php endif; ?>
        </form>

        <?php if ($canEdit): ?>
        <div class="form-box">
            <h3 style="margin-top:0;font-size:16px"><?= $editCTDT ? 'Cập nhật CTĐT' : 'Thêm mới CTĐT' ?></h3>
            <form method="post" action="ctdt_manager.php"
                style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px">
                <input type="hidden" name="action" value="<?= $editCTDT ? 'update_program' : 'add_program' ?>">
                <input type="hidden" name="id" value="<?= h($editCTDT['id'] ?? 0) ?>">

                <div><label style="font-size:12px;color:#666">Thuộc Ngành *</label>
                    <select name="nganh_id" required
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <option value="">-- Chọn Ngành --</option>
                        <?php foreach ($nganhList as $n): ?>
                        <option value="<?= $n['id'] ?>" <?= $vNganh==$n['id']?'selected':'' ?>><?= h($n['tenNganh']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div><label style="font-size:12px;color:#666">Dựa trên Khung mẫu (Tuỳ chọn)</label>
                    <select name="cautruc_id" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <option value="0">-- Không áp dụng --</option>
                        <?php foreach ($khungList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $vCauTruc==$k['id']?'selected':'' ?>>
                            <?= h($k['tenCauTruc']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div><label style="font-size:12px;color:#666">Mã CTĐT *</label><input name="maCTDT"
                        value="<?= h($vMa) ?>" placeholder="VD: KTPM-K15" required
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

                <div style="grid-column: span 1"><label style="font-size:12px;color:#666">Tên Chương Trình
                        *</label><input name="tenCTDT" value="<?= h($vTen) ?>" required
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

                <div><label style="font-size:12px;color:#666">Năm bắt đầu</label><input type="number" name="namBatDau"
                        value="<?= h($vNam) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                </div>

                <div><label style="font-size:12px;color:#666">Tổng tín chỉ</label><input type="number" name="tongTinChi"
                        value="<?= h($vTin) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                </div>

                <div style="grid-column: span 2"><label style="font-size:12px;color:#666">Mô tả thêm</label><input
                        name="moTa" value="<?= h($vMoTa) ?>"
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

                <div style="grid-column:1/-1; display:flex; gap:10px; margin-top:5px">
                    <button class="tab" style="height:36px"><?= $editCTDT ? 'Lưu Cập Nhật' : 'Thêm Mới' ?></button>
                    <?php if ($editCTDT): ?><a href="ctdt_manager.php?view=program" class="tab"
                        style="text-decoration:none;background:#eee;color:#333;height:36px;line-height:36px">Hủy
                        Sửa</a><?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div
            style="background:#fff;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.05);overflow:hidden;border:1px solid #eee">
            <table>
                <thead>
                    <tr>
                        <th>Mã CTĐT</th>
                        <th>Tên Chương Trình</th>
                        <th>Ngành</th>
                        <th>Khung Áp Dụng</th>
                        <th style="text-align:center">Năm</th>
                        <th style="text-align:center">Tín chỉ</th>
                        <?php if($canEdit): ?><th>Hành động</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($ctdtList) > 0): foreach ($ctdtList as $ct): ?>
                    <tr>
                        <td style="font-weight:bold;color:#2563eb"><?= h($ct['maCTDT']) ?></td>
                        <td><?= h($ct['tenCTDT']) ?><div style="font-size:12px;color:#666"><?= h($ct['moTa']) ?></div>
                        </td>
                        <td><?= h($ct['tenNganh']) ?></td>
                        <td>
                            <?php if($ct['tenCauTruc']): ?>
                            <a href="ctdt_manager.php?view=khung&edit_khung=<?= $ct['cautruc_id'] ?>"
                                title="Xem chi tiết cấu trúc">
                                <span class="badge"
                                    style="background:#e0e7ff;color:#3730a3;padding:3px 8px;border-radius:4px;font-size:11px"><i
                                        class="fa-solid fa-sitemap"></i> <?= h($ct['tenCauTruc']) ?></span>
                            </a>
                            <?php else: ?><span style="color:#999;font-size:12px">--</span><?php endif; ?>
                        </td>
                        <td style="text-align:center"><?= h($ct['namBatDau']) ?></td>
                        <td style="text-align:center;font-weight:bold"><?= h($ct['tongTinChi']) ?></td>
                        <?php if ($canEdit): ?>
                        <td>
                            <a href="ctdt_manager.php?view=program&edit_program=<?= $ct['id'] ?>"
                                style="color:#2563eb;margin-right:8px"><i class="fa-solid fa-pen-to-square"></i></a>
                            <a href="ctdt_manager.php?delete_program=<?= $ct['id'] ?>"
                                onclick="return confirm('Xoá CTĐT này?')" style="color:#ef4444"><i
                                    class="fa-solid fa-trash"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:20px;color:#888">Chưa có dữ liệu CTĐT.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($view == 'khung'): ?>
        <?php if ($compareData): ?>
        <div class="card" style="border: 2px solid #3b82f6;">
            <div
                style="padding:15px;background:#eff6ff;border-bottom:1px solid #dbeafe;display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0;color:#1e40af"><i class="fa-solid fa-scale-balanced"></i> Bảng đối chiếu cấu trúc
                </h3>
                <div style="display:flex;gap:10px">
                    <a href="ctdt_manager.php?view=khung&export_compare=csv&compare_id1=<?= h($compareData['s1']['id']) ?>&compare_id2=<?= h($compareData['s2']['id']) ?>"
                        class="tab" style="text-decoration:none;background:#22c55e;color:white"><i
                            class="fa-solid fa-download"></i> Xuất CSV</a>
                    <a href="ctdt_manager.php?view=khung" class="tab"
                        style="text-decoration:none;background:#fff;border:1px solid #ccc;color:#333">Đóng so sánh</a>
                </div>
            </div>
            <table class="compare-table">
                <thead>
                    <tr>
                        <th style="width:20%">Hạng mục</th>
                        <th style="width:40%"><?= h($compareData['s1']['tenCauTruc']) ?> (ID:
                            <?= h($compareData['s1']['id']) ?>)</th>
                        <th style="width:40%"><?= h($compareData['s2']['tenCauTruc']) ?> (ID:
                            <?= h($compareData['s2']['id']) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $fields = ['khoaTuyen' => 'Khóa tuyển', 'bacDaoTao' => 'Bậc đào tạo', 'tenNganh' => 'Ngành', 'quyTacTinChi' => 'Tổng tín chỉ', 'khoiKienThuc' => 'Các khối mẫu', 'moTa' => 'Mô tả chi tiết'];
                        foreach ($fields as $key => $label): 
                            $val1 = $compareData['s1'][$key]; $val2 = $compareData['s2'][$key]; $isDiff = ($val1 != $val2);
                    ?>
                    <tr class="<?= $isDiff ? 'diff-highlight' : '' ?>">
                        <td style="font-weight:500"><?= $label ?></td>
                        <td><?= nl2br(h($val1)) ?></td>
                        <td><?= nl2br(h($val2)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>


        <?php if ($canEdit): ?>
        <div class="form-box">
            <h3 style="margin-top:0"><?= $editKhung ? 'Chỉnh sửa Cấu trúc' : 'Khởi tạo Cấu trúc nền' ?></h3>
            <form method="post" action="ctdt_manager.php"
                style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px">
                <input type="hidden" name="action" value="save_structure">
                <input type="hidden" name="id" value="<?= h($editKhung['id'] ?? 0) ?>">
                <input type="hidden" name="view" value="khung">

                <div style="grid-column: span 2"><label style="font-size:12px;color:#666">Tên cấu trúc *</label><input
                        name="tenCauTruc" value="<?= h($vTenKhung) ?>" placeholder="VD: Khung đào tạo CNTT K18" required
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

                <div><label style="font-size:12px;color:#666">Ngành *</label>
                    <select name="nganh_id" required
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <option value="">-- Chọn Ngành --</option>
                        <?php foreach($nganhList as $n): ?><option value="<?= $n['id'] ?>"
                            <?= $vNganhKhung==$n['id']?'selected':'' ?>><?= h($n['tenNganh']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div><label style="font-size:12px;color:#666">Bậc đào tạo</label>
                    <select name="bacDaoTao" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <?php foreach($BAC_DAO_TAO as $bdt): ?><option value="<?= $bdt ?>"
                            <?= $vBacKhung==$bdt?'selected':'' ?>><?= $bdt ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div><label style="font-size:12px;color:#666">Khóa tuyển (Cohort) *</label><input name="khoaTuyen"
                        value="<?= h($vKhoaKhung) ?>" placeholder="VD: K18" required
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

                <div><label style="font-size:12px;color:#666">Tổng tín chỉ mẫu</label><input type="number"
                        name="quyTacTinChi" value="<?= h($vTinKhung) ?>"
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

                <div style="grid-column: span 2"><label style="font-size:12px;color:#666">Các khối mẫu (Mô tả
                        text)</label><textarea name="khoiKienThuc" rows="1"
                        placeholder="VD: Đại cương: 30TC, Chuyên ngành: 90TC..."
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"><?= h($vKhoiKhung) ?></textarea>
                </div>

                <div style="grid-column: span 4"><label style="font-size:12px;color:#666">Mô tả chi
                        tiết</label><textarea name="moTa" rows="2"
                        style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"><?= h($vMoTaKhung) ?></textarea>
                </div>

                <div style="grid-column: span 4; display:flex; gap:10px; margin-top:5px">
                    <button class="tab"
                        style="height:36px"><?= $editKhung ? 'Lưu Cập Nhật' : 'Tạo Mới (Nháp)' ?></button>
                    <?php if($editKhung): ?><a href="ctdt_manager.php?view=khung" class="tab"
                        style="background:#eee;color:#333;text-decoration:none;height:36px;line-height:36px">Huỷ</a><?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="get" action="ctdt_manager.php" id="compareForm">
                <input type="hidden" name="view" value="khung">
                <input type="text" name="search_khung" value="<?= h($_GET['search_khung'] ?? '') ?>"
                    placeholder="Tìm kiếm theo Tên cấu trúc / Ngành..."
                    style="padding:8px;margin:15px;width:300px;border:1px solid #ccc;border-radius:4px">
                <button class="tab" style="height:36px;margin-left:15px;vertical-align:top">Tìm</button>
                <?php if(isset($_GET['search_khung'])): ?><a href="ctdt_manager.php?view=khung" class="tab"
                    style="background:#eee;color:#333;text-decoration:none;height:36px;line-height:36px">Xoá
                    lọc</a><?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center">Chọn</th>
                            <th>Tên cấu trúc</th>
                            <th>Ngành - Bậc - Khóa</th>
                            <th>Tín chỉ</th>
                            <th>Trạng thái</th>
                            <?php if($canEdit): ?><th>Hành động</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($editKhung) && empty($compareData)): // CHỈ HIỆN KHI KHÔNG EDIT HOẶC KHÔNG COMPARE ?>
                        <?php foreach ($listStruct as $s): 
                    $stClass = 'status-nhap';
                    if($s['trangThai']=='HieuLuc') $stClass = 'status-hieuluc';
                    if($s['trangThai']=='NgungHieuLuc') $stClass = 'status-ngung';
                ?>
                        <tr>
                            <td style="text-align:center"><input type="checkbox" name="compare_ids[]"
                                    value="<?= $s['id'] ?>" class="chk-compare"></td>
                            <td style="font-weight:bold;color:#2563eb"><?= h($s['tenCauTruc']) ?></td>
                            <td><?= h($s['tenNganh']) ?> - <?= h($s['bacDaoTao']) ?> - <b><?= h($s['khoaTuyen']) ?></b>
                            </td>
                            <td><?= h($s['quyTacTinChi']) ?></td>
                            <td><span class="badge <?= $stClass ?>"><?= h($s['trangThai']) ?></span></td>

                            <?php if ($canEdit): ?>
                            <td style="display:flex; gap:8px">
                                <?php if($s['trangThai'] === 'Nháp'): ?>
                                <a href="ctdt_manager.php?view=khung&edit_khung=<?= $s['id'] ?>" style="color:#2563eb;"
                                    title="Chỉnh sửa"><i class="fa-solid fa-pen-to-square"></i></a>

                                <form action="ctdt_manager.php" method="POST" style="display:inline"
                                    onsubmit="return confirm('Bạn muốn ngừng hiệu lực cấu trúc này?')">
                                    <input type="hidden" name="action" value="deactivate"><input type="hidden" name="id"
                                        value="<?= $s['id'] ?>"><input type="hidden" name="view" value="khung">
                                    <button style="border:none;background:none;color:#f97316;cursor:pointer"
                                        title="Ngừng hiệu lực"><i class="fa-solid fa-lock"></i></button>
                                </form>
                                <?php elseif($s['trangThai'] === 'NgungHieuLuc'): ?>
                                <form action="ctdt_manager.php" method="POST" style="display:inline"
                                    onsubmit="return confirm('Bạn muốn MỞ LẠI cấu trúc này?')">
                                    <input type="hidden" name="action" value="reactivate_khung"><input type="hidden"
                                        name="id" value="<?= $s['id'] ?>"><input type="hidden" name="view"
                                        value="khung">
                                    <button style="border:none;background:none;color:#22c55e;cursor:pointer"
                                        title="Mở lại hiệu lực"><i class="fa-solid fa-lock-open"></i></button>
                                </form>
                                <?php endif; ?>

                                <?php if($s['trangThai'] !== 'HieuLuc'): ?>
                                <form action="ctdt_manager.php" method="POST" style="display:inline"
                                    onsubmit="return confirm('CẢNH BÁO: Bạn muốn xóa vĩnh viễn cấu trúc này?')">
                                    <input type="hidden" name="action" value="delete_khung"><input type="hidden"
                                        name="id" value="<?= $s['id'] ?>"><input type="hidden" name="view"
                                        value="khung">
                                    <button style="border:none;background:none;color:#dc2626;cursor:pointer"
                                        title="Xoá"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </form>
            <?php if (empty($editKhung) && empty($compareData)): ?>
            <div style="padding:15px; background:#f9fafb; border-top:1px solid #eee">
                <button type="button" class="tab" id="btnCompare" style="background:#6366f1; color:white"><i
                        class="fa-solid fa-scale-balanced"></i> Đối chiếu 2 mục đã chọn</button>
            </div>
            <?php endif; ?>
        </div>

        <script>
        document.getElementById('btnCompare').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.chk-compare:checked');
            if (checkboxes.length !== 2) {
                alert('Vui lòng chọn chính xác 2 cấu trúc để đối chiếu.');
                return;
            }
            const id1 = checkboxes[0].value;
            const id2 = checkboxes[1].value;
            window.location.href = `ctdt_manager.php?compare_id1=${id1}&compare_id2=${id2}&view=khung`;
        });
        </script>
        <?php endif; ?>

    </div>
</body>

</html>