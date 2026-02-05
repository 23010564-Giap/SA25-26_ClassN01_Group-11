<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php';
require __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$role = $_SESSION['urole'] ?? 'viewer';
$uname = $_SESSION['uname'] ?? null;
$urole = $role;
$canEdit = in_array($role, ['admin','editor'], true);
$isStudent = ($role === 'viewer');

$msg = '';
$err = '';

if ($canEdit) {
    if (isset($_GET['reset_data'])) {
        $conn->query("TRUNCATE TABLE khoan_phai_thu");
        $conn->query("TRUNCATE TABLE giaodich_sinhvien");
        $conn->query("TRUNCATE TABLE taichinh_sinhvien");
        $msg = "Cleared financial data to restart testing from scratch.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] == 'add_ctdt' || $_POST['action'] == 'update_ctdt')) {
        $action = $_POST['action'];
        $id = (int)($_POST['id'] ?? 0);
        $maCTDT = trim($_POST['maCTDT'] ?? '');
        $tenCTDT = trim($_POST['tenCTDT'] ?? '');
        $nganh_id = (int)($_POST['nganh_id'] ?? 0);
        $namBatDau = (int)($_POST['namBatDau'] ?? date('Y'));
        $tongTinChi = (int)($_POST['tongTinChi'] ?? 0);
        $giaTinChi = (float)str_replace('.', '', $_POST['giaTinChi'] ?? '0'); 

        if ($maCTDT === '' || $tenCTDT === '' || $nganh_id <= 0) {
            $err = 'Please enter complete curriculum information.';
        } else {
            if ($action === 'add_ctdt') {
                $st = $conn->prepare("INSERT INTO ctdt (maCTDT, tenCTDT, nganh_id, namBatDau, tongTinChi, giaTinChi) VALUES (?, ?, ?, ?, ?, ?)");
                $st->bind_param('ssiiid', $maCTDT, $tenCTDT, $nganh_id, $namBatDau, $tongTinChi, $giaTinChi);
                if ($st->execute()) { $msg = 'Sample text'; $_POST = []; }
                else { $err = 'Sample text' . $st->error; }
                $st->close();
            }
            if ($action === 'update_ctdt') {
                $st = $conn->prepare("UPDATE ctdt SET maCTDT=?, tenCTDT=?, nganh_id=?, namBatDau=?, tongTinChi=?, giaTinChi=? WHERE id=?");
                $st->bind_param('ssiiidi', $maCTDT, $tenCTDT, $nganh_id, $namBatDau, $tongTinChi, $giaTinChi, $id);
                if ($st->execute()) { $msg = 'Sample text'; } else { $err = 'Error cap nhat: ' . $st->error; }
                $st->close();
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_fee') {
        $maSV = trim($_POST['maSV'] ?? '');
        $noiDung = trim($_POST['noiDung'] ?? '');
        $soTien = (float)str_replace('.', '', $_POST['soTien'] ?? '0');
        $hanNop = $_POST['hanNop'] ?? '';

        if ($maSV === '' || $noiDung === '' || $soTien <= 0 || $hanNop === '') {
            $err = 'Please nhap ay u thong tin.';
        } else {
            $st = $conn->prepare("INSERT INTO khoan_phai_thu (maSV, noiDung, soTien, hanNop, trangThai) VALUES (?, ?, ?, ?, 'CHUA_NOP')");
            $st->bind_param('ssds', $maSV, $noiDung, $soTien, $hanNop);
            if ($st->execute()) { 
                $msg = "a tao khoan thu cho $maSV: " . number_format($soTien) . "Sample text";
                $chk = $conn->query("SELECT id FROM taichinh_sinhvien WHERE maSV = '$maSV'");
                if ($chk->num_rows == 0) $conn->query("INSERT INTO taichinh_sinhvien (maSV) VALUES ('$maSV')");
            } else { 
                $err = 'Error tao khoan thu: ' . $st->error; 
            }
            $st->close();
        }
    }

    if (isset($_GET['delete_ctdt'])) { $id = (int)$_GET['delete_ctdt']; if ($id > 0) { $conn->query("DELETE FROM ctdt WHERE id=$id"); $msg = 'Sample text'; } }
    if (isset($_GET['delete_fee'])) { $id = (int)$_GET['delete_fee']; if ($id > 0) { $conn->query("DELETE FROM khoan_phai_thu WHERE id=$id"); $msg = 'Sample text'; } }

    $listNganh = $conn->query("SELECT id, tenNganh, maNganh FROM nganh ORDER BY tenNganh")->fetch_all(MYSQLI_ASSOC);
    $listAllCTDT = $conn->query("SELECT id, maCTDT, tenCTDT, giaTinChi FROM ctdt ORDER BY maCTDT")->fetch_all(MYSQLI_ASSOC);
    $listAllMon = $conn->query("SELECT id, maMon, tenMon, soTinChi FROM monhoc ORDER BY tenMon")->fetch_all(MYSQLI_ASSOC);

    $dataByNganh = [];
    $rc = $conn->query("SELECT c.*, n.tenNganh, n.maNganh FROM ctdt c LEFT JOIN nganh n ON c.nganh_id = n.id ORDER BY n.tenNganh ASC, c.namBatDau DESC");
    if ($rc) while ($row = $rc->fetch_assoc()) {
        $nID = $row['nganh_id'];
        if (!isset($dataByNganh[$nID])) $dataByNganh[$nID] = ['info' => ['tenNganh' => $row['tenNganh'], 'maNganh' => $row['maNganh']], 'items' => []];
        $dataByNganh[$nID]['items'][] = $row;
    }
    $listNewFees = $conn->query("SELECT * FROM khoan_phai_thu ORDER BY id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
    $edit = null;
    if (isset($_GET['edit_ctdt'])) { $id = (int)$_GET['edit_ctdt']; $rs = $conn->query("SELECT * FROM ctdt WHERE id=$id"); if ($rs) $edit = $rs->fetch_assoc(); }
    
    $vMa = $edit['maCTDT'] ?? $_POST['maCTDT'] ?? '';
    $vTen = $edit['tenCTDT'] ?? $_POST['tenCTDT'] ?? '';
    $vNganh = $edit['nganh_id'] ?? $_POST['nganh_id'] ?? 0;
    $vNam = $edit['namBatDau'] ?? $_POST['namBatDau'] ?? date('Y');
    $vTin = $edit['tongTinChi'] ?? $_POST['tongTinChi'] ?? 0;
    $vGia = $edit['giaTinChi'] ?? $_POST['giaTinChi'] ?? 0;
}

if ($isStudent) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_items'])) {
        $selectedIds = $_POST['ids'] ?? [];
        if (empty($selectedIds)) {
            $err = "Sample text";
        } else {
            $idsStr = implode(',', array_map('intval', $selectedIds)); 
            $rowSum = $conn->query("SELECT SUM(soTien) as total FROM khoan_phai_thu WHERE id IN ($idsStr) AND maSV = '$uname' AND trangThai = 'CHUA_NOP'")->fetch_assoc();
            $totalPay = (float)$rowSum['total'];

            if ($totalPay > 0) {
                $conn->begin_transaction();
                try {
                    $conn->query("UPDATE khoan_phai_thu SET trangThai = 'DA_NOP' WHERE id IN ($idsStr)");
                    $st = $conn->prepare("INSERT INTO giaodich_sinhvien (maSV, loai, soPhieu, noiDung, soTien, ngayTao) VALUES (?, 'PHIEU_THU', ?, ?, ?, NOW())");
                    $soPhieu = 'PT' . time(); 
                    $content = "Payments online (" . count($selectedIds) . "Sample text";
                    $st->bind_param('sssd', $uname, $soPhieu, $content, $totalPay);
                    $st->execute(); $st->close();
                    $conn->commit();
                    $msg = "Sample text" . number_format($totalPay, 0, ',', '.') . "Sample text";
                } catch (Exception $e) { $conn->rollback(); $err = "Error: " . $e->getMessage(); }
            } else { $err = "Sample text"; }
        }
    }

    $rowTongNo = $conn->query("SELECT SUM(soTien) as total FROM khoan_phai_thu WHERE maSV = '$uname'")->fetch_assoc();
    $tongPhatSinh = (float)$rowTongNo['total'];

    $rowDaNop = $conn->query("SELECT SUM(soTien) as total FROM giaodich_sinhvien WHERE maSV = '$uname' AND loai = 'PHIEU_THU'")->fetch_assoc();
    $daNop = (float)$rowDaNop['total'];

    $rowMG = $conn->query("SELECT mienGiam FROM taichinh_sinhvien WHERE maSV = '$uname' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $mienGiam = $rowMG ? (float)$rowMG['mienGiam'] : 0;

    $rowConNo = $conn->query("SELECT SUM(soTien) as total FROM khoan_phai_thu WHERE maSV = '$uname' AND trangThai = 'CHUA_NOP'")->fetch_assoc();
    $conNo = (float)$rowConNo['total']; 

    $balance = ($daNop + $mienGiam) - $tongPhatSinh;
    $duTaiKhoan = $balance > 0 ? $balance : 0;

    $listKhoanNo = $conn->query("SELECT * FROM khoan_phai_thu WHERE maSV = '$uname' AND trangThai = 'CHUA_NOP' ORDER BY hanNop ASC")->fetch_all(MYSQLI_ASSOC);
    $listPhieuThu = $conn->query("SELECT * FROM giaodich_sinhvien WHERE maSV = '$uname' AND loai = 'PHIEU_THU' ORDER BY ngayTao DESC")->fetch_all(MYSQLI_ASSOC);
}

$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tai chinh & Hoc phi</title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    
    .tuition-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 24px; border: 1px solid #e2e8f0; overflow: hidden; }
    .tuition-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .tuition-title { font-size: 16px; font-weight: 600; color: #334155; }
    .tuition-table { width: 100%; border-collapse: collapse; }
    .tuition-table th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 13px; text-align: left; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; }
    .tuition-table td { padding: 12px 20px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .price-tag { font-weight: 600; color: #0f172a; }
    .form-box { background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0; }
    .userbox{margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px}
    .userbox a{color:#e5e7eb;text-decoration:underline}
    .admin-section-title { font-size: 18px; font-weight: bold; margin: 30px 0 15px 0; color: #1e293b; border-left: 4px solid #2563eb; padding-left: 10px; }
    .calc-row { display:flex; gap:10px; background:#f8fafc; padding:10px; border-radius:6px; border:1px dashed #cbd5e1; margin-bottom:15px; align-items:center;}
    
    .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px; }
    .stat-card { background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; }
    .stat-value { font-size: 18px; font-weight: bold; margin-top: 5px; color: #0f172a; }
    .stat-label { font-size: 13px; color: #64748b; text-transform: uppercase; }
    .stat-card.blue .stat-value { color: #2563eb; } .stat-card.green .stat-value { color: #16a34a; }
    .stat-card.orange .stat-value { color: #d97706; } .stat-card.red .stat-value { color: #dc2626; } .stat-card.teal .stat-value { color: #0d9488; }
    .payment-bar { background: #fff; padding: 15px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .btn-pay { background: #16a34a; color: white; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .btn-pay:disabled { background: #cbd5e1; cursor: not-allowed; }
    .empty-data { text-align:center; padding:20px; color:#888; font-style:italic;}
  </style>
</head>
<body>

<div class="sidebar">
    <div class="logo"><i class="fa-solid fa-graduation-cap"></i><span>PHENIKAA UNIVERSITY</span></div>
    <ul>
  <li>
      <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-house"></i><span>Home</span>
      </a>
  </li>
  <li>
      <a href="giangvien.php" class="<?= basename($_SERVER['PHP_SELF']) === 'giangvien.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-chalkboard-user"></i><span>Lecturers</span>
      </a>
  </li>
  <li>
      <a href="khoa.php" class="<?= basename($_SERVER['PHP_SELF']) === 'khoa.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-building-columns"></i><span>Khoa</span>
      </a>
  </li>
  <li>
      <a href="monhoc.php" class="<?= basename($_SERVER['PHP_SELF']) === 'monhoc.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-book"></i><span>Courses</span>
      </a>
  </li>
  <li>
      <a href="nganh.php" class="<?= basename($_SERVER['PHP_SELF']) === 'nganh.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-layer-group"></i><span>Majors</span>
      </a>
  </li>
  
  <li>
      <a href="lophoc.php" class="<?= basename($_SERVER['PHP_SELF']) === 'lophoc.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-chalkboard"></i><span>Management Khoa hoc</span>
      </a>
  </li>
  <li>
      <a href="khoikienthuc.php" class="<?= basename($_SERVER['PHP_SELF']) === 'khoikienthuc.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-book"></i><span>Knowledge Blocks</span>
      </a>
  </li>
  <li>
      <a href="ctdt_manager.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ctdt_manager.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-project-diagram"></i><span>Curriculum Management</span>
      </a>
  </li>
  <li>
      <a href="news.php" class="<?= basename($_SERVER['PHP_SELF']) === 'news.php' ? 'active' : '' ?>">
          <i class="fa-regular fa-newspaper"></i><span>News</span>
      </a>
  </li>
  <li>
      <a href="thanhtoan.php" class="<?= basename($_SERVER['PHP_SELF']) === 'thanhtoan.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-credit-card"></i><span>Payments</span>
      </a>
  </li>
</ul>
    <div class="userbox">
        <?php if ($uname): ?>
            <div><i class="fa-solid fa-user-shield"></i> <?= h($urole) ?>: <b><?= h($uname) ?></b></div>
            <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php">Logout</a></div>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
  <?php if ($msg): ?><div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?></div><?php endif; ?>

  <?php if ($canEdit): ?>
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2>Management Tai chinh & Hoc phi</h2>
        <a href="thanhtoan.php?reset_data=1" onclick="return confirm('CANH BAO: Actions nay se XOA HET du lieu no va lich su nop cua tat ca sinh vien e lam lai tu au. Ban co chac chan?')" style="background:#ef4444;color:white;padding:8px 12px;border-radius:4px;text-decoration:none;font-size:13px"><i class="fa-solid fa-rotate-right"></i>N/A</a>
    </div>

    <div class="admin-section-title"><i class="fa-solid fa-calculator"></i> Create khoan thu / Gan hoc phi cho Sinh vien</div>
    <div class="form-box" style="border-left: 4px solid #10b981;">
        <form method="post" action="thanhtoan.php" id="createFeeForm">
            <input type="hidden" name="action" value="create_fee">
            <div class="calc-row">
                <div style="flex:1">
                    <label style="font-size:11px;color:#666;font-weight:bold">N/A</label>
                    <select id="selectCTDT" style="width:100%;padding:6px;border:1px solid #ccc;border-radius:4px">
                        <option value="0" data-price="0">N/A</option>
                        <?php foreach($listAllCTDT as $c): ?><option value="<?= $c['id'] ?>" data-price="<?= $c['giaTinChi'] ?>"><?= h($c['maCTDT']) ?>N/A<?= number_format($c['giaTinChi']) ?>N/A</option><?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1">
                    <label style="font-size:11px;color:#666;font-weight:bold">N/A</label>
                    <select id="selectMon" style="width:100%;padding:6px;border:1px solid #ccc;border-radius:4px">
                        <option value="0" data-credit="0">N/A</option>
                        <?php foreach($listAllMon as $m): ?><option value="<?= $m['id'] ?>" data-credit="<?= $m['soTinChi'] ?>" data-name="<?= h($m['tenMon']) ?>"><?= h($m['maMon']) ?> - <?= h($m['tenMon']) ?> (<?= $m['soTinChi'] ?>N/A</option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:15px;align-items:end">
                <div><label style="font-size:12px;color:#666">N/A</label><input name="maSV" placeholder="VD: sv001" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
                <div><label style="font-size:12px;color:#666">N/A</label><input name="noiDung" id="inputNoiDung" placeholder="Hoc phi..." required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
                <div><label style="font-size:12px;color:#666">N/A</label><input name="soTien" id="inputSoTien" type="text" placeholder="0" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-weight:bold;color:#2563eb"></div>
                <div><label style="font-size:12px;color:#666">N/A</label><input name="hanNop" type="date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
                <button class="tab" style="height:36px;background:#10b981;color:white;border:none">N/A</button>
            </div>
        </form>
    </div>
    <script>
        const selCTDT=document.getElementById('selectCTDT'),selMon=document.getElementById('selectMon'),inpTien=document.getElementById('inputSoTien'),inpND=document.getElementById('inputNoiDung');
        function autoCalc(){const p=parseFloat(selCTDT.options[selCTDT.selectedIndex].getAttribute('data-price'))||0,c=parseFloat(selMon.options[selMon.selectedIndex].getAttribute('data-credit'))||0,n=selMon.options[selMon.selectedIndex].getAttribute('data-name')||'';if(p>0&&c>0){inpTien.value=new Intl.NumberFormat('vi-VN').format(p*c);inpND.value="Hoc phi: "+n+" ("+c+"Sample text"}}
        selCTDT.addEventListener('change',autoCalc);selMon.addEventListener('change',autoCalc);
    </script>

    <div class="tuition-card">
        <div class="tuition-header"><div class="tuition-title">Cac khoan thu vua tao (10 khoan moi nhat)</div></div>
        <table class="tuition-table">
            <thead><tr><th>N/A</th><th>N/A</th><th>N/A</th><th>N/A</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($listNewFees as $f): ?>
                <tr>
                    <td style="font-weight:bold"><?= h($f['maSV']) ?></td><td><?= h($f['noiDung']) ?></td><td><?= number_format($f['soTien'],0,',','.') ?>N/A</td><td><?= h($f['hanNop']) ?></td>
                    <td><?= $f['trangThai']=='DA_NOP' ? 'Sample text' : 'Sample text' ?></td>
                    <td><a href="thanhtoan.php?delete_fee=<?= $f['id'] ?>" onclick="return confirm('Sample text')" style="color:#ef4444"><i class="fa-solid fa-trash"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-section-title"><i class="fa-solid fa-sliders"></i>N/A</div>
    <div class="form-box">
      <form method="post" action="thanhtoan.php" style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px">
        <input type="hidden" name="action" value="<?= $edit ? 'update_ctdt' : 'add_ctdt' ?>">
        <input type="hidden" name="id" value="<?= h($edit['id'] ?? 0) ?>">
        <div><label style="font-size:12px;color:#666">N/A</label><select name="nganh_id" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"><option value="">N/A</option><?php foreach ($listNganh as $n): $sel = ($edit && $edit['nganh_id']==$n['id'])?'selected':''; ?><option value="<?= $n['id'] ?>" <?= $sel ?>><?= h($n['tenNganh']) ?></option><?php endforeach; ?></select></div>
        <div><label style="font-size:12px;color:#666">N/A</label><input name="maCTDT" value="<?= h($edit['maCTDT']??'') ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
        <div style="grid-column: span 2"><label style="font-size:12px;color:#666">N/A</label><input name="tenCTDT" value="<?= h($edit['tenCTDT']??'') ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
        <div><label style="font-size:12px;color:#666">N/A</label><input name="namBatDau" type="number" value="<?= h($edit['namBatDau']??date('Y')) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
        <div><label style="font-size:12px;color:#666">N/A</label><input name="tongTinChi" type="number" value="<?= h($edit['tongTinChi']??0) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
        <div><label style="font-size:12px;color:#666">N/A</label><input name="giaTinChi" value="<?= number_format($edit['giaTinChi']??0,0,'','') ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
        <div style="display:flex;align-items:end"><button class="tab" style="height:36px"><?= $edit ? 'Sample text' : 'Add' ?></button><?php if($edit): ?><a href="thanhtoan.php" class="tab" style="background:#eee;color:#333;text-decoration:none;margin-left:5px;height:36px;line-height:36px">N/A</a><?php endif; ?></div>
      </form>
    </div>
    <?php foreach ($dataByNganh as $nganhId => $group): ?>
        <div class="tuition-card">
            <div class="tuition-header"><div class="tuition-title"><?= h($group['info']['maNganh']) ?> - <?= h($group['info']['tenNganh']) ?></div></div>
            <table class="tuition-table">
                <thead><tr><th>N/A</th><th>N/A</th><th>N/A</th><th>N/A</th><th style="text-align:right">N/A</th><th style="text-align:center">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($group['items'] as $item): ?>
                    <tr>
                        <td><?= h($item['maCTDT']) ?></td><td><?= h($item['tenCTDT']) ?></td><td><?= h($item['namBatDau']) ?></td><td><?= h($item['tongTinChi']) ?></td>
                        <td style="text-align:right"><?= number_format($item['giaTinChi'],0,',','.') ?>N/A</td>
                        <td style="text-align:center"><a href="thanhtoan.php?edit_ctdt=<?= $item['id'] ?>" style="color:#2563eb;margin-right:5px"><i class="fa-solid fa-pen"></i></a><a href="thanhtoan.php?delete_ctdt=<?= $item['id'] ?>" onclick="return confirm('Delete?')" style="color:#ef4444"><i class="fa-solid fa-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

  <?php elseif ($isStudent): ?>
    <h2>Tinh hinh tai chinh hoc phi</h2>
    <div class="stats-grid">
        <div class="stat-card blue"><div class="stat-label">N/A</div><div class="stat-value"><?= number_format($tongPhatSinh, 0, ',', '.') ?></div></div>
        <div class="stat-card orange"><div class="stat-label">N/A</div><div class="stat-value"><?= number_format($mienGiam, 0, ',', '.') ?></div></div>
        <div class="stat-card green"><div class="stat-label">N/A</div><div class="stat-value"><?= number_format($daNop, 0, ',', '.') ?></div></div>
        <div class="stat-card red"><div class="stat-label">N/A</div><div class="stat-value"><?= number_format($conNo, 0, ',', '.') ?></div></div>
        <div class="stat-card teal"><div class="stat-label">N/A</div><div class="stat-value"><?= number_format($duTaiKhoan, 0, ',', '.') ?></div></div>
    </div>

    <div class="tuition-card" style="border: 1px solid #3b82f6;">
        <div class="tuition-header" style="background:#eff6ff"><div class="tuition-title" style="color:#1e40af">N/A</div></div>
        <form method="post" id="paymentForm">
            <input type="hidden" name="pay_items" value="1">
            <table class="tuition-table">
                <thead><tr><th><input type="checkbox" id="checkAll"></th><th>N/A</th><th style="text-align:center">N/A</th><th style="text-align:right">N/A</th></tr></thead>
                <tbody>
                    <?php if (count($listKhoanNo) > 0): foreach ($listKhoanNo as $khoan): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $khoan['id'] ?>" class="pay-check" data-amount="<?= $khoan['soTien'] ?>"></td>
                        <td><?= h($khoan['noiDung']) ?></td>
                        <td style="text-align:center;color:#dc2626"><?= date('d/m/Y', strtotime($khoan['hanNop'])) ?></td>
                        <td style="text-align:right;font-weight:600"><?= number_format($khoan['soTien'], 0, ',', '.') ?>N/A</td>
                    </tr>
                    <?php endforeach; else: ?><tr><td colspan="4" class="empty-data">N/A</td></tr><?php endif; ?>
                </tbody>
            </table>
            <?php if (count($listKhoanNo) > 0): ?>
            <div class="payment-bar">
                <div style="font-weight:bold">N/A<span id="totalAmount" style="color:#dc2626;font-size:18px">0</span>N/A</div>
                <button class="btn-pay" id="btnPay" disabled>Payments ngay</button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="tuition-card">
        <div class="tuition-header"><div class="tuition-title">N/A</div></div>
        <table class="tuition-table">
            <?php if(count($listPhieuThu)>0): foreach($listPhieuThu as $pt): ?>
            <tr><td><?= h($pt['soPhieu']) ?></td><td><?= h($pt['noiDung']) ?></td><td><?= $pt['ngayTao'] ?></td><td style="text-align:right;color:green">+<?= number_format($pt['soTien']) ?></td></tr>
            <?php endforeach; else: ?><tr><td colspan="4" class="empty-data">N/A</td></tr><?php endif; ?>
        </table>
    </div>

    <script>
        const checkAll=document.getElementById('checkAll'), checks=document.querySelectorAll('.pay-check'), totalEl=document.getElementById('totalAmount'), btn=document.getElementById('btnPay');
        function calc(){ let t=0, c=0; checks.forEach(k=>{if(k.checked){t+=parseInt(k.dataset.amount);c++}}); totalEl.innerText=new Intl.NumberFormat('vi-VN').format(t); btn.disabled=(c==0); }
        if(checkAll) checkAll.onclick=function(){checks.forEach(c=>c.checked=this.checked);calc()};
        checks.forEach(c=>c.onclick=calc);
    </script>
  <?php endif; ?>
</div>
</body>
</html>