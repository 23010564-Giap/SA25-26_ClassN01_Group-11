<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php';
require __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$role = $_SESSION['urole'] ?? 'viewer';
$uname = $_SESSION['uname'] ?? null;
$canEdit = in_array($role, ['admin','editor'], true);

$msg = '';
$err = '';

function checkConflict($conn, $gvID, $phong, $thu, $tietBD, $soTiet, $hocKy, $excludeId = 0) {
    $tietKT = $tietBD + $soTiet;
    
    $sqlRoom = "SELECT maLop FROM lophoc 
                WHERE phongHoc = ? AND thu = ? AND hocKy = ? AND id <> ?
                AND tietBatDau < ? AND (tietBatDau + soTiet) > ?";
    $st = $conn->prepare($sqlRoom);
    $st->bind_param('sisiis', $phong, $thu, $hocKy, $excludeId, $tietKT, $tietBD);
    $st->execute();
    if ($st->get_result()->num_rows > 0) return "Phong $phong a co lop hoc vao thoi gian nay.";
    $st->close();

    $sqlGV = "SELECT maLop FROM lophoc 
              WHERE giangvien_id = ? AND thu = ? AND hocKy = ? AND id <> ?
              AND tietBatDau < ? AND (tietBatDau + soTiet) > ?";
    $st = $conn->prepare($sqlGV);
    $st->bind_param('iisiis', $gvID, $thu, $hocKy, $excludeId, $tietKT, $tietBD);
    $st->execute();
    if ($st->get_result()->num_rows > 0) return "Sample text";
    $st->close();

    return false;
}

if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $maLop = trim($_POST['maLop'] ?? '');
    $monhoc_id = (int)($_POST['monhoc_id'] ?? 0);
    $giangvien_id = (int)($_POST['giangvien_id'] ?? 0);
    $phongHoc = trim($_POST['phongHoc'] ?? '');
    $thu = (int)$_POST['thu'];
    $tietBatDau = (int)$_POST['tietBatDau'];
    $soTiet = (int)$_POST['soTiet'];
    $siSoToiDa = (int)$_POST['siSoToiDa'];
    $phuongThuc = $_POST['phuongThuc'];
    $hocKy = trim($_POST['hocKy'] ?? '2025-1');

    if ($maLop==='' || $monhoc_id<=0 || $giangvien_id<=0 || $phongHoc==='' || $soTiet<=0) {
        $err = "Please nhap ay u thong tin bat buoc.";
    } else {
        $conflictMsg = checkConflict($conn, $giangvien_id, $phongHoc, $thu, $tietBatDau, $soTiet, $hocKy, ($action=='update' ? $id : 0));
        
        if ($conflictMsg) {
            $err = "Sample text" . $conflictMsg;
        } else {
            if ($action === 'add') {
                $chk = $conn->query("SELECT id FROM lophoc WHERE maLop='$maLop'");
                if ($chk->num_rows > 0) {
                    $err = "Sample text";
                } else {
                    $sql = "INSERT INTO lophoc (maLop, monhoc_id, giangvien_id, phongHoc, thu, tietBatDau, soTiet, siSoToiDa, phuongThuc, hocKy) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $st = $conn->prepare($sql);
                    $st->bind_param('siisiiiiss', $maLop, $monhoc_id, $giangvien_id, $phongHoc, $thu, $tietBatDau, $soTiet, $siSoToiDa, $phuongThuc, $hocKy);
                    if ($st->execute()) {
                        $msg = "Sample text";
                        $conn->query("INSERT INTO audit_log (table_name, action, message) VALUES ('lophoc', 'CREATE', 'Sample text')");
                        $_POST = [];
                    } else {
                        $err = "Error: " . $st->error;
                    }
                }
            }
            if ($action === 'update') {
                $sql = "UPDATE lophoc SET maLop=?, monhoc_id=?, giangvien_id=?, phongHoc=?, thu=?, tietBatDau=?, soTiet=?, siSoToiDa=?, phuongThuc=?, hocKy=? WHERE id=?";
                $st = $conn->prepare($sql);
                $st->bind_param('siisiiiissi', $maLop, $monhoc_id, $giangvien_id, $phongHoc, $thu, $tietBatDau, $soTiet, $siSoToiDa, $phuongThuc, $hocKy, $id);
                if ($st->execute()) {
                    $msg = "Update lop hoc thanh cong!";
                } else {
                    $err = "Error: " . $st->error;
                }
            }
        }
    }
}

if ($canEdit && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $conn->query("DELETE FROM lophoc WHERE id=$id");
        $msg = "a xoa lop hoc.";
    }
}

$edit = null;
if ($canEdit && isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM lophoc WHERE id=$id")->fetch_assoc();
}

$listMon = $conn->query("SELECT id, maMon, tenMon FROM monhoc ORDER BY tenMon")->fetch_all(MYSQLI_ASSOC);
$listGV = $conn->query("SELECT id, hoTen FROM giangvien ORDER BY hoTen")->fetch_all(MYSQLI_ASSOC);

$q = trim($_GET['q'] ?? '');
$where = $q ? "WHERE l.maLop LIKE '%$q%' OR m.tenMon LIKE '%$q%'" : "";
$sqlList = "SELECT l.*, m.tenMon, g.hoTen as tenGV 
            FROM lophoc l 
            JOIN monhoc m ON l.monhoc_id = m.id 
            JOIN giangvien g ON l.giangvien_id = g.id 
            $where ORDER BY l.id DESC";
$listLop = $conn->query($sqlList)->fetch_all(MYSQLI_ASSOC);

$vMa = $edit['maLop'] ?? $_POST['maLop'] ?? '';
$vMon = $edit['monhoc_id'] ?? $_POST['monhoc_id'] ?? 0;
$vGV = $edit['giangvien_id'] ?? $_POST['giangvien_id'] ?? 0;
$vPhong = $edit['phongHoc'] ?? $_POST['phongHoc'] ?? '';
$vThu = $edit['thu'] ?? $_POST['thu'] ?? 2;
$vTiet = $edit['tietBatDau'] ?? $_POST['tietBatDau'] ?? 1;
$vSoTiet = $edit['soTiet'] ?? $_POST['soTiet'] ?? 3;
$vSiSo = $edit['siSoToiDa'] ?? $_POST['siSoToiDa'] ?? 60;
$vPT = $edit['phuongThuc'] ?? $_POST['phuongThuc'] ?? 'Offline';
$vHK = $edit['hocKy'] ?? $_POST['hocKy'] ?? '2025-1';

$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Management Khoa hoc</title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .form-box { background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0; }
    .card { background:#fff; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); border:1px solid #e2e8f0; overflow:hidden; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px 15px; border-bottom:1px solid #eee; text-align:left; font-size:14px; }
    th { background:#f8fafc; font-weight:600; color:#475569; }
    .badge { padding:3px 8px; border-radius:4px; font-size:11px; font-weight:500; }
    .bg-green { background:#dcfce7; color:#166534; }
    .bg-blue { background:#dbeafe; color:#1e40af; }
    .userbox{margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px}
    .userbox a{color:#e5e7eb;text-decoration:underline}
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
            <div><i class="fa-solid fa-user-shield"></i> <?= h($role) ?>: <b><?= h($uname) ?></b></div>
            <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php">Logout</a></div>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">
  <h2>Management Khoa hoc / Mo lop</h2>
  
  <?php if ($msg): ?><div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?></div><?php endif; ?>

  <?php if ($canEdit): ?>
    <div class="form-box" style="border-left:4px solid #2563eb">
        <h3 style="margin-top:0"><?= $edit ? 'Sample text' : 'Sample text' ?></h3>
        <form method="post" action="lophoc.php" style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px">
            <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
            <input type="hidden" name="id" value="<?= h($edit['id'] ?? 0) ?>">

            <div><label style="font-size:12px;color:#666">Hoc ky</label><input name="hocKy" value="<?= h($vHK) ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
            
            <div><label style="font-size:12px;color:#666">N/A</label><input name="maLop" value="<?= h($vMa) ?>" placeholder="VD: KTPM01" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
            
            <div><label style="font-size:12px;color:#666">Courses *</label>
                <select name="monhoc_id" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    <option value="">-- Chon Mon --</option>
                    <?php foreach($listMon as $m): ?><option value="<?= $m['id'] ?>" <?= $vMon==$m['id']?'selected':'' ?>><?= h($m['tenMon']) ?> (<?= h($m['maMon']) ?>)</option><?php endforeach; ?>
                </select>
            </div>

            <div><label style="font-size:12px;color:#666">Lecturers *</label>
                <select name="giangvien_id" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    <option value="">N/A</option>
                    <?php foreach($listGV as $g): ?><option value="<?= $g['id'] ?>" <?= $vGV==$g['id']?'selected':'' ?>><?= h($g['hoTen']) ?></option><?php endforeach; ?>
                </select>
            </div>

            <div><label style="font-size:12px;color:#666">Phong hoc *</label><input name="phongHoc" value="<?= h($vPhong) ?>" placeholder="VD: A2-301" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
            
            <div><label style="font-size:12px;color:#666">N/A</label>
                <select name="thu" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    <?php for($i=2;$i<=8;$i++): ?><option value="<?= $i ?>" <?= $vThu==$i?'selected':'' ?>><?= $i==8?'Chu Nhat':"Sample text" ?></option><?php endfor; ?>
                </select>
            </div>

            <div><label style="font-size:12px;color:#666">N/A</label><input type="number" name="tietBatDau" min="1" max="15" value="<?= h($vTiet) ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
            
            <div><label style="font-size:12px;color:#666">N/A</label><input type="number" name="soTiet" min="1" max="5" value="<?= h($vSoTiet) ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>

            <div><label style="font-size:12px;color:#666">N/A</label><input type="number" name="siSoToiDa" value="<?= h($vSiSo) ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></div>
            
            <div><label style="font-size:12px;color:#666">N/A</label>
                <select name="phuongThuc" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    <option value="Offline" <?= $vPT=='Offline'?'selected':'' ?>N/A</option>
                    <option value="Online" <?= $vPT=='Online'?'selected':'' ?>N/A</option>
                </select>
            </div>

            <div style="grid-column: span 2; display:flex; align-items:end; gap:10px">
                <button class="tab" style="height:36px"><?= $edit ? 'Luu Cap Nhat' : 'Sample text' ?></button>
                <?php if($edit): ?><a href="lophoc.php" class="tab" style="background:#eee;color:#333;text-decoration:none;height:36px;line-height:36px">N/A</a><?php endif; ?>
            </div>
        </form>
    </div>
  <?php endif; ?>

  <div class="card">
    <table>
        <thead><tr><th>N/A</th><th>Mon Hoc</th><th>N/A</th><th>Lich Hoc</th><th>N/A</th><th>N/A</th><th>Status</th><?php if($canEdit): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
            <?php foreach ($listLop as $lop): ?>
            <tr>
                <td style="font-weight:bold;color:#2563eb"><?= h($lop['maLop']) ?></td>
                <td><?= h($lop['tenMon']) ?></td>
                <td><?= h($lop['tenGV']) ?></td>
                <td>
                    <span class="badge bg-blue">N/A<?= $lop['thu']==8?'CN':$lop['thu'] ?></span>
                    <span>N/A<?= $lop['tietBatDau'] ?> - <?= $lop['tietBatDau']+$lop['soTiet']-1 ?></span>
                </td>
                <td><?= h($lop['phongHoc']) ?></td>
                <td><?= h($lop['siSoThucTe']) ?> / <?= h($lop['siSoToiDa']) ?></td>
                <td><?= $lop['trangThai']=='DangMo' ? 'Sample text' : '<span style="color:red">a khoa</span>' ?></td>
                <?php if($canEdit): ?>
                <td>
                    <a href="lophoc.php?edit=<?= $lop['id'] ?>" style="color:#2563eb;margin-right:10px"><i class="fa-solid fa-pen"></i></a>
                    <a href="lophoc.php?delete=<?= $lop['id'] ?>" onclick="return confirm('Sample text')" style="color:#ef4444"><i class="fa-solid fa-trash"></i></a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if(empty($listLop)): ?><div style="padding:20px;text-align:center;color:#888">Chua co lop hoc nao.</div><?php endif; ?>
  </div>

</div>
</body>
</html>